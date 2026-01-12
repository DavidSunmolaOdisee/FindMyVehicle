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
      
      <div class="ion-padding">

        <!-- Foutmelding -->
        <ion-text color="danger" v-if="errorMessage">
          <p>{{ errorMessage }}</p>
        </ion-text>

        <!-- Loading -->
        <div v-if="isLoading" class="ion-text-center ion-padding">
          <ion-spinner name="crescent"></ion-spinner>
          <p>Dashboard aan het laden...</p>
        </div>

        <!-- Inhoud -->
        <div v-else>
          <!-- Welkom -->
          <ion-card class="welcome-card">
            <ion-card-header>
              <ion-card-title>Welkom, {{ username }}</ion-card-title>
            </ion-card-header>
          </ion-card>

          <!-- Statistieken -->
          <ion-grid class="stats-grid">
            <ion-row>
              <ion-col size="4">
                <ion-card class="stat-card">
                  <ion-card-content>
                    <div class="stat-icon">
                      <ion-icon :icon="carOutline"></ion-icon>
                    </div>
                    <div class="stat-value">{{ vehicles.length }}</div>
                  </ion-card-content>
                </ion-card>
              </ion-col>

              <ion-col size="4">
                <ion-card class="stat-card">
                  <ion-card-content>
                    <div class="stat-icon">
                      <!-- duidelijk locatie-icoon voor actieve parkings -->
                      <ion-icon :icon="locationOutline"></ion-icon>
                    </div>
                    <div class="stat-value">{{ activeParkings.length }}</div>
                  </ion-card-content>
                </ion-card>
              </ion-col>

              <ion-col size="4">
                <ion-card class="stat-card">
                  <ion-card-content>
                    <div class="stat-icon">
                      <ion-icon :icon="timeOutline"></ion-icon>
                    </div>
                    <div class="stat-value">{{ finishedParkings.length }}</div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
            </ion-row>
          </ion-grid>

          <!-- Actieve parkings (laatste 3) -->
          <ion-card>
            <ion-card-header>
              <ion-card-title>Actieve parkings</ion-card-title>
              <ion-card-subtitle>Laatste 3 actieve parkings</ion-card-subtitle>
            </ion-card-header>
            <ion-card-content>
              <ion-list v-if="activeParkingsLimited.length > 0">
                <ion-item v-for="p in activeParkingsLimited" :key="p.id">
                  <ion-label>
                    <h2>Parking #{{ p.id }} - {{ p.level || 'Geen level' }}</h2>
                    <p>{{ p.description || 'Geen beschrijving' }}</p>
                    <p>Gestart: {{ formatDateTime(p.started_at) }}</p>
                    <p v-if="p.expires_at">
                      Verloopt: {{ formatDateTime(p.expires_at) }}</p>
                  </ion-label>
                </ion-item>
              </ion-list>
              <div v-else>
                <p>Je hebt momenteel geen actieve parkings.</p>
                <p class="helper-text">
                  Start een nieuwe parking via de tab
                  <strong>“Parkings”</strong> onderaan.
                </p>
              </div>
            </ion-card-content>
          </ion-card>

          <!-- Historiek (laatste 3 afgesloten) -->
          <ion-card>
            <ion-card-header>
              <ion-card-title>Parking historiek</ion-card-title>
              <ion-card-subtitle>Laatste 3 afgesloten parkings</ion-card-subtitle>
            </ion-card-header>
            <ion-card-content>
              <ion-list v-if="finishedParkingsLimited.length > 0">
                <ion-item v-for="p in finishedParkingsLimited" :key="p.id">
                  <ion-label>
                    <h2>Parking #{{ p.id }} - {{ p.level || 'Geen level' }}</h2>
                    <p>{{ p.description || 'Geen beschrijving' }}</p>
                    <p>Gestart: {{ formatDateTime(p.started_at) }}</p>
                    <p>Gesloten: {{ formatDateTime(p.closed_at) }}</p>
                  </ion-label>
                </ion-item>
              </ion-list>
              <div v-else>
                <p>Nog geen afgesloten parkings.</p>
              </div>
            </ion-card-content>
          </ion-card>

          <!-- Voertuigen overzicht (laatste 3, met foto) -->
          <ion-card>
            <ion-card-header>
              <ion-card-title>Mijn voertuigen</ion-card-title>
              <ion-card-subtitle>Laatste 3 voertuigen</ion-card-subtitle>
            </ion-card-header>
            <ion-card-content>
              <ion-list v-if="vehiclesLimited.length > 0">
                <ion-item v-for="v in vehiclesLimited" :key="v.id">
                  <ion-thumbnail slot="start">
                    <img v-if="v.photo_url" :src="v.photo_url" />
                    <div v-else class="thumbnail-placeholder">
                      <ion-icon :icon="carOutline"></ion-icon>
                    </div>
                  </ion-thumbnail>

                  <ion-label>
                    <h2>{{ v.brand }} {{ v.model }}</h2>
                    <p>Type: {{ getVehicleTypeDisplay(v.type) }}</p>
                  </ion-label>
                </ion-item>
              </ion-list>
              <div v-else>
                <p>Je hebt nog geen voertuigen toegevoegd.</p>
                <p class="helper-text">
                  Voeg een voertuig toe via de tab
                  <strong>“Vehicles”</strong> onderaan.
                </p>
              </div>
            </ion-card-content>
          </ion-card>

          <!-- Vernieuw-knop -->
          <div class="ion-text-center ion-padding">
            <ion-button fill="solid" color="primary" @click="loadDashboard">
              <ion-icon slot="start" :icon="refreshOutline"></ion-icon>
              Vernieuw dashboard
            </ion-button>
          </div>
        </div>
      </div>
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
  IonGrid,
  IonRow,
  IonCol,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardSubtitle,
  IonCardContent,
  IonList,
  IonItem,
  IonLabel,
  IonButton,
  IonIcon,
  IonThumbnail,
  onIonViewWillEnter,
  IonButtons
} from '@ionic/vue';

import { ref, computed } from 'vue';

import { authService } from '@/services/auth.js';
import { apiGet } from '@/services/api.js';

import {
  carOutline,
  locationOutline,
  timeOutline,
  refreshOutline,
  logOut
} from 'ionicons/icons';

const username = ref('User');
const vehicles = ref([]);
const parkings = ref([]);

const isLoading = ref(false);
const errorMessage = ref('');

// helpers
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

const activeParkings = computed(() =>
  parkings.value.filter(p => p.closed_at === null || p.closed_at === undefined)
);

const finishedParkings = computed(() =>
  parkings.value.filter(p => p.closed_at !== null && p.closed_at !== undefined)
);

// laatste 3 per lijst
const vehiclesLimited = computed(() => vehicles.value.slice(0, 3));
const activeParkingsLimited = computed(() => activeParkings.value.slice(0, 3));
const finishedParkingsLimited = computed(() => finishedParkings.value.slice(0, 3));

function formatDateTime(value) {
  if (!value) return '';
  return new Date(value).toLocaleString();
}

async function loadDashboard() {
  try {
    const user = authService.getCurrentUser();

    if (!user || !user.id) {
      console.log('No user found');
      errorMessage.value = 'Je bent niet ingelogd.';
      return;
    }

    const userId = user.id;

    isLoading.value = true;
    errorMessage.value = '';

    const [userRes, vehiclesRes, parkingsRes] = await Promise.all([
      apiGet(`users.php?id=${userId}`),
      apiGet(`vehicles.php?user_id=${userId}`),
      apiGet(`parkings.php?user_id=${userId}`)
    ]);

    if (Array.isArray(userRes) && userRes.length > 0) {
      username.value = userRes[0].username;
    } else if (userRes && userRes.data && Array.isArray(userRes.data) && userRes.data.length > 0) {
      username.value = userRes.data[0].username;
    }

    if (Array.isArray(vehiclesRes)) {
      vehicles.value = vehiclesRes;
    } else if (vehiclesRes && Array.isArray(vehiclesRes.data)) {
      vehicles.value = vehiclesRes.data;
    } else {
      vehicles.value = [];
    }

    if (Array.isArray(parkingsRes)) {
      parkings.value = parkingsRes;
    } else if (parkingsRes && Array.isArray(parkingsRes.data)) {
      parkings.value = parkingsRes.data;
    } else {
      parkings.value = [];
    }

  } catch (error) {
    console.log('Error loading dashboard:', error);
    errorMessage.value = 'Er ging iets mis bij het laden van het dashboard.';
  } finally {
    isLoading.value = false;
  }
}

// Logout functie
function logout() {
  if (confirm('Weet je zeker dat je wilt uitloggen?')) {
    authService.logout();
    window.location.href = '/login';
  }
}

onIonViewWillEnter(() => {
  loadDashboard();
});
</script>

<style scoped>
.ion-text-center {
  text-align: center;
}

/* minder ruimte tussen welkom-card en stats */
.welcome-card {
  margin-bottom: 8px;
}

/* grid zelf compacter maken */
.stats-grid {
  margin-top: 4px;
  margin-bottom: 8px;
}

/* Statistiek-kaartjes compacter maken */
.stat-card {
  text-align: center;
  margin: 0;
}

.stat-card ion-card-content {
  padding: 8px 4px;
}

.stat-icon {
  font-size: 18px;
  margin-bottom: 2px;
}

.stat-value {
  font-size: 18px;
  font-weight: 600;
}

/* helpertekst onder lege lijsten */
.helper-text {
  font-size: 13px;
  color: #666;
  margin-top: 4px;
}

/* Thumbnail placeholder als er geen foto is */
.thumbnail-placeholder {
  width: 100%;
  height: 100%;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  opacity: 0.6;
}

.logout-text {
  margin-right: 8px;
}
</style>
