document.getElementById("login-btn").addEventListener("click", async (event) => {
    event.preventDefault();
    const email = document.getElementById("username").value.trim();
    const passwort = document.getElementById("passwort").value.trim();
    const resultEl = document.getElementById("result");

    if (!email || !passwort) {
        resultEl.textContent = "Email und passwort eingeben!";
        resultEl.style.color = "red";
        return;
    }


    const url = "http://localhost/bibapp_xampp/restapi.php/user/login";

    try {

        // session 
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "include",
            body: JSON.stringify({
                email: email,   // oder username
                passwort: passwort,
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