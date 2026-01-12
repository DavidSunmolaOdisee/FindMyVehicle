import { apiPost } from './api.js';

export const authService = {
    // Login Functie   
    async login(username, password) {
        try {
            console.log("Login attempt for:", username);
            
            const responseData = await apiPost("users/login", {
                username: username,
                password: password
            });

            console.log("Login response:", responseData);

            if (responseData.message === "Login successful" && responseData.user) {
                console.log("Login attempt for:", responseData.user.id);
                localStorage.setItem("currentUser", JSON.stringify(responseData.user));
                return responseData;
            } else {
                throw new Error(responseData.message || "Login mislukt");
            }
            
        } catch (error) {
            console.log("Login error:", error);
            throw error;
        }
    },

    // Registratie Functie
    async register(userData) {
        try {
            console.log("Registration attempt:", userData);
            
            const responseData = await apiPost("users", userData);

            console.log("Registration response:", responseData);

            if (responseData.message === "User added" && responseData.id) {
                return responseData;
            } else {
                throw new Error(responseData.message || "Registratie mislukt");
            }
            
        } catch (error) {
            console.log("Registration error:", error);
            throw error;
        }
    },

    // Get Current User voor sessiebeheer
    getCurrentUser() {
        const userJson = localStorage.getItem("currentUser");
        if (userJson) {
            return JSON.parse(userJson);
        }
        return null;
    },

    // Get User ID
    getUserId() {
        const user = this.getCurrentUser();
        return user ? user.id : null;
    },

    // Check of gebruiker is ingelogd
    isAuthenticated() {
        return !!localStorage.getItem("currentUser");
    },

    // Logout Function
    logout() {
        localStorage.removeItem("currentUser");
    },

    // Debug functie om huidige gebruiker te loggen | MOET WEG DOEN
    debugAuth() {
        const user = authService.getCurrentUser()
        console.log('🔍 Current user in localStorage:', user)
        console.log('🔍 User ID:', authService.getUserId())
    }
};