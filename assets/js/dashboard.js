(function () {
    const config = window.WebDropDashboard || {};
    const modal = document.getElementById('wd-create-modal');
    const confirmModal = document.getElementById('wd-confirm-modal');
    const toast = document.getElementById('wd-toast');
    const createButtons = Array.from(document.querySelectorAll('[data-create-project]'));
    const createForm = document.querySelector('[data-create-form]');
    const searchInput = document.querySelector('[data-project-search]');
    const sortSelect = document.querySelector('[data-project-sort]');
    const filterButtons = Array.from(document.querySelectorAll('[data-project-filter]'));
    const projectGrid = document.querySelector('[data-project-grid]');
    const deleteUrl = config.deleteUrl;
    let pendingDeleteId = null;
    let activeFilter = 'all';
    let activeSort = 'latest';

    if (!modal || !confirmModal || !toast) {
        return;
    }

    const setCsrf = (payload) => {
        if (!payload || !payload.csrf) {
            return;
        }

        config.csrfName = payload.csrf.name;
        config.csrfHash = payload.csrf.hash;

        document.querySelectorAll('meta[name="csrf-token-name"]').forEach((meta) => meta.setAttribute('content', config.csrfName));
        document.querySelectorAll('meta[name="csrf-token-hash"]').forEach((meta) => meta.setAttribute('content', config.csrfHash));
    };

    const showToast = (message, kind = 'success') => {
        toast.textContent = message;
        toast.className = 'fixed right-4 top-4 z-50 border px-4 py-3 text-sm font-medium ' + (kind === 'error' ? 'border-[#da1e28] bg-white text-[#da1e28]' : 'border-[#24a148] bg-white text-[#161616]');
        toast.classList.remove('hidden');
        clearTimeout(showToast.timer);
        showToast.timer = setTimeout(() => toast.classList.add('hidden'), 2500);
    };

    const copyText = async (text) => {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
            return;
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    };

    const openModal = (element) => {
        element.classList.remove('hidden');
        element.classList.add('flex');
    };

    const closeModal = (element) => {
        element.classList.add('hidden');
        element.classList.remove('flex');
    };

    const closeProjectMenus = () => {
        document.querySelectorAll('[data-project-menu]').forEach((menu) => menu.classList.add('hidden'));
    };

    const applyDashboardFilters = () => {
        if (!projectGrid) {
            return;
        }

        const query = (searchInput?.value || '').trim().toLowerCase();
        const cards = Array.from(projectGrid.querySelectorAll('[data-project-card]'));
        const createCard = projectGrid.querySelector('[data-create-project]');

        cards.forEach((card) => {
            const name = String(card.dataset.projectName || '').toLowerCase();
            const status = String(card.dataset.projectStatus || 'draft').toLowerCase();
            const matchesSearch = !query || name.includes(query);
            const matchesFilter = activeFilter === 'all' || status === activeFilter;
            card.classList.toggle('hidden', !(matchesSearch && matchesFilter));
        });

        if (activeSort === 'name') {
            cards.sort((left, right) => String(left.dataset.projectName || '').localeCompare(String(right.dataset.projectName || '')))
                .forEach((card) => projectGrid.appendChild(card));
        } else {
            cards.sort((left, right) => Number(right.dataset.projectUpdatedTs || 0) - Number(left.dataset.projectUpdatedTs || 0))
                .forEach((card) => projectGrid.appendChild(card));
        }

        if (createCard) {
            projectGrid.prepend(createCard);
        }
    };

    const submitForm = async (url, formData) => {
        const csrfName = document.querySelector('meta[name="csrf-token-name"]')?.getAttribute('content') || config.csrfName;
        const csrfHash = document.querySelector('meta[name="csrf-token-hash"]')?.getAttribute('content') || config.csrfHash;

        config.csrfName = csrfName;
        config.csrfHash = csrfHash;
        formData.set(config.csrfName, config.csrfHash);
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        });
        const payload = await response.json();
        setCsrf(payload);
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Request gagal.');
        }
        return payload;
    };

    createButtons.forEach((button) => button.addEventListener('click', () => openModal(modal)));
    modal.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', () => closeModal(modal)));
    confirmModal.querySelectorAll('[data-close-confirm]').forEach((button) => button.addEventListener('click', () => closeModal(confirmModal)));

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-project-menu-wrap]')) {
            closeProjectMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeProjectMenus();
        }
    });

    document.querySelectorAll('[data-project-menu-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const wrap = button.closest('[data-project-menu-wrap]');
            const menu = wrap?.querySelector('[data-project-menu]');
            if (!menu) {
                return;
            }

            const isOpen = !menu.classList.contains('hidden');
            closeProjectMenus();
            if (!isOpen) {
                menu.classList.remove('hidden');
            }
        });
    });

    document.querySelectorAll('[data-project-menu]').forEach((menu) => {
        menu.addEventListener('click', async (event) => {
            const copyButton = event.target.closest('[data-copy-link]');
            if (copyButton) {
                const card = copyButton.closest('[data-project-card]');
                const publicUrl = card?.dataset.publicUrl || '';
                if (!publicUrl) {
                    showToast('Public URL belum tersedia.', 'error');
                    return;
                }

                await copyText(publicUrl);
                showToast('Link copied');
                closeProjectMenus();
                return;
            }

            const deleteButton = event.target.closest('[data-delete-project]');
            if (deleteButton) {
                const card = deleteButton.closest('[data-project-card]');
                pendingDeleteId = card?.dataset.projectId || null;
                closeProjectMenus();
                openModal(confirmModal);
                return;
            }

            const openSite = event.target.closest('a[href]');
            if (openSite) {
                closeProjectMenus();
            }
        });
    });

    searchInput?.addEventListener('input', applyDashboardFilters);

    sortSelect?.addEventListener('change', () => {
        activeSort = sortSelect.value || 'latest';
        applyDashboardFilters();
    });

    filterButtons.forEach((button) => {
        button.dataset.active = button.dataset.projectFilter === activeFilter ? 'true' : 'false';
        button.addEventListener('click', () => {
            activeFilter = button.dataset.projectFilter || 'all';
            filterButtons.forEach((item) => {
                item.dataset.active = item.dataset.projectFilter === activeFilter ? 'true' : 'false';
            });
            applyDashboardFilters();
        });
    });

    createForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(createForm);
        try {
            const payload = await submitForm(config.createUrl, formData);
            closeModal(modal);
            showToast(payload.message);
            if (payload.data && payload.data.redirect) {
                window.location.href = payload.data.redirect;
            }
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    document.querySelectorAll('[data-project-card]').forEach((card) => {
        const deleteButton = card.querySelector('[data-delete-project]');
        const copyButton = card.querySelector('[data-copy-link]');
        const openEditor = card.querySelector('a[href*="/projects/editor/"]');
        const publicUrl = card.dataset.publicUrl || '';
        const projectId = card.dataset.projectId;

        deleteButton?.addEventListener('click', () => {
            pendingDeleteId = projectId;
            openModal(confirmModal);
        });

        copyButton?.addEventListener('click', async () => {
            if (!publicUrl) {
                showToast('Public URL belum tersedia.', 'error');
                return;
            }

            await copyText(publicUrl);
            showToast('Link disalin ke clipboard.');
        });

        openEditor?.addEventListener('click', () => {
            window.location.href = openEditor.href;
        });
    });

    document.querySelector('[data-confirm-delete]')?.addEventListener('click', async () => {
        if (!pendingDeleteId) {
            closeModal(confirmModal);
            return;
        }

        const formData = new FormData();
        formData.set('id_project', pendingDeleteId);

        try {
            const payload = await submitForm(deleteUrl, formData);
            closeModal(confirmModal);
            showToast(payload.message);
            window.location.reload();
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    document.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal(modal);
        }

        if (event.target === confirmModal) {
            closeModal(confirmModal);
        }
    });

    applyDashboardFilters();

    if (window.lucide) {
        window.lucide.createIcons();
    }
})();
