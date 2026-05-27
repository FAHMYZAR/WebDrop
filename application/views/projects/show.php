<section class="page-header page-header--row">
    <div>
        <h1><?php echo html_escape($project->project_name); ?></h1>
        <p>Slug: <?php echo html_escape($project->slug); ?></p>
    </div>
    <div class="plain-actions">
        <a class="secondary-link" href="<?php echo site_url('dashboard'); ?>">Kembali</a>
        <a class="primary-link" href="<?php echo site_url('projects/editor/' . $project->id_project); ?>">Buka Editor</a>
    </div>
</section>

<section class="settings-card">
    <dl class="detail-grid">
        <div><dt>Status</dt><dd><?php echo html_escape($project->status); ?></dd></div>
        <div><dt>Workspace</dt><dd><?php echo html_escape($project->workspace_path); ?></dd></div>
        <div><dt>Public URL</dt><dd><?php echo $project->public_url ? html_escape($project->public_url) : '-'; ?></dd></div>
        <div><dt>Last Published</dt><dd><?php echo format_datetime_id($project->last_published_at); ?></dd></div>
    </dl>
</section>
