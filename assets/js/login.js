document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById("loginModal");
    const closeButton = document.querySelector(".close");
    const restrictedLinks = document.querySelectorAll('.restricted');
    let redirectTo = '';
    
    // Lorsqu'on clique sur un lien protégé (par exemple, /admin)
    restrictedLinks.forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            redirectTo = event.target.href;

            // Récupérer le formulaire et ajouter l'URL de redirection à formData
            const formData = new FormData(document.getElementById('login-form'));
            formData.append('redirect_to', redirectTo);  // Ajoute l'URL du lien cliqué

            console.log('Redirection vers', formData.get('redirect_to'));

            fetch('/is_authenticated')  // Vérifier si l'utilisateur est connecté
                .then(response => {
                    if (response.status === 200) {
                        window.location.href = redirectTo;
                    } else {
                        // Si non authentifié, afficher la modale de login
                        modal.style.display = "block";
                    }
                })
                .catch(error => console.log('Erreur de vérification de connexion', error));
        });
    });
    

    // Fermer la modale lorsqu'on clique sur "x"
    closeButton.addEventListener('click', function() {
        modal.style.display = "none";
    });

    // Fermer la modale si on clique en dehors de la fenêtre modale
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });

    document.getElementById("login-form").addEventListener('submit', function(event) {
        event.preventDefault();  // Empêche la soumission classique du formulaire
    
        const formData = new FormData(event.target); // Récupère les données du formulaire
        
        // Soumettre les données du formulaire via AJAX (utilise l'URL de login)
        fetch(event.target.action, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                // Si l'authentification réussit, fermer la modale et rediriger l'utilisateur
                document.getElementById("loginModal").style.display = "none";  // Fermer la modale
                const redirectUrl = redirectTo || window.location.href;  // Récupère l'URL à rediriger
                console.log('Redirection vers', redirectTo || '/admin');
                window.location.href = redirectUrl;  // Rediriger l'utilisateur
            } else {
                // Si l'authentification échoue, afficher un message d'erreur
                const errorMessage = 'Nom d\'utilisateur ou mot de passe incorrect.';
                document.getElementById('loginModal').querySelector('.error-message').textContent = errorMessage;  // Ajouter un élément pour afficher l'erreur dans la modale
            }
        })
        .catch(error => {
            console.log('Erreur de soumission du formulaire', error);
        });
    });
});