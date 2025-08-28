// Handle login
document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const email = loginForm.email.value;
      const password = loginForm.password.value;

      const res = await fetch("/backend/endpoints/auth.php?action=login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password })
      });

      const data = await res.json();
      if (data.token) {
        localStorage.setItem("token", data.token);
        alert("Login successful");
        window.location.href = "/frontend/pages/user/profile.html";
      } else {
        alert(data.error || "Login failed");
      }
    });
  }

  // Handle register
  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const name = registerForm.name.value;
      const email = registerForm.email.value;
      const password = registerForm.password.value;

      const res = await fetch("/backend/endpoints/auth.php?action=register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name, email, password })
      });

      const data = await res.json();
      if (data.message) {
        alert("Registration successful! Please login.");
        window.location.href = "login.html";
      } else {
        alert(data.error || "Registration failed");
      }
    });
  }
});
