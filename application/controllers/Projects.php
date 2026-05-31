<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Project_model', 'Activity_log_model', 'File_model'));
        $this->load->library(array('ProjectManager', 'FileManagerService'));
    }

    public function create()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->projectmanager->createProject($this->user(), $this->input->post('project_name', true));

            $this->jsonResponse(true, 'Project berhasil dibuat.', array(
                'project' => $this->projectPayload($project),
                'redirect' => site_url('projects/editor/' . $project->id_project),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function delete_project()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $this->projectmanager->deleteProject($project, $this->user());
            $this->jsonResponse(true, 'Project berhasil dihapus.');
        } catch (Throwable $exception) {
            log_message('error', 'Delete project failed: ' . $exception->getMessage());
            $this->jsonResponse(false, $exception->getMessage(), array(), 500);
        }
    }

    public function rename_project()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $projectName = trim((string) $this->input->post('project_name', true));
            $updatedProject = $this->projectmanager->renameProject($project, $this->user(), $projectName);

            try {
                $this->Activity_log_model->create($this->user()->id_user, 'rename_project', 'Project diubah: ' . $project->project_name . ' -> ' . $updatedProject->project_name, $project->id_project);
            } catch (Throwable $exception) {
                log_message('error', 'Rename project log failed: ' . $exception->getMessage());
            }

            $this->jsonResponse(true, 'Project berhasil di-rename.', array(
                'project' => $this->projectPayload($updatedProject),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function editor($projectId)
    {
        $project = $this->requireProjectForUser((int) $projectId);
        $selectedPath = $this->input->get('file', true) ?: 'index.html';
        $selectedEntry = null;
        $selectedContent = null;
        $selectedError = null;

        try {
            $selectedEntry = $this->filemanagerservice->findFileEntry($project, $selectedPath);

            if ($selectedEntry && (int) $selectedEntry->is_folder === 0 && (int) $selectedEntry->is_editable === 1) {
                $selectedContent = $this->filemanagerservice->readFile($project, $selectedPath);
            } else {
                $selectedError = 'File ini tidak bisa diedit di Monaco.';
            }
        } catch (Throwable $exception) {
            $selectedError = $exception->getMessage();
        }

        $this->render('projects/editor', array(
            'page_title' => $project->project_name,
            'project' => $project,
            'tree' => $this->filemanagerservice->tree($project),
            'selected_path' => $selectedPath,
            'selected_entry' => $selectedEntry,
            'selected_content' => $selectedContent,
            'selected_error' => $selectedError,
            'project_usage_bytes' => $this->File_model->totalSizeByProject($project->id_project),
            'preview_url' => site_url('project-preview/' . $project->id_project),
            'layout_variant' => 'editor',
            'body_class' => 'app-body h-screen overflow-hidden',
        ));
    }

    public function show($slug)
    {
        $project = $this->Project_model->findByUserAndSlug($this->user()->id_user, $slug);

        if ( ! $project) {
            show_404();
        }

        $this->render('projects/show', array(
            'page_title' => $project->project_name,
            'project' => $project,
            'layout_variant' => 'app',
            'body_class' => 'app-body',
        ));
    }
}
