<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Vehicles</ion-title>
        <ion-buttons slot="end">
          <p class="logout-text">Logout</p>
          <ion-button @click="logout" fill="outline" color="danger">
            <ion-icon slot="icon-only" :icon="logOut"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">

       <!-- Add Voertuig Button -->
      <ion-fab vertical="bottom" horizontal="end" slot="fixed" >
        <ion-fab-button @click="openAddVehicleModal">
          <ion-icon :icon="add"></ion-icon>
        </ion-fab-button>
      </ion-fab>

      <!-- Voertuig List -->
      <ion-list>
        <ion-item-sliding v-for="vehicle in vehicles" :key="vehicle.id">
          <ion-item @click="viewVehicleDetails(vehicle)">
            <ion-thumbnail slot="start">
              <img v-if="vehicle.photo_url" :src="vehicle.photo_url" />
              <div v-else class="thumbnail-placeholder">
                <span>No Image</span>
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

       <!-- Add/Edit Voertuig Modal -->
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
              <ion-select v-model="vehicleForm.type" placeholder="Kies type" interface="popover">
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
                placeholder="Bijv. Zwart, Stickers, etc."
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
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonFab, IonFabButton, IonIcon, IonList, IonItemSliding, IonItem, IonThumbnail, IonLabel, IonItemOptions, IonItemOption, IonButton, IonModal, IonSelect, IonSelectOption, IonTextarea, IonAlert, IonSpinner, IonButtons, IonInput } from '@ionic/vue';
import { add, pencil, trash, logOut } from 'ionicons/icons'
import { ref, onMounted } from 'vue';
import { authService } from '@/services/auth.js';
import { apiGet, apiPost, apiPut, apiDelete } from '@/services/api.js';

// Variables
const vehicles = ref([])
const showVehicleModal = ref(false)
const isEditing = ref(false)
const saving = ref(false)
const showDeleteAlert = ref(false)
const vehicleToDelete = ref(null)

// Form
const vehicleForm = ref({
  id: null,
  type: '',
  brand: '',
  model: '',
  notes: '',
  photo_url: ''
})

// Alert buttons
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

// ==================== HELP FUNCTIONS ====================
function viewVehicleDetails(vehicle) {
  console.log('View vehicle:', vehicle)
}

// Vehicle type function
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

// Vehicle modal functions
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

function closeVehicleModal() {
  showVehicleModal.value = false
}

// ==================== READ OPERATION ====================
async function loadVehicles() {
  try {
    const user = authService.getCurrentUser()
    
    if (!user || !user.id) {
      console.log('No user found')
      return
    }
    
    const userId = user.id
    console.log('Loading vehicles for user ID:', userId)
    
    const response = await apiGet(`vehicles.php?user_id=${userId}`)
    console.log('Vehicles API response:', response)
    
    if (Array.isArray(response)) {
      vehicles.value = response
    } else if (response.data && Array.isArray(response.data)) {
      vehicles.value = response.data
    } else {
      console.log('Unexpected response format:', response)
      vehicles.value = []
    }
    
  } catch (error) {
    console.log('Error loading vehicles:', error)
    vehicles.value = []
  }
}

// ==================== UPDATE OPERATION ====================
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
  
  console.log('Editing vehicle ID:', vehicle.id, 'Data:', vehicleForm.value)
  
  showVehicleModal.value = true
}

// ==================== CREATE/UPDATE OPERATION ====================
async function saveVehicle() {
  saving.value = true
  
  try {
    // 1. Haal huidige ingelogde gebruiker op
    const user = authService.getCurrentUser()
    
    if (!user || !user.id) {
      alert('Error: You are not logged in')
      return
    }
    
    // 2. data voor API
    const vehicleData = {
      user_id: user.id,
      type: vehicleForm.value.type,
      brand: vehicleForm.value.brand || null,
      model: vehicleForm.value.model || null,
      notes: vehicleForm.value.notes || null,
      photo_url: vehicleForm.value.photo_url || null
    }
    
    console.log('Sending vehicle data to API:', vehicleData)
    
    let response
    
    // 3. Create of Read Operatie
    if (isEditing.value && vehicleForm.value.id) {
      console.log('Updating vehicle with ID:', vehicleForm.value.id)
      response = await apiPut(`vehicles.php?id=${vehicleForm.value.id}`, vehicleData)
      alert('Voertuig succesvol bijgewerkt!')
    } else {
      console.log('Creating new vehicle')
      response = await apiPost('vehicles.php', vehicleData)
      alert('Voertuig succesvol toegevoegd!')
    }
    
    console.log('API response:', response)
    
    // 4. Modal dicht doen
    closeVehicleModal()
    
    // 5. Vernieuw de lijst met voertuigen
    await loadVehicles()
    
  } catch (error) {
    console.log('Error saving vehicle:', error)
    alert('Fout bij opslaan: ' + error.message)
  } finally {
    saving.value = false
  }
}

// ==================== DELETE OPERATION ====================
async function deleteVehicle() {
  try {
    if (!vehicleToDelete.value) {
      console.log('No vehicle to delete')
      return
    }
    
    const vehicleId = vehicleToDelete.value.id
    console.log('Deleting vehicle with ID:', vehicleId)
    
    const response = await apiDelete(`vehicles.php?id=${vehicleId}`)
    console.log('Delete API response:', response)
    
    vehicles.value = vehicles.value.filter(v => v.id !== vehicleId)
    
    alert('Voertuig succesvol verwijderd!')
    
  } catch (error) {
    console.log('Error deleting vehicle:', error)
    alert('Fout bij verwijderen: ' + error.message)
    
    await loadVehicles()
  } finally {
    vehicleToDelete.value = null
    showDeleteAlert.value = false
  }
}

// ==================== CONFIRM DELETE ====================
function confirmDelete(vehicle) {
  vehicleToDelete.value = vehicle
  showDeleteAlert.value = true
  console.log('Confirm delete for vehicle:', vehicle)
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
  loadVehicles()
})
</script>

<style scoped>
.ion-margin-top {
  margin-top: 16px;
  margin-bottom: 16px;
}

.logout-text {
  margin-right: 8px;
}
</style>