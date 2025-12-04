<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Vehicles</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Vehicles</ion-title>
        </ion-toolbar>
      </ion-header>

       <!-- Add Vehicle Button -->
      <ion-fab vertical="top" horizontal="center" slot="fixed" edge>
        <ion-fab-button @click="openAddVehicleModal">
          <ion-icon :icon="add"></ion-icon>
        </ion-fab-button>
      </ion-fab>

      <!-- Vehicles List -->
      <ion-list>
        <ion-item-sliding v-for="vehicle in vehicles" :key="vehicle.id">
          <ion-item @click="viewVehicleDetails(vehicle)">
            <ion-thumbnail slot="start">
              <img v-if="vehicle.photo_url" :src="vehicle.photo_url" />
              <div v-else class="thumbnail-placeholder">
                <ion-icon :icon="getVehicleIcon(vehicle.type)" size="large"></ion-icon>
              </div>
            </ion-thumbnail>
            
            <ion-label>
              <h2>{{ vehicle.brand || '' }} {{ vehicle.model || '' }}</h2>
              <p>{{ getVehicleTypeDisplay(vehicle.type) }}</p>
              <p v-if="vehicle.notes">{{ vehicle.notes }}</p>
            </ion-label>
          </ion-item>

          <ion-item-options side="end">
            <ion-item-option color="primary" @click="editVehicle(vehicle)">
              <ion-icon slot="icon-only" :icon="pencil"></ion-icon>
            </ion-item-option>
            <ion-item-option color="danger" @click="confirmDelete(vehicle)">
              <ion-icon slot="icon-only" :icon="trash"></ion-icon>
            </ion-item-option>
          </ion-item-options>
        </ion-item-sliding>
      </ion-list>

       <!-- Add/Edit Vehicle Modal -->
      <ion-modal :is-open="showVehicleModal" @didDismiss="closeVehicleModal">
        <ion-header>
          <ion-toolbar>
            <ion-title>{{ isEditing ? 'Bewerk Voertuig' : 'Voeg Voertuig Toe' }}</ion-title>
            <ion-buttons slot="end">
              <ion-button @click="closeVehicleModal">Sluiten</ion-button>
            </ion-buttons>
          </ion-toolbar>
        </ion-header>
        
        <ion-content class="ion-padding">
          <ion-list>
            <ion-item>
              <ion-label position="stacked">Type *</ion-label>
              <ion-select v-model="vehicleForm.type" placeholder="Kies type" interface="action-sheet">
                <ion-select-option value="car">Auto</ion-select-option>
                <ion-select-option value="bike">Fiets</ion-select-option>
                <ion-select-option value="scooter">Scooter</ion-select-option>
                <ion-select-option value="motor">Motor</ion-select-option>
                <ion-select-option value="other">Anders</ion-select-option>
              </ion-select>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Merk</ion-label>
              <ion-input v-model="vehicleForm.brand" placeholder="Bijv. BMW, Toyota"></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Model</ion-label>
              <ion-input v-model="vehicleForm.model" placeholder="Bijv. 320i, Corolla"></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Beschrijving</ion-label>
              <ion-textarea 
                v-model="vehicleForm.notes" 
                placeholder="Bijv. Zwarte sedan, kenteken 1-ABC-123, opvallende stickers..."
                rows="3"
              ></ion-textarea>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Foto URL (optioneel)</ion-label>
              <ion-input v-model="vehicleForm.photo_url" placeholder="https://voorbeeld.com/foto.jpg"></ion-input>
            </ion-item>
          </ion-list>

          <ion-button 
            expand="block" 
            @click="saveVehicle" 
            :disabled="!vehicleForm.type || saving"
            class="ion-margin-top"
          >
            <ion-spinner v-if="saving" name="crescent"></ion-spinner>
            <span v-else>{{ isEditing ? 'Opslaan' : 'Toevoegen' }}</span>
          </ion-button>
        </ion-content>
      </ion-modal>

      <!-- Delete Confirmation Alert -->
      <ion-alert
        :is-open="showDeleteAlert"
        header="Voertuig Verwijderen"
        message="Weet je zeker dat je dit voertuig wilt verwijderen?"
        :buttons="deleteAlertButtons"
        @didDismiss="showDeleteAlert = false"
      ></ion-alert>
      
    </ion-content>
  </ion-page>
</template>

<script setup>
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonFab, IonFabButton, IonIcon, IonList, IonItemSliding, IonItem, IonThumbnail, IonLabel, IonItemOptions, IonItemOption, IonButton, IonModal, IonSelect, IonSelectOption, IonTextarea, IonAlert, IonSpinner, IonButtons } from '@ionic/vue';
import { add, pencil, trash, } from 'ionicons/icons'
import { ref, onMounted } from 'vue';
import { authService } from '@/services/auth.js';
import { apiGet } from '@/services/api.js';

const loading = ref(true)
const vehicles = ref([])
const showVehicleModal = ref(false)
const isEditing = ref(false)
const saving = ref(false)
const showDeleteAlert = ref(false)
const vehicleToDelete = ref(null)
const errorMessage = ref('')

const vehicleForm = ref({
  id: null,
  type: '',
  brand: '',
  model: '',
  notes: '',
  photo_url: ''
})

const deleteAlertButtons = [
  {
    text: 'Annuleren',
    role: 'cancel'
  },
  {
    text: 'Verwijderen',
    role: 'destructive',
    handler: () => deleteVehicle()
  }
]

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

async function loadVehicles() {
  try {
    loading.value = true
    errorMessage.value = ''
    
    // Get current user ID
    const userId = authService.getUserId()
    
    if (!userId) {
      errorMessage.value = 'Niet ingelogd'
      console.error('No user ID found')
      return
    }
    
    console.log('Loading vehicles for user ID:', userId)
    
    // Call your API endpoint: /vehicle?user_id=1
    const response = await apiGet(`vehicle?user_id=${userId}`)
    
    console.log('API response for vehicles:', response)
    
    // Handle different response formats
    if (Array.isArray(response)) {
      vehicles.value = response
    } else if (response.data && Array.isArray(response.data)) {
      vehicles.value = response.data
    } else {
      console.error('Unexpected API response format:', response)
      vehicles.value = []
    }
    
  } catch (error) {
    console.error('Error loading vehicles:', error)
    errorMessage.value = 'Fout bij laden voertuigen'
    
    // For development/testing - use mock data
    vehicles.value = getMockVehicles()
    
  } finally {
    loading.value = false
  }
}

// Temporary mock data for testing
function getMockVehicles() {
  const userId = authService.getUserId()
  return [
    {
      id: 1,
      user_id: userId || 1,
      type: 'car',
      brand: 'BMW',
      model: '320i',
      notes: 'Zwarte sedan',
      photo_url: 'https://picsum.photos/id/1/400/200',
      created_at: '2025-11-12 13:30:39'
    },
    {
      id: 2,
      user_id: userId || 1,
      type: 'bike',
      brand: 'Canyon',
      model: 'Endurace',
      notes: 'Racefiets voor weekend',
      photo_url: 'https://picsum.photos/id/2/400/200',
      created_at: '2025-11-12 13:30:39'
    }
  ]
}

function openAddVehicleModal() {
  isEditing.value = false
  vehicleForm.value = {
    id: null,
    type: '',
    brand: '',
    model: '',
    notes: '',
    photo_url: ''
  }
  showVehicleModal.value = true
}

function editVehicle(vehicle) {
  isEditing.value = true
  vehicleForm.value = {
    id: vehicle.id,
    type: vehicle.type,
    brand: vehicle.brand || '',
    model: vehicle.model || '',
    notes: vehicle.notes || '',
    photo_url: vehicle.photo_url || ''
  }
  showVehicleModal.value = true
}

function closeVehicleModal() {
  showVehicleModal.value = false
}

function viewVehicleDetails(vehicle) {
  console.log('View vehicle details:', vehicle)
}

function confirmDelete(vehicle) {
  vehicleToDelete.value = vehicle
  showDeleteAlert.value = true
}

async function saveVehicle() {
  saving.value = true
  console.log('Save vehicle:', vehicleForm.value)
  
  // Will implement API call later
  await new Promise(resolve => setTimeout(resolve, 500)) // Simulate API delay
  
  saving.value = false
  closeVehicleModal()
}

async function deleteVehicle() {
  console.log('Delete vehicle:', vehicleToDelete.value)
  // Will implement API call later
  vehicleToDelete.value = null
  showDeleteAlert.value = false
}

// Load vehicles when component mounts
onMounted(() => {
  loadVehicles()
})
</script>
