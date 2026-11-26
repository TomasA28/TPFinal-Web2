(function () {
    var contenedor = document.querySelector('[data-safe-back]');
    if (!contenedor || !window.history || !window.history.pushState) return;

    var destinoSeguro = contenedor.getAttribute('data-safe-back') || '/juego/jugar';

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) window.location.reload();
    });

    window.history.pushState({ tpFinalJuego: true }, '', window.location.href);
    window.addEventListener('popstate', function () {
        window.location.replace(destinoSeguro);
    });
})();
