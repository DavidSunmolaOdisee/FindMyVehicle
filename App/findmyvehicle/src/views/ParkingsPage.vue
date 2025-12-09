<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Parkings</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Parkings</ion-title>
        </ion-toolbar>
      </ion-header>

      <ion-fab vertical="top" horizontal="center" slot="fixed" edge>
  <ion-fab-button @click="openAddParkingModal">
    <ion-icon :icon="add"></ion-icon>
  </ion-fab-button>
</ion-fab>
      <div v-if="parkings.length === 0" class="ion-text-center ion-padding">
        <h3>Geen parkings</h3>
        <p>Start je eerste parking</p>
        <ion-button @click="openAddParkingModal" class="ion-margin-top">
          Start Parking
        </ion-button>
      </div>

      <!-- Parkings List -->
      <ion-list v-else>
        <ion-item-sliding v-for="parking in parkings" :key="parking.id">
          <ion-item>
            <ion-label>
              <h2>{{ getVehicleName(parking.vehicle_id) }}</h2>
              <p>Locatie: {{ formatLocation(parking) }}</p>
              <p v-if="parking.level">Verdieping: {{ parking.level }}</p>
              <p v-if="parking.description">{{ parking.description }}</p>
              
              <!-- Timer -->
              <div v-if="parking.expires_at" class="timer">
                Verloopt: {{ formatTimeRemaining(parking.expires_at) }}
              </div>
              
              <!-- Google Maps Button -->
              <ion-button 
                expand="block" 
                size="small" 
                @click="openGoogleMaps(parking)"
                class="ion-margin-top"
              >
                Open in Google Maps
              </ion-button>
            </ion-label>
          </ion-item>

          <ion-item-options side="end">
            <ion-item-option color="primary" @click="editParking(parking)">
              Bewerk
            </ion-item-option>
            <ion-item-option color="danger" @click="confirmDeleteParking(parking)">
              Verwijder
            </ion-item-option>
          </ion-item-options>
        </ion-item-sliding>
      </ion-list>

      <!-- Add Parkng -->
      <ion-modal :is-open="showParkingModal" @didDismiss="closeParkingModal">
        <ion-header>
          <ion-toolbar>
            <ion-title>Start Nieuwe Parking</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeParkingModal">Sluiten</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        
        <ion-content class="ion-padding">
          <ion-list>
            <!-- Vehicle Selection -->
            <ion-item>
              <ion-label position="stacked">Voertuig *</ion-label>
              <ion-select 
                v-model="parkingForm.vehicle_id" 
                placeholder="Kies voertuig" 
                interface="action-sheet" 
                required
              >
                <ion-select-option 
                  v-for="vehicle in userVehicles" 
                  :key="vehicle.id" 
                  :value="vehicle.id"
                >
                  {{ vehicle.brand || '' }} {{ vehicle.model || '' }} ({{ getVehicleTypeDisplay(vehicle.type) }})
                </ion-select-option>
              </ion-select>
            </ion-item>

            <!-- Location -->
            <ion-item>
              <ion-label position="stacked">Locatie *</ion-label>
              <div class="location-controls">
                <ion-button 
                  expand="block" 
                  color="primary" 
                  @click="getCurrentLocation"
                  :disabled="gettingLocation"
                >
                  <ion-spinner v-if="gettingLocation" name="crescent"></ion-spinner>
                  <span v-else>📍 Gebruik huidige locatie</span>
                </ion-button>
                
                <div v-if="parkingForm.latitude && parkingForm.longitude" class="coordinates-info">
                  <ion-text color="success">
                    <p>Locatie opgeslagen!</p>
                    <p>{{ parkingForm.latitude.toFixed(6) }}, {{ parkingForm.longitude.toFixed(6) }}</p>
                  </ion-text>
                </div>
                
                <div v-else class="coordinates-info">
                  <ion-text color="warning">
                    <p>Klik op de knop om uw huidige locatie te gebruiken</p>
                  </ion-text>
                </div>
              </div>
            </ion-item>

            <!-- Parking Level -->
            <ion-item>
              <ion-label position="stacked">Verdieping/Zone</ion-label>
              <ion-input 
                v-model="parkingForm.level" 
                placeholder="Bijv. P-1, Zone A, Buiten"
              ></ion-input>
            </ion-item>

            <!-- Description -->
            <ion-item>
              <ion-label position="stacked">Beschrijving</ion-label>
              <ion-textarea 
                v-model="parkingForm.description" 
                placeholder="Bijv. Naast lift, onder boom, dicht bij uitgang..."
                rows="3"
              ></ion-textarea>
            </ion-item>

            <!-- Expiration Time (optional) -->
            <ion-item>
              <ion-label position="stacked">Verlooptijd (optioneel)</ion-label>
              <ion-input 
                v-model="parkingForm.expires_at" 
                type="datetime-local"
                :min="minDateTime"
              ></ion-input>
            </ion-item>
          </ion-list>

          <ion-button 
            expand="block" 
            @click="saveParking" 
            :disabled="!parkingForm.vehicle_id || !parkingForm.latitude || !parkingForm.longitude || saving"
            class="ion-margin-top"
          >
            <ion-spinner v-if="saving" name="crescent"></ion-spinner>
            <span v-else>Start Parking</span>
          </ion-button>
          <!-- Add Parkng Modal -->

        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script setup>
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButton, IonList, IonItemSliding, IonItem, IonItemOptions, IonItemOption, IonLabel, IonModal, IonButtons, IonSelect, IonSelectOption, IonSpinner, IonText, IonInput, IonTextarea, IonFab, IonFabButton, IonIcon } from '@ionic/vue';

import { ref, onMounted, computed } from 'vue';

import { add } from 'ionicons/icons';
import { authService } from '@/services/auth.js';
import { apiGet, apiPost } from '@/services/api.js';
// import { Geolocation } from '@capacitor/geolocation';


// State
const parkings = ref([])
const userVehicles = ref([])
const showParkingModal = ref(false)
const saving = ref(false)
const gettingLocation = ref(false)

const minDateTime = computed(() => {
  const now = new Date();
  now.setMinutes(now.getMinutes() + 5); // Minimum 5 minutes from now
  return now.toISOString().slice(0, 16);
})

// Form
const parkingForm = ref({
  vehicle_id: null,
  latitude: null,
  longitude: null,
  level: '',
  description: '',
  expires_at: null
})

// Helper functions
function formatLocation(parking) {
  try {
    if (parking.latitude && parking.longitude) {
      const lat = parseFloat(parking.latitude);
      const lng = parseFloat(parking.longitude);
      
      if (isNaN(lat) || isNaN(lng)) {
        return 'Ongeldige locatie';
      }
      
      return `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
    }
    return 'Geen locatie';
  } catch (error) {
    console.log('Error formatting location:', error);
    return 'Locatie fout';
  }
}

function formatTimeRemaining(expiresAt) {
  if (!expiresAt) return '';
  
  const now = new Date();
  const expires = new Date(expiresAt);
  const diff = expires - now;
  
  if (diff <= 0) return 'Verlopen';
  
  const hours = Math.floor(diff / (1000 * 60 * 60));
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  
  return `${hours} uur ${minutes} min`;
}

function getVehicleName(vehicleId) {
  return `Voertuig:${vehicleId}`;
}

function getVehicleTypeDisplay(type) {
  const types = {
    car: 'Auto',
    bike: 'Fiets',
    scooter: 'Scooter',
    motor: 'Motor',
    other: 'Anders'
  }
  return types[type] || type
}

// GOOGLE MAPS FUNCTION
function openGoogleMaps(parking) {
  try {
    const lat = parseFloat(parking.latitude);
    const lng = parseFloat(parking.longitude);
    
    if (isNaN(lat) || isNaN(lng)) {
      alert('Geen geldige locatie beschikbaar voor deze parking');
      return;
    }
    
    // Create Google Maps URL for navigation
    const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
    
    // Open in new tab
    window.open(mapsUrl, '_blank');
    console.log('Opening Google Maps to:', lat, lng);
    
  } catch (error) {
    console.log('Error opening Google Maps:', error);
    alert('Kon Google Maps niet openen');
  }
}

// READ OPERATION: Load parkings
async function loadParkings() {
  try {
    const user = authService.getCurrentUser();
    
    if (!user || !user.id) {
      console.log('User not logged in');
      return;
    }
    
    const userId = user.id;
    console.log('Loading parkings for user ID:', userId);
    
    const response = await apiGet(`parkings.php?user_id=${userId}&active=1`);
    console.log('All parkings API response:', response);
      
    // Handle response format
    let parkingsData = [];
    
    if (Array.isArray(response)) {
      parkingsData = response;
    } else if (response.data && Array.isArray(response.data)) {
      parkingsData = response.data;
    } else {
      console.log('Unexpected response format:', response);
      parkingsData = [];
    }
    
    parkings.value = parkingsData;
    
    console.log('Loaded parkings:', parkings.value);
    
  } catch (error) {
    console.log('Error loading parkings:', error);
    parkings.value = [];
  }
}



function editParking(parking) {
  console.log('Edit parking:', parking);
  alert('Bewerken komt later');
}

function confirmDeleteParking(parking) {
  console.log('Delete parking:', parking);
  alert('Verwijderen komt later');
}




// GET CURRENT LOCATION
async function getCurrentLocation() {
  if (!navigator.geolocation) {
    alert('Geolocatie wordt niet ondersteund in uw browser');
    return;
  }
  
  gettingLocation.value = true;
  
  try {
    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      });
    });
    
    parkingForm.value.latitude = position.coords.latitude;
    parkingForm.value.longitude = position.coords.longitude;
    
    console.log('Location obtained:', {
      lat: position.coords.latitude,
      lng: position.coords.longitude,
      accuracy: position.coords.accuracy + ' meters'
    });
    
  } catch (error) {
    console.log('Geolocation error:', error);
    
    let errorMessage = 'Kon locatie niet ophalen';
    switch(error.code) {
      case 1:
        errorMessage = 'Locatie toestemming geweigerd. Sta toegang toe in browser instellingen.';
        break;
      case 2:
        errorMessage = 'Locatie niet beschikbaar. Controleer of GPS aan staat.';
        break;
      case 3:
        errorMessage = 'Timeout bij ophalen locatie. Probeer opnieuw.';
        break;
    }
    
    alert(errorMessage);
    
    parkingForm.value.latitude = 51.2194;
    parkingForm.value.longitude = 4.4025;
    console.log('Using fallback location for testing');
    
  } finally {
    gettingLocation.value = false;
  }
}

async function saveParking() {
  saving.value = true;
  
  try {
    const user = authService.getCurrentUser();
    
    if (!user || !user.id) {
      alert('Error: Je bent niet ingelogd');
      return;
    }
    
    if (!parkingForm.value.latitude || !parkingForm.value.longitude) {
      alert('Error: Geen locatie ingesteld. Gebruik "huidige locatie" knop.');
      return;
    }
    
    if (!parkingForm.value.vehicle_id) {
      alert('Error: Selecteer een voertuig');
      return;
    }
    
    // Prepare data for API
    const parkingData = {
      user_id: user.id,
      vehicle_id: parkingForm.value.vehicle_id,
      latitude: parseFloat(parkingForm.value.latitude),
      longitude: parseFloat(parkingForm.value.longitude),
      level: parkingForm.value.level || null,
      description: parkingForm.value.description || null,
      expires_at: parkingForm.value.expires_at || null

    };
    
    console.log('Sending parking data:', parkingData);
    
    // POST /parkings
    const response = await apiPost('parkings.php', parkingData);
    console.log('Parking created:', response);
    
    // Succes
    alert('Parking succesvol gestart!');
    
    // Close modal reset form
    closeParkingModal();
    
    // Refresh parkings list
    await loadParkings();
    
  } catch (error) {
    console.log('Error saving parking:', error);
    alert('Fout bij starten parking: ' + error.message);
  } finally {
    saving.value = false;
  }
}

// Load vehicles for dropdown
async function loadUserVehicles() {
  try {
    const user = authService.getCurrentUser();
    
    if (!user || !user.id) return;
    
    const response = await apiGet(`vehicles.php?user_id=${user.id}`);
    
    if (Array.isArray(response)) {
      userVehicles.value = response;
    } else if (response.data && Array.isArray(response.data)) {
      userVehicles.value = response.data;
    }
    
    console.log('Loaded vehicles for dropdown:', userVehicles.value.length);
    
  } catch (error) {
    console.log('Error loading vehicles:', error);
    userVehicles.value = [];
  }
}

// Modal functies
function openAddParkingModal() {
  // Load vehicles eerst
  loadUserVehicles();
  
  // Reset form
  parkingForm.value = {
    vehicle_id: null,
    latitude: null,
    longitude: null,
    level: '',
    description: '',
    expires_at: null
  };
  
  showParkingModal.value = true;
}

function closeParkingModal() {
  showParkingModal.value = false;
}


// Initial load
onMounted(() => {
  loadParkings();
});

</script>
