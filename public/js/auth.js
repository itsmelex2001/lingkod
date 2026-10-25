// auth.js — Login page logic (fetches from PHP backend)

document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("loginForm");
  if (!loginForm) return;

  loginForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = document.getElementById("loginUser").value.trim();
    const password = document.getElementById("loginPass").value;
    const submitBtn = loginForm.querySelector('button[type="submit"]');

    if (!username || !password) {
      alert("Please enter both username and password.");
      return;
    }

    // Disable button to prevent double-submit
    submitBtn.disabled = true;
    submitBtn.textContent = "Logging in…";

    try {
      const res = await fetch(`${API_BASE}/auth.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "login", username, password }),
      });

      console.log('Response status:', res.status);
      
      let data;
      try {
        data = await res.json();
      } catch (parseErr) {
        console.error('Failed to parse JSON response:', parseErr);
        alert(`Server error (${res.status}). Check browser console for details.`);
        submitBtn.disabled = false;
        submitBtn.textContent = "Secure Login";
        return;
      }

      console.log('Login response:', data);

      if (data.success) {
        if (data.role === "admin") {
          window.location.href = "/sdn/pages/admin-dashboard.php";
        } else {
          window.location.href = "/sdn/pages/staff-dashboard.php";
        }
      } else {
        alert(`Access Denied: ${data.message || "Invalid credentials."}`);
        submitBtn.disabled = false;
        submitBtn.textContent = "Secure Login";
      }
    } catch (err) {
      console.error('Fetch error:', err);
      alert(
        "Network error — make sure XAMPP is running and the database is set up.",
      );
      submitBtn.disabled = false;
      submitBtn.textContent = "Secure Login";
    }
  });
});
