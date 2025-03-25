document.addEventListener('turbo:load', function () {
    console.log("Script de suppression chargé !");
});

// Fonction que chaque bouton va appeler au clic
function handleDeleteClick(event) {
    event.preventDefault();

    console.log("Suppression demandée"); // ← pour debug

    const recipeName = event.target.dataset.recipeName;
    const deleteUrl = event.target.href;

    if (confirm(`Voulez-vous vraiment supprimer la recette "${recipeName}" ?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = deleteUrl;

        const csrfToken = event.target.dataset.csrfToken;
        if (csrfToken) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);
        }

        document.body.appendChild(form);
        form.submit();
    }
}

// Fonction globale qu'on pourra appeler depuis l’extérieur
window.setupDeleteButtons = function () {
    const deleteButtons = document.querySelectorAll('.delete-button');

    deleteButtons.forEach(button => {
        // Important : éviter d'attacher plusieurs fois le listener
        button.removeEventListener('click', handleDeleteClick); // safe même s’il n’est pas encore attaché
        button.addEventListener('click', handleDeleteClick);
    });
};

// Exécution initiale
window.setupDeleteButtons();
