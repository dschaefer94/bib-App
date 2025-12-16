document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.querySelector('.login-form form');
    if (!loginForm || loginForm.id === 'cheater') {
        // Ensure we are targeting the correct form, not the 'cheater' form.
        const allForms = document.querySelectorAll('.login-form form');
        allForms.forEach(form => {
            if (form.id !== 'cheater') {
                loginForm = form;
            }
        });
    }


    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Remove previous error messages
        const existingError = document.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        // The URL is now relative to the domain root, pointing to the api endpoint.
        // It relies on the .htaccess file for rewriting.
        const loginUrl = 'api/login';

        try {
            const response = await fetch(loginUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email, password: password }),
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Login successful
                const userData = result.userData;
                
                // Store user data in sessionStorage for the dashboard page to use
                sessionStorage.setItem('benutzername', `${userData.vorname} ${userData.name}`);
                sessionStorage.setItem('klasse', userData.klassenname);
                sessionStorage.setItem('user_id', userData.benutzer_id);

                // Redirect to the main page
                window.location.href = 'startseite.php';

            } else {
                // Login failed, show error message
                const errorMessage = result.error || 'An unknown error occurred.';
                const errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                errorElement.textContent = errorMessage;
                errorElement.style.color = 'red';
                errorElement.style.marginTop = '10px';
                loginForm.prepend(errorElement);
            }
        } catch (error) {
            console.error('Login request failed:', error);
            const errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            errorElement.textContent = 'Login request failed. Please check the console.';
            errorElement.style.color = 'red';
            errorElement.style.marginTop = '10px';
            loginForm.prepend(errorElement);
        }
    });
});
