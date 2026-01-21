document.getElementById("login-btn").addEventListener("click", async (event) => {
    event.preventDefault();
    const email = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();
    const resultEl = document.getElementById("result");

    if (!email || !password) {
        resultEl.textContent = "Введите email и пароль!";
        resultEl.style.color = "red";
        return;
    }


    const url = "http://localhost/bibapp_xampp/restapi.php/user/login";

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
            resultEl.textContent = `✔ Glückwunsch!, ${data.user.email}`;
            resultEl.style.color = "green";

        } else {
            resultEl.textContent = `❌ ${data.message}`;
            resultEl.style.color = "red";
        }
    } catch (err) {
        console.error("Fetch error:", err);
        resultEl.textContent = `Fehler: ${err.message}`;
        resultEl.style.color = "red";
    }
});