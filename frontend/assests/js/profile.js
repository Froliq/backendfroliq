// frontend/assets/js/profile.js

const API_URL = "http://localhost:8000";

async function getProfile() {
    const token = localStorage.getItem("authToken");
    if (!token) return window.location.href = "/login.html";

    try {
        const res = await fetch(`${API_URL}/profile`, {
            headers: { "Authorization": `Bearer ${token}` }
        });
        const data = await res.json();
        return data;
    } catch (err) {
        console.error("Profile fetch error:", err);
    }
}

async function updateProfile(profileData) {
    const token = localStorage.getItem("authToken");
    try {
        const res = await fetch(`${API_URL}/profile`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${token}`
            },
            body: JSON.stringify(profileData)
        });
        return await res.json();
    } catch (err) {
        console.error("Profile update error:", err);
    }
}
