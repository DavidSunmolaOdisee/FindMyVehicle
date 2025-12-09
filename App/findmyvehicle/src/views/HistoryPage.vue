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
</script>

<style scoped>
.ion-text-center {
  text-align: center;
}
</style>
