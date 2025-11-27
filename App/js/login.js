(function(){
    "use strict";

    async function loginUser(username, password) {
        try {
            console.log("Login attempt for:", username);
            
            const responseData = await apiPost("users/login", {
                username: username,
                password: password
            });

            console.log("Login response:", responseData);

            if (responseData.message === "Login successful" && responseData.user) {
                localStorage.setItem("currentUser", JSON.stringify(responseData.user));
                
                showMessage("Login succesvol!", "success");
                
                setTimeout(() => {
                    window.location.href = "dashboard.html";
                }, 1000);
                
                return responseData;
            } else {
                throw new Error(responseData.message || "Login mislukt");
            }
            
        } catch (error) {
            console.log("Login error:", error);
            throw error;
        }
    }

    async function registerUser(userData) {
        try {
            console.log("Registration attempt:", userData);
            
            const responseData = await apiPost("users", userData);

            console.log("Registration response:", responseData);

            if (responseData.message === "User added" && responseData.id) {
                showMessage("Registratie succesvol! Je kunt nu inloggen.", "success");
                
                setTimeout(() => {
                    showTab('login');
                }, 2000);
                
                return responseData;
            } else {
                throw new Error(responseData.message || "Registratie mislukt");
            }
            
        } catch (error) {
            console.log("Registration error:", error);
            throw error;
        }
    }

    function showMessage(text, type) {
        const activeForm = document.querySelector('.form.active');
        const messageEl = activeForm.querySelector('.message');
        
        messageEl.textContent = text;
        messageEl.className = `message ${type}`;
        messageEl.style.display = 'block';
        
        setTimeout(() => {
            messageEl.style.display = 'none';
        }, 5000);
    }

    function showTab(tabName) {
        document.querySelectorAll('.form').forEach(form => {
            form.classList.remove('active');
        });
        
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected form and activate tab
        if (tabName === 'login') {
            document.getElementById('loginForm').classList.add('active');
            document.querySelectorAll('.tab')[0].classList.add('active');
        } else if (tabName === 'register') {
            document.getElementById('registerForm').classList.add('active');
            document.querySelectorAll('.tab')[1].classList.add('active');
        }
    }

    function showForgotPassword() {
        document.querySelectorAll('.form').forEach(form => {
            form.classList.remove('active');
        });
        document.getElementById('forgotPasswordForm').classList.add('active');
        
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        const currentUser = localStorage.getItem("currentUser");
        if (currentUser && !window.location.href.includes("index.html")) {
            window.location.href = "dashboard.html";
        }

        document.getElementById("loginForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const username = document.getElementById("loginUsername").value;
            const password = document.getElementById("loginPassword").value;
            
            if (!username || !password) {
                showMessage("Vul alle velden in", "error");
                return;
            }

            try {
                await loginUser(username, password);
            } catch (error) {
                showMessage(error.message, "error");
            }
        });

        document.getElementById("registerForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const username = document.getElementById("regUsername").value;
            const email = document.getElementById("regEmail").value;
            const password = document.getElementById("regPassword").value;
            const confirmPassword = document.getElementById("regConfirmPassword").value;
            
            if (!username || !email || !password || !confirmPassword) {
                showMessage("Vul alle velden in", "error");
                return;
            }

            if (password !== confirmPassword) {
                showMessage("Wachtwoorden komen niet overeen", "error");
                return;
            }

            try {
                await registerUser({
                    username: username,
                    password: password,
                    email: email
                });
            } catch (error) {
                showMessage(error.message, "error");
            }
        });

        document.getElementById("forgotPasswordForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const email = document.getElementById("forgotEmail").value;
            
            showMessage(`Reset link zou verzonden worden naar ${email}`, "warning");
            setTimeout(() => {
                showTab('login');
            }, 3000);
        });

        document.querySelectorAll('.tab').forEach((tab, index) => {
            tab.addEventListener('click', function() {
                if (index === 0) showTab('login');
                if (index === 1) showTab('register');
            });
        });

        document.querySelector('.forgot-password a').addEventListener('click', function(e) {
            e.preventDefault();
            showForgotPassword();
        });
    });

    window.showTab = showTab;
    window.showForgotPassword = showForgotPassword;
    
})();