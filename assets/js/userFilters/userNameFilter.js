// userNameFilter.js

document.querySelector('#search-by-username').addEventListener('input', function () {
    const userNameFilter = this.value.trim();
    updateFilter('username', userNameFilter);  // Met à jour le filtre 'username' dans la variable filters
});
