// Florian 

document.addEventListener('DOMContentLoaded', function () {
    const searchForm = document.getElementById('searchForm');
    const profileSection = document.getElementById('profile-section');
    const profileForm = document.getElementById('profileForm');
    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');

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

    // Handle the search form submission
    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        hideMessages();
        profileSection.style.display = 'none';

        const benutzerId = document.getElementById('benutzer_id').value;
        if (!benutzerId) {
            showMessage(errorMessage, 'Bitte geben Sie eine Benutzer-ID ein.');
            return;
        }

        // Fetch user data
        fetch(`profil.php?benutzer_id=${benutzerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.userData) {
                    populateProfileForm(data.userData, data.klassen);
                    profileSection.style.display = 'block';
                } else {
                    showMessage(errorMessage, data.error || 'Benutzer nicht gefunden.');
                }
            })
            .catch(error => {
                console.error('Error fetching user data:', error);
                showMessage(errorMessage, 'Ein Fehler ist beim Laden der Daten aufgetreten.');
            });
    });

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
        
        fetch('profil.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(successMessage, data.message);
                // Repopulate form with potentially updated data
                if (data.userData) {
                    populateProfileForm(data.userData, data.klassen);
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

    // Function to populate the profile form with data
    function populateProfileForm(userData, klassen) {
        document.getElementById('profile_benutzer_id').value = userData.benutzer_id || '';
        document.getElementById('name').value = userData.name || '';
        document.getElementById('vorname').value = userData.vorname || '';
        document.getElementById('email').value = userData.email || '';
        document.getElementById('passwort').value = ''; // Clear password field
        document.getElementById('passwort_confirm').value = ''; // Clear password confirmation field

        const klassenSelect = document.getElementById('klassen_id');
        klassenSelect.innerHTML = '<option value="">-- Keine Klasse --</option>'; // Clear existing options

        if (klassen && klassen.length > 0) {
            klassen.forEach(klasse => {
                const option = document.createElement('option');
                option.value = klasse.klassen_id;
                option.textContent = klasse.klassenname;
                if (userData.klassen_id && userData.klassen_id == klasse.klassen_id) {
                    option.selected = true;
                }
                klassenSelect.appendChild(option);
            });
        }
    }
    
    // Check for benutzer_id in URL on page load
    const urlParams = new URLSearchParams(window.location.search);
    const benutzerIdFromUrl = urlParams.get('benutzer_id');
    if (benutzerIdFromUrl) {
        document.getElementById('benutzer_id').value = benutzerIdFromUrl;
        searchForm.dispatchEvent(new Event('submit'));
    }
});
