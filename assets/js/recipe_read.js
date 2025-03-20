function getRecipeData() {
    const dataElement = document.getElementById('recipe-data');
    if (dataElement) {
        return JSON.parse(dataElement.textContent);
    }
    return null;
}

document.addEventListener('DOMContentLoaded', function () {
    const recipeData = getRecipeData();
    if (recipeData) {
        // console.log(recipeData);
        const recipesList = recipeData.recipesList;
        const itemsPerPage = recipeData.itemsPerPage;
        const csrfToken = recipeData.csrfToken;
        const updateUrlTemplate = recipeData.urls.update;
        const deleteUrlTemplate = recipeData.urls.delete;
        const recipeContainer = document.querySelector('.recipe-container');
        const tagButtons = document.querySelectorAll(".tag-button");
        let filteredRecipes = [...recipesList]; // Initialement, toutes les recettes sont visibles
        let currentPage = 1; // Page actuelle
        const recipeSearch = document.getElementById('recipe-search');
        const pagesBtnList = document.getElementsByClassName('page-btn');
        console.log("recettes chargées");
        let selectedTags = []; // Stocke les tags sélectionnés pour la recherche
        let searchValue = ""; // Stocke le texte de recherche

        function createRecipeElement(recipe) {
            // Générer les URLs dynamiques en remplaçant '__ID__' par l'ID réel
            const updateUrl = updateUrlTemplate.replace('__ID__', recipe.id);
            const deleteUrl = deleteUrlTemplate.replace('__ID__', recipe.id);
        
            return `
                <div class="recipe-frame">
                    <div class="recipe-name">${recipe.name}</div>
                    <div class="recipe-image">
                        ${recipe.img ? `<img src="/images/recipes/${recipe.img}" alt="Image">` : `<img src="" alt="Image">`}
                    </div>
                    <div class="recipe-btns">
                        <a href="${updateUrl}" class="btn btn-success" data-turbo="false">Edit</a>
                        <a href="${deleteUrl}" class="btn btn-danger delete-button" data-recipe-name="${recipe.name}" data-csrf-token="${csrfToken}">
                            X
                        </a>
                    </div>
                </div>
            `;
        }
        // Fonction pour afficher une page de recettes
        function displayRecipes(page, recipeSubset) {
            recipeContainer.innerHTML = ''; // Vider le conteneur

            const startIndex = (page - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const recipesToShow = recipeSubset.slice(startIndex, endIndex);

            recipesToShow.forEach(recipe => {
                recipeContainer.innerHTML += createRecipeElement(recipe);
            });
            
            // Mise à jour de l'information de pagination
            const totalPages = Math.ceil(recipeSubset.length / itemsPerPage);
            // currentPageInfo.textContent = `Page ${page} / ${totalPages}`;
            currentPage = page;

            // Gestion des boutons de navigation
            updatePaginationButtons(totalPages);
        }
        // Fonction pour mettre à jour les boutons "Précédent" et "Suivant" dans le popup ingrédients
        function updatePaginationButtons(totalPages) {
            const prevButton = document.getElementById('page-prev');
            const firstButton = document.getElementById('page-first');
            const nextButton = document.getElementById('page-next');
            const lastButton = document.getElementById('page-last');
            const pagesCount = document.getElementById('pages-count');
            const fragment = document.createDocumentFragment();
            let paginationButtons = [];
            const visiblePages = 2; // Nombre de pages visibles avant et après la page active

            prevButton.disabled = currentPage <= 1;
            firstButton.disabled = currentPage <= 1;
            nextButton.disabled = currentPage >= totalPages;
            lastButton.disabled = currentPage >= totalPages;


            // reset des boutons
            pagesCount.innerHTML ='';
            
            // console.log(currentPage);

            function createPageButton(page) {
                const button = document.createElement('button');
                button.classList.add('page-btn');
                button.textContent = page;
                if (page === currentPage) {
                    button.disabled = true;
                    button.classList.add('active');
                }
                button.addEventListener('click', () => displayRecipes(page, filteredRecipes));
                fragment.appendChild(button);
            }
            createPageButton(1);
            if (currentPage > visiblePages + 2) {
                fragment.appendChild(document.createTextNode(' ... '));
            }

            // Affichage des pages autour de la page actuelle
            for (let i = Math.max(2, currentPage - visiblePages); i <= Math.min(totalPages - 1, currentPage + visiblePages); i++) {
                createPageButton(i);
            }
            if (currentPage < totalPages - visiblePages - 1) {
                fragment.appendChild(document.createTextNode(' ... '));
            }

            // Toujours afficher la dernière page si elle n'a pas déjà été ajoutée
            if (totalPages > 1) {
                createPageButton(totalPages);
            }

            pagesCount.appendChild(fragment);

            prevButton.onclick = () => {
                if (currentPage > 1) {
                    displayRecipes(currentPage - 1, filteredRecipes);
                }
            };
            firstButton.onclick = () => {
                displayRecipes(1, filteredRecipes);
            };

            nextButton.onclick = () => {
                if (currentPage < totalPages) {
                    displayRecipes(currentPage + 1, filteredRecipes);
                }
            };
            lastButton.onclick = () => {
                displayRecipes((Math.ceil(filteredRecipes.length / itemsPerPage)), filteredRecipes);
            };
        }

        // Afficher la première page au chargement
        updateFilteredRecipes();

        // Fonction pour gérer la couleur du texte des tags
        function getTextColor(hexColor) {
            if (!hexColor) return "#000"; // Défaut : texte noir
            const rgb = parseInt(hexColor.substring(1), 16); // Convertir en nombre
            const r = (rgb >> 16) & 0xff; // Rouge
            const g = (rgb >> 8) & 0xff; // Vert
            const b = (rgb >> 0) & 0xff; // Bleu
        
            // Calcul de la luminosité (YIQ)
            const yiq = (r * 299 + g * 587 + b * 114) / 1000;
            return yiq >= 128 ? "#000" : "#fff"; // Noir si fond clair, blanc si fond foncé
        }

        //Fonction pour gérer les tags
        function toggleTag(button) {
            const tagId = button.getAttribute("data-id");
            const tagColor = button.getAttribute("data-color");
            
            if (button.classList.contains("selected")) {
                button.classList.remove("selected");
                button.style.backgroundColor = ""; // Réinitialise la couleur
                button.style.color = ""; // Remet la couleur du texte par défaut
            } else {
                button.classList.add("selected");
                button.style.backgroundColor = tagColor; // Applique la couleur
                button.style.color = getTextColor(tagColor); // Applique la couleur du texte
            }
        }

        // Fonction qui met à jour la liste en fonction du texte et des tags
        function updateFilteredRecipes() {
            filteredRecipes = recipesList;

            // Filtrer par nom si un texte est entré
            if (searchValue.trim() !== "") {
                filteredRecipes = filteredRecipes.filter(recipe => 
                    recipe.name.toLowerCase().includes(searchValue)
                );
            }

            // Filtrer par tags sélectionnés
            if (selectedTags.length > 0) {
                filteredRecipes = filteredRecipes.filter(recipe =>
                    selectedTags.every(tag => recipe.tags.includes(tag))
                );
            }

            // Afficher les recettes filtrées
            displayRecipes(currentPage, filteredRecipes);
        }

        // Modifier la liste des recettes par un champ de recherche
        recipeSearch.addEventListener('input', (e) => {
            searchValue = e.target.value.toLowerCase(); // Stocke la valeur du champ texte
            updateFilteredRecipes();
        });

        // Modifier la liste des recettes par sélection de tags
        tagButtons.forEach(button => {
            button.addEventListener("click", function() {
                toggleTag(this);
                
                const tagId = parseInt(this.getAttribute("data-id"));
                
                if (this.classList.contains("selected")) {
                    // Ajouter le tag sélectionné
                    if (!selectedTags.includes(tagId)) {
                        selectedTags.push(tagId);
                    }
                } else {
                    // Retirer le tag si on le désélectionne
                    selectedTags = selectedTags.filter(id => id !== tagId);
                }

                updateFilteredRecipes();
            });
        });
    }
});