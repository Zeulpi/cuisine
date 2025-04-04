// roleFilter.js

function initRoleFilter(){
    const filterName = 'roles';
    let selectedFilters = [];
    function filterListen(filtersToApply, filtersApplied) {
        const roleCheckboxes = document.querySelectorAll('[name="roles[]"]');

        // Ajouter un écouteur d'événement sur chaque case à cocher
        roleCheckboxes.forEach(checkbox => {
            // Si le filtre est déjà appliqué, cocher la case correspondante
            if (filtersApplied[filterName] && JSON.parse(filtersApplied[filterName].includes(checkbox.value))) {
                checkbox.checked = true; // Si le filtre est déjà appliqué, cocher la case correspondante
            }
            checkbox.addEventListener('change', function () {
                selectedFilters = [];
                roleCheckboxes.forEach(cb => {
                    if (this.value === 'none' && this.checked && cb.value !== 'none') {
                        cb.checked = false; // si 'none' est coché, décocher les autres
                    }
                    if (this.value !== 'none' && this.checked && cb.value === 'none') {
                        cb.checked = false; // si un autre est coché, décocher 'none'
                    }
                });
                // Recalculer les rôles sélectionnés après les ajustements
                roleCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        selectedFilters.push(cb.value);
                    }
                });
                // Mettre à jour le filtre dans filtersToApply
                filtersToApply[filterName] = selectedFilters;
            });
        });
    }
    // console.log("roleFilter.js chargé");
    return{
        filterListen,
        filterName,
    };
}
const roleFilter = initRoleFilter();
export default roleFilter;