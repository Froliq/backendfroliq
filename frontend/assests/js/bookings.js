// frontend/assets/js/bookings.js

const API_URL = "http://localhost:8000";

async function getBookings() {
    const token = localStorage.getItem("authToken");
    const res = await fetch(`${API_URL}/bookings`, {
        headers: { "Authorization": `Bearer ${token}` }
    });
    return await res.json();
}
