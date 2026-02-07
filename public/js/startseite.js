document.addEventListener("DOMContentLoaded", () => {
    const userInfoEl = document.getElementById("user-info");
    const storedUser = sessionStorage.getItem('user');

    if (storedUser) {
        try {
            const user = JSON.parse(storedUser);
            // I am assuming the user object has vorname, nachname and klassenname properties.
            // Based on the console.log in login.js, I should have access to the user object.
            // If these properties are named differently, this part will need to be adjusted.
            const vorname = user.vorname || '';
            const nachname = user.nachname || '';
            const klassenname = user.klassenname || '';

            if (vorname && nachname) {
                userInfoEl.textContent = `${vorname} ${nachname} (${klassenname})`;
            } else if (user.email) {
                // Fallback to email if name is not available
                userInfoEl.textContent = user.email;
            } else {
                // If no user info is found, redirect to login
                window.location.href = "index.html";
            }
        } catch (e) {
            console.error("Failed to parse user data from sessionStorage", e);
            sessionStorage.removeItem('user');
            window.location.href = "index.html";
        }
    } else {
        // If there is no user data in sessionStorage, redirect to the login page
        window.location.href = "index.html";
    }
});
