<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>History</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">History</ion-title>
          <ion-title size="large">Historiek</ion-title>
        </ion-toolbar>
      </ion-header>

      <div class="ion-padding">

        <!-- Foutmelding -->
        <ion-text color="danger" v-if="errorMessage">
          <p>{{ errorMessage }}</p>
        </ion-text>

        <!-- Loading -->
        <div v-if="isLoading" class="ion-text-center ion-padding">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Historiek aan het laden...</p>
        </div>

        <!-- Inhoud -->
        <div v-else>
          <ion-list v-if="historicalParkings.length > 0">
            <ion-item-sliding
              v-for="p in historicalParkings"
              :key="p.id"
            >
              <ion-item>
                <ion-label>
                  <h2>
                    Parking #{{ p.id }}
                    <span v-if="p.level"> - {{ p.level }}</span>
                  </h2>
                  <p>{{ p.description || 'Geen beschrijving' }}</p>
                  <p>Gestart: {{ formatDateTime(p.started_at) }}</p>
                  <p>Gesloten: {{ formatDateTime(p.closed_at) }}</p>
                </ion-label>
              </ion-item>

              <!-- Slide actiemenu -->
              <ion-item-options side="end">
                <!-- Opnieuw activeren -->
                <ion-item-option
                  color="primary"
                  @click="confirmReactivate(p)"
                >
                  <ion-icon slot="icon-only" :icon="refreshOutline"></ion-icon>
                </ion-item-option>

                <!-- Permanent verwijderen -->
                <ion-item-option
                  color="danger"
                  @click="confirmDelete(p)"
                >
                  <ion-icon slot="icon-only" :icon="trash"></ion-icon>
                </ion-item-option>
              </ion-item-options>
            </ion-item-sliding>
          </ion-list>

          <div v-else>
            <p>Nog geen afgesloten parkings.</p>
          </div>
        </div>
      </div>

      <!-- Confirm reactivate alert -->
      <ion-alert
        :is-open="showReactivateAlert"
        header="Parking opnieuw activeren"
        :message="reactivateMessage"
        :buttons="reactivateAlertButtons"
        @didDismiss="showReactivateAlert = false"
      ></ion-alert>

      <!-- Confirm permanent delete alert -->
      <ion-alert
        :is-open="showDeleteAlert"
        header="Parking definitief verwijderen"
        message="Ben je zeker dat je deze parking definitief wil verwijderen? Dit kan niet ongedaan gemaakt worden."
        :buttons="deleteAlertButtons"
        @didDismiss="showDeleteAlert = false"
      ></ion-alert>

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
        message="Weet je zeker dat je deze parking uit de geschiedenis wilt verwijderen?"
        :buttons="deleteAlertButtons"
        @didDismiss="showDeleteAlert = false"
      ></ion-alert>
      
    </ion-content>
  </ion-page>
</template>

<script setup>
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonText,
  IonSpinner,
  IonList,
  IonItem,
  IonLabel,
  IonIcon,
  IonAlert,
  IonItemSliding,
  IonItemOptions,
  IonItemOption,
  onIonViewWillEnter
} from '@ionic/vue';

import { ref, computed } from 'vue';
import { authService } from '@/services/auth.js';
import { apiGet, apiPut, apiDelete } from '@/services/api.js';
import { refreshOutline, trash } from 'ionicons/icons';

const parkings = ref([]);
const isLoading = ref(false);
const errorMessage = ref('');

// Reactivate state
const showReactivateAlert = ref(false);
const parkingToReactivate = ref(null);

// Delete state
const showDeleteAlert = ref(false);
const parkingToDelete = ref(null);

// filter voor historiek: alle parkings met closed_at niet null
const historicalParkings = computed(() =>
  parkings.value.filter(p => p.closed_at !== null && p.closed_at !== undefined)
);

const reactivateMessage = computed(() => {
  if (!parkingToReactivate.value) return '';
  return `Wil je parking #${parkingToReactivate.value.id} opnieuw actief zetten?`;
});

function formatDateTime(value) {
  if (!value) return '';
  return new Date(value).toLocaleString();
}

async function loadHistory() {
  try {
    const user = authService.getCurrentUser();

    if (!user || !user.id) {
      errorMessage.value = 'Je bent niet ingelogd.';
      return;
    }

    const userId = user.id;
    isLoading.value = true;
    errorMessage.value = '';

    const res = await apiGet(`parkings.php?user_id=${userId}`);

    if (Array.isArray(res)) {
      parkings.value = res;
    } else if (res && Array.isArray(res.data)) {
      parkings.value = res.data;
    } else {
      parkings.value = [];
    }

  } catch (error) {
    console.log('Error loading history:', error);
    errorMessage.value = 'Er ging iets mis bij het laden van de historiek.';
  } finally {
    isLoading.value = false;
  }
}

// ===== Reactivate flow =====
function confirmReactivate(p) {
  parkingToReactivate.value = p;
  showReactivateAlert.value = true;
}

async function reactivateParking() {
  try {
    if (!parkingToReactivate.value) return;

    const id = parkingToReactivate.value.id;

    // closed_at terug op NULL
    await apiPut(`parkings.php?id=${id}`, { closed_at: null });

    await loadHistory();
    alert('Parking opnieuw actief gezet.');
  } catch (err) {
    alert('Fout bij opnieuw activeren.');
  } finally {
    parkingToReactivate.value = null;
    showReactivateAlert.value = false;
  }
}

const reactivateAlertButtons = [
  { text: 'Annuleren', role: 'cancel' },
  { text: 'Opnieuw activeren', role: 'confirm', handler: () => reactivateParking() },
];

// ===== Permanent delete flow =====
function confirmDelete(p) {
  parkingToDelete.value = p;
  showDeleteAlert.value = true;
}

async function deleteParking() {
  try {
    if (!parkingToDelete.value) return;

    const id = parkingToDelete.value.id;

    await apiDelete(`parkings.php?id=${id}`);

    await loadHistory();
    alert('Parking definitief verwijderd.');
  } catch (err) {
    alert('Fout bij verwijderen van parking.');
  } finally {
    parkingToDelete.value = null;
    showDeleteAlert.value = false;
  }
}

const deleteAlertButtons = [
  { text: 'Annuleren', role: 'cancel' },
  { text: 'Verwijderen', role: 'destructive', handler: () => deleteParking() },
];

// automatische refresh elke keer als je naar de History-tab gaat
onIonViewWillEnter(() => {
  loadHistory();
});
import { ref, onMounted, computed } from 'vue';
import { authService } from '@/services/auth.js';
import { apiGet, apiDelete } from '@/services/api.js';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonList, IonItemSliding, IonItem, IonLabel, IonItemOptions, IonItemOption, IonButton, IonAlert } from '@ionic/vue';

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

// Initial load
onMounted(() => {
  loadHistoryParkings();
});
</script>

<style scoped>
.ion-text-center {
  text-align: center;
}
</style>
