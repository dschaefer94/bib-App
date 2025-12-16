document.getElementById("login-btn").addEventListener("click", async () => {
    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();
    const resultEl = document.getElementById("result");

    if (!username || !password) {
        resultEl.textContent = "Введите username и password!";
        resultEl.style.color = "red";
        return;
    }

    const url = `http://localhost/bib-App/restapi.php/user`; // POST → writeUser

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ username, password })
        });

        const data = await response.json(); // теперь JSON точно вернется

        if (data.success) {
            resultEl.textContent = `✔ Успешный вход! Привет, ${data.user.email}`;
            resultEl.style.color = "green";

        } else {
            resultEl.textContent = `❌ ${data.message}`;
            resultEl.style.color = "red";
        }
    } catch (err) {
        resultEl.textContent = `Ошибка запроса: ${err.message}`;
        resultEl.style.color = "red";
    }
});
