// roleFilter.js
console.log("roleFilter.js chargé");
const roleFilter = () => {
    const roleCheckboxes = document.querySelectorAll('[name="roles[]"]');
    let selectedRoles = [];

    // Ajouter un écouteur d'événement sur chaque case à cocher
    roleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            selectedRoles = [];
            roleCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedRoles.push(checkbox.value);
                }
            });

            // Mettre à jour la variable globale filters avec les rôles sélectionnés
            updateFilter('roles', selectedRoles);  // Fonction générique pour mettre à jour filters
        });
    });
}
export default roleFilter;