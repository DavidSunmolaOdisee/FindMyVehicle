<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Dashboard</ion-title>
        <ion-buttons slot="end">
          <p class="logout-text">Logout</p>
          <ion-button @click="logout" fill="outline" color="danger">
            <ion-icon slot="icon-only" :icon="logOut"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">

      <!-- Add Parking Button -->
      <ion-fab vertical="bottom" horizontal="end" slot="fixed">
        <ion-fab-button @click="openAddParkingModal">
          <ion-icon :icon="add"></ion-icon>
        </ion-fab-button>
      </ion-fab>

      <div v-if="parkings.length === 0" class="ion-text-center ion-padding">
        <h3>Geen parkings</h3>
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
              <p v-if="parking.expires_at">Verloopt: {{ formatTimeRemaining(parking.expires_at) }}</p>
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
              <ion-icon slot="icon-only" :icon="pencil"></ion-icon>
            </ion-item-option>
            <ion-item-option color="danger" @click="closeParking(parking)">
              <ion-icon slot="icon-only" :icon="trash"></ion-icon>
            </ion-item-option>
          </ion-item-options>
        </ion-item-sliding>
      </ion-list>

      <!-- Add/Edit Modal -->
      <ion-modal :is-open="showParkingModal" @didDismiss="closeParkingModal">
        <ion-header>
          <ion-toolbar>
            <ion-title>{{ isEditing ? 'Parking Bewerken' : 'Start Nieuwe Parking' }}</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeParkingModal">Sluiten</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        
        <ion-content class="ion-padding">
          <ion-list>
            <ion-item>
              <ion-label position="stacked">Voertuig *</ion-label>
              <ion-select v-model="parkingForm.vehicle_id" required>
                <ion-select-option 
                  v-for="vehicle in userVehicles" 
                  :key="vehicle.id" 
                  :value="vehicle.id"
                >
                  {{ vehicle.brand || '' }} {{ vehicle.model || '' }} ({{ getVehicleTypeDisplay(vehicle.type) }})
                </ion-select-option>
              </ion-select>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Locatie *</ion-label>
              <ion-button expand="block" @click="getCurrentLocation" :disabled="gettingLocation">
                <ion-spinner v-if="gettingLocation" name="crescent"></ion-spinner>
                <span v-else>📍 Gebruik huidige locatie</span>
              </ion-button>
              <div v-if="parkingForm.latitude" class="ion-margin-top">
                <ion-text color="success">
                  <small>Locatie: {{ parkingForm.latitude.toFixed(6) }}, {{ parkingForm.longitude.toFixed(6) }}</small>
                </ion-text>
              </div>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Verdieping/Zone</ion-label>
              <ion-input v-model="parkingForm.level" placeholder="Bijv. P-1"></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Beschrijving</ion-label>
              <ion-textarea v-model="parkingForm.description" rows="3"></ion-textarea>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Verlooptijd (optioneel)</ion-label>
              <ion-input v-model="parkingForm.expires_at" type="datetime-local"></ion-input>
            </ion-item>
          </ion-list>

          <ion-button 
            expand="block" 
            @click="saveParking" 
            :disabled="!parkingForm.vehicle_id || !parkingForm.latitude"
            class="ion-margin-top"
          >
            <ion-spinner v-if="saving" name="crescent"></ion-spinner>
            <span v-else>{{ isEditing ? 'Opslaan' : 'Start Parking' }}</span>
          </ion-button>
        </ion-content>
      </ion-modal>
    </ion-content>
  </ion-page>
</template>

<script setup>
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButton, IonList, IonItemSliding, IonItem, IonItemOptions, IonItemOption, IonLabel, IonModal, IonButtons, IonSelect, IonSelectOption, IonSpinner, IonText, IonInput, IonTextarea, IonFab, IonFabButton, IonIcon } from '@ionic/vue';
import { ref, onMounted } from 'vue';
import { add, pencil, trash, logOut } from 'ionicons/icons';
import { authService } from '@/services/auth.js';
import { apiGet, apiPost, apiPut, apiDelete } from '@/services/api.js';
import { Geolocation } from '@capacitor/geolocation';

// State
const parkings = ref([]);
const userVehicles = ref([]);
const vehiclesMap = ref({});
const showParkingModal = ref(false);
const saving = ref(false);
const gettingLocation = ref(false);
const currentParkingId = ref(null);
const isEditing = ref(false);

// Form
const parkingForm = ref({
  vehicle_id: null,
  latitude: null,
  longitude: null,
  level: '',
  description: '',
  expires_at: null
});

// Helper functions
function formatLocation(parking) {
  if (parking.latitude && parking.longitude) {
    return `${parseFloat(parking.latitude).toFixed(4)}, ${parseFloat(parking.longitude).toFixed(4)}`;
  }
  return 'Geen locatie';
}

// Modal functions
function openAddParkingModal() {
  loadUserVehicles();
  parkingForm.value = { vehicle_id: null, latitude: null, longitude: null, level: '', description: '', expires_at: null };
  isEditing.value = false;
  currentParkingId.value = null;
  showParkingModal.value = true;
}

function closeParkingModal() {
  showParkingModal.value = false;
}

// Time formatting
function formatTimeRemaining(expiresAt) {
  if (!expiresAt) return '';
  const diff = new Date(expiresAt) - new Date();
  if (diff <= 0) return 'Verlopen';
  const hours = Math.floor(diff / (1000 * 60 * 60));
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  return `${hours} uur ${minutes} min`;
}

// Vehicle type function
function getVehicleTypeDisplay(type) {
  const types = { car: 'Auto', bike: 'Fiets', scooter: 'Scooter', motor: 'Motor', other: 'Anders' };
  return types[type] || type;
}

// Vehicle name function
function getVehicleName(vehicleId) {
  const vehicle = vehiclesMap.value[vehicleId];
  if (!vehicle) return `Voertuig #${vehicleId}`;
  let name = vehicle.brand && vehicle.model ? `${vehicle.brand} ${vehicle.model}` : vehicle.brand || vehicle.model || getVehicleTypeDisplay(vehicle.type);
  const typeDisplay = getVehicleTypeDisplay(vehicle.type);
  if (name !== typeDisplay) name += ` (${typeDisplay})`;
  return name;
}

// Google maps function
function openGoogleMaps(parking) {
  const lat = parseFloat(parking.latitude);
  const lng = parseFloat(parking.longitude);
  if (isNaN(lat) || isNaN(lng)) return alert('Geen geldige locatie');
  window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`, '_blank');
}

// Location fucntion
async function getCurrentLocation() {
  gettingLocation.value = true;
  try {
    const permission = await Geolocation.checkPermissions();
    if (permission.location !== 'granted') {
      const request = await Geolocation.requestPermissions();
      if (request.location !== 'granted') return alert('Locatietoestemming nodig');
    }
    const position = await Geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 15000 });
    parkingForm.value.latitude = position.coords.latitude;
    parkingForm.value.longitude = position.coords.longitude;
  } catch (error) {
    console.error('Geolocation error:', error);
    alert('Kon locatie niet bepalen');
  } finally {
    gettingLocation.value = false;
  }
}

// Load vehicles function
async function loadUserVehicles() {
  try {
    const user = authService.getCurrentUser();
    if (!user?.id) return;
    const response = await apiGet(`vehicles.php?user_id=${user.id}`);
    const vehiclesData = Array.isArray(response) ? response : response.data || [];
    userVehicles.value = vehiclesData;
    vehiclesMap.value = vehiclesData.reduce((map, vehicle) => {
      if (vehicle.id) map[vehicle.id] = vehicle;
      return map;
    }, {});
  } catch (error) {
    console.log('Error loading vehicles:', error);
    userVehicles.value = [];
    vehiclesMap.value = {};
  }
}

// ==================== READ OPERATION ====================
async function loadParkings() {
  try {
    const user = authService.getCurrentUser();
    if (!user?.id) return;
    await loadUserVehicles();
    const response = await apiGet(`parkings.php?user_id=${user.id}`);
    const parkingsData = Array.isArray(response) ? response : response.data || [];
    const now = new Date();
    parkings.value = parkingsData.filter(p => 
      !p.closed_at && (!p.expires_at || new Date(p.expires_at) > now)
    );
  } catch (error) {
    console.log('Error loading parkings:', error);
    parkings.value = [];
  }
}

// ==================== UPDATE OPERATION ====================
function editParking(parking) {
  loadUserVehicles();
  currentParkingId.value = parking.id;
  isEditing.value = true;
  parkingForm.value = {
    vehicle_id: parking.vehicle_id,
    latitude: parseFloat(parking.latitude),
    longitude: parseFloat(parking.longitude),
    level: parking.level || '',
    description: parking.description || '',
    expires_at: parking.expires_at ? new Date(parking.expires_at).toISOString().slice(0, 16) : null
  };
  showParkingModal.value = true;
}

// ==================== CREATE/UPDATE OPERATION ====================
async function saveParking() {
  if (!parkingForm.value.vehicle_id || !parkingForm.value.latitude) {
    alert('Selecteer voertuig en locatie');
    return;
  }
  
  saving.value = true;
  try {
    const user = authService.getCurrentUser();
    if (!user?.id) return alert('Niet ingelogd');
    
    const parkingData = {
      vehicle_id: parseInt(parkingForm.value.vehicle_id),
      latitude: parseFloat(parkingForm.value.latitude),
      longitude: parseFloat(parkingForm.value.longitude),
      level: parkingForm.value.level || null,
      description: parkingForm.value.description || null,
      expires_at: parkingForm.value.expires_at || null
    };
    
    let response;
    if (isEditing.value && currentParkingId.value) {
      // Update
      parkingData.user_id = user.id;
      response = await apiPut(`parkings.php?id=${currentParkingId.value}`, parkingData);
      alert('Parking bijgewerkt!');
    } else {
      // Create
      parkingData.user_id = user.id;
      // eslint-disable-next-line no-unused-vars
      response = await apiPost('parkings.php', parkingData);
      alert('Parking gestart!');
    }
    await loadParkings();

    closeParkingModal();
  } catch (error) {
    console.log('Error saving:', error);
    alert('Fout: ' + error.message);
  } finally {
    saving.value = false;
  }
}

// ==================== DELETE OPERATION ====================
async function closeParking(parking) {
  if (
    !confirm(
      `Weet je zeker dat je deze parking wilt afsluiten?\n` +
      `${getVehicleName(parking.vehicle_id)} - ${formatLocation(parking)}`
    )
  ) return;

  try {
    await apiPost(
      `parkings/close?id=${parking.id}`,
      {} 
    );

    // parking uit actieve lijst halen (niet deleten in DB!)
    parkings.value = parkings.value.filter(p => p.id !== parking.id);

    alert('Parking afgesloten');
  } catch (error) {
    console.error('Close parking error:', error);
    alert('Fout bij afsluiten van parking');
  }
}


// Logout functie
function logout() {
  if (confirm('Weet je zeker dat je wilt uitloggen?')) {
    authService.logout();
    window.location.href = '/login';
  }
}

// Initial load
onMounted(() => {
  loadParkings();
});
</script>

<style scoped>
.ion-margin-top {
  margin-top: 16px;
}

.logout-text {
  margin-right: 8px;
}
</style>