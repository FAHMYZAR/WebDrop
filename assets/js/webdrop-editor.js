(function () {
    const config = window.WebDropEditor || {};
    const root = document.getElementById('wd-editor');
    if (!root) {
        return;
    }

    const projectId = config.projectId;
    const endpoints = config.endpoints || {};
    const treeRoot = root.querySelector('[data-tree-root]');
    const tabBar = root.querySelector('[data-tab-bar]');
    const sidebar = root.querySelector('[data-sidebar]');
    const sidebarCollapseButton = root.querySelector('[data-sidebar-collapse]');
    const sidebarExpandButton = root.querySelector('[data-sidebar-expand]');
    const sidebarCreateToggle = root.querySelector('[data-sidebar-create-toggle]');
    const sidebarCreateMenu = root.querySelector('[data-sidebar-create-menu]');
    const sidebarResizeHandle = root.querySelector('[data-sidebar-resize-handle]');
    const previewPanel = root.querySelector('[data-preview-panel]');
    const previewBody = root.querySelector('[data-preview-body]');
    const previewFrame = root.querySelector('[data-preview-frame]');
    const previewResizeHandle = root.querySelector('[data-preview-resize-handle]');
    const editorNode = document.getElementById('wd-monaco');
    const saveButton = root.querySelector('[data-save-file]');
    const nonEditablePanel = root.querySelector('[data-noneditable-panel]');
    const nonEditableContent = root.querySelector('[data-noneditable-content]');
    const publishModal = document.getElementById('wd-publish-modal');
    const publishUrlNode = publishModal?.querySelector('[data-public-url]');
    const openPublishUrl = publishModal?.querySelector('[data-open-public-url]');
    const actionModal = document.getElementById('wd-action-modal');
    const actionForm = actionModal?.querySelector('[data-action-form]');
    const modalTitle = actionModal?.querySelector('[data-modal-title]');
    const modalDesc = actionModal?.querySelector('[data-modal-desc]');
    const nameField = actionModal?.querySelector('[data-name-field]');
    const fileField = actionModal?.querySelector('[data-file-field]');
    const actionSubmit = actionModal?.querySelector('[data-action-submit]');
    const editorLayout = root.querySelector('[data-editor-layout]');
    const mobileButtons = Array.from(root.querySelectorAll('[data-mobile-tab]'));
    const tree = Array.isArray(config.tree) ? config.tree : [];
    const state = {
        activeRelativePath: config.selectedPath || 'index.html',
        activeEntry: config.selectedEntry || null,
        openTabs: [],
        dirty: new Set(),
        editor: null,
        csrfName: config.csrfName,
        csrfHash: config.csrfHash,
        previewEnabled: false,
        previewHeight: 0,
        previewResizeStartY: 0,
        previewResizeStartHeight: 0,
        isResizingPreview: false,
        previewDirty: false,
        mobileView: 'editor',
        sidebarWidth: 280,
        sidebarCollapsed: false,
        isResizingSidebar: false,
        sidebarResizeStartX: 0,
        sidebarResizeStartWidth: 280,
        inlineRenamePath: null,
        inlineRenameValue: '',
    };
    let layoutFrameId = null;
    const previewStorageKey = 'webdrop-editor-preview-height';
    const previewMinHeight = 180;
    const previewMaxRatio = 0.75;

    const iconsReady = () => {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    };

    const closeCreateMenu = () => {
        if (!sidebarCreateMenu || !sidebarCreateToggle) {
            return;
        }

        sidebarCreateMenu.classList.add('hidden');
        sidebarCreateToggle.setAttribute('aria-expanded', 'false');
    };

    const openCreateMenu = () => {
        if (!sidebarCreateMenu || !sidebarCreateToggle) {
            return;
        }

        sidebarCreateMenu.classList.remove('hidden');
        sidebarCreateToggle.setAttribute('aria-expanded', 'true');
    };

    const toggleCreateMenu = () => {
        if (!sidebarCreateMenu || !sidebarCreateToggle) {
            return;
        }

        const open = !sidebarCreateMenu.classList.contains('hidden');
        if (open) {
            closeCreateMenu();
        } else {
            openCreateMenu();
        }
    };

    const scheduleEditorLayout = () => {
        if (!state.editor) {
            return;
        }

        if (layoutFrameId) {
            cancelAnimationFrame(layoutFrameId);
        }

        layoutFrameId = requestAnimationFrame(() => {
            layoutFrameId = requestAnimationFrame(() => {
                state.editor?.layout();
            });
        });
    };

    const getPreviewMaxHeight = () => Math.max(previewMinHeight, Math.floor(window.innerHeight * previewMaxRatio));

    const clampPreviewHeight = (height) => {
        const maxHeight = getPreviewMaxHeight();
        return Math.min(Math.max(Math.round(height), previewMinHeight), maxHeight);
    };

    const loadPreviewHeight = () => {
        const storedHeight = Number(window.localStorage.getItem(previewStorageKey));
        if (Number.isFinite(storedHeight) && storedHeight > 0) {
            return clampPreviewHeight(storedHeight);
        }

        return clampPreviewHeight(Math.round(window.innerHeight * 0.35));
    };

    const savePreviewHeight = () => {
        window.localStorage.setItem(previewStorageKey, String(state.previewHeight));
    };

    const applyPreviewLayout = () => {
        if (!previewPanel) {
            return;
        }

        previewPanel.dataset.open = state.previewEnabled ? 'true' : 'false';
        previewPanel.style.height = state.previewEnabled ? `${state.previewHeight}px` : '0px';
    };

    const refreshPreviewSize = () => {
        if (!previewBody || !previewFrame || !state.previewEnabled) {
            return;
        }

        previewFrame.style.height = `${previewBody.clientHeight}px`;
    };

    const refreshPreview = () => {
        if (!previewFrame) {
            return;
        }

        const baseUrl = previewFrame.dataset.src || previewFrame.getAttribute('src') || config.previewUrl;
        previewFrame.setAttribute('src', baseUrl.split('?')[0] + '?t=' + Date.now());
        state.previewDirty = false;
    };

    const showPreviewPanel = () => {
        state.previewEnabled = true;
        applyPreviewLayout();
        requestAnimationFrame(() => {
            refreshPreview();
            refreshPreviewSize();
            scheduleEditorLayout();
        });
    };

    const hidePreviewPanel = () => {
        state.previewEnabled = false;
        applyPreviewLayout();
        requestAnimationFrame(() => {
            refreshPreviewSize();
            scheduleEditorLayout();
        });
    };

    const setPreviewHeight = (height, persist = false) => {
        state.previewHeight = clampPreviewHeight(height);
        applyPreviewLayout();
        requestAnimationFrame(() => {
            refreshPreviewSize();
            scheduleEditorLayout();
        });

        if (persist) {
            savePreviewHeight();
        }
    };

    const setCsrf = (payload) => {
        if (!payload || !payload.csrf) {
            return;
        }

        state.csrfName = payload.csrf.name;
        state.csrfHash = payload.csrf.hash;
        document.querySelectorAll('meta[name="csrf-token-name"]').forEach((meta) => meta.setAttribute('content', state.csrfName));
        document.querySelectorAll('meta[name="csrf-token-hash"]').forEach((meta) => meta.setAttribute('content', state.csrfHash));
    };

    const showToast = (message, kind = 'success') => {
        const toast = document.getElementById('wd-editor-toast');
        if (!toast) {
            return;
        }

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

    const openModal = () => {
        actionModal?.classList.remove('hidden');
        actionModal?.classList.add('flex');
    };

    const closeModal = () => {
        actionModal?.classList.add('hidden');
        actionModal?.classList.remove('flex');
    };

    const openPublishModal = (url) => {
        if (!publishModal || !publishUrlNode || !openPublishUrl) {
            return;
        }

        publishUrlNode.textContent = url;
        publishUrlNode.href = url;
        openPublishUrl.href = url;
        publishModal.classList.remove('hidden');
        publishModal.classList.add('flex');
    };

    const closePublishModal = () => {
        publishModal?.classList.add('hidden');
        publishModal?.classList.remove('flex');
    };

    const requestJSON = async (url, formData = null, method = 'POST') => {
        const options = {
            method,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        };

        if (formData) {
            formData.set(state.csrfName, state.csrfHash);
            options.body = formData;
        }

        const response = await fetch(url, options);
        const payload = await response.json();
        setCsrf(payload);

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Request gagal.');
        }

        return payload;
    };

    const pathKey = (path) => path || '';

    const getActiveRelativePath = () => state.activeRelativePath;

    const setActiveRelativePath = (relativePath) => {
        state.activeRelativePath = relativePath || 'index.html';
        state.activeEntry = tree.find((item) => item.relative_path === state.activeRelativePath) || null;
    };

    const fileIconFor = (entry) => {
        if (!entry || Number(entry.is_folder) === 1) {
            return 'folder';
        }

        const extension = String(entry.file_extension || '').toLowerCase();

        if (extension === 'html') return 'file-code-2';
        if (extension === 'css') return 'file-code';
        if (extension === 'js') return 'file-scan';
        if (extension === 'json' || extension === 'txt') return 'file-text';
        if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'].includes(extension)) return 'image';

        return 'file-text';
    };

    const tabStatusIconFor = (path) => (state.dirty.has(path) ? 'circle-alert' : 'check-circle-2');

    const tabStatusClassFor = (path) => (state.dirty.has(path) ? 'text-amber-500' : 'text-emerald-500');

    const extensionFor = (path) => {
        const parts = path.split('.');
        return parts.length > 1 ? parts.pop().toLowerCase() : '';
    };

    const editableFor = (entry) => Boolean(entry && Number(entry.is_folder) === 0 && Number(entry.is_editable) === 1);

    const languageFor = (path) => ({ html: 'html', css: 'css', js: 'javascript', json: 'json', txt: 'plaintext' }[extensionFor(path)] || 'plaintext');

    const clearInlineRename = () => {
        state.inlineRenamePath = null;
        state.inlineRenameValue = '';
    };

    const getTreeItem = (relativePath) => tree.find((item) => item.relative_path === relativePath) || null;

    const pickFallbackPath = () => {
        const lastOpenTab = state.openTabs[state.openTabs.length - 1];
        if (lastOpenTab && getTreeItem(lastOpenTab)) {
            return lastOpenTab;
        }

        const firstFile = tree.find((item) => Number(item.is_folder) === 0);
        return firstFile ? firstFile.relative_path : '';
    };

    const syncActiveStateAfterPathChange = (oldPath, newPath, newName = null) => {
        const item = getTreeItem(oldPath);
        if (item) {
            if (newName !== null) {
                item.file_name = newName;
            }
            item.relative_path = newPath;
        }

        state.openTabs = state.openTabs.map((path) => (path === oldPath ? newPath : path));
        state.dirty = new Set(Array.from(state.dirty, (path) => (path === oldPath ? newPath : path)));

        if (state.activeRelativePath === oldPath) {
            setActiveRelativePath(newPath);
        } else if (state.activeEntry) {
            state.activeEntry = getTreeItem(state.activeRelativePath);
        }
    };

    const removeTreePath = (relativePath) => {
        const index = tree.findIndex((item) => item.relative_path === relativePath);
        if (index !== -1) {
            tree.splice(index, 1);
        }

        state.openTabs = state.openTabs.filter((path) => path !== relativePath);
        state.dirty.delete(relativePath);

        if (state.inlineRenamePath === relativePath) {
            clearInlineRename();
        }
    };

    const beginInlineRename = (relativePath) => {
        const item = getTreeItem(relativePath);
        if (!item) {
            return;
        }

        state.inlineRenamePath = relativePath;
        state.inlineRenameValue = item.file_name || relativePath;
        displayTree();
        requestAnimationFrame(() => {
            const input = treeRoot?.querySelector('[data-inline-rename-input]');
            if (input) {
                input.focus();
                input.select();
            }
        });
    };

    const commitInlineRename = async () => {
        const relativePath = state.inlineRenamePath;
        if (!relativePath) {
            return;
        }

        const newName = String(state.inlineRenameValue || '').trim();
        const item = getTreeItem(relativePath);
        if (!item || !newName || newName === item.file_name) {
            clearInlineRename();
            displayTree();
            return;
        }

        try {
            const formData = new FormData();
            formData.set('id_project', projectId);
            formData.set('relative_path', relativePath);
            formData.set('new_name', newName);
            const payload = await requestJSON(endpoints.renameFile, formData);
            const renamedFile = payload?.data?.file || null;
            const newPath = renamedFile?.relative_path || item.relative_path;
            const nextName = renamedFile?.file_name || newName;

            syncActiveStateAfterPathChange(relativePath, newPath, nextName);
            clearInlineRename();
            displayTree();
            renderTabs();

            if (state.editor) {
                const content = state.editor.getValue();
                const model = window.monaco?.editor?.createModel ? window.monaco.editor.createModel(content, languageFor(newPath)) : null;
                if (model) {
                    const previousModel = state.editor.getModel();
                    if (previousModel) {
                        previousModel.dispose();
                    }
                    state.editor.setModel(model);
                }
            }

            showToast(payload.message);
        } catch (error) {
            showToast(error.message, 'error');
            displayTree();
        }
    };

    const deleteInlineFile = async (relativePath) => {
        const item = getTreeItem(relativePath);
        if (!item) {
            return;
        }

        if (!window.confirm(`Delete ${item.file_name}?`)) {
            return;
        }

        try {
            const formData = new FormData();
            formData.set('id_project', projectId);
            formData.set('relative_path', relativePath);
            const payload = await requestJSON(endpoints.deleteFile, formData);

            const wasActive = state.activeRelativePath === relativePath;
            const wasCurrentTab = state.openTabs.includes(relativePath);
            removeTreePath(relativePath);

            if (wasActive || wasCurrentTab) {
                const fallback = pickFallbackPath();
                if (fallback) {
                    await loadFile(fallback, true);
                } else {
                    state.activeRelativePath = '';
                    state.activeEntry = null;
                    if (state.editor) {
                        state.editor.setModel(null);
                    }
                    hideNonEditable();
                    renderTabs();
                    displayTree();
                }
            } else {
                displayTree();
                renderTabs();
            }

            showToast(payload.message);
        } catch (error) {
            showToast(error.message, 'error');
        }
    };

    const displayTree = () => {
        const byParent = new Map();
        tree.forEach((item) => {
            const parentKey = item.parent_id ? String(item.parent_id) : 'root';
            if (!byParent.has(parentKey)) {
                byParent.set(parentKey, []);
            }
            byParent.get(parentKey).push(item);
        });

        const sortItems = (items) => items.sort((left, right) => {
            if (Number(left.is_folder) !== Number(right.is_folder)) {
                return Number(right.is_folder) - Number(left.is_folder);
            }
            return String(left.file_name).localeCompare(String(right.file_name));
        });

        const renderNode = (item) => {
            const isFolder = Number(item.is_folder) === 1;
            const isActive = pathKey(item.relative_path) === pathKey(state.activeRelativePath);
            const isRenaming = state.inlineRenamePath === item.relative_path;
            const icon = fileIconFor(item);
            const children = sortItems(byParent.get(String(item.id_file)) || []);

            return `
                <div class="space-y-1">
                    <div class="group flex h-10 items-center gap-2 px-3 py-2 text-sm transition ${isActive ? 'border border-[#0f62fe] bg-[#f4f4f4] text-[#0f62fe]' : 'border border-transparent text-[#161616] hover:bg-[#f4f4f4]'}" data-tree-row="${item.relative_path}" data-is-folder="${isFolder ? '1' : '0'}">
                        ${isRenaming ? `
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <i data-lucide="${icon}" class="h-[18px] w-[18px] shrink-0"></i>
                                <input type="text" value="${escapeHtml(state.inlineRenameValue || item.file_name)}" data-inline-rename-input="${item.relative_path}" class="min-w-0 flex-1 border border-[#0f62fe] bg-white px-2 py-1 text-sm font-medium text-[#161616] outline-none focus:border-[#0f62fe]">
                                ${!isFolder && Number(item.is_editable) !== 1 ? '<span class="border border-[#e0e0e0] bg-[#f4f4f4] px-2 py-0.5 text-[11px] font-semibold text-[#525252]">asset</span>' : ''}
                            </div>
                        ` : `
                            <button type="button" class="flex min-w-0 flex-1 items-center gap-2 text-left" data-tree-item="${item.relative_path}" data-is-folder="${isFolder ? '1' : '0'}" data-parent-path="${item.parent_id ? item.relative_path.split('/').slice(0, -1).join('/') : ''}">
                                <i data-lucide="${icon}" class="h-[18px] w-[18px] shrink-0"></i>
                                <span class="min-w-0 flex-1 truncate font-medium">${escapeHtml(item.file_name)}</span>
                                ${!isFolder && Number(item.is_editable) !== 1 ? '<span class="border border-[#e0e0e0] bg-[#f4f4f4] px-2 py-0.5 text-[11px] font-semibold text-[#525252]">asset</span>' : ''}
                            </button>
                        `}
                        <div class="flex items-center gap-1 transition ${isRenaming || isActive ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'}">
                            ${isRenaming ? `
                                <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-700" data-inline-rename-save="${item.relative_path}" aria-label="Save rename">
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                </button>
                                <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-gray-700" data-inline-rename-cancel="${item.relative_path}" aria-label="Cancel rename">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            ` : `
                                <button type="button" class="inline-flex h-7 w-7 items-center justify-center border border-transparent text-[#8d8d8d] transition hover:border-[#e0e0e0] hover:bg-[#f4f4f4] hover:text-[#0f62fe]" data-inline-rename-start="${item.relative_path}" aria-label="Rename ${escapeHtml(item.file_name)}">
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </button>
                                <button type="button" class="inline-flex h-7 w-7 items-center justify-center border border-transparent text-[#8d8d8d] transition hover:border-[#e0e0e0] hover:bg-[#fff1f1] hover:text-[#da1e28]" data-inline-delete="${item.relative_path}" aria-label="Delete ${escapeHtml(item.file_name)}">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            `}
                        </div>
                    </div>
                    ${isFolder && children.length ? `<div class="ml-4 border-l border-[#e0e0e0] pl-3">${children.map(renderNode).join('')}</div>` : ''}
                </div>
            `;
        };

        const roots = sortItems(byParent.get('root') || []);
        treeRoot.innerHTML = roots.map(renderNode).join('') || '<div class="border border-dashed border-[#e0e0e0] bg-[#f4f4f4] p-6 text-sm text-[#525252]">No files yet.</div>';
        iconsReady();
    };

    const renderTabs = () => {
        tabBar.innerHTML = state.openTabs.map((path) => {
            const entry = tree.find((item) => item.relative_path === path) || { file_name: path };
            const active = path === state.activeRelativePath;
            return `
                <div class="inline-flex items-stretch overflow-hidden border-b ${active ? 'border-[#0f62fe] bg-white' : 'border-transparent bg-[#f4f4f4]'}" data-tab-wrap="${path}" data-active="${active ? 'true' : 'false'}">
                    <button type="button" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium transition ${active ? 'bg-white text-[#161616]' : 'bg-[#f4f4f4] text-[#525252] hover:bg-white'}" data-tab-path="${path}">
                        <i data-lucide="${fileIconFor(entry)}" class="h-[18px] w-[18px] shrink-0"></i>
                        <span class="max-w-[180px] truncate">${escapeHtml(entry.file_name || path)}</span>
                        <i data-lucide="${tabStatusIconFor(path)}" class="h-3.5 w-3.5 shrink-0 ${tabStatusClassFor(path)}"></i>
                    </button>
                    <button type="button" class="inline-flex items-center justify-center border-l border-[#e0e0e0] px-2 text-[#8d8d8d] transition hover:bg-[#f4f4f4] hover:text-[#161616]" data-tab-close="${path}" aria-label="Close ${escapeHtml(entry.file_name || path)}">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            `;
        }).join('');
        iconsReady();
    };

    const escapeHtml = (value) => String(value || '').replace(/[&<>"]+/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));

    const renderNonEditable = (entry, content) => {
        nonEditablePanel?.classList.remove('hidden');
        nonEditablePanel?.classList.add('flex');
        if (nonEditableContent) {
            if (entry && ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'].includes(entry.file_extension || '')) {
                nonEditableContent.innerHTML = `<img src="${buildPreviewAssetUrl(entry.relative_path)}" alt="${escapeHtml(entry.file_name)}" class="mx-auto max-h-80 rounded-2xl border border-gray-200 object-contain">`;
            } else {
                nonEditableContent.textContent = content || 'Preview only.';
            }
        }
    };

    const hideNonEditable = () => {
        nonEditablePanel?.classList.add('hidden');
        nonEditablePanel?.classList.remove('flex');
    };

    const buildPreviewAssetUrl = (relativePath) => `${config.previewUrl}/${encodeURIComponent(relativePath).replace(/%2F/g, '/')}`;

    const ensureEditor = () => {
        if (state.editor || !editorNode || !window.require) {
            return Promise.resolve(state.editor);
        }

        return new Promise((resolve) => {
            window.require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.49.0/min/vs' } });
            window.require(['vs/editor/editor.main'], () => {
                state.editor = window.monaco.editor.create(editorNode, {
                    value: '',
                    language: 'html',
                    theme: 'vs',
                    automaticLayout: true,
                    lineNumbers: 'on',
                    fontSize: 14,
                    fontFamily: 'JetBrains Mono, Fira Code, Consolas, monospace',
                    minimap: { enabled: false },
                    scrollBeyondLastLine: false,
                    wordWrap: 'on',
                    renderWhitespace: 'selection',
                });

                state.editor.onDidChangeModelContent(() => {
                    if (!state.activeRelativePath) {
                        return;
                    }

                    state.dirty.add(state.activeRelativePath);
                    renderTabs();
                });

                window.addEventListener('resize', () => state.editor?.layout());
                resolve(state.editor);
            });
        });
    };

    const showEditorFor = async (entry, content) => {
        if (editableFor(entry)) {
            hideNonEditable();
            await ensureEditor();
            if (!state.editor) {
                return;
            }
            state.editor.updateOptions({ readOnly: false });
            const previousModel = state.editor.getModel();
            if (previousModel) {
                previousModel.dispose();
            }
            const model = window.monaco.editor.createModel(content || '', languageFor(entry.relative_path));
            state.editor.setModel(model);
            state.editor.focus();
            scheduleEditorLayout();
        } else {
            if (state.editor) {
                state.editor.setModel(null);
            }
            renderNonEditable(entry, content);
        }
        renderTabs();
    };

    const loadFile = async (relativePath, pushState = true) => {
        const entry = tree.find((item) => item.relative_path === relativePath) || null;
        clearInlineRename();
        setActiveRelativePath(relativePath);
        if (!state.openTabs.includes(relativePath)) {
            state.openTabs.push(relativePath);
        }
        displayTree();
        renderTabs();

        if (pushState) {
            const url = new URL(window.location.href);
            url.searchParams.set('file', relativePath);
            window.history.replaceState({}, '', url.toString());
        }

        if (entry && editableFor(entry)) {
            const response = await fetch(endpoints.getFile + `?id_project=${encodeURIComponent(projectId)}&relative_path=${encodeURIComponent(relativePath)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const payload = await response.json();
            setCsrf(payload);
            if (!payload.success) {
                throw new Error(payload.message || 'Gagal memuat file.');
            }
            await showEditorFor(entry, payload.data.file.content || '');
            return;
        }

        await showEditorFor(entry, '');
    };

    const selectedParentPath = () => {
        if (!state.activeEntry) {
            return '';
        }
        if (Number(state.activeEntry.is_folder) === 1) {
            return state.activeEntry.relative_path;
        }
        const parts = state.activeEntry.relative_path.split('/');
        parts.pop();
        return parts.join('/');
    };

    const populateModal = (mode) => {
        actionForm.mode.value = mode;
        actionForm.relative_path.value = state.activeRelativePath || '';
        actionForm.parent_path.value = selectedParentPath();
        actionForm.name.value = '';
        actionForm.asset.value = '';

        nameField?.classList.remove('hidden');
        fileField?.classList.add('hidden');
        actionSubmit.textContent = 'Save';
        actionSubmit.innerHTML = '<i data-lucide="check"></i> Save';

        if (mode === 'create-file') {
            modalTitle.textContent = 'Create File';
            modalDesc.textContent = 'Buat file baru di workspace.';
            actionForm.name.placeholder = 'style.css';
        } else if (mode === 'create-folder') {
            modalTitle.textContent = 'Create Folder';
            modalDesc.textContent = 'Buat folder baru di workspace.';
            actionForm.name.placeholder = 'assets';
        } else if (mode === 'rename') {
            modalTitle.textContent = 'Rename Item';
            modalDesc.textContent = 'Ubah nama file atau folder terpilih.';
            actionForm.name.placeholder = 'new-name';
            actionForm.name.value = state.activeEntry ? state.activeEntry.file_name : '';
        } else if (mode === 'upload') {
            modalTitle.textContent = 'Upload Asset';
            modalDesc.textContent = 'Upload file asset ke folder yang dipilih.';
            nameField?.classList.add('hidden');
            fileField?.classList.remove('hidden');
            actionSubmit.textContent = 'Upload';
            actionSubmit.innerHTML = '<i data-lucide="upload"></i> Upload';
        }
        iconsReady();
        openModal();
    };

    const submitAction = async (event) => {
        event.preventDefault();
        const mode = actionForm.mode.value;
        const formData = new FormData();
        formData.set('id_project', projectId);
        formData.set(state.csrfName, state.csrfHash);
        formData.set('parent_path', actionForm.parent_path.value || '');

        try {
            let payload;
            if (mode === 'create-file') {
                formData.set('file_name', actionForm.name.value.trim());
                payload = await requestJSON(endpoints.createFile, formData);
            } else if (mode === 'create-folder') {
                formData.set('folder_name', actionForm.name.value.trim());
                payload = await requestJSON(endpoints.createFolder, formData);
            } else if (mode === 'rename') {
                formData.set('relative_path', actionForm.relative_path.value);
                formData.set('new_name', actionForm.name.value.trim());
                payload = await requestJSON(endpoints.renameFile, formData);
            } else if (mode === 'upload') {
                const uploadInput = actionForm.querySelector('input[name="asset"]');
                formData.append('asset', uploadInput.files[0]);
                payload = await requestJSON(endpoints.uploadFile, formData);
            }

            closeModal();
            showToast(payload.message);
            window.location.reload();
        } catch (error) {
            showToast(error.message, 'error');
        }
    };

    const refreshTree = async (focusPath = state.activeRelativePath, reloadContent = false) => {
        if (reloadContent && focusPath) {
            await loadFile(focusPath, false).catch((error) => showToast(error.message, 'error'));
            return;
        }

        displayTree();
        renderTabs();
    };

    const setMobileView = (view) => {
        state.mobileView = view;
        if (view === 'preview') {
            state.previewEnabled = true;
            applyPreviewLayout();
        }
        mobileButtons.forEach((button) => {
            const active = button.dataset.mobileTab === view;
            button.classList.toggle('bg-white', active);
            button.classList.toggle('text-gray-900', active);
            button.classList.toggle('shadow-sm', active);
        });

        const sidebar = root.querySelector('aside');
        const mainPane = root.querySelector('[data-pane="editor"]');
        const preview = previewPanel;

        if (sidebar) sidebar.classList.toggle('hidden', view !== 'files');
        if (mainPane) mainPane.classList.toggle('hidden', view !== 'editor');
        if (preview) preview.classList.toggle('hidden', view !== 'preview');

        requestAnimationFrame(() => {
            refreshPreviewSize();
            scheduleEditorLayout();
        });
    };

    state.previewHeight = loadPreviewHeight();
    applyPreviewLayout();
    requestAnimationFrame(() => refreshPreviewSize());

    previewResizeHandle?.addEventListener('mousedown', (event) => {
        if (!state.previewEnabled) {
            return;
        }

        event.preventDefault();
        state.isResizingPreview = true;
        state.previewResizeStartY = event.clientY;
        state.previewResizeStartHeight = state.previewHeight;
        document.body.style.cursor = 'row-resize';
        document.body.style.userSelect = 'none';
    });

    treeRoot?.addEventListener('click', async (event) => {
        const renameStartButton = event.target.closest('[data-inline-rename-start]');
        if (renameStartButton) {
            event.preventDefault();
            event.stopPropagation();
            beginInlineRename(renameStartButton.dataset.inlineRenameStart);
            return;
        }

        const renameSaveButton = event.target.closest('[data-inline-rename-save]');
        if (renameSaveButton) {
            event.preventDefault();
            event.stopPropagation();
            state.inlineRenamePath = renameSaveButton.dataset.inlineRenameSave;
            await commitInlineRename();
            return;
        }

        const renameCancelButton = event.target.closest('[data-inline-rename-cancel]');
        if (renameCancelButton) {
            event.preventDefault();
            event.stopPropagation();
            clearInlineRename();
            displayTree();
            return;
        }

        const deleteButton = event.target.closest('[data-inline-delete]');
        if (deleteButton) {
            event.preventDefault();
            event.stopPropagation();
            await deleteInlineFile(deleteButton.dataset.inlineDelete);
            return;
        }

        const button = event.target.closest('[data-tree-item]');
        if (!button) {
            return;
        }

        const path = button.dataset.treeItem;
        const entry = tree.find((item) => item.relative_path === path) || null;
        setActiveRelativePath(path);
        displayTree();
        renderTabs();
        try {
            await loadFile(path, true);
        } catch (error) {
            showToast(error.message, 'error');
        }
        if (window.innerWidth < 1024) {
            setMobileView('editor');
        }
    });

    treeRoot?.addEventListener('keydown', async (event) => {
        const input = event.target.closest('[data-inline-rename-input]');
        if (!input || !state.inlineRenamePath) {
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            state.inlineRenameValue = input.value;
            await commitInlineRename();
        } else if (event.key === 'Escape') {
            event.preventDefault();
            clearInlineRename();
            displayTree();
        }
    });

    treeRoot?.addEventListener('blur', async (event) => {
        const input = event.target.closest('[data-inline-rename-input]');
        if (!input || !state.inlineRenamePath) {
            return;
        }

        state.inlineRenameValue = input.value;
        await commitInlineRename();
    }, true);

    tabBar?.addEventListener('click', async (event) => {
        const closeButton = event.target.closest('[data-tab-close]');
        if (closeButton) {
            event.preventDefault();
            event.stopPropagation();

            const closingPath = closeButton.dataset.tabClose;
            state.openTabs = state.openTabs.filter((path) => path !== closingPath);

            if (state.activeRelativePath === closingPath) {
                setActiveRelativePath(state.openTabs[state.openTabs.length - 1] || config.selectedPath || 'index.html');
            }

            if (!state.openTabs.includes(state.activeRelativePath)) {
                state.openTabs.push(state.activeRelativePath);
            }

            displayTree();
            renderTabs();

            try {
                await loadFile(state.activeRelativePath, true);
            } catch (error) {
                showToast(error.message, 'error');
            }

            return;
        }

        const button = event.target.closest('[data-tab-path]');
        if (!button) {
            return;
        }

        await loadFile(button.dataset.tabPath, true).catch((error) => showToast(error.message, 'error'));
    });

    root.querySelectorAll('[data-create-file]').forEach((button) => button.addEventListener('click', () => populateModal('create-file')));
    root.querySelectorAll('[data-create-folder]').forEach((button) => button.addEventListener('click', () => populateModal('create-folder')));
    root.querySelectorAll('[data-upload-file]').forEach((button) => button.addEventListener('click', () => populateModal('upload')));
    root.querySelectorAll('[data-refresh-tree]').forEach((button) => button.addEventListener('click', () => window.location.reload()));

    saveButton?.addEventListener('click', async () => {
        if (!state.editor || !state.activeRelativePath || !editableFor(state.activeEntry)) {
            return;
        }

        const formData = new FormData();
        formData.set('id_project', projectId);
        formData.set('relative_path', state.activeRelativePath);
        formData.set('content', state.editor.getValue());
        try {
            const payload = await requestJSON(endpoints.saveFile, formData);
            state.dirty.delete(state.activeRelativePath);
            state.previewDirty = true;
            showToast(payload.message);
            renderTabs();
            displayTree();

            if (state.previewEnabled) {
                requestAnimationFrame(() => {
                    refreshPreview();
                    refreshPreviewSize();
                });
            }
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    actionForm?.addEventListener('submit', submitAction);
    root.querySelectorAll('[data-close-action-modal]').forEach((button) => button.addEventListener('click', closeModal));
    root.querySelectorAll('[data-preview-toggle]').forEach((button) => button.addEventListener('click', () => {
        if (state.previewEnabled) {
            hidePreviewPanel();
            return;
        }

        showPreviewPanel();
    }));
    root.querySelectorAll('[data-preview-close]').forEach((button) => button.addEventListener('click', () => {
        hidePreviewPanel();
    }));
    root.querySelectorAll('[data-preview-refresh]').forEach((button) => button.addEventListener('click', () => {
        refreshPreview();
    }));
    root.querySelectorAll('[data-preview-newtab]').forEach((button) => button.addEventListener('click', () => {
        window.open(config.previewUrl, '_blank', 'noopener');
    }));
    root.querySelectorAll('[data-publish-project]').forEach((button) => button.addEventListener('click', async () => {
        try {
            button.disabled = true;
            button.innerHTML = '<i data-lucide="loader-circle"></i><span>Publishing...</span>';
            iconsReady();
            const formData = new FormData();
            formData.set('id_project', projectId);
            const payload = await requestJSON(endpoints.publish, formData);
            showToast(payload.message);
            openPublishModal(payload.data.public_url);
        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = '<i data-lucide="globe"></i><span class="hidden sm:inline">Publish</span>';
            iconsReady();
        }
    }));
    root.querySelectorAll('[data-copy-public-url]').forEach((button) => button.addEventListener('click', async () => {
        if (!publishUrlNode) return;
        await copyText(publishUrlNode.textContent || publishUrlNode.href);
        showToast('Link copied.');
    }));
    root.querySelectorAll('[data-close-publish-modal]').forEach((button) => button.addEventListener('click', closePublishModal));
    publishModal?.addEventListener('click', (event) => {
        if (event.target === publishModal) {
            closePublishModal();
        }
    });
    actionModal?.addEventListener('click', (event) => {
        if (event.target === actionModal) {
            closeModal();
        }
    });

    mobileButtons.forEach((button) => button.addEventListener('click', () => setMobileView(button.dataset.mobileTab)));

    document.addEventListener('keydown', async (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
            event.preventDefault();
            saveButton?.click();
        }
    });

    const sidebarStorageKey = 'webdrop-editor-sidebar';
    const clampSidebarWidth = (value) => Math.max(220, Math.min(480, value));

    const applySidebarState = () => {
        if (!sidebar) {
            return;
        }

        sidebar.dataset.collapsed = state.sidebarCollapsed ? 'true' : 'false';
        const appliedWidth = state.sidebarCollapsed ? 56 : clampSidebarWidth(state.sidebarWidth);
        sidebar.style.setProperty('--sidebar-width', appliedWidth + 'px');
        sidebar.style.width = 'var(--sidebar-width)';
        const contentNodes = sidebar.querySelectorAll('[data-sidebar-label], [data-sidebar-actions], [data-tree-root]');
        contentNodes.forEach((node) => {
            node.classList.toggle('hidden', state.sidebarCollapsed);
        });
        sidebarExpandButton?.closest('[data-sidebar-rail]')?.classList.toggle('hidden', !state.sidebarCollapsed);
        if (sidebarCollapseButton) {
            sidebarCollapseButton.innerHTML = state.sidebarCollapsed ? '<i data-lucide="panel-left-open"></i>' : '<i data-lucide="panel-left-close"></i>';
            sidebarCollapseButton.title = state.sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar';
        }
        iconsReady();
        scheduleEditorLayout();
    };

    const refreshSidebarLayout = () => {
        if (!sidebar) {
            return;
        }

        const nextWidth = clampSidebarWidth(state.sidebarWidth);
        state.sidebarWidth = nextWidth;
        sidebar.style.setProperty('--sidebar-width', nextWidth + 'px');
        sidebar.style.width = nextWidth + 'px';
        root.classList.toggle('wd-sidebar-resizing', state.isResizingSidebar);
        requestAnimationFrame(() => {
            scheduleEditorLayout();
        });
    };

    const loadSidebarPreferences = () => {
        try {
            const saved = JSON.parse(localStorage.getItem(sidebarStorageKey) || '{}');
            if (typeof saved.width === 'number') {
                state.sidebarWidth = clampSidebarWidth(saved.width);
            }
            if (typeof saved.collapsed === 'boolean') {
                state.sidebarCollapsed = saved.collapsed;
            }
        } catch (error) {
            return;
        }
    };

    const saveSidebarPreferences = () => {
        localStorage.setItem(sidebarStorageKey, JSON.stringify({ width: state.sidebarWidth, collapsed: state.sidebarCollapsed }));
    };

    sidebarCollapseButton?.addEventListener('click', () => {
        state.sidebarCollapsed = !state.sidebarCollapsed;
        saveSidebarPreferences();
        applySidebarState();
    });

    sidebarExpandButton?.addEventListener('click', () => {
        if (!state.sidebarCollapsed) {
            return;
        }

        state.sidebarCollapsed = false;
        saveSidebarPreferences();
        applySidebarState();
    });

    sidebarCreateToggle?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        toggleCreateMenu();
    });

    sidebarCreateMenu?.addEventListener('click', (event) => {
        const target = event.target.closest('[data-create-file], [data-create-folder], [data-upload-file]');
        if (!target) {
            return;
        }

        closeCreateMenu();
    });

    document.addEventListener('click', (event) => {
        if (sidebarCreateMenu && !sidebarCreateMenu.classList.contains('hidden')) {
            const insideSidebarCreate = event.target.closest('[data-sidebar-create]');
            if (!insideSidebarCreate) {
                closeCreateMenu();
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeCreateMenu();
        }
    });

    sidebarResizeHandle?.addEventListener('mousedown', (event) => {
        if (!sidebar || state.sidebarCollapsed) {
            return;
        }

        event.preventDefault();
        state.isResizingSidebar = true;
        state.sidebarResizeStartX = event.clientX;
        state.sidebarResizeStartWidth = state.sidebarWidth;
        root.classList.add('wd-sidebar-resizing');
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', (event) => {
        if (!state.isResizingSidebar || !sidebar || state.sidebarCollapsed) {
            return;
        }

        const delta = event.clientX - state.sidebarResizeStartX;
        const nextWidth = clampSidebarWidth(state.sidebarResizeStartWidth + delta);
        if (nextWidth === state.sidebarWidth) {
            return;
        }

        state.sidebarWidth = nextWidth;
        sidebar.style.setProperty('--sidebar-width', nextWidth + 'px');
        sidebar.style.width = nextWidth + 'px';
        requestAnimationFrame(() => {
            scheduleEditorLayout();
        });
    });

    document.addEventListener('mouseup', () => {
        if (!state.isResizingSidebar) {
            return;
        }

        state.isResizingSidebar = false;
        root.classList.remove('wd-sidebar-resizing');
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        saveSidebarPreferences();
        refreshSidebarLayout();
    });

    window.addEventListener('resize', () => {
        state.previewHeight = clampPreviewHeight(state.previewHeight);
        applyPreviewLayout();
        if (window.innerWidth >= 1024) {
            root.querySelector('aside')?.classList.remove('hidden');
            root.querySelector('[data-pane="editor"]')?.classList.remove('hidden');
            previewPanel?.classList.remove('hidden');
        }
        requestAnimationFrame(() => {
            refreshPreviewSize();
            scheduleEditorLayout();
        });
    });

    document.addEventListener('mousemove', (event) => {
        if (!state.isResizingPreview) {
            return;
        }

        const delta = state.previewResizeStartY - event.clientY;
        setPreviewHeight(state.previewResizeStartHeight + delta, false);
    });

    document.addEventListener('mouseup', () => {
        if (!state.isResizingPreview) {
            return;
        }

        state.isResizingPreview = false;
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        savePreviewHeight();
        requestAnimationFrame(() => refreshPreviewSize());
    });

    previewFrame?.addEventListener('load', () => {
        refreshPreviewSize();
    });

    if (window.innerWidth < 1024) {
        setMobileView('editor');
    }

    loadSidebarPreferences();
    applySidebarState();

    displayTree();
    renderTabs();
    iconsReady();

    if (config.selectedPath) {
        loadFile(config.selectedPath, false).catch(() => {
            if (config.selectedError) {
                showToast(config.selectedError, 'error');
            }
        });
    }
})();