(async () => {
    // Importation du module principal
    const module = await import(recipeListPath); // adapte ce chemin si besoin
    const RecipeList = module.default;
    const recipeData = RecipeList.recipeData;
    console.log("Script de boutons chargé !");

    // Déclare la fonction globale à appeler après chaque affichage
    window.injectRecipeButtons = function () {
        const frames = document.querySelectorAll(".recipe-frame");
    
        frames.forEach(frame => {
            const recipeId = parseInt(frame.dataset.id);
            const recipe = recipeData.recipesList.find(r => r.id === recipeId);
            if (!recipe) return;
    
            const updateUrl = recipeData.urls.update.replace('__ID__', recipeId);
            const deleteUrl = recipeData.urls.delete.replace('__ID__', recipeId);
            const csrfDeleteToken = recipeData.csrfTokens.delete;
            const csrfEditToken = recipeData.csrfTokens.edit;
    
            const btnsContainer = frame.querySelector(".recipe-btns");
            if (!btnsContainer) return;
    
            btnsContainer.innerHTML = `
                <a href="${updateUrl}" class="btn btn-success" data-turbo="false">Edit</a>
                <a href="${deleteUrl}" class="btn btn-danger delete-button" data-recipe-name="${recipe.name}" data-csrf-token="${csrfDeleteToken}">
                    X
                </a>
            `;
        });
    
        // ✅ Après avoir injecté les boutons, on rebranche les handlers "delete"
        if (typeof window.setupDeleteButtons === 'function') {
            window.setupDeleteButtons();
        }
    };
    // ✅ Appeler la fonction immédiatement au premier chargement :
    window.injectRecipeButtons();
})();
