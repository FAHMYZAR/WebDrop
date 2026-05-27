<?php
$tree = isset($tree) && is_array($tree) ? $tree : array();
$selectedPath = isset($selected_path) ? $selected_path : 'index.html';
$selectedEntry = isset($selected_entry) ? $selected_entry : null;
$selectedContent = isset($selected_content) ? $selected_content : '';
$selectedError = isset($selected_error) ? $selected_error : null;
$projectName = isset($project->project_name) ? $project->project_name : 'Project';
$projectId = (int) $project->id_project;
?>
<div id="wd-editor" class="wd-editor-shell h-full flex flex-col overflow-hidden bg-slate-50" data-project-id="<?php echo $projectId; ?>" data-preview-url="<?php echo html_escape($preview_url); ?>" data-project-name="<?php echo html_escape($projectName); ?>">
    <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur">
        <div class="flex h-14 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <a href="<?php echo site_url('dashboard'); ?>" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-300 bg-white text-gray-600 hover:bg-gray-50"><i data-lucide="arrow-left" class="h-4 w-4"></i></a>
                <div class="min-w-0">
                    <div class="flex min-w-0 items-center gap-2 text-sm text-gray-500">
                        <span class="font-extrabold text-gray-900">WebDrop</span>
                        <span>/</span>
                        <span class="truncate font-semibold text-gray-900"><?php echo html_escape($projectName); ?></span>
                    </div>
                    <p class="truncate text-xs text-gray-500">Dashboard / <?php echo html_escape($projectName); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" data-save-file class="inline-flex items-center gap-2 border border-[#e0e0e0] bg-white px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]"><i data-lucide="save" class="h-4 w-4"></i><span class="hidden sm:inline">Save</span></button>
                <button type="button" data-preview-toggle class="inline-flex items-center gap-2 border border-[#e0e0e0] bg-white px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]"><i data-lucide="eye" class="h-4 w-4"></i><span class="hidden sm:inline">Preview</span></button>
                <div class="relative" data-publish-dropdown>
                    <button type="button" data-publish-toggle class="inline-flex items-center gap-2 border border-[#0f62fe] bg-[#0f62fe] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0353e9]"><i data-lucide="globe" class="h-4 w-4"></i><span class="hidden sm:inline">Publish</span><i data-lucide="chevron-down" class="h-4 w-4"></i></button>
                    <div class="absolute right-0 top-full z-50 mt-2 hidden w-48 border border-[#e0e0e0] bg-white shadow-sm" data-publish-menu>
                        <button type="button" data-publish-now class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-[#525252] hover:bg-[#f4f4f4] hover:text-[#161616]"><i data-lucide="globe" class="h-4 w-4"></i><span>Publish Now</span></button>
                        <button type="button" data-save-draft class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-[#525252] hover:bg-[#f4f4f4] hover:text-[#161616]"><i data-lucide="file-text" class="h-4 w-4"></i><span>Save as Draft</span></button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex min-h-0 flex-1 flex-col overflow-hidden lg:flex-row">
        <aside class="wd-scrollbar hidden shrink-0 border-r border-[#e0e0e0] bg-white lg:flex lg:flex-col" data-sidebar data-collapsed="false" style="--sidebar-width:280px; flex:0 0 var(--sidebar-width); width:var(--sidebar-width);">
            <div class="border-b border-[#e0e0e0] px-4 py-4" data-sidebar-head>
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0" data-sidebar-label>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-gray-500">FILES</p>
                    </div>
                    <div class="flex items-center gap-1 text-[#525252]" data-sidebar-actions>
                        <div class="relative inline-flex" data-sidebar-create>
                            <button type="button" data-sidebar-create-toggle class="inline-flex h-8 w-8 items-center justify-center rounded-sm hover:bg-[#f4f4f4] hover:text-[#161616]" title="Create" aria-haspopup="menu" aria-expanded="false"><i data-lucide="plus" class="h-4 w-4"></i></button>
                            <div class="absolute right-0 top-full z-50 mt-2 hidden w-48 border border-[#e0e0e0] bg-white shadow-sm" data-sidebar-create-menu role="menu">
                                <button type="button" data-create-file class="flex h-9 w-full items-center gap-2 px-3 text-left text-sm text-[#525252] hover:bg-[#f4f4f4] hover:text-[#161616]" role="menuitem"><i data-lucide="file-plus" class="h-4 w-4"></i><span>New file</span></button>
                                <button type="button" data-create-folder class="flex h-9 w-full items-center gap-2 px-3 text-left text-sm text-[#525252] hover:bg-[#f4f4f4] hover:text-[#161616]" role="menuitem"><i data-lucide="folder-plus" class="h-4 w-4"></i><span>New folder</span></button>
                                <button type="button" data-upload-file class="flex h-9 w-full items-center gap-2 px-3 text-left text-sm text-[#525252] hover:bg-[#f4f4f4] hover:text-[#161616]" role="menuitem"><i data-lucide="upload" class="h-4 w-4"></i><span>Upload file</span></button>
                            </div>
                        </div>
                        <button type="button" data-refresh-tree class="inline-flex h-8 w-8 items-center justify-center rounded-sm hover:bg-[#f4f4f4] hover:text-[#161616]" title="Refresh"><i data-lucide="refresh-cw" class="h-4 w-4"></i></button>
                        <button type="button" data-sidebar-collapse class="inline-flex h-8 w-8 items-center justify-center rounded-sm hover:bg-[#f4f4f4] hover:text-[#161616]" data-sidebar-toggle="collapse" title="Collapse sidebar"><i data-lucide="panel-left-close" class="h-4 w-4"></i></button>
                    </div>
                </div>
            </div>
            <div class="hidden px-2 py-2" data-sidebar-rail>
                <button type="button" data-sidebar-expand class="inline-flex h-8 w-8 items-center justify-center rounded-sm hover:bg-[#f4f4f4] hover:text-[#161616]" data-sidebar-toggle="expand" title="Expand sidebar"><i data-lucide="panel-left-open" class="h-4 w-4"></i></button>
            </div>
            <div class="wd-scrollbar flex-1 overflow-y-auto px-3 py-4" data-tree-root></div>
            <div class="hidden lg:block" data-sidebar-resize-handle aria-hidden="true"></div>
        </aside>

        <main class="relative flex min-w-0 flex-1 flex-col overflow-hidden border-t border-[#e0e0e0] bg-white lg:border-t-0">
            <div class="border-b border-[#e0e0e0] bg-white">
                <div class="flex items-center gap-2 overflow-x-auto px-3 py-2" data-tab-bar></div>
            </div>

            <section class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <div class="flex items-center justify-between border-b border-[#f4f4f4] px-4 py-3 lg:hidden">
                    <div class="flex items-center gap-2 bg-[#f4f4f4] p-1 text-sm font-semibold text-[#525252]" data-mobile-switcher>
                        <button type="button" data-mobile-tab="files" class="bg-white px-3 py-1.5 text-[#161616]">Files</button>
                        <button type="button" data-mobile-tab="editor" class="px-3 py-1.5">Editor</button>
                        <button type="button" data-mobile-tab="preview" class="px-3 py-1.5">Preview</button>
                    </div>
                    <button type="button" data-open-actions class="border border-[#e0e0e0] bg-white px-3 py-2 text-sm font-semibold text-[#525252]"><i data-lucide="ellipsis"></i></button>
                </div>

                <div class="relative flex min-h-0 flex-1 flex-col overflow-hidden" data-editor-layout>
                    <div class="wd-editor-pane flex min-h-0 flex-1 flex-col" data-pane="editor">
                        <div class="relative flex min-h-0 flex-1 overflow-hidden bg-white">
                            <div id="wd-monaco" class="wd-monaco-wrap h-full w-full flex-1 min-h-0"></div>
                            <div class="absolute inset-0 hidden items-center justify-center bg-white px-6 text-center" data-noneditable-panel>
                                <div class="max-w-md border border-[#e0e0e0] bg-[#f4f4f4] p-6">
                                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center border border-[#e0e0e0] bg-white text-[#0f62fe]"><i data-lucide="file-text" class="h-5 w-5"></i></div>
                                    <h3 class="text-lg font-semibold text-[#161616]" data-noneditable-title>Preview only</h3>
                                    <p class="mt-2 text-sm text-[#525252]" data-noneditable-desc>File ini tidak dibuka di Monaco. Gunakan preview panel atau file info.</p>
                                    <pre class="mt-4 max-h-48 overflow-auto border border-[#e0e0e0] bg-white p-4 text-left text-xs text-[#525252] wd-scrollbar" data-noneditable-content></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <section class="wd-preview-panel flex-none flex min-h-0 flex-col overflow-hidden border-t border-[#e0e0e0] bg-white" data-preview-panel data-open="false" style="height:0px;">
                        <div class="h-2 cursor-row-resize border-b border-[#e0e0e0] bg-white hover:bg-[#f4f4f4]" data-preview-resize-handle></div>
                        <div class="preview-header flex h-10 flex-none items-center justify-between border-b border-[#e0e0e0] px-4">
                            <div class="flex items-center gap-2 text-sm font-semibold text-[#161616]"><i data-lucide="monitor" class="h-4 w-4"></i><span>Preview</span></div>
                            <div class="flex items-center gap-2">
                                <button type="button" data-preview-refresh class="border border-[#e0e0e0] bg-white px-3 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]"><i data-lucide="refresh-cw" class="h-4 w-4"></i></button>
                                <button type="button" data-preview-newtab class="border border-[#e0e0e0] bg-white px-3 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]"><i data-lucide="external-link" class="h-4 w-4"></i></button>
                                <button type="button" data-preview-close class="border border-[#e0e0e0] bg-white px-3 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]"><i data-lucide="x" class="h-4 w-4"></i></button>
                            </div>
                        </div>
                        <div class="preview-body flex min-h-0 flex-1 overflow-hidden bg-white" data-preview-body>
                            <iframe id="previewFrame" data-preview-frame data-src="<?php echo html_escape($preview_url); ?>" sandbox="allow-scripts allow-forms allow-same-origin" class="block h-full w-full border-0" src="<?php echo html_escape($preview_url); ?>?t=<?php echo time(); ?>" title="Preview <?php echo html_escape($projectName); ?>"></iframe>
                        </div>
                    </section>
                </div>
            </section>
        </main>
    </div>

    <div id="wd-action-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-[#161616]/20 px-4 py-6">
        <div class="w-full max-w-lg border border-[#e0e0e0] bg-white p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900" data-modal-title>Action</h2>
                    <p class="mt-1 text-sm text-gray-500" data-modal-desc>Isi detail lalu lanjutkan.</p>
                </div>
                <button type="button" data-close-action-modal class="bg-[#f4f4f4] p-2 text-[#525252] hover:bg-[#e0e0e0]"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <form class="mt-5 space-y-4" data-action-form>
                <input type="hidden" name="mode" value="">
                <input type="hidden" name="relative_path" value="<?php echo html_escape($selectedPath); ?>">
                <input type="hidden" name="parent_path" value="">
                <label class="block text-sm font-medium text-gray-700" data-name-field>
                    <span class="mb-1 block">Name</span>
                    <input type="text" name="name" class="w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm outline-none focus:border-[#0f62fe]" placeholder="file.html">
                </label>
                <label class="block text-sm font-medium text-gray-700 hidden" data-file-field>
                    <span class="mb-1 block">Upload file</span>
                    <input type="file" name="asset" class="block w-full border border-[#e0e0e0] bg-white px-4 py-3 text-sm">
                </label>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" data-close-action-modal class="border border-[#e0e0e0] px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]">Cancel</button>
                    <button type="submit" class="inline-flex items-center gap-2 border border-[#0f62fe] bg-[#0f62fe] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0353e9]" data-action-submit><i data-lucide="check" class="h-4 w-4"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="wd-publish-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/30 px-4 py-6 backdrop-blur-sm">
        <div class="w-full max-w-lg border border-[#e0e0e0] bg-white p-6">
            <h2 class="text-lg font-semibold text-[#161616]">Project published successfully!</h2>
            <p class="mt-2 text-sm text-[#525252]">Public URL tersedia di bawah ini.</p>
            <div class="mt-4 border border-[#e0e0e0] bg-[#f4f4f4] p-4 text-sm text-[#525252]">
                <a href="#" target="_blank" rel="noopener noreferrer" data-public-url class="break-all font-medium text-[#0f62fe]"></a>
            </div>
            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" data-copy-public-url class="border border-[#e0e0e0] px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]">Copy Link</button>
                <a href="#" target="_blank" rel="noopener noreferrer" data-open-public-url class="border border-[#0f62fe] bg-[#0f62fe] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0353e9]">Open Site</a>
                <button type="button" data-close-publish-modal class="border border-[#e0e0e0] px-4 py-2 text-sm font-semibold text-[#525252] hover:bg-[#f4f4f4]">Close</button>
            </div>
        </div>
    </div>

    <div id="wd-editor-toast" class="fixed right-4 top-4 z-50 hidden border px-4 py-3 text-sm font-medium"></div>

    <script>
        window.WebDropEditor = {
            projectId: <?php echo $projectId; ?>,
            projectName: <?php echo json_encode($projectName); ?>,
            selectedPath: <?php echo json_encode($selectedPath); ?>,
            selectedContent: <?php echo json_encode($selectedContent); ?>,
            selectedEntry: <?php echo json_encode($selectedEntry); ?>,
            selectedError: <?php echo json_encode($selectedError); ?>,
            previewUrl: <?php echo json_encode($preview_url); ?>,
            tree: <?php echo json_encode($tree); ?>,
            endpoints: {
                getFile: <?php echo json_encode(site_url('projects/get_file_content')); ?>,
                saveFile: <?php echo json_encode(site_url('projects/save_file')); ?>,
                createFile: <?php echo json_encode(site_url('projects/create_file')); ?>,
                createFolder: <?php echo json_encode(site_url('projects/create_folder')); ?>,
                renameFile: <?php echo json_encode(site_url('projects/rename_file')); ?>,
                deleteFile: <?php echo json_encode(site_url('projects/delete_file')); ?>,
                uploadFile: <?php echo json_encode(site_url('projects/upload_file')); ?>,
                publish: <?php echo json_encode(site_url('projects/publish/' . $projectId)); ?>,
                saveDraft: <?php echo json_encode(site_url('projects/savedraft')); ?>
            },
            csrfName: <?php echo json_encode($this->security->get_csrf_token_name()); ?>,
            csrfHash: <?php echo json_encode($this->security->get_csrf_hash()); ?>,
        };
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.49.0/min/vs/loader.min.js"></script>
    <script src="<?php echo base_url('assets/js/webdrop-editor.js'); ?>" defer></script>
</div>
