/* Bolão da Copa 2026 — JS vanilla (sem dependências). */
(function () {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf"]')?.content
        || window.CSRF_TOKEN || '';

    function api(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        }).then(r => r.json());
    }

    // ---- Salvar palpite (debounce ao digitar) ----
    function setupPredictions() {
        const grid = document.getElementById('palpites');
        if (!grid) return;
        const poolId = parseInt(grid.dataset.pool, 10);
        const timers = {};

        grid.addEventListener('input', function (ev) {
            const inp = ev.target;
            if (!inp.classList.contains('score-input')) return;
            const row = inp.closest('.match');
            const matchId = parseInt(row.dataset.match, 10);
            clearTimeout(timers[matchId]);
            timers[matchId] = setTimeout(() => savePrediction(poolId, row), 500);
        });
    }

    function savePrediction(poolId, row) {
        const matchId = parseInt(row.dataset.match, 10);
        const home = row.querySelector('.score-input.home').value;
        const away = row.querySelector('.score-input.away').value;
        const badge = row.querySelector('.row-status');
        if (home === '' || away === '') return;

        if (badge) { badge.textContent = 'salvando…'; badge.className = 'row-status muted'; }
        api(window.APP_URL + '/api/salvar_palpite.php', {
            pool_id: poolId, match_id: matchId,
            home: parseInt(home, 10), away: parseInt(away, 10),
        }).then(res => {
            if (!badge) return;
            if (res.ok) { badge.textContent = '✓ salvo'; badge.className = 'row-status saved-badge'; }
            else { badge.textContent = res.error || 'erro'; badge.className = 'row-status lock-badge'; }
        }).catch(() => {
            if (badge) { badge.textContent = 'sem conexão'; badge.className = 'row-status lock-badge'; }
        });
    }

    // ---- Preencher todos (desta página) com o placar padrão ----
    function setupFillAll() {
        const btn = document.getElementById('fill-all');
        if (!btn) return;
        btn.addEventListener('click', function () {
            const h = document.getElementById('default-home').value;
            const a = document.getElementById('default-away').value;
            if (h === '' || a === '') { alert('Defina o placar padrão (ex.: 1 x 1).'); return; }
            document.querySelectorAll('#palpites .match:not(.locked)').forEach(row => {
                const hi = row.querySelector('.score-input.home');
                const ai = row.querySelector('.score-input.away');
                if (!hi || !ai) return;
                hi.value = h; ai.value = a;
                savePrediction(parseInt(document.getElementById('palpites').dataset.pool, 10), row);
            });
        });
    }

    // ---- Copiar link de convite ----
    function setupCopy() {
        document.querySelectorAll('[data-copy]').forEach(btn => {
            btn.addEventListener('click', function () {
                const target = document.querySelector(btn.dataset.copy);
                if (!target) return;
                target.select();
                navigator.clipboard?.writeText(target.value);
                const old = btn.textContent;
                btn.textContent = 'Copiado!';
                setTimeout(() => btn.textContent = old, 1500);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupPredictions();
        setupFillAll();
        setupCopy();
    });
})();
