(async () => {
    const module = await import(recipeCreatePath);
    const RecipeCreate = module.default;

    console.log("Script d'édition chargé");
    // console.log("RecipeCreate :", RecipeCreate);

    const recipeData = RecipeCreate.recipeData;
    const existingRecipe = recipeData.existingRecipe;
    
    // console.log(recipeData);
    let recipeQuantities = {};
    let recipePortions = {};
    let recipeName = '';
    let recipeImg = '';
    let recipeOperations = {};
    let recipeSteps = {};
    existingRecipe ? recipeQuantities = existingRecipe.recipeQuantities :null;
    existingRecipe ? recipePortions = existingRecipe.recipePortions :null;
    existingRecipe ? recipeName = existingRecipe.recipeName :null;
    existingRecipe ? recipeImg = existingRecipe.recipeImg :null;
    existingRecipe ? recipeOperations = existingRecipe.recipeOperations :null;
    existingRecipe ? recipeSteps = existingRecipe.recipeSteps :null;
    // console.log("Ingrédients existants :", existingIngredients);
    // console.log(recipeOperations);
    // console.log(recipeSteps);

    // Récupérer les ingrédients déjà ajoutés à la recette
    const selectedIngredientsField = document.getElementById('recipe_selectedIngredients');
    let selectedIngredients = JSON.parse(selectedIngredientsField.value || '[]');

    const tagButtons = document.querySelectorAll(".tag-button");
    const recipePortionsInput = document.getElementById('recipe_recipePortions');
    const recipeNameInput = document.getElementById('recipe_recipeName');
    const recipeImgInput = document.getElementById('recipe_image');
    const resultIngredientsField = document.getElementById('result-ingredients');
    let resultIngredients = [];

    // Attribuer les ingrédients a la recette
    if (existingRecipe && existingRecipe.recipeIngredient) {
        existingRecipe.recipeIngredient.forEach(ingredient => {
            // Récupérer la quantité et l’unité depuis recipeQuantities
            const ingredientId = ingredient.id.toString();
            const quantityData = recipeQuantities[ingredientId] || { quantity: 1, unit: '' };

            // Ajouter l'ingrédient au DOM
            RecipeCreate.addIngredientToDOM(ingredient.id, quantityData.quantity, quantityData.unit);

            // Construire l'objet de l'ingrédient à ajouter
            let ingredientObject = {
                ingredientId: ingredient.id,
                quantity: quantityData.quantity
            };
            // Ajouter 'unit' seulement si elle est non vide
            if (quantityData.unit.trim() !== '') {
                ingredientObject.unit = quantityData.unit;
            }

            // Vérifier si l'ingrédient est déjà dans selectedIngredients
            if (!selectedIngredients.some(ing => ing.ingredientId == ingredientId)) {
                selectedIngredients.push(ingredientObject);
            }
        });
        // Mettre à jour le champ caché avec la liste des ingrédients sélectionnés
        selectedIngredientsField.value = JSON.stringify(selectedIngredients);
        // Désactiver les ingrédients déjà sélectionnés
        RecipeCreate.disableSelectedIngredients();
    }

    // Fonction pour récupérer le nom d'un ingrédient par son ID
    function getIngredientNameById(ingredientId) {
        // console.log("Ingrédient ID :", ingredientId);
        if (ingredientId < 0) {
            return getIntermediateIngredientName(ingredientId);
        }
        if (!existingRecipe.recipeIngredient) return "Ingrédient inconnu";
        
        const ingredient = existingRecipe.recipeIngredient.find(ing => ing.id === ingredientId);
        return ingredient ? ingredient.ingredientName : "Ingrédient inconnu";
    }
    // Fonction pour récupérer le nom d'une operation par son ID
    function getOperationNameById(operationId) {
        if (!recipeData.operations) return "Operation inconnue";
        
        const operation = recipeData.operations.find(ope => ope.id === operationId);
        return operation ? operation.operationName : "Operation inconnue";
    }
    function getIntermediateIngredientName(ingredientId) {
        const resultIngredients = JSON.parse(resultIngredientsField.value || '[]');
    
        // Chercher l'ingrédient intermédiaire correspondant à l'ID donné
        let foundIngredient = resultIngredients.find(ing => ing.resultId === ingredientId);
    
        return foundIngredient ? foundIngredient.resultName : "Ingrédient intermédiaire inconnu";
    }

    // Attribuer les bonnes couleurs aux tags déjà sélectionnés lors de l'édition
    tagButtons.forEach(button => {
        const tagColor = button.getAttribute("data-color");

        if (button.classList.contains("selected")) {
            button.style.backgroundColor = tagColor;
            button.style.color = RecipeCreate.getTextColor(tagColor);
        } else {
            button.style.backgroundColor = "";
            button.style.color = "";
        }
    });

    // Ajuster les valeurs des données de recette pendant l'édition
    existingRecipe ? recipePortionsInput.value = parseInt(recipePortions, 10) : null;
    existingRecipe ? recipeNameInput.value = recipeName : null;


    // Construire la liste des ingrédients intermédiaires
    Object.keys(recipeOperations).forEach(stepId => {
        recipeOperations[stepId].forEach(operation => {
            if (operation.operationResult && Object.keys(operation.operationResult).length > 0 && operation.operationResult['resultId']) {
                resultIngredients.push(operation.operationResult);
            }
        });
    });
    // Mettre à jour le champ caché des ingrédients intermediaires avec les valeurs JSON
    resultIngredientsField.value = JSON.stringify(resultIngredients);

    // Associer les stepOperations au bon step 
    document.querySelectorAll('.step-item').forEach((stepItem, stepIndex) => {
        const stepIdInBase = stepItem.getAttribute('data-id');
        const stepId = stepIndex;
        
        // Trouver le champ caché `.selected-operations`
        const selectedOperationsField = stepItem.querySelector('.selected-operations');

        if (recipeOperations[stepIdInBase]) {
            // console.log(`Opérations trouvées pour l'étape ${stepId}:`, recipeOperations[stepIdInBase]);

            const stepOperationContainer = stepItem.querySelector('.step-operation');
            let operationsData = [];

            recipeOperations[stepIdInBase].forEach((operation, index) => {
                // Générer l'affichage HTML pour chaque opération
                const ingredientId = operation.ingredient !== null ? operation.ingredient : (operation.operationResult?.usedIng ?? 'unknown');
                const operationId = operation.operation ;

                // console.log("ingredientId :", ingredientId);
                const operationHTML = `
                    <div class="operation-item d-flex" id="operation-item_${stepId}_${index}">
                        <div class="m-2 operation-remove-frame">
                            <button type="button" class="remove-operation btn btn-danger">x</button>
                        </div>
                        <div class="operation-frame">
                            <strong class="operation" id="operation_${stepId}_${operation.operation}">${getOperationNameById(operation.operation)}</strong>
                            <strong>-</strong> 
                            <strong class="ingredient" id="ingredient_${stepId}_${ingredientId}">${getIngredientNameById(ingredientId)}</strong>
                            ${operation.operationResult?.resultName ? `<br> <span>---></span> <span id="result_${operation.operationResult.resultId}">${operation.operationResult.resultName}</span>` : ""}
                        </div>
                        <div class="m-2 operation-edit-frame ms-auto">
                            <button type="button" class="edit-operation btn btn-success">...</button>
                        </div>
                    </div>
                `;

                // Injecter dans la div .step-operation correspondante
                stepOperationContainer.innerHTML += operationHTML;

                // Ajouter l'opération aux données à stocker dans le champ caché
                operationsData.push({
                    id: operation.id,
                    stepIndex: stepId,
                    operationId: operationId,
                    ingredientId: ingredientId,
                    operationResult: operation.operationResult?.resultId ?? null
                });
            });
            // Mettre à jour le champ caché avec les opérations de cette étape
            selectedOperationsField.value = JSON.stringify(operationsData);
        }
    });
})();