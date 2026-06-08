    </main>
    <footer class="footer">
        <div class="container">
            <p><?= e(APP_NAME) ?> — brincadeira entre amigos, sem propaganda. 🌎 Copa 2026</p>
        </div>
    </footer>

<style>
.page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.95);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.page-loader.show {
    display: flex;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #2ecc71;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
const loader = document.getElementById('page-loader');

// Mostra loader ao submeter formulários
document.addEventListener('submit', function(e) {
    if (e.target.tagName === 'FORM') {
        setTimeout(() => loader.classList.add('show'), 100);
    }
});

// Mostra loader ao clicar em links (exceto âncoras)
document.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    if (link && link.href && !link.href.includes('#') && !link.target && link.href.includes(window.location.hostname)) {
        setTimeout(() => loader.classList.add('show'), 200);
    }
});

// Remove loader quando página carrega
window.addEventListener('load', function() {
    loader.classList.remove('show');
});

// Esconde loader após 3 segundos de inatividade (fallback)
setTimeout(() => {
    if (loader.classList.contains('show')) {
        loader.classList.remove('show');
    }
}, 3000);
</script>

    <script src="<?= e(APP_URL) ?>/assets/js/app.js?v=1"></script>
</body>
</html>
