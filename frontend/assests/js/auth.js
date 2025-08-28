// frontend/assets/js/auth.js

const API_URL = "http://localhost:8000"; // Adjust if your backend URL differs

// Save token in localStorage
function saveToken(token) {
    localStorage.setItem("authToken", token);
}

// Get token
function getToken() {
    return localStorage.getItem("authToken");
}

// Register user
async function registerUser(username, email, password) {
    try {
        const res = await fetch(`${API_URL}/register`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, email, password })
        });
        const data = await res.json();
        return data;
    } catch (err) {
        console.error("Registration error:", err);
    }
}

// Login user
async function loginUser(email, password) {
    try {
        const res = await fetch(`${API_URL}/login`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        if (data.token) saveToken(data.token);
        return data;
    } catch (err) {
        console.error("Login error:", err);
    }
}

// Logout user
function logoutUser() {
    localStorage.removeItem("authToken");
    window.location.href = "/index.html"; // redirect to homepage
}
