<section class="editor-topbar">
    <div class="editor-topbar__left">
        <a href="<?php echo site_url('dashboard'); ?>" class="editor-icon-btn">←</a>
        <div class="editor-brand">
            <span class="editor-brand__name">WebDrop</span>
            <span class="editor-brand__sep">/</span>
            <span class="editor-brand__project"><?php echo html_escape($project->project_name); ?></span>
            <?php if ($project->status === 'published'): ?>
                <span class="editor-published">Published</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="editor-topbar__right">
        <button type="button" class="editor-action" data-preview-toggle>Preview</button>
        <a href="<?php echo site_url('publish/' . $project->id_project); ?>" class="editor-publish">Publish</a>
    </div>
</section>

<section class="editor-shell">
    <aside class="editor-sidebar">
        <div class="editor-sidebar__head">
            <span>Files</span>
        </div>
        <div class="editor-sidebar__toolbar">
            <?php echo form_open('editor/' . $project->id_project . '/create-file', array('class' => 'mini-form')); ?>
                <input type="hidden" name="parent_path" value="">
                <input type="text" name="file_name" placeholder="new.html" required>
                <button type="submit">+ File</button>
            <?php echo form_close(); ?>
            <?php echo form_open('editor/' . $project->id_project . '/create-folder', array('class' => 'mini-form')); ?>
                <input type="hidden" name="parent_path" value="">
                <input type="text" name="folder_name" placeholder="assets" required>
                <button type="submit">+ Folder</button>
            <?php echo form_close(); ?>
        </div>
        <div class="editor-filelist">
            <?php foreach ($tree as $item): ?>
                <?php if ((int) $item->is_folder === 1): ?>
                    <div class="editor-file editor-file--folder"><?php echo html_escape($item->relative_path); ?></div>
                <?php else: ?>
                    <a
                        class="editor-file <?php echo $selected_path === $item->relative_path ? 'is-active' : ''; ?>"
                        href="<?php echo site_url('editor/' . $project->id_project . '?file=' . rawurlencode($item->relative_path)); ?>"
                    >
                        <?php echo html_escape($item->relative_path); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="editor-sidebar__forms">
            <?php echo form_open('editor/' . $project->id_project . '/rename', array('class' => 'mini-form')); ?>
                <input type="text" name="path" placeholder="path/lama" required>
                <input type="text" name="new_name" placeholder="nama-baru" required>
                <button type="submit">Rename</button>
            <?php echo form_close(); ?>
            <?php echo form_open('editor/' . $project->id_project . '/delete', array('class' => 'mini-form')); ?>
                <input type="text" name="path" placeholder="path/target" required>
                <button type="submit" class="danger">Delete</button>
            <?php echo form_close(); ?>
            <?php echo form_open_multipart('editor/' . $project->id_project . '/upload', array('class' => 'mini-form')); ?>
                <input type="text" name="parent_path" placeholder="assets/opsional">
                <input type="file" name="asset" required>
                <button type="submit">Upload</button>
            <?php echo form_close(); ?>
        </div>
    </aside>

    <section class="editor-workspace">
        <div class="editor-tabbar">
            <div class="editor-tab editor-tab--active"><?php echo html_escape($selected_path); ?></div>
        </div>
        <div class="editor-canvas">
            <?php if ($selected_error): ?>
                <div class="editor-empty"><?php echo html_escape($selected_error); ?></div>
            <?php else: ?>
                <?php echo form_open('editor/' . $project->id_project . '/save', array('class' => 'editor-save-form')); ?>
                    <input type="hidden" name="path" value="<?php echo html_escape($selected_path); ?>">
                    <textarea name="content" class="editor-code" spellcheck="false"><?php echo html_escape($selected_content); ?></textarea>
                    <button type="submit" class="editor-save-button">Save</button>
                <?php echo form_close(); ?>
            <?php endif; ?>
        </div>
    </section>
</section>

<section class="preview-drawer" data-preview-drawer>
    <div class="preview-drawer__head">
        <div>Live Preview</div>
        <div class="preview-drawer__actions">
            <button type="button" data-preview-reload>Reload</button>
            <a href="<?php echo html_escape($preview_url); ?>" target="_blank" rel="noopener noreferrer">Open</a>
            <button type="button" data-preview-close>Close</button>
        </div>
    </div>
    <div class="preview-drawer__body">
        <iframe
            src="<?php echo html_escape($preview_url); ?>"
            data-preview-frame
            title="Preview <?php echo html_escape($project->project_name); ?>"
        ></iframe>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var drawer = document.querySelector('[data-preview-drawer]');
    var toggle = document.querySelector('[data-preview-toggle]');
    var close = document.querySelector('[data-preview-close]');
    var reload = document.querySelector('[data-preview-reload]');
    var frame = document.querySelector('[data-preview-frame]');

    if (!drawer || !toggle || !close || !reload || !frame) {
        return;
    }

    toggle.addEventListener('click', function () {
        drawer.classList.toggle('is-open');
    });

    close.addEventListener('click', function () {
        drawer.classList.remove('is-open');
    });

    reload.addEventListener('click', function () {
        frame.src = frame.src.split('?')[0] + '?t=' + Date.now();
    });
});
</script>
