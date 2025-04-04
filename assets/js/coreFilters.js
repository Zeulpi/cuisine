(async () => {
   let allFilters = [];
   let allActions = [];
    
    const roleModule = await import(roleFilterPath);
    const roleFilter = roleModule.default;
    allFilters.push(roleFilter);
    const roleActionsModule = await import(roleActionsPath);
    const roleActions = roleActionsModule.default;
    allActions.push(roleActions);

    console.log('script Core chargé');
    allFilters.forEach(filter => {
        console.log("Filtre "+filter.filterName+" chargé");
    });
    let filtersApplied = {};  // Les filtres déjà appliqués (serviront dans la requête finale)
    let filtersToApply = {};  // Les filtres sélectionnés mais non encore appliqués


    // Mettre à jour les boutons et les événements dès le premier chargement de la page
    const initialPage = new URL(window.location.href).searchParams.get('page') || 1;
    function initPage( page = 1){
        updatePaginationButtons(page);  // Mettre à jour les boutons de pagination au premier chargement
        addPaginationEventListeners();  // Ajouter les événements de pagination au premier chargement
        addApplyEventListener();
        allFilters.forEach(filter => {
            filter.filterListen(filtersToApply, filtersApplied);
        });
        allActions.forEach(actions =>{
            actions.actionsListen(fetchWithFilters, initPage);
        });
    }
    
    // Fonction générique pour ajouter ou modifier un filtre
    function updateFilter(key, value) {
        if (value && value.length > 0) {
            filtersToApply[key] = value;  // Ajouter ou mettre à jour un filtre
        } else {
            delete filtersToApply[key];  // Si la valeur est vide, supprimer le filtre
        }
    }

    function buildUrl(page = 1) {
        const url = new URL(window.location.href);  // Utiliser l'URL actuelle

        // Ajouter les filtres appliqués dans l'URL
        for (const [key, value] of Object.entries(filtersApplied)) {
            if (value && value.length > 0) {
                url.searchParams.set(key, value);  // Ajouter ou mettre à jour les filtres
            }
        }

        // Assurer que la page est définie dans l'URL
        url.searchParams.set('page', page);  // Ajouter ou mettre à jour le paramètre 'page'

        // console.log('built url : ', url.toString());  // Pour vérifier l'URL construite
        return url.toString();  // Retourner l'URL finale
    }

    // Fonction qui envoie la requête avec les filtres à chaque fois qu'une action se produit (pagination, changement de filtre)
    function fetchWithFilters(page) {
        const url = buildUrl(page);  // Construire l'URL avec la page et les filtres appliqués
        // console.log('url a fetch :', url);
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.text())
        .then(html => {
            // Remplacer le contenu du tableau avec les nouvelles données
            document.querySelector('#user-table-pagination-container').innerHTML = html;

            // Mettre à jour les boutons de pagination avec la page actuelle
            initPage(page);
        })
        .catch(error => console.log('Erreur AJAX:', error));
    }


    // Fonction pour ajouter les événements de clic sur les boutons de pagination
    function addPaginationEventListeners() {
        const paginationButtons = document.querySelectorAll('.pagination button');

        paginationButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                // Si le bouton est désactivé, on ne fait rien
                if (this.disabled) return;

                const page = this.getAttribute('data-page');  // Récupérer la page du bouton cliqué

                // Effectuer la requête AJAX avec la page récupérée
                fetchWithFilters(page);  // Passe le numéro de la page à fetchWithFilters
            });
        });
    }

    // Fonction pour mettre à jour les boutons de pagination
    function updatePaginationButtons(currentPage) {
        const paginationButtons = document.querySelectorAll('.pagination button');

        // Réinitialiser tous les boutons avant de définir la page active
        paginationButtons.forEach(button => {
            button.disabled = false;  // Réactiver tous les boutons
            button.classList.remove('active');  // Retirer la classe active de tous les boutons
        });

        // Appliquer la classe active et désactiver le bouton de la page courante
        const activeButton = document.querySelector(`.pagination button[data-page="${currentPage}"]`);
        if (activeButton) {
            activeButton.classList.add('active');  // Marquer la page courante comme active
            activeButton.disabled = true;  // Désactiver le bouton de la page courante
        }
    }

    function addApplyEventListener() {
        document.getElementById('apply-filters').addEventListener('click', function () {
            // Transférer les filtres sélectionnés dans filtersToApply vers filtersApplied
            filtersApplied = { ...filtersApplied, ...filtersToApply };
            
            // Forcer la page à 1 lors de l'application des filtres (réinitialiser la pagination)
            fetchWithFilters();  // Passer l'URL mise à jour à fetchWithFilters
        });
    }

    initPage();
})();