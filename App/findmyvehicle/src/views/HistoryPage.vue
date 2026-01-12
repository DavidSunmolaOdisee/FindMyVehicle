<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Historiek</ion-title>
        <ion-buttons slot="end">
          <p class="logout-text">Logout</p>
          <ion-button @click="logout" fill="outline" color="danger">
            <ion-icon slot="icon-only" :icon="logOut"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      


      <div v-if="historyParkings.length === 0" class="ion-text-center ion-padding">
        <h3>Geen geschiedenis</h3>
        <p>Nog geen afgesloten of verlopen parkings</p>
      </div>
      
      <!-- History List -->
      <ion-list v-else>
    <ion-item-sliding v-for="parking in historyParkings" :key="parking.id">
      <ion-item>
        <ion-label>
          <!-- Status badge -->
          <div class="status-badge" :class="getHistoryStatus(parking)">
            {{ getHistoryStatusText(parking) }}
          </div>
          
          <h2>{{ getVehicleName(parking.vehicle_id) }}</h2>
          <p>Locatie: {{ formatLocation(parking) }}</p>
          <p v-if="parking.level">Verdieping: {{ parking.level }}</p>
          <p v-if="parking.description">{{ parking.description }}</p>
          
          <!-- DATES SECTION -->
          <div class="date-section">
            <p class="date-info">
              Gestart: {{ formatDateTime(parking.started_at) }}
            </p>
            
            <!-- If parking expired -->
            <p v-if="parking.expires_at && !parking.closed_at" class="date-info">
              Verlopen: {{ formatDateTime(parking.expires_at) }}
            </p>
            
            <!-- If parking was closed -->
            <p v-if="parking.closed_at" class="date-info">
              Beëindigd: {{ formatDateTime(parking.closed_at) }} 
            </p>
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
        <!-- DELETE ONLY - No edit button -->
        <ion-item-option color="danger" @click="confirmDeleteHistory(parking)">
          Verwijder
        </ion-item-option>
      </ion-item-options>
    </ion-item-sliding>
  </ion-list>

      <!-- Delete Confirmation Alert -->
      <ion-alert
        :is-open="showDeleteAlert"
        header="Verwijderen uit Geschiedenis"
        message="Ben je zeker dat je deze parking uit de geschiedenis wilt verwijderen?"
        :buttons="deleteAlertButtons"
        @didDismiss="showDeleteAlert = false"
      ></ion-alert>
    </ion-content>
  </ion-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { authService } from '@/services/auth.js';
import { apiGet, apiDelete } from '@/services/api.js';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItemSliding, IonItem, IonLabel, IonItemOptions, IonItemOption, IonButton, IonAlert, IonButtons, IonIcon } from '@ionic/vue';
import { logOut } from 'ionicons/icons'

// State
const historyParkings = ref([])
const vehiclesMap = ref({})
const showDeleteAlert = ref(false)
const parkingToDelete = ref(null)

// Alert buttons
const deleteAlertButtons = [
  {
    text: 'Annuleren',
    role: 'cancel',
    handler: () => {
      parkingToDelete.value = null
      showDeleteAlert.value = false
    }
  },
  {
    text: 'Verwijderen',
    role: 'destructive',
    handler: () => deleteHistoryParking()
  }
]

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

// Functions


// TEST

const getVehicleName = computed(() => {
  return (vehicleId) => {
    const vehicle = vehiclesMap.value[vehicleId];
    
    if (!vehicle) {
      console.log('Vehicle not found for ID:', vehicleId);
      return `Voertuig #${vehicleId}`;
    }
    
    // Build display name
    let name = '';
    
    if (vehicle.brand && vehicle.model) {
      name = `${vehicle.brand} ${vehicle.model}`;
    } else if (vehicle.brand) {
      name = vehicle.brand;
    } else if (vehicle.model) {
      name = vehicle.model;
    } else {
      name = getVehicleTypeDisplay(vehicle.type);
    }
    
    // Add type in parentheses
    const typeDisplay = getVehicleTypeDisplay(vehicle.type);
    if (name !== typeDisplay) {
      name += ` (${typeDisplay})`;
    }
    
    return name;
  };
});

// Helper function
function getVehicleTypeDisplay(type) {
  const types = {
    car: 'Auto',
    bike: 'Fiets',
    scooter: 'Scooter',
    motor: 'Motor',
    other: 'Anders'
  };
  return types[type] || type;
}

async function loadUserVehicles() {
  try {
    const user = authService.getCurrentUser();
    
    if (!user || !user.id) return;
    
    let response;
    try {
      response = await apiGet(`vehicle.php?user_id=${user.id}`);
    } catch (error) {
      response = await apiGet(`vehicles.php?user_id=${user.id}`);
    }
    
    let vehiclesData = [];
    
    if (Array.isArray(response)) {
      vehiclesData = response;
    } else if (response.data && Array.isArray(response.data)) {
      vehiclesData = response.data;
    }
    
    // Create lookup map
    const map = {};
    vehiclesData.forEach(vehicle => {
      if (vehicle.id) {
        map[vehicle.id] = vehicle;
      }
    });
    
    vehiclesMap.value = map;
    console.log('History: Vehicles map created:', Object.keys(map).length, 'vehicles');
    
  } catch (error) {
    console.log('Error loading vehicles for history:', error);
    vehiclesMap.value = {};
  }
}



//TEST

function formatDateTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('nl-BE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// History-specific status functions
function getHistoryStatus(parking) {
  if (parking.closed_at) return 'closed';
  if (parking.expires_at && new Date(parking.expires_at) < new Date()) return 'expired';
  return 'other';
}

function getHistoryStatusText(parking) {
  const status = getHistoryStatus(parking);
  const texts = {
    closed: 'Afgesloten',
    expired: 'Verlopen'
  };
  return texts[status] || 'Onbekend';
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
    
    const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
    window.open(mapsUrl, '_blank');
    console.log('Opening Google Maps to:', lat, lng);
    
  } catch (error) {
    console.log('Error opening Google Maps:', error);
    alert('Kon Google Maps niet openen');
  }
}

// READ OPERATION: Load history parkings (closed or expired)
async function loadHistoryParkings() {
  try {
    const user = authService.getCurrentUser();
    
    if (!user || !user.id) {
      console.log('User not logged in');
      return;
    }
    await loadUserVehicles();

    const userId = user.id;
    console.log('Loading history parkings for user ID:', userId);
    
    // Get ALL parkings
    const response = await apiGet(`parkings.php?user_id=${userId}`);
    console.log('All parkings API response:', response);
    
    let parkingsData = [];
    
    if (Array.isArray(response)) {
      parkingsData = response;
    } else if (response.data && Array.isArray(response.data)) {
      parkingsData = response.data;
    } else {
      console.log('Unexpected response format:', response);
      parkingsData = [];
    }
    
    // Filter: Show only HISTORY parkings (closed OR expired)
    const now = new Date();
    const historyParkingsData = parkingsData.filter(parking => {
      // Show if parking is closed
      if (parking.closed_at) return true;
      
      // Show if parking has expiration and it's past
      if (parking.expires_at) {
        const expires = new Date(parking.expires_at);
        if (expires < now) return true; // Expired
      }
      
      return false; // Not history
    });
    
    // Sort by started_at (newest first)
    historyParkingsData.sort((a, b) => new Date(b.started_at) - new Date(a.started_at));
    
    historyParkings.value = historyParkingsData;
    
    console.log(`History parkings: ${historyParkings.value.length}`);
    
  } catch (error) {
    console.log('Error loading history parkings:', error);
    historyParkings.value = [];
  }
}

// DELETE OPERATION: Remove from history
function confirmDeleteHistory(parking) {
  parkingToDelete.value = parking;
  showDeleteAlert.value = true;
  console.log('Confirm delete from history:', parking);
}

async function deleteHistoryParking() {
  try {
    if (!parkingToDelete.value) {
      console.log('No parking to delete');
      return;
    }
    
    const parkingId = parkingToDelete.value.id;
    console.log('Deleting history parking ID:', parkingId);
    
    // Call DELETE API endpoint: DELETE /parkings?id={id}
    const response = await apiDelete(`parkings.php?id=${parkingId}`);
    console.log('Delete API response:', response);
    
    // Remove from local array immediately
    historyParkings.value = historyParkings.value.filter(p => p.id !== parkingId);
    
    // Show success message
    alert('Parking verwijderd uit geschiedenis!');
    
  } catch (error) {
    console.log('Error deleting history parking:', error);
    alert('Fout bij verwijderen: ' + error.message);
  } finally {
    // Reset state
    parkingToDelete.value = null;
    showDeleteAlert.value = false;
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
  loadHistoryParkings();
});
</script>

<style scoped>

.logout-text {
  margin-right: 8px;
}
</style>