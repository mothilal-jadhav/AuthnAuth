document.addEventListener('submit', function (e) {
    var message = e.target.dataset ? e.target.dataset.confirm : null;

    if (message && !confirm(message)) {
        e.preventDefault();
    }
});
