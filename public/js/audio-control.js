(function () {
    var button = document.querySelector('[data-audio-toggle]');
    if (!button) return;

    var storageKey = 'tpfinal-musica-silenciada';
    var muted = false;
    try {
        muted = window.localStorage.getItem(storageKey) === 'true';
    } catch (error) {
        // El control sigue funcionando durante esta visita aunque storage esté bloqueado.
    }

    var AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) {
        button.disabled = true;
        button.textContent = 'Música no disponible';
        return;
    }

    var context = null;
    var master = null;
    var interval = null;
    var nota = 0;
    var melodia = [261.63, 329.63, 392.00, 329.63, 293.66, 349.23, 440.00, 349.23];
    var acordes = [
        [130.81, 164.81, 196.00],
        [174.61, 220.00, 261.63],
        [146.83, 185.00, 220.00],
        [196.00, 246.94, 293.66]
    ];

    function actualizarControl() {
        button.textContent = muted ? 'Activar música' : 'Silenciar música';
        button.setAttribute('aria-pressed', String(!muted));
    }

    function guardarPreferencia() {
        try {
            window.localStorage.setItem(storageKey, String(muted));
        } catch (error) {
            // La preferencia queda aplicada hasta que se cierre esta página.
        }
    }

    function tocarNota() {
        if (!context || context.state !== 'running') return;
        var ahora = context.currentTime;
        if (nota % 4 === 0) tocarAcorde(ahora, acordes[Math.floor(nota / 4) % acordes.length]);
        var oscilador = context.createOscillator();
        var volumen = context.createGain();
        oscilador.type = 'sine';
        oscilador.frequency.setValueAtTime(melodia[nota % melodia.length], ahora);
        volumen.gain.setValueAtTime(0.0001, ahora);
        volumen.gain.exponentialRampToValueAtTime(0.12, ahora + 0.06);
        volumen.gain.exponentialRampToValueAtTime(0.0001, ahora + 0.65);
        oscilador.connect(volumen);
        volumen.connect(master);
        oscilador.start(ahora);
        oscilador.stop(ahora + 0.7);
        nota++;
    }

    function tocarAcorde(ahora, frecuencias) {
        frecuencias.forEach(function (frecuencia) {
            var oscilador = context.createOscillator();
            var volumen = context.createGain();
            oscilador.type = 'sine';
            oscilador.frequency.setValueAtTime(frecuencia, ahora);
            volumen.gain.setValueAtTime(0.0001, ahora);
            volumen.gain.exponentialRampToValueAtTime(0.025, ahora + 0.35);
            volumen.gain.exponentialRampToValueAtTime(0.0001, ahora + 2.9);
            oscilador.connect(volumen);
            volumen.connect(master);
            oscilador.start(ahora);
            oscilador.stop(ahora + 3);
        });
    }

    function iniciarMusica() {
        if (muted) return;
        if (!context) {
            context = new AudioContext();
            master = context.createGain();
            master.gain.value = 0.22;
            master.connect(context.destination);
        }
        master.gain.setTargetAtTime(0.22, context.currentTime, 0.08);
        context.resume().then(function () {
            if (muted || interval !== null) return;
            tocarNota();
            interval = window.setInterval(tocarNota, 760);
        }).catch(function () {
            // El navegador iniciará el audio en la próxima interacción del usuario.
        });
    }

    function silenciarMusica() {
        if (interval !== null) {
            window.clearInterval(interval);
            interval = null;
        }
        if (context && master) {
            master.gain.setTargetAtTime(0, context.currentTime, 0.08);
            window.setTimeout(function () {
                if (muted && context && context.state === 'running') context.suspend();
            }, 350);
        }
    }

    actualizarControl();
    button.addEventListener('click', function () {
        muted = !muted;
        actualizarControl();
        guardarPreferencia();
        if (muted) {
            silenciarMusica();
        } else {
            iniciarMusica();
        }
    });

    // Si la música quedó habilitada, el primer clic de la página la inicia
    // respetando las restricciones de reproducción automática del navegador.
    document.addEventListener('click', function () {
        if (!muted && (!context || context.state !== 'running')) iniciarMusica();
    });

    // Intenta continuar la preferencia al cambiar de pantalla; si el navegador
    // bloquea el inicio automático, el primer clic del usuario la reanuda.
    if (!muted) iniciarMusica();
})();
