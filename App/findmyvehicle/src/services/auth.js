import { apiPost } from './api.js';

export const authService = {
    // Login Function
    async login(username, password) {
        try {
            console.log("Login attempt for:", username);
            
            const responseData = await apiPost("users/login", {
                username: username,
                password: password
            });

            console.log("Login response:", responseData);

            if (responseData.message === "Login successful" && responseData.user) {
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

    // Registration Function
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

    getUserId() {
        const user = this.getCurrentUser();
        return user ? user.id : null;
    },

    isAuthenticated() {
        return !!localStorage.getItem("currentUser");
    },

    // Logout Function (NOG NIET GEBRUIKT)
    logout() {
        localStorage.removeItem("currentUser");
    }
};