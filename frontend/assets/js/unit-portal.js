(() => {
    const sidebar = document.getElementById('portalSidebar');
    const toggle = document.getElementById('sidebarToggle');
    const backdrop = document.querySelector('.unit-sidebar-backdrop');
    const mobile = matchMedia('(max-width: 980px)');
    if (sidebar && toggle && backdrop) {
        const setMenu = (open, restoreFocus = false) => {
            sidebar.classList.toggle('open', open);
            document.body.classList.toggle('unit-menu-open', open);
            backdrop.hidden = !open;
            sidebar.inert = mobile.matches && !open;
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
            if (open) sidebar.querySelector('a')?.focus();
            if (restoreFocus) toggle.focus();
        };
        toggle.addEventListener('click', () => setMenu(!sidebar.classList.contains('open')));
        backdrop.addEventListener('click', () => setMenu(false, true));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && sidebar.classList.contains('open')) setMenu(false, true);
            if (event.key !== 'Tab' || !mobile.matches || !sidebar.classList.contains('open')) return;
            const links = [...sidebar.querySelectorAll('a[href],button')];
            const first = links[0], last = links[links.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        });
        mobile.addEventListener('change', () => setMenu(false));
        setMenu(false);
    }
    document.querySelectorAll('.unit-content .panel > table').forEach(table => {
        const wrapper = document.createElement('div');
        wrapper.className = 'unit-table-scroll';
        wrapper.tabIndex = 0;
        wrapper.setAttribute('role', 'region');
        const title = table.closest('.panel').querySelector('h2')?.textContent || 'Data';
        wrapper.setAttribute('aria-label', `Tabel ${title}`);
        const hint = document.createElement('p');
        hint.className = 'unit-table-hint';
        hint.textContent = 'Geser tabel ke samping untuk melihat seluruh data.';
        table.before(hint, wrapper);
        wrapper.append(table);
    });
    document.querySelectorAll('[data-copy-target]').forEach(button => {
        button.addEventListener('click', async () => {
            const input = document.getElementById(button.dataset.copyTarget);
            const feedback = button.closest('.spmb-share')?.querySelector('.spmb-copy-feedback');
            if (!input) return;
            try {
                if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(input.value);
                else { input.select(); if (!document.execCommand('copy')) throw new Error('Copy unavailable'); }
                button.textContent = 'Tersalin';
                if (feedback) feedback.textContent = 'Tautan tersalin. Bagikan kepada orang tua pendaftar ini.';
            } catch {
                input.focus(); input.select();
                if (feedback) feedback.textContent = 'Pilih tautan di atas, lalu salin dari menu perangkat Anda.';
            }
        });
    });
})();
