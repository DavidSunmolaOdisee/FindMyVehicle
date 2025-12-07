<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Login</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Login</ion-title>
        </ion-toolbar>
      </ion-header>

      <!-- Login/Register Tabs -->
      <ion-segment v-model="activeSegment" @ionChange="onSegmentChange">
        <ion-segment-button value="login">
          <ion-label>Inloggen</ion-label>
        </ion-segment-button>
        <ion-segment-button value="register">
          <ion-label>Registreren</ion-label>
        </ion-segment-button>
      </ion-segment>

      <!-- Login Form -->
      <ion-card v-if="activeSegment === 'login'">
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-label position="stacked">Gebruikersnaam</ion-label>
              <ion-input 
                v-model="loginData.username" 
                placeholder="Voer uw gebruikersnaam in"
                required
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Wachtwoord</ion-label>
              <ion-input 
                v-model="loginData.password" 
                type="password"
                placeholder="Voer uw wachtwoord in"
                required
              ></ion-input>
            </ion-item>
          </ion-list>

          <ion-button
            expand="block" 
            @click="loginHandler" 
            :disabled="loading"
            class="ion-margin-top"
          >

          <ion-spinner v-if="loading" name="crescent"></ion-spinner>
            <span v-else>Inloggen</span>
          </ion-button>

          <ion-text v-if="errorMessage" color="danger">
            <p class="ion-text-center ion-margin-top">{{ errorMessage }}</p>
          </ion-text>
        </ion-card-content>
      </ion-card>

      <!-- Register Form -->
      <ion-card v-if="activeSegment === 'register'">
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-label position="stacked">Gebruikersnaam</ion-label>
              <ion-input 
                v-model="registerData.username" 
                placeholder="Kies een gebruikersnaam"
                required
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">E-mail</ion-label>
              <ion-input 
                v-model="registerData.email" 
                type="email"
                placeholder="Voer uw e-mailadres in"
                required
              ></ion-input>
            </ion-item>

            <ion-item>
              <ion-label position="stacked">Wachtwoord</ion-label>
              <ion-input 
                v-model="registerData.password" 
                type="password"
                placeholder="Kies een wachtwoord"
                required
              ></ion-input>
            </ion-item>
          </ion-list>

          <ion-button 
            expand="block" 
            @click="registerHandler" 
            :disabled="loading"
            class="ion-margin-top"
          >
            <ion-spinner v-if="loading" name="crescent"></ion-spinner>
            <span v-else>Account Aanmaken</span>
          </ion-button>

          <ion-text v-if="errorMessage" color="danger">
            <p class="ion-text-center ion-margin-top">{{ errorMessage }}</p>
          </ion-text>
        </ion-card-content>
      </ion-card>
    </ion-content>
  </ion-page>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '@/services/auth.js'
import { IonPage, IonContent, IonCard, IonCardContent, IonItem, IonLabel, IonInput, IonButton, IonSpinner, IonText, IonList, IonTitle, IonSegment, IonSegmentButton, IonToolbar, IonHeader } from '@ionic/vue'

const router = useRouter()
const loading = ref(false)
const errorMessage = ref('')
const activeSegment = ref('login')

// Form data
const loginData = ref({
  username: '',
  password: ''
})

const registerData = ref({
  username: '',
  email: '',
  password: '',
})

// Segment voor Login/Register wisselen
function onSegmentChange(event) {
  activeSegment.value = event.detail.value
  errorMessage.value = ''
}

// API Call voor Login
async function loginHandler() {
  loading.value = true
  errorMessage.value = ''

  if (!loginData.value.username || !loginData.value.password) {
    errorMessage.value = "Vul alle velden in"
    loading.value = false
    return
  }

  try {
    await authService.login(loginData.value.username, loginData.value.password)
    router.push('/tabs/tab1')
  } catch (error) {
    errorMessage.value = error.message || "Login mislukt"
    console.error('Login error:', error)
  } finally {
    loading.value = false
  }
}

// API Call voor Register
async function registerHandler() {
  loading.value = true
  errorMessage.value = ''

  // Updated validation - geen confirm wachtwoord check
  if (!registerData.value.username || !registerData.value.email || 
      !registerData.value.password) {
    errorMessage.value = "Vul alle velden in"
    loading.value = false
    return
  }

  try {
    const userData = {
      username: registerData.value.username,
      password: registerData.value.password,
      email: registerData.value.email
    }
    
    await authService.register(userData)
    
    // Auto-fill login en switch terug naar login tab
    loginData.value.username = registerData.value.username
    loginData.value.password = registerData.value.password
    
    // Reset register form
    registerData.value = {
      username: '',
      email: '',
      password: ''
    }
    
    // Switch login en toon succes
    activeSegment.value = 'login'
    errorMessage.value = 'Registratie succesvol! Je kunt nu inloggen.'
    
  } catch (error) {
    errorMessage.value = error.message || "Registratie mislukt"
    console.error('Register error:', error)
  } finally {
    loading.value = false
  }
}
</script>
