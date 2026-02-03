document.getElementById("login-btn").addEventListener("click", async (event) => {
    event.preventDefault();
    const email = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();
    const resultEl = document.getElementById("result");

    resultEl.textContent = "";
    resultEl.className = "";

    if (!email || !password) {
        resultEl.textContent = "Bitte geben Sie E-Mail und Passwort ein!";
        resultEl.className = "error-message";
        return;
    }


   const url = "./restAPI.php/user/login";

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                email: email,   // oder username
                passwort: password,
                credentials: "include"
            })

        });

        const data = await response.json();
        console.log("Server response:", data);

        if (data.success) {
            sessionStorage.setItem('user', JSON.stringify(data.user));
            resultEl.textContent = `✔ Glückwunsch!, ${data.user.email}`;
            resultEl.className = "success-message";
            window.location.href = "startseite.html";

        } else {
            resultEl.textContent = `❌ ${data.message}`;
            resultEl.className = "error-message";
        }
    } catch (err) {
        console.error("Fetch error:", err);
        resultEl.textContent = `Fehler: ${err.message}`;
        resultEl.className = "error-message";
    }
});