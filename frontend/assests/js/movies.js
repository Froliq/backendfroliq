// frontend/assets/js/movies.js

const API_URL = "http://localhost:8000";

async function getMovies() {
    const token = localStorage.getItem("authToken");
    const res = await fetch(`${API_URL}/movies`, {
        headers: { "Authorization": `Bearer ${token}` }
    });
    return await res.json();
}

async function bookMovie(movieId) {
    const token = localStorage.getItem("authToken");
    const res = await fetch(`${API_URL}/movies/${movieId}/book`, {
        method: "POST",
        headers: { "Authorization": `Bearer ${token}` }
    });
    return await res.json();
}
