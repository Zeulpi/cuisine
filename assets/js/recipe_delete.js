document.addEventListener('turbo:load', function () {
    console.log("Script de suppression chargé !");
});

document.addEventListener('click', function (event) {
    if (event.target.classList.contains('delete-button')) {
        event.preventDefault();

        const recipeName = event.target.dataset.recipeName;
        const deleteUrl = event.target.href;

        if (confirm(`Voulez-vous vraiment supprimer la recette "${recipeName}" ?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = deleteUrl;

            // CSRF Token (si nécessaire)
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
});
