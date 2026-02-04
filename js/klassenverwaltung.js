document.addEventListener("DOMContentLoaded", () => {
    const adminContainer = document.querySelector(".admin-container");
    const classList = document.getElementById("class-list");
    const addBtn = document.getElementById("add-class-btn");
    const msgBox = document.getElementById("msg-box");

    // Zuerst alles verstecken
    adminContainer.style.opacity = "0";

    // Admin-Check Funktion
    async function checkAdminStatus() {
        try {
            const response = await fetch("./restAPI.php/user", { credentials: 'include' });
            const data = await response.json();

            if (data && data.ist_admin == 1) {
                // User ist Admin -> Container anzeigen
                adminContainer.style.opacity = "1";
                loadClasses(); // Erst jetzt Daten laden
            } else {
                // Kein Admin -> Wegschicken
                window.location.href = "startseite.html";
            }
        } catch (err) {
            window.location.href = "startseite.html";
        }
    }

    checkAdminStatus();

    // Hilfsfunktion für Nachrichten (jetzt mit Typ-Unterstützung)
    function showMsg(text, type = "error-message") {
        msgBox.textContent = text;
        msgBox.className = type;
        // Bei Erfolg nach 3 Sek ausblenden, bei Fehlern stehen lassen oder länger zeigen
        setTimeout(() => msgBox.textContent = "", type === "success-message" ? 3000 : 5000);
    }

    // 1. READ: Klassen laden
    async function loadClasses() {
        try {
            const response = await fetch("./restAPI.php/class/getClass");
            if (!response.ok) throw new Error("Server-Antwort war nicht ok");

            const classes = await response.json();
            classList.innerHTML = "";

            classes.forEach(item => {
                const row = document.createElement("tr");
                const safeLink = item.ical_link ? item.ical_link.replace(/'/g, "\\'") : '';

                row.innerHTML = `
                    <td>${item.klassenname}</td>
                    <td>
                        <button class="btn btn-edit" onclick="openEditModal(${item.klassen_id}, '${item.klassenname}', '${safeLink}')">Bearbeiten</button>
                        <button class="btn btn-delete" onclick="deleteClass(${item.klassen_id})">Löschen</button>
                    </td>
                `;
                classList.appendChild(row);
            });
        } catch (err) {
            showMsg("Klassen konnten nicht geladen werden: " + err.message);
        }
    }

    // 2. CREATE: Neue Klasse
    addBtn.addEventListener("click", async () => {
        const name = document.getElementById("new-classname").value.trim();
        const ical = document.getElementById("new-ical-link").value.trim();

        if (!name) {
            showMsg("Bitte einen Klassennamen eingeben.");
            return;
        }

        try {
            const response = await fetch("./restAPI.php/class", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ klassenname: name, ical_link: ical })
            });

            const result = await response.json();

            if (response.ok && result.erfolg) {
                showMsg("Klasse erfolgreich erstellt!", "success-message");
                document.getElementById("new-classname").value = "";
                document.getElementById("new-ical-link").value = "";
                loadClasses();
            } else {
                // Hier fangen wir die benutzerfreundliche Nachricht ab
                showMsg(result.grund || "Fehler beim Erstellen.");
            }
        } catch (err) {
            showMsg("Netzwerkfehler: " + err.message);
        }
    });

    // 3. DELETE: Klasse löschen
    window.deleteClass = async (id) => {
        if (!confirm("Wirklich löschen? Alle zugehörigen Tabellen werden entfernt!")) return;

        try {
            const response = await fetch(`./restAPI.php/class/${id}`, { method: "DELETE" });
            const result = await response.json();

            if (response.ok && result.erfolg) {
                showMsg("Klasse gelöscht.", "success-message");
                loadClasses();
            } else {
                showMsg(result.grund || "Löschen fehlgeschlagen.");
            }
        } catch (err) {
            showMsg("Server nicht erreichbar.");
        }
    };

    // 4. UPDATE: Modal & Speichern
    window.openEditModal = (id, name, ical) => {
        document.getElementById("edit-class-id").value = id;
        document.getElementById("edit-classname").value = name;
        document.getElementById("edit-ical-link").value = ical || "";
        document.getElementById("editModal").style.display = "block";
    };

    document.getElementById("save-edit-btn").addEventListener("click", async () => {
        const id = document.getElementById("edit-class-id").value;
        const newName = document.getElementById("edit-classname").value.trim();
        const newIcal = document.getElementById("edit-ical-link").value.trim();

        try {
            const response = await fetch(`./restAPI.php/class/${id}`, {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ klassenname: newName, ical_link: newIcal })
            });

            const result = await response.json();

            if (response.ok && result.erfolg) {
                document.getElementById("editModal").style.display = "none";
                showMsg("Update erfolgreich!", "success-message");
                loadClasses();
            } else {
                showMsg(result.grund || "Update fehlgeschlagen.");
            }
        } catch (err) {
            showMsg("Kritischer Fehler beim Update.");
        }
    });

    loadClasses();
});