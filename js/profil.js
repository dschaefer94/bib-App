// Florian
document.addEventListener('DOMContentLoaded', function () {
    const profileSection = document.getElementById('profile-section');
    const profileForm = document.getElementById('profileForm');
    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    
    // Sicherheitsprüfung: Überprüfe, ob alle notwendigen Elemente vorhanden sind
    if (!profileForm || !errorMessage || !successMessage || !profileSection) {
        console.error('Erforderliche HTML-Elemente nicht gefunden. Stelle sicher, dass profil.html verwendet wird.');
        return;
    }
    
    // Dynamische API-Base-URL
    const apiBase = 'restapi.php';

    // Function to hide messages
    function hideMessages() {
        errorMessage.style.display = 'none';
        successMessage.style.display = 'none';
    }

    // Function to show a message
    function showMessage(element, message) {
        element.textContent = message;
        element.style.display = 'block';
    }

    // Lade nur die Klassen-Liste von der API
    function loadKlassen() {
        const klassenUrl = apiBase + '/class';
        console.log('Loading classes from:', klassenUrl);
        
        fetch(klassenUrl, {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Classes data received:', data);
            
            if (Array.isArray(data)) {
                const klassenSelect = document.getElementById('klassen_id');
                klassenSelect.innerHTML = '<option value="">-- Keine Klasse --</option>';
                
                // Hole die aktuelle Klasse des Benutzers aus sessionStorage
                const storedUser = sessionStorage.getItem('user');
                let currentClassname = null;
                if (storedUser) {
                    try {
                        const user = JSON.parse(storedUser);
                        currentClassname = user.klassenname;
                        console.log('Current user class:', currentClassname);
                    } catch (e) {
                        console.error('Error parsing user data:', e);
                    }
                }
                
                // Erstelle alle Optionen
                data.forEach(klasse => {
                    const option = document.createElement('option');
                    option.value = klasse.klassen_id;
                    option.textContent = klasse.klassenname;
                    
                    // Markiere die aktuelle Klasse des Benutzers
                    if (currentClassname && currentClassname === klasse.klassenname) {
                        option.selected = true;
                        console.log('Selected class:', klasse.klassenname);
                    }
                    
                    klassenSelect.appendChild(option);
                });
                
                console.log('Classes loaded successfully');
            } else {
                console.error('Classes data is not an array:', data);
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
            showMessage(errorMessage, 'Fehler beim Laden der Klassen.');
        });
    }

    // Function to populate the profile form with data
    function populateProfileForm(userData) {
        console.log('Populating form with user data:', userData);
        
        document.getElementById('profile_benutzer_id').value = userData.id || '';
        document.getElementById('name').value = userData.nachname || '';
        document.getElementById('vorname').value = userData.vorname || '';
        document.getElementById('email').value = userData.email || '';
        document.getElementById('passwort').value = '';
        document.getElementById('passwort_confirm').value = '';
    }

    // Handle the profile update form submission
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        hideMessages();

        const passwort = document.getElementById('passwort').value;
        const passwortConfirm = document.getElementById('passwort_confirm').value;

        if (passwort !== passwortConfirm) {
            showMessage(errorMessage, 'Die Passwörter stimmen nicht überein.');
            return;
        }

        const formData = new FormData(profileForm);
        const data = Object.fromEntries(formData.entries());
        
        const updateUrl = apiBase + '/personalData/updateProfile';
        fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(successMessage, data.message);
                if (data.userData) {
                    populateProfileForm(data.userData);
                    loadKlassen();
                }
            } else {
                showMessage(errorMessage, data.error || 'Fehler beim Speichern.');
            }
        })
        .catch(error => {
            console.error('Error saving user data:', error);
            showMessage(errorMessage, 'Ein Fehler ist beim Speichern der Daten aufgetreten.');
        });
    });

    // Lade Benutzerdaten aus sessionStorage (wurden beim Login gespeichert)
    const storedUser = sessionStorage.getItem('user');
    if (storedUser) {
        try {
            const user = JSON.parse(storedUser);
            console.log('User data from sessionStorage:', user);
            populateProfileForm(user);
            profileSection.style.display = 'block';
            loadKlassen();
        } catch (e) {
            console.error('Error parsing user data from sessionStorage:', e);
            showMessage(errorMessage, 'Fehler beim Laden der Benutzerdaten');
            profileSection.style.display = 'none';
        }
    } else {
        showMessage(errorMessage, 'Keine Benutzerdaten gefunden. Bitte melde dich an.');
        profileSection.style.display = 'none';
    }
});

