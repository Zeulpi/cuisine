function initPlannerActions() {
    
    const actionsName = 'planner';

    function actionsListen(fetchCallback, initCallback) {
        const plannerButtons = document.querySelectorAll('.planner-action');
        plannerButtons.forEach(button => {
            button.addEventListener('click', async () => {
                const userId = button.dataset.userId;
                const action = button.dataset.action;

                try {
                    const response = await fetch('/users/planner/reset', {
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
                    console.error('Erreur lors de la modification du planner :', error);
                }
            });
        });
    }
    return{
        actionsListen,
        actionsName,
    };
}

const plannerActions = initPlannerActions();
export default  plannerActions;
