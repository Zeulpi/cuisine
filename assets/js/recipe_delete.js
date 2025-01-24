document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('.delete-button');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();

            const recipeName = this.dataset.recipeName;
            const deleteUrl = this.href;

            if (confirm(`Voulez-vous vraiment supprimer la recette "${recipeName}" ?`)) {
                // Si l'utilisateur confirme, soumettre une requête POST pour supprimer la recette
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = deleteUrl;

                // CSRF Token (si nécessaire)
                const csrfToken = this.dataset.csrfToken;
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
        });
    });
});