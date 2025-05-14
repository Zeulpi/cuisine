function initCreateForm() {
    function getRecipeData() {
        const dataElement = document.getElementById('recipe-data');
        if (dataElement) {
            return JSON.parse(dataElement.textContent);
        }
        return null;
    }

    // document.addEventListener('turbo:load', () => {

        const recipeData = getRecipeData();
        // if (recipeData) {
            // console.log("Données recette existante :", JSON.stringify(recipeData.existingRecipe, null, 2));

            
            // Utiliser les données récupérées
            const ingredients = recipeData.ingredients;
            console.log(ingredients);
            const ingredientsPerPage = recipeData.ingredientsPerPage;
            const operations = recipeData.operations;
            const imagePath = recipeData.imagePath;

            const recipeImg = recipeData.existingRecipe?.recipeImg || '';
        

            const addIngredientButton = document.getElementById('add-ingredient-button');
            const ingredientPopup = document.getElementById('ingredient-popup');
            const closePopupButton = document.getElementById('close-popup');
            const ingredientsContainer = document.getElementById('ingredients-container');
            const stepsContainer = document.getElementById('steps');
            const addStepButton = document.getElementById('add-step-button');
            const addOperationButton = document.getElementById('add-operation-button');
            const operationPopup = document.getElementById('operation-popup');
            const confirmationPopup = document.getElementById('confirmation-popup');
            const closeOperationPopupButton = document.getElementById('close-operation-popup');
            const saveOperationButton = document.getElementById('save-operation-button');
            const operationsContainer = document.getElementById('operations-container');
            const selectedOperationsField = document.getElementById('selected-operations');
            const ingredientsList = document.getElementById('ingredients-list');
            const prevPageButton = document.getElementById('prev-page');
            const nextPageButton = document.getElementById('next-page');
            const currentPageSpan = document.getElementById('current-page');
            const ingredientSearch = document.getElementById('ingredient-search');
            const currentPageInfo = document.getElementById('current-page-info');
            const tagButtons = document.querySelectorAll(".tag-button");
            const hiddenTagField = document.querySelector(".hidden");
            const recipePortionsInput = document.getElementById('recipe_recipePortions');
            const recipeNameInput = document.getElementById('recipe_recipeName');
            let activeStepItem = null;
            let tempID = -1;
            let selectedTags = [];
            const imageInput = document.getElementById("recipe_image");
            const previewImage = document.getElementById("image-preview-img");
            const removeImageButton = document.getElementById("remove-image-button");
            const removeImageField = document.createElement("input");

            removeImageField.type = "hidden";
            removeImageField.name = "remove_image";
            removeImageField.id = "remove_image";
            removeImageField.value = "0"; // Par défaut, ne pas supprimer l'image
            document.querySelector(".image-input").appendChild(removeImageField);

            const itemsPerPage = ingredientsPerPage; // Nombre d'ingrédients par page
            let currentPage = 1; // Page actuelle
            const totalPages = Math.ceil(ingredients.length / itemsPerPage); // Nombre total de pages
            let filteredIngredients = [...ingredients]; // Initialement, tous les ingrédients sont visibles

            // -------------------------- //
            // Gestion de l'image recette //
            // -------------------------- //

            // Fonction pour cacher/afficher le bouton de suppression d'image
            function toggleRemoveImageButton() {
                const imageSrc = previewImage.getAttribute("src");
                
                if (!imageSrc || imageSrc.trim() === "" || imageSrc === "about:blank") {
                    removeImageButton.style.display = "none"; // Cacher le bouton
                    previewImage.style.display = "none"; // Cacher l'aperçu
                } else {
                    removeImageButton.style.display = "flex"; // Afficher le bouton
                    previewImage.style.display = "block"; // Afficher l'aperçu
                }
            }

            // Cas 1 : Vérifier au chargement si une image existe
            toggleRemoveImageButton();

            // Cas 2 : L'utilisateur sélectionne une nouvelle image
            imageInput.addEventListener("change", function (event) {
                recipeImg ? document.getElementById("remove_image").value = "1" : null;
                const file = event.target.files[0];
                if (file && !file.type.startsWith("image/")) {
                    alert("Seules les images sont autorisées !");
                    event.target.value = ""; // Réinitialiser le champ
                }
                else if (file && file.type.startsWith("image/")) {
                    const fileReader = new FileReader();
                    fileReader.onload = function (e) {
                        previewImage.src = e.target.result; // Mettre à jour l'aperçu
                        toggleRemoveImageButton(); // Afficher le bouton si une image est chargée
                    };
                    fileReader.readAsDataURL(this.files[0]);
                }
            });

            // Cas 3 : L'utilisateur clique sur le bouton "X" pour supprimer l'image
            removeImageButton.addEventListener("click", function () {
                previewImage.src = ""; // Effacer l’image
                imageInput.value = ""; // Réinitialiser le champ input file
                document.getElementById("remove_image").value = "1"; // Mettre à jour le champ caché
                toggleRemoveImageButton(); // Cacher le bouton après suppression
            });
            
            // Récupérer les ingrédients déjà ajoutés à la recette
            const selectedIngredientsField = document.getElementById('recipe_selectedIngredients');
            let selectedIngredients = JSON.parse(selectedIngredientsField.value || '[]');

            // Récupérer les ingrédients intermédiaires
            const resultIngredientsField = document.getElementById('result-ingredients');
            let resultIngredients = JSON.parse(resultIngredientsField.value || '[]');

            let stepIndex = stepsContainer.querySelectorAll('.step-item').length;
            
            document.getElementById('recipe_recipeSteps').innerHTML = ``;
            
            // Fonction pour ajouter un ingrédient au DOM (et au champ caché) lors de l'edition d'une recette
            function addIngredientToDOM(ingredientId, quantity, unit) {
                let ingredientDiv = document.createElement('div');
                ingredientDiv.classList.add('ingredient-item');
                ingredientDiv.setAttribute('data-id', ingredientId);

                const ingr = ingredients.find(item => item.id === ingredientId);
                const ingredientImage = ingr.ingredientImg;
                const ingredientName = ingr.ingredientName;
                let ingredientUnit = ingr.ingredientUnit;
                // Si c’est un objet indexé, on le transforme en tableau de ses valeurs
                if (!Array.isArray(ingredientUnit)) {
                    ingredientUnit = Object.values(ingredientUnit);
                }

                let qtyChoice = "";
                (ingredientUnit && (ingredientUnit.includes("") || ingredientUnit.includes("")) && (!unit || unit=="" || unit== " ")) ? (qtyChoice += `<option value='' selected></option>`) : (qtyChoice += `<option value=''></option>`);
                ingredientUnit.forEach(element => {
                    if (element != "" && element != " "){
                        (element == unit) ?
                        qtyChoice += `<option value='${element}' selected>${element}</option>`
                        : qtyChoice += `<option value='${element}'>${element}</option>`
                    }
                });

                ingredientDiv.innerHTML = `
                    <div class="ingredient-img">
                        <img src="/images/ingredients/${ingredientImage}" alt="${ingredientName}" style="width: 50px;">
                    </div>
                    <span class="ingredient-name">${ingredientName}</span>
                    <input type="number" class="ingredient-quantity" placeholder="Quantity" min="0" step="0.1" value="${quantity}">
                    <div class="qty-unit">
                        <select class="ingredient-unit">
                            ${qtyChoice}
                        </select>
                    </div>
                    <div class="ingredient-btn">
                        <button type="button" class="remove-ingredient btn btn-danger">X</button>
                    </div>
                `;
            
                ingredientsContainer.appendChild(ingredientDiv);  
            }


            // Fonction pour mettre a jour le compte des etapes
            function updateStepIndicators() {
                const stepItems = document.querySelectorAll('.step-item'); // Sélectionner toutes les étapes
                stepItems.forEach((step, index) => {
                    let indicator = step.querySelector('.step-indicator');
                    indicator.textContent = `Étape ${index + 1}/${stepItems.length}`;
                });
            }
            // Mettre à jour les options dans la liste des ingrédients du popup opérations
            function updateIngredientOptions(popup) {
                const ingredientSelect = popup.querySelector('.ingredient-select');
                const stepItems = stepsContainer.querySelectorAll('.step-item');
                
                ingredientSelect.innerHTML = '<option value="">Choisissez un ingrédient</option>'; // Réinitialiser

                // Récupérer l'index de l'étape actuelle
                const currentStepIndex = parseInt(activeStepItem.querySelector('[id^="recipe_recipeSteps_"]').id.split('_').pop());

                // Récupérer les ingrédients sélectionnés avec leurs quantités
                const selectedIngredientsField = document.getElementById('recipe_selectedIngredients');
                const selectedIngredients = JSON.parse(selectedIngredientsField?.value || '[]');

                //Recupérer les ingrédients intermédiaires
                const resultIngredientsField = document.getElementById('result-ingredients');
                const resultIngredients = JSON.parse(resultIngredientsField?.value || '[]');

                //Ajout des ingrédients de la recette a la selection
                selectedIngredients.forEach(selectedIngredient => {
                    const ingredient = ingredients.find(ing => ing.id === parseInt(selectedIngredient.ingredientId));

                    if (ingredient) {
                        // Ajouter l'ingrédient au menu déroulant
                        const option = document.createElement('option');
                        option.value = ingredient.id;
                        option.textContent = ingredient.ingredientName;
                        option.classList.add('ingredient-option');
                        ingredientSelect.appendChild(option);
                    }
                });

                // Récupérer les résultats d'opération AVANT ou À l'étape actuelle
                let validResultIds = new Set();
                stepItems.forEach(step => {
                    const stepIndex = parseInt(step.querySelector('[id^="recipe_recipeSteps_"]').id.split('_').pop());
                    
                    if (stepIndex <= currentStepIndex) {
                        const selectedOperationsField = step.querySelector('.selected-operations');
                        const selectedOperations = JSON.parse(selectedOperationsField?.value || '[]');

                        selectedOperations.forEach(operation => {
                            if (operation.operationResult !== null) {
                                validResultIds.add(operation.operationResult);
                            }
                        });
                    }
                });

                // Vérifier si nous sommes en mode édition
                const editingOperationId = popup.getAttribute('data-editing-operation-id');
                let currentResultId = null;
                
                if (editingOperationId) {
                    const operationItem = document.getElementById(editingOperationId);
                    const resultDescriptionElement = operationItem.querySelector('span[id^="result_"]');
                    if (resultDescriptionElement) {
                        currentResultId = parseInt(resultDescriptionElement.id.split('_')[1]);
                    }
                }

                // Ajout des ingrédients intermédiaires valides, excluant le résultat actuel de l'opération modifiée
                resultIngredients.forEach(resultIngredient => {
                    // Ajouter l'ingrédient si il est valide et ce n'est pas le résultat de l'opération modifiée
                    if (validResultIds.has(resultIngredient.resultId) && resultIngredient.resultId !== currentResultId) {
                        const option = document.createElement('option');
                        option.value = resultIngredient.resultId;
                        option.textContent = resultIngredient.resultName;
                        option.classList.add('result-ingredient-option');
                        ingredientSelect.appendChild(option);
                    }
                });
            }

            //Fonction pour mettre a jour les operations dépendantes apres edition d'une opération
            function updateOperationResults(newResultId, newResultName) {
                const stepItems = stepsContainer.querySelectorAll('.step-item');
                stepItems.forEach(step => {
                    const operationItems = step.querySelectorAll('.step-frame .operation-item');

                    operationItems.forEach(operationItem => {
                        // Récupérer les éléments de l'opération et de l'ingrédient
                        const operationElement = operationItem.querySelector('.operation');
                        const ingredientElement = operationItem.querySelector('.ingredient');

                        // Vérifier si l'ingrédient ou l'opération est lié au résultat que nous avons mis à jour
                        if (ingredientElement && parseInt(ingredientElement.id.split('_')[2]) === newResultId) {
                            // Si l'ingrédient utilise le résultat mis à jour, on change le texte de l'ingrédient
                            ingredientElement.textContent = newResultName;
                        }
                    });
                });
            }

            // Afficher le popup de confirmation
            function showConfirmationPopup(message, callback) {
                const messageElement = document.getElementById('confirmation-message');
                const confirmButton = document.getElementById('confirm-delete');
                const cancelButton = document.getElementById('cancel-delete');

                messageElement.textContent = message; // Insérer le message personnalisé
                confirmationPopup.style.display = 'block'; // Afficher le popup

                // Lorsque l'utilisateur clique sur "Confirmer"
                confirmButton.onclick = function() {
                    callback(true);  // Exécuter l'action (par exemple, supprimer)
                    confirmationPopup.style.display = 'none';  // Fermer le popup
                };

                // Lorsque l'utilisateur clique sur "Annuler"
                cancelButton.onclick = function() {
                    callback(false); // Ne rien faire
                    confirmationPopup.style.display = 'none';  // Fermer le popup
                };
            }

            // Fonction pour enlever les caracteres indesirables d'une chaine
            function sanitizeInput(input) {
                // Créer un élément temporaire pour traiter le HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = input;

                // Supprimer les balises indésirables
                const allowedTags = ['br', 'b', 'i', 'u', 'div', 'span', 'p']; // Ajouter d'autres balises autorisées si nécessaire
                tempDiv.querySelectorAll('*').forEach(node => {
                    if (!allowedTags.includes(node.tagName.toLowerCase())) {
                        node.replaceWith(document.createTextNode(node.textContent));
                    }
                });

                return tempDiv.innerHTML;
            }

            // Fonction pour synchroniser le contenu de "step-description" avec le champ caché
            function syncStepDescription(stepItem, description) {
                const hiddenField = stepItem.querySelector('input[name*="[stepText]"]');
                const sanitizedValue = sanitizeInput(description);
                if (hiddenField) {
                    hiddenField.value += sanitizedValue; // Sauvegarder uniquement la description
                }
            }

            // Fonction pour ajouter les ingrédients intermédiaires au champ caché
            function addResultIngredient(resultDescription) {
                const resultIngredientsField = document.getElementById('result-ingredients');
                const resultIngredients = JSON.parse(resultIngredientsField.value || '[]');
                // 🔍 Trouver le plus petit `resultId` dans `resultIngredients`
                let minResultId = 0; // Valeur de départ si aucun ingrédient intermédiaire n'existe encore

                resultIngredients.forEach(ingredient => {
                    if (ingredient.resultId < minResultId) {
                        minResultId = ingredient.resultId; // Trouver le plus petit `resultId`
                    }
                });
                const newResultId = minResultId - 1;

                // Ajouter result au tableau des ingrédients intermédiaires
                resultIngredients.push({ resultId: newResultId, resultName: resultDescription }); // Ajout de l'Id négatif
                // RecipeCreate.tempID--; // Décrémentation pour les prochains ID négatifs

                // Mettre à jour le champ caché avec la liste des ingrédients intermédiaires
                resultIngredientsField.value = JSON.stringify(resultIngredients);

                return newResultId;
            }

            // Fonction pour masquer le champ 'stepSimult' pour l'étape 1
            function hideStepSimultForFirstStep() {
                const stepItems = stepsContainer.querySelectorAll('.step-item');
                stepItems.forEach((stepItem, index) => {
                    const stepSimult = stepItem.querySelector('input[name*="[stepSimult]"]');
                    if (index === 0 && stepSimult) {
                        stepSimult.closest('div').style.display = 'none'; // Masque le champ pour l'étape 1
                    } else if (stepSimult) {
                        stepSimult.closest('div').style.display = ''; // Affiche le champ pour les autres étapes
                    }
                });
            }
            
            // Fonction pour supprimer une opération
            function removeOperation(operationItem) {
                if (!operationItem) {
                    console.error("Erreur : Aucune opération fournie.");
                    return;
                }
                const stepItem = operationItem.closest('.step-item'); // Récupérer l'étape concernée
                const operationsContainer = stepItem.querySelector('.added-operations'); // Conteneur des opérations
                const operationDiv = operationItem.querySelector('.operation');
                const selectedOperationsField = operationsContainer.querySelector('.selected-operations'); // Champ caché

                if (!stepItem || !operationsContainer || !selectedOperationsField) {
                    console.error("Erreur : Élément manquant.");
                    return;
                }

                // Extraire operationId et ingredientId
                const operationId = parseInt(operationDiv.id.split('_').pop(), 10);
                const ingredientElement = operationItem.querySelector('.ingredient'); 
                const ingredientId = ingredientElement ? parseInt(ingredientElement.id.split('_').pop(), 10) : null;

                if (isNaN(operationId) || isNaN(ingredientId)) {
                    console.error("Erreur : ID invalide.");
                    return;
                }

                // Récupérer la liste des opérations de l'étape en cours
                let stepOperations = JSON.parse(selectedOperationsField.value || '[]');

                // Trouver le resultId avant suppression
                const resultId = stepOperations.find(op => op.operationId === operationId && op.ingredientId === ingredientId)?.operationResult;

                // Supprimer visuellement l'opération
                operationItem.remove();

                // Supprimer l'opération du tableau
                stepOperations = stepOperations.filter(op => !(op.operationId === operationId && op.ingredientId === ingredientId));

                // Mettre à jour le champ caché des opérations
                selectedOperationsField.value = stepOperations.length > 0 ? JSON.stringify(stepOperations) : '';

                // Si un résultat intermédiaire est supprimé, supprimer toutes les opérations qui l’utilisent
                if (resultId !== null && resultId < 0) {
                    removeDependentOperations(resultId);
                }
                // Mettre à jour la liste des ingrédients intermédiaires
                cleanupResultIngredients();
            }
            // Fonction pour supprimer les opérations dépendantes d'un ingrédient supprimé
            function removeDependentOperations(deletedIngredientId) {
                const stepItems = document.querySelectorAll('.step-item'); // Sélectionner toutes les étapes
                let newDeletedIngredients = []; // Stocker les nouveaux ingrédients intermédiaires à supprimer
            
                stepItems.forEach((stepItem) => {
                    const operationsContainer = stepItem.querySelector('.added-operations');
                    const selectedOperationsField = operationsContainer.querySelector('.selected-operations');
                    let stepOperations = JSON.parse(selectedOperationsField.value || '[]'); // Récupérer les opérations
            
                    // Trouver les opérations qui dépendent de l'ingrédient supprimé
                    const dependentOperations = stepOperations.filter(op => op.ingredientId === deletedIngredientId);
            
                    dependentOperations.forEach((op) => {
                        const operationId = op.operationId;
                        const stepIdMatch = stepItem.querySelector('[id^="recipe_recipeSteps_"]')?.id.match(/recipe_recipeSteps_(\d+)/);
                        const stepId = stepIdMatch ? stepIdMatch[1] : null;
                        const operationElements = stepItem.querySelectorAll(`#operation_${stepId}_${operationId}`);
            
                        operationElements.forEach((operationElement) => {
                            const operationItem = operationElement.closest('.operation-item');
                            if (!operationItem) return;
            
                            // Vérifier si l'opération utilise bien l'ingrédient supprimé
                            const ingredientElement = operationItem.querySelector('.ingredient');
                            const operationIngredientId = ingredientElement ? parseInt(ingredientElement.id.split('_').pop(), 10) : null;
            
                            if (operationIngredientId === deletedIngredientId) {
                                // **Stocker l'ID de l'ingrédient intermédiaire généré avant suppression**
                                if (op.operationResult && op.operationResult < 0) {
                                    newDeletedIngredients.push(op.operationResult);
                                }
            
                                // **Supprimer l'opération du DOM**
                                operationItem.remove();
            
                                // **Mettre à jour `selected-operations`**
                                stepOperations = stepOperations.filter(existingOp => !(existingOp.operationId === operationId && existingOp.ingredientId === deletedIngredientId));
                            }
                        });
                    });
            
                    // **Mettre à jour le champ caché `selected-operations`**
                    selectedOperationsField.value = stepOperations.length > 0 ? JSON.stringify(stepOperations) : '';
                });
            
                // **Réexécuter la suppression pour les nouveaux ingrédients intermédiaires**
                newDeletedIngredients.forEach(newIngredientId => {
                    removeDependentOperations(newIngredientId);
                });
            }
            // Fonction pour nettoyer les ingrédients intermédiaires non utilisés
            function cleanupResultIngredients() {
                const resultIngredientsField = document.getElementById('result-ingredients');
                let resultIngredients = JSON.parse(resultIngredientsField.value || '[]');

                // Récupérer tous les `selected-operations` pour voir quels resultIds sont encore utilisés
                const usedResultIds = new Set();
                document.querySelectorAll('.selected-operations').forEach(field => {
                    const stepOperations = JSON.parse(field.value || '[]');
                    stepOperations.forEach(op => {
                        if (op.operationResult && op.operationResult < 0) {
                            usedResultIds.add(op.operationResult);
                        }
                    });
                });

                // Filtrer pour ne garder que les resultIngredients encore utilisés
                resultIngredients = resultIngredients.filter(result => usedResultIds.has(result.resultId));

                // Mettre à jour le champ caché
                resultIngredientsField.value = JSON.stringify(resultIngredients);
            }


            // Fonction pour afficher une page d'ingrédients dans le popup
            function displayPage(page, ingredientSubset) {
                const startIndex = (page - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;

                // Sous-ensemble des ingrédients à afficher
                const ingredientsToDisplay = ingredientSubset.slice(startIndex, endIndex);

                // Mise à jour du conteneur d'ingrédients
                ingredientsList.innerHTML = ingredientsToDisplay
                    .map(
                        ingredient => `
                        <div class="ingredient-card" data-id="${ingredient.id}" data-name="${ingredient.ingredientName}" data-image="${ingredient.ingredientImg}">
                            <img src="${imagePath}${ingredient.ingredientImg}" alt="${ingredient.ingredientName}" style="width: 100px;">
                            <p>${ingredient.ingredientName}</p>
                        </div>
                    `
                    )
                    .join('');

                // Mise à jour de l'information de pagination
                const totalPages = Math.ceil(ingredientSubset.length / itemsPerPage);
                currentPageInfo.textContent = `Page ${page} / ${totalPages}`;
                currentPage = page;

                // Gestion des boutons de navigation
                updatePaginationButtons(totalPages);
                disableSelectedIngredients();
            }

            // Fonction pour mettre à jour les boutons "Précédent" et "Suivant" dans le popup ingrédients
            function updatePaginationButtons(totalPages) {
                const prevButton = document.getElementById('prev-page');
                const nextButton = document.getElementById('next-page');

                prevButton.disabled = currentPage <= 1;
                nextButton.disabled = currentPage >= totalPages;

                prevButton.onclick = () => {
                    if (currentPage > 1) {
                        displayPage(currentPage - 1, filteredIngredients);
                    }
                };

                nextButton.onclick = () => {
                    if (currentPage < totalPages) {
                        displayPage(currentPage + 1, filteredIngredients);
                    }
                };
            }
            // Gérer la recherche d'ingrédients dans le popup ingrédients
            ingredientSearch.addEventListener('input', (e) => {
                const searchValue = e.target.value.toLowerCase();
                filteredIngredients = ingredients.filter(ingredient =>
                    ingredient.ingredientName.toLowerCase().includes(searchValue)
                );

                // Réinitialiser à la première page des résultats filtrés
                displayPage(1, filteredIngredients);
            });

            // Ouvrir le popup ingrédients
            addIngredientButton.addEventListener('click', function() {
                ingredientPopup.style.display = 'block';
                // displayPage(1, filteredIngredients);
            });

            // Fermer le popup ingrédients
            closePopupButton.addEventListener('click', function () {
                ingredientPopup.style.display = 'none';
            });
            // Initialisation
            displayPage(1, filteredIngredients);

            // Désactiver les ingrédients déjà ajoutés
            function disableSelectedIngredients() {
                const ingredientCards = document.querySelectorAll('.ingredient-card');
                ingredientCards.forEach(card => {
                    const ingredientId = card.getAttribute('data-id');
                    if (selectedIngredients.some(item => item.ingredientId == ingredientId)) { // Comparaison avec ingredientId
                        card.classList.add('disabled'); // Ajouter une classe 'disabled'
                        const button = card.querySelector('button'); 
                        if (button) {
                            button.disabled = true; // Désactiver le bouton
                        }
                    } else {
                        card.classList.remove('disabled'); // Retirer la classe 'disabled'
                        const button = card.querySelector('button'); 
                        if (button) {
                            button.disabled = false; // Activer le bouton
                        }
                    }
                });
            }

            // Sélectionner un ingrédient
            document.getElementById('ingredients-list').addEventListener('click', (e) => {
                if (e.target && e.target.closest('.ingredient-card')) {
                    const selectedCard = e.target.closest('.ingredient-card');
                    const ingredientId = parseInt(selectedCard.getAttribute('data-id'), 10);

                    addIngredientToDOM(ingredientId);

                    // Ajouter l'ID au tableau des ingrédients sélectionnés
                    selectedIngredients.push({ ingredientId, quantity: 1 }); // Valeur par défaut pour la quantité

                    // Mettre à jour le champ caché avec la liste des ingrédients sélectionnés
                    selectedIngredientsField.value = JSON.stringify(selectedIngredients);

                    // Désactiver les ingrédients déjà sélectionnés
                    disableSelectedIngredients(); 

                    // Fermer le pop-up après sélection
                    ingredientPopup.style.display = 'none';
                }
            });

            // Mise à jour des quantités par ingrédient
            ingredientsContainer.addEventListener('input', (e) => {
                const ingredientDiv = e.target.closest('.ingredient-item');
                const ingredientId = parseInt(ingredientDiv.getAttribute('data-id')); // Convertir en entier
                const quantityInput = ingredientDiv.querySelector('.ingredient-quantity');
                const quantity = parseFloat(quantityInput.value); // Convertir en float
                const unitInput = ingredientDiv.querySelector('.ingredient-unit');
                const unit = unitInput.value.trim(); // Récupérer l'unité saisie

                // Si l'élément modifié est la quantité
                if (e.target && e.target.classList.contains('ingredient-quantity')) {

                    if (quantityInput.value.length > 0 && (isNaN(quantity) || quantity <= 0)) {
                        alert('Please enter a valid quantity.');
                        e.target.value = null; // Valeur par défaut si la quantité n'est pas valide
                        return;
                    }

                    // Mettre à jour la quantité dans `selectedIngredients`
                    const selectedIngredientsField = document.getElementById('recipe_selectedIngredients');
                    let selectedIngredients = JSON.parse(selectedIngredientsField.value || '[]');

                    // Trouver l'ingrédient et mettre à jour sa quantité
                    const ingredientIndex = selectedIngredients.findIndex(ing => parseInt(ing.ingredientId, 10) === parseInt(ingredientId, 10)); // Conversion explicite

                    if (ingredientIndex !== -1) {
                        selectedIngredients[ingredientIndex].quantity = quantity;
                        selectedIngredients[ingredientIndex].unit = unit; // Ajouter l'unité
                        selectedIngredientsField.value = JSON.stringify(selectedIngredients); // Mise à jour du champ caché
                    }
                }
                // Si l'élément modifié est l'unité
                if (e.target && e.target.classList.contains('ingredient-unit')) {

                    // Mettre à jour l'unité dans `selectedIngredients`
                    const selectedIngredientsField = document.getElementById('recipe_selectedIngredients');
                    let selectedIngredients = JSON.parse(selectedIngredientsField.value || '[]');

                    // Trouver l'ingrédient et mettre à jour son unité
                    const ingredientIndex = selectedIngredients.findIndex(ing => parseInt(ing.ingredientId, 10) === ingredientId); // Conversion explicite
                    if (ingredientIndex !== -1) {
                        selectedIngredients[ingredientIndex].quantity = quantity;
                        selectedIngredients[ingredientIndex].unit = unit; // Mettre à jour l'unité
                        selectedIngredientsField.value = JSON.stringify(selectedIngredients); // Mise à jour du champ caché
                    }
                }
            });

            // Gestion de la suppression des ingrédients
            ingredientsContainer.addEventListener('click', (e) => {
                if (e.target && e.target.classList.contains('remove-ingredient')) {
                    const ingredientDiv = e.target.closest('.ingredient-item');
                    const ingredientId = parseInt(ingredientDiv.getAttribute('data-id')); // Récupérer l'ID de l'ingrédient
                    selectedIngredients = JSON.parse(selectedIngredientsField.value || '[]');

                    // Afficher le popup de confirmation avant suppression
                    showConfirmationPopup("Si vous supprimez cet ingrédient, toutes les opérations qui en dépendent seront également supprimées. Confirmez-vous la suppression ?", (confirm) => {
                        if (confirm) {
                            // Supprimer l'élément visuel
                            ingredientDiv.remove();

                            // ✅ Mettre à jour `selectedIngredients` en supprimant uniquement l'ingrédient ciblé
                            selectedIngredients = selectedIngredients.filter(ing => ing.ingredientId !== ingredientId);
                            // ✅ Mise à jour sécurisée de selected-ingredients
                            if (selectedIngredients.length > 0) {
                                selectedIngredientsField.value = JSON.stringify(selectedIngredients);
                            } else {
                                selectedIngredientsField.value = ''; // ✅ Vide le champ au lieu de stocker `[]`
                            }

                            // ✅ Supprimer les opérations contenant cet ingrédient
                            document.querySelectorAll('.operation-item').forEach(operationItem => {
                                const ingredientElement = operationItem.querySelector('.ingredient');

                                if (ingredientElement) {
                                    const extractedId = parseInt(ingredientElement.id.split('_').pop(), 10); // Récupérer l'ingredientId
                                    if (extractedId === ingredientId) {
                                        if (document.body.contains(operationItem)) {
                                            removeOperation(operationItem); // Supprimer avec la fonction existante
                                        }
                                    }
                                }
                            });

                            // Réactiver les ingrédients disponibles
                            disableSelectedIngredients();
                        } else {
                            console.log("Suppression annulée");
                        }
                    });
                }
            });

            // Appeler la fonction au chargement de la page pour masquer 'stepSimult' pour la première étape
            hideStepSimultForFirstStep();
            
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
                const checkboxes = document.querySelectorAll(".hidden input[type='checkbox']"); // Récupère toutes les checkboxes cachées
            
                // Trouve la checkbox associée
                const checkbox = [...checkboxes].find(cb => cb.value === tagId);
            
                if (checkbox) {
                    if (checkbox.checked) {
                        checkbox.checked = false; // Décocher si déjà sélectionné
                        button.classList.remove("selected");
                        button.style.backgroundColor = ""; // Réinitialise la couleur
                        button.style.color = ""; // Remet la couleur du texte par défaut
                    } else {
                        const selectedCount = document.querySelectorAll(".hidden input[type='checkbox']:checked").length;
                        if (selectedCount >= 3) return; // Bloquer la sélection si 3 tags sont déjà choisis

                        checkbox.checked = true; // Cocher si sélectionné
                        button.classList.add("selected");
                        button.style.backgroundColor = tagColor; // Applique la couleur
                        button.style.color = getTextColor(tagColor); // Applique la couleur du texte
                    }
                }
                updateTagButtonsState();
            }
            
            // Fonction pour activer/desactiver les boutons tags
            function updateTagButtonsState() {
                const selectedCount = document.querySelectorAll(".hidden input[type='checkbox']:checked").length;
                const tagButtons = document.querySelectorAll(".tag-button");
            
                tagButtons.forEach(button => {
                    if (!button.classList.contains("selected")) {
                        button.disabled = selectedCount >= 3; // Désactiver les boutons non sélectionnés si on a déjà 3 tags
                    }
                });
            }
            
            // Gérer les clicks sur les tags
            tagButtons.forEach(button => {
                button.addEventListener("click", function() {
                    toggleTag(this);
                });
            });


            // Ajouter une étape
            addStepButton.addEventListener('click', (e) => {
                e.preventDefault();
                const stepCount = document.querySelectorAll('.step-item');
                const prototype = stepsContainer.dataset.prototype;
                const newForm = prototype.replace(/__name__/g, stepIndex + 1);

                const newStepElement = document.createElement('div');
                newStepElement.classList.add('step-item');

                // Création de la structure HTML de la nouvelle étape avec un div contenteditable
                newStepElement.innerHTML = `
                    <div class="step-header">
                        <div class="step-indicator">
                            Étape : ${stepCount.length +1}/${stepCount.length +1}
                        </div>
                        <div class="step-remove">
                            <button type="button" class="remove-step btn btn-danger">X</button>
                        </div>
                    </div>
                    ${newForm}
                    <hr class="step-time-hr"/>
                    <h5>Opérations sur les Ingrédients et descriptions</h5>
                    <div class="step-info-frame d-flex flex-row justify-content-between">
                        <div class="w-50 add-operation-frame"><button class="add-operation-button btn btn-primary" type="button">Ajouter une opération</button></div>
                        <div class="w-50 add-operation-desc">Ajoutez ici la description de l'étape.</div>
                    </div>
                    <div class="step-frame d-flex flex-row justify-content-between">
                        <div class="step-operation w-50"></div>
                        <div class="step-description w-50" contenteditable="true" id="step-description-${stepIndex}"></div>
                    </div>
                    <div class="operations-container">
                        <div class="added-operations">
                            <input type="hidden" class="selected-operations" name="selected-operations" value="[]">
                        </div>
                    </div>
                `;

                stepsContainer.appendChild(newStepElement);
                stepIndex++;

                hideStepSimultForFirstStep();

                // Mettre a jour les indicateurs d'etapes
                updateStepIndicators();

                //Ajout de classes aux différents éléments du widget des steps
                setTimeout(() => {
                    const stepFormElement = newStepElement.querySelector(':scope > div[id^="recipe_recipeSteps_"]');
                    if (stepFormElement) {
                        stepFormElement.classList.add('step-time-container');

                        // Sélectionner tous les divs enfants directs qui n'ont pas de classe
                        const divsSansClasse = stepFormElement.querySelectorAll(':scope > div:not([class])');
                        // Ajouter une classe spécifique en fonction de leur position
                        if (divsSansClasse[0]) divsSansClasse[0].classList.add('step-time-frame');        // Conteneur du temps
                        if (divsSansClasse[1]) divsSansClasse[1].classList.add('step-time-unit-frame');  // Conteneur de l'unité
                        if (divsSansClasse[2]) divsSansClasse[2].classList.add('step-simult-frame');     // Conteneur de la checkbox
                    }
                }, 50);
            });

            // Supprimer une étape
            stepsContainer.addEventListener('click', (e) => {
                if (e.target && e.target.classList.contains('remove-step')) {
                    const operationsContainer = e.target.closest('.step-item').querySelector('.step-frame .step-operation');
                    const operationItems = operationsContainer.querySelectorAll('.operation-item');
                    
                    // Afficher le popup de confirmation avant suppression
                    showConfirmationPopup("Si vous supprimez cette étape, toutes les opérations qui en dépendent seront également supprimées. Confirmez-vous la suppression ?", (confirm) => {
                        if (confirm) {
                            operationItems.forEach((operationElement) => {
                            // Appeler la fonction removeOperation pour chaque operation-item si il existe encore
                            document.body.contains(operationElement) ? removeOperation(operationElement) : null;
                            });
                            e.target.closest('.step-item').remove();

                            // Après suppression, réappliquer la logique pour vérifier les étapes restantes
                            hideStepSimultForFirstStep();
                            // Mettre a jour les indicateurs d'etapes
                            updateStepIndicators();
                        } else {
                            console.log("Suppression annulée");
                        }
                    });
                }
            });

            // Gérer les opérations sur les ingrédients
            stepsContainer.addEventListener('click', (e) => {

                // Ajouter une opération
                if (e.target && e.target.classList.contains('add-operation-button')) {
                    activeStepItem = e.target.closest('.step-item');
                    // Réinitialiser les champs du popup à chaque ouverture
                    operationPopup.querySelector('.operation-select').value = ''; // Réinitialiser le champ opération
                    operationPopup.querySelector('.ingredient-select').value = ''; // Réinitialiser le champ ingrédient
                    operationPopup.querySelector('.result-description').value = ''; // Réinitialiser la description
                    updateIngredientOptions(operationPopup);
                    operationPopup.style.display = 'block';
                }

                // Fermer le popup d'opération
                if (e.target && e.target.classList.contains('close-operation-popup')) {
                    operationPopup.setAttribute('data-editing-operation-id', '');
                    operationPopup.style.display = 'none';
                }

                // Sauvegarder une opération dans la description et le champ caché
                if (e.target && e.target.classList.contains('save-operation-button')) {
                    const operationSelect = operationPopup.querySelector('.operation-select');
                    const ingredientSelect = operationPopup.querySelector('.ingredient-select');
                    const descriptionSelect = operationPopup.querySelector('.result-description');
                    const stepOp = activeStepItem.querySelector('.step-frame .step-operation');
                    const operationsContainer = activeStepItem.querySelector('.added-operations');

                    const operationId = operationSelect.value;
                    const operationName = operationSelect.options[operationSelect.selectedIndex]?.text;
                    const ingredientId = ingredientSelect.value;
                    const ingredientName = ingredientSelect.options[ingredientSelect.selectedIndex]?.text;
                    const resultDescription = descriptionSelect.value;
                    const selectedOperationsField = operationsContainer.querySelector('.selected-operations');
                    let newResultId = null;

                    // Vérification des champs requis
                    if (operationId && ingredientId) {
                        const editingOperationId = operationPopup.getAttribute('data-editing-operation-id');

                        // Mode édition : mettre à jour l'opération existante
                        if (editingOperationId) {
                            const operationItem = document.getElementById(editingOperationId);
                            let resultToRemove = null;
                            let currentResultId = null;
                            
                            // Trouver l'élément de l'opération à modifier
                            const operationElement = operationItem.querySelector('.operation');
                            const ingredientElement = operationItem.querySelector('.ingredient');
                            
                            operationElement.textContent = operationSelect.options[operationSelect.selectedIndex].text;
                            ingredientElement.textContent = ingredientSelect.options[ingredientSelect.selectedIndex].text;

                            // Recuperer le nouvel id de l'operation
                            const updatedOperationId = operationSelect.value;
                            const currentOperationId = parseInt(operationElement.id.split('_')[2]);
                            const currentOperationIndex = operationElement.id.split('_')[1]; // 'X' dans 'operation_X_Y'

                            // Recuperer le nouvel id de l'ingredient
                            const updatedIngredientId = ingredientSelect.value;
                            const currentIngredientId = parseInt(ingredientElement.id.split('_')[2]);
                            const currentIngredientIndex = ingredientElement.id.split('_')[1]; // 'X' dans 'operation_X_Y'

                            // Mettre à jour les id operation et ingredient
                            operationElement.id = `operation_${currentOperationIndex}_${updatedOperationId}`;
                            ingredientElement.id = `ingredient_${currentIngredientIndex}_${updatedIngredientId}`;

                            const resultDescriptionElement = operationItem.querySelector('span[id^="result_"]');
                            resultDescriptionElement ? currentResultId = resultDescriptionElement.id.split('_')[1] : null;
                            if (resultDescription) {
                                // cas 1 : le resultat existe deja et il est mis a jour
                                if (resultDescriptionElement) {
                                    resultDescriptionElement.textContent = resultDescription;
                                    newResultId = parseInt(resultDescriptionElement.id.split('_')[1]);

                                    // Mise à jour du champ caché des ingrédients intermédiaires
                                    const resultIngredientsField = document.getElementById('result-ingredients');
                                    let resultIngredients = JSON.parse(resultIngredientsField.value || '[]');
                                    resultIngredients = resultIngredients.map(ri => 
                                        ri.resultId == newResultId ? { ...ri, resultName: resultDescription } : ri
                                    );

                                    // Mettre à jour le champ caché
                                    resultIngredientsField.value = JSON.stringify(resultIngredients);
                                }
                                // cas 3 : le resultat n'existe pas et j'en crée un nouveau
                                else {
                                    newResultId = addResultIngredient(resultDescription);
                                    // Créer les éléments
                                    const br = document.createElement('br');
                                    const textNode = document.createTextNode(' ---> ');
                                    const arrowSpan = document.createElement('span');
                                    arrowSpan.appendChild(textNode);
                                    const span = document.createElement('span');
                                    span.id = `result_${newResultId}`;
                                    span.textContent = resultDescription;
                                    
                                    // Insérer après l'élément strong.ingredient
                                    ingredientElement.parentNode.insertBefore(br, ingredientElement.nextSibling);
                                    ingredientElement.parentNode.insertBefore(arrowSpan, br.nextSibling);
                                    ingredientElement.parentNode.insertBefore(span, textNode.nextSibling);
                                }
                            } else if (resultDescriptionElement) {
                                // cas 2 : Supprime l'ancienne description si elle devient vide
                                let resultId = resultDescriptionElement.id.split('_')[1]; // Extrait l'ID du resultat existant
                                let nextSibling = ingredientElement.nextElementSibling;
                                while (nextSibling) {
                                    const elementToRemove = nextSibling;
                                    nextSibling = nextSibling.nextElementSibling;
                                    elementToRemove.remove();
                                }

                                // Selection du champ caché des ingrédients intermédiaires
                                const resultIngredientsField = document.getElementById('result-ingredients');
                                let resultIngredients = JSON.parse(resultIngredientsField.value || '[]');
                                // Supprimer le résultat du champ caché
                                resultIngredients = resultIngredients.filter(ri => ri.resultId != resultId);
                                resultIngredientsField.value = JSON.stringify(resultIngredients);
                                resultToRemove = parseInt(resultId);
                            }

                            // Mettre à jour les données JSON des opérations
                            let stepOperations = JSON.parse(selectedOperationsField.value || '[]');
                            stepOperations = stepOperations.map(op => {
                                // Vérifier si l'entrée correspond à l'opération en cours de modification
                                const isSameOperation = 
                                    op.operationId == currentOperationId &&
                                    op.ingredientId == currentIngredientId &&
                                    (op.operationResult == currentResultId || (op.operationResult === null && currentResultId === null));

                                if (isSameOperation) { 
                                    return {
                                        ...op,
                                        operationId: parseInt(updatedOperationId),
                                        ingredientId: parseInt(updatedIngredientId),
                                        operationResult: newResultId !== null ? newResultId : null, // Mettre à jour le résultat si besoin
                                    };
                                }
                                return op;
                            });
                            selectedOperationsField.value = JSON.stringify(stepOperations);

                            // si le resultat a ete supprimé, supprimer les dépendances en cascade
                            resultToRemove ? removeDependentOperations(resultToRemove) : null;

                            // Réinitialiser l'attribut du popup
                            operationPopup.removeAttribute('data-editing-operation-id');

                            // Mise a jour des opérations dépendantes apres edition
                            if (newResultId !== null) {
                                updateOperationResults(newResultId, resultDescription); // Passer le nouveau nom du résultat
                            }
                        }
                        // Mode Ajout de nouvelle opération
                        else {
                            // Identifier l'étape par son index dans le conteneur
                            const stepIdElement = activeStepItem.querySelector('div[id^="recipe_recipeSteps_"]');
                            const match = stepIdElement.id.match(/recipe_recipeSteps_(\d+)/);
                            const stepIndex = parseInt(match[1], 10); // Extraire l'index
                            let stepOperations = JSON.parse(selectedOperationsField.value || '[]');

                            const resultHtml = resultDescription.trim() !== "" ? (() => {
                                newResultId = addResultIngredient(resultDescription);
                                return `<br> <span>---></span> <span id="result_${newResultId}">${resultDescription}</span>`;
                            })() : "";

                            // Ajouter visuellement l'opération à la description de l'étape
                            stepOp.innerHTML += `
                                <div class="operation-item d-flex" id="operation-item_${stepIndex}_${stepOperations.length ?? 0}">
                                    <div class="m-2 operation-remove-frame">
                                        <button type="button" class="remove-operation btn btn-danger">x</button>
                                    </div>
                                    <div class="operation-frame">
                                        <strong class="operation" id="operation_${stepIndex}_${operationId}">${operationName}</strong> 
                                        <strong>-</strong> 
                                        <strong class="ingredient" id="ingredient_${stepIndex}_${ingredientId}">${ingredientName}</strong>
                                        ${resultHtml}
                                    </div>
                                    <div class="m-2 operation-edit-frame ms-auto">
                                        <button type="button" class="edit-operation btn btn-success">...</button>
                                    </div>
                                </div>
                            `;

                            // Synchroniser les données avec le champ caché des operations
                            stepOperations = JSON.parse(selectedOperationsField.value || '[]');
                            
                            // Ajouter une nouvelle opération liée à l'étape
                            stepOperations.push({
                                stepIndex: stepIndex,
                                operationId: parseInt(operationId),
                                ingredientId: parseInt(ingredientId),
                                operationResult: newResultId !== null ? newResultId : null, // Stocker l'ID du résultat, ou null si aucun
                            });

                            selectedOperationsField.value = JSON.stringify(stepOperations);
                        }

                        // Fermer le popup après sauvegarde
                        operationPopup.setAttribute('data-editing-operation-id', '');
                        operationPopup.style.display = 'none';
                    } else {
                        alert('Veuillez remplir tous les champs pour l\'opération.');
                    }
                }

                // Editer une opération
                if (e.target && e.target.classList.contains('edit-operation')) {
                    activeStepItem = e.target.closest('.step-item');
                    const operationItem = e.target.closest('.operation-item');

                    // Récupérer les elements DOM de l'opération
                    const operationElement = operationItem.querySelector('.operation');
                    const ingredientElement = operationItem.querySelector('.ingredient');
                    const resultDescriptionElement = operationItem.querySelector('span[id^="result_"]'); // Chercher directement le span avec un ID commençant par "result_"

                    // Recuperer les valeurs des elemnts DOM (id et nom de l'operation et de l'ingrédient)
                    const operationId = operationElement.id.split('_').pop();
                    const ingredientId = ingredientElement.id.split('_').pop();
                    const operationName = operationElement.textContent.trim();
                    const ingredientName = ingredientElement.textContent.trim();
                    const resultDescription = resultDescriptionElement ? resultDescriptionElement.textContent.trim() : '';

                    // Mettre à jour les champs du popup avec les valeurs actuelles
                    const operationSelect = operationPopup.querySelector('.operation-select');
                    const ingredientSelect = operationPopup.querySelector('.ingredient-select');
                    const descriptionInput = operationPopup.querySelector('.result-description');

                    // Sauvegarder l'opération actuelle dans une variable pour la modifier après validation
                    operationPopup.setAttribute('data-editing-operation-id', operationItem.id);

                    // Mettre à jour la liste des ingrédients dans le popup
                    updateIngredientOptions(operationPopup);

                    operationSelect.value = operationId;
                    ingredientSelect.value = ingredientId;
                    descriptionInput.value = resultDescription;

                    // Afficher le popup
                    operationPopup.style.display = 'block';
                }

                // Supprimer une opération
                if (e.target && e.target.classList.contains('remove-operation')) {
                    const operationItem = e.target.closest('.operation-item');
                    // Afficher le popup de confirmation avant suppression
                    showConfirmationPopup("Si vous supprimez cette opération, toutes les opérations qui en dépendent seront également supprimées. Confirmez-vous la suppression ?", (confirm) => {
                        if (confirm) {
                            removeOperation(operationItem);
                        } else {
                            console.log("Suppression annulée");
                        }
                    });
                }
            });
            
            // Fonction de surveillance des keypress et des copier/coller pour les champs de texte
            function setupTextValidation(containerSelector, targetClass, allowedRegex = /^[a-z0-9.,;:!?()"'%+\-]$/i) {
                const container = document.querySelector(containerSelector);
            
                if (!container) {
                    console.error(`Le conteneur ${containerSelector} n'existe pas !`);
                    return;
                }
            
                const allowedKeys = ['Enter', 'Backspace', ' ', 'Home', 'End', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Control', 'Shift', 'Delete'];
            
                container.addEventListener('keydown', (e) => {
                    if (!e.target.classList.contains(targetClass)) return;
            
                    if (allowedRegex.test(e.key) || allowedKeys.includes(e.key)) {
                        return;
                    }
            
                    //console.warn(`Touche interdite bloquée : ${e.key}`);
                    e.preventDefault();
                });
            
                container.addEventListener('paste', (e) => {
                    if (!e.target.classList.contains(targetClass)) return;
            
                    e.preventDefault();
                    const text = (e.clipboardData || window.Clipboard).getData('text/plain');
                    const sanitizedValue = sanitizeInput(text); // Supprime les caractères interdits
                    e.target.insertAdjacentText('beforeend', sanitizedValue);
                });
            }
            
            
            setupTextValidation("#steps", "step-description");
            setupTextValidation("#operation-popup", "result-description");
            setupTextValidation("#ingredients-container", "ingredient-unit", /^[a-z0-9.,;:()+\-]$/i);
            setupTextValidation("#name-container", "recipe-name-input", /^[a-z0-9.,;:()+\-]$/i);


            // Synchroniser les contenus au moment de la soumission
            document.querySelector('form[name="recipe"]').addEventListener('submit', (e) => {
                
                const stepItems = stepsContainer.querySelectorAll('.step-item');
                const allSelectedOperations = [];

                document.querySelectorAll("input[type='hidden']").forEach(input => {
                    if (
                        input.name.includes("[id]") || // ✅ Conversion pour ID
                        input.name.includes("[stepNumber]") // ✅ Conversion pour StepNumber
                    ) {
                        if (!isNaN(input.value) && input.value.trim() !== "") {
                            input.value = parseInt(input.value, 10); // ✅ Conversion en entier
                        }
                    }
                });

                // Vérifier que des ingrédients ont été sélectionnés
                const selectedIngredientsField = document.getElementById('recipe_selectedIngredients');
                const selectedIngredients = JSON.parse(selectedIngredientsField.value || '[]');
                if (selectedIngredients.length === 0) {
                    alert('Veuillez sélectionner au moins un ingrédient avant de soumettre.');
                    e.preventDefault(); // Empêche l'envoi du formulaire
                    return;
                }

                // Mettre à jour les quantités et les unités des ingrédients avant la soumission
                const ingredientItems = ingredientsContainer.querySelectorAll('.ingredient-item');
                
                ingredientItems.forEach((ingredientItem) => {
                    const ingredientId = ingredientItem.getAttribute('data-id');
                    const quantityInput = ingredientItem.querySelector('.ingredient-quantity');
                    const unitInput = ingredientItem.querySelector('.ingredient-unit');
                    
                    const quantity = parseFloat(quantityInput.value);
                    const unit = unitInput.value.trim(); // Récupérer l'unité saisie (peut être vide)

                    // Vérifier si la quantité est valide
                    if (isNaN(quantity) || quantity <= 0) {
                        alert('Veuillez entrer une quantité valide pour chaque ingrédient.');
                        e.preventDefault(); // Empêche l'envoi du formulaire
                        return;
                    }

                    // Mettre à jour la quantité et l'unité dans `selectedIngredients`
                    const ingredientIndex = selectedIngredients.findIndex(ing => parseInt(ing.ingredientId, 10) === parseInt(ingredientId, 10)); // Conversion explicite
                    if (ingredientIndex !== -1) {
                        selectedIngredients[ingredientIndex].quantity = quantity;
                        selectedIngredients[ingredientIndex].unit = unit; // Ajouter l'unité
                    }
                });

                // Mettre à jour le champ caché avec les quantités et unités modifiées
                selectedIngredientsField.value = JSON.stringify(selectedIngredients);

                // Parcourir les étapes et synchroniser les opérations
                stepItems.forEach((stepItem, index) => {
                    //Synchroniser les descriptions des étapes
                    const stepDescription = stepItem.querySelector('.step-description');
                    syncStepDescription(stepItem, stepDescription.innerHTML);
                    
                    // Vérifier les opérations associées à chaque étape
                    const operationsContainer = stepItem.querySelector('.added-operations');
                    const selectedOperationsField = operationsContainer.querySelector('.selected-operations');
                    const stepOperations = JSON.parse(selectedOperationsField.value || '[]');

                    // Vérifier qu'il y a bien des opérations associées, sinon un message d'avertissement
                    if (stepOperations.length === 0) {
                        console.warn(`Aucune opération associée à l'étape ${index + 1} (index ${index}).`);
                    }

                    // Ajouter le stepIndex à toutes les opérations pour la soumission
                    stepOperations.forEach((operation) => {
                        if (!operation.stepIndex) {
                            operation.stepIndex = index; // Utilise l'index de l'étape
                        }
                    });

                    // Ajouter les opérations de cette étape au tableau global
                    allSelectedOperations.push(...stepOperations);

                    // Mettre à jour le champ caché avec les opérations modifiées
                    selectedOperationsField.value = JSON.stringify(stepOperations); // Sérialisation
                });

                // Mettre à jour le champ caché global avec toutes les opérations
                const allSelectedOperationsField = document.getElementById('all-selected-operations');
                allSelectedOperationsField.value = JSON.stringify(allSelectedOperations);

                // Vérifier si le formulaire est valide avant soumettre (ajoute d'autres validations si nécessaire)
                if (allSelectedOperations.length === 0) {
                    alert('Veuillez ajouter des opérations avant de soumettre.');
                    e.preventDefault(); // Empêche l'envoi du formulaire
                }
            });
            console.log("script recette  chargé !");
        // }
    // });
    return {
        recipeData,
        addIngredientToDOM,
        disableSelectedIngredients,
        getTextColor
    };
}

// Exécuter immédiatement `initCreateForm()` et exporter son résultat
const RecipeCreate = initCreateForm();
export default RecipeCreate;