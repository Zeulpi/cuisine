function initRoleActions() {
    
    const actionsName = 'roles';

    function actionsListen(fetchCallback, initCallback) {
        const roleButtons = document.querySelectorAll('.role-action');
        roleButtons.forEach(button => {
            button.addEventListener('click', async () => {
                const userId = button.dataset.userId;
                const action = button.dataset.action;

                try {
                    const response = await fetch('/users/role/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ userId, action })
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Recharger les données du tableau (avec les filtres en cours)
                        const currentPage = new URL(window.location.href).searchParams.get('page') || 1;
                        fetchCallback(currentPage);
                        // console.log('requete action envoyée');
                        initCallback();
                    } else {
                        alert('Erreur : ' + result.message);
                    }

                } catch (error) {
                    console.error('Erreur lors de la modification du rôle :', error);
                }
            });
        });
    }
    return{
        actionsListen,
        actionsName,
    };
}

const roleActions = initRoleActions();
export default  roleActions;
