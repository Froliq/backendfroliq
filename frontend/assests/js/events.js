// frontend/assets/js/events.js

const API_URL = "http://localhost:8000";

async function getEvents() {
    const token = localStorage.getItem("authToken");
    const res = await fetch(`${API_URL}/events`, {
        headers: { "Authorization": `Bearer ${token}` }
    });
    return await res.json();
}

async function bookEvent(eventId) {
    const token = localStorage.getItem("authToken");
    const res = await fetch(`${API_URL}/events/${eventId}/book`, {
        method: "POST",
        headers: { "Authorization": `Bearer ${token}` }
    });
    return await res.json();
}
