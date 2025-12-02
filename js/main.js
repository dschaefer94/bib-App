
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('daten-tabelle');
    const video = document.getElementById('success-video');

    if (table && video) {
        video.style.display = 'block'; // Video sichtbar machen
        video.play(); // Automatisch abspielen
    }
});
