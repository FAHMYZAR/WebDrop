<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Editor extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Project_model');
        $this->load->library(array('FileManagerService', 'PathResolver'));
    }

    public function index($projectId)
    {
        $project = $this->findProject($projectId);
        $selectedPath = $this->input->get('file', true) ?: 'index.html';
        $selectedContent = null;
        $selectedError = null;

        try {
            $selectedContent = $this->filemanagerservice->readFile($project, $selectedPath);
        } catch (Throwable $exception) {
            $selectedError = $exception->getMessage();
        }

        $this->render('editor/index', array(
            'page_title' => 'Editor',
            'project' => $project,
            'tree' => $this->filemanagerservice->tree($project),
            'selected_path' => $selectedPath,
            'selected_content' => $selectedContent,
            'selected_error' => $selectedError,
            'preview_url' => site_url('preview/' . $project->id_project),
            'layout_variant' => 'editor',
            'body_class' => 'editor-body',
        ));
    }

    public function file($projectId)
    {
        $project = $this->findProject($projectId);
        $path = $this->input->get('path', true);

        try {
            $content = $this->filemanagerservice->readFile($project, $path);
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => true, 'content' => $content)));
        } catch (Throwable $exception) {
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json')
                ->set_output(json_encode(array('ok' => false, 'message' => $exception->getMessage())));
        }
    }

    public function save($projectId)
    {
        $project = $this->findProject($projectId);

        try {
            $this->filemanagerservice->saveFile(
                $project,
                $this->input->post('path', true),
                (string) $this->input->post('content')
            );
            $this->session->set_flashdata('success', 'File berhasil disimpan.');
        } catch (Throwable $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
        }

        redirect('editor/' . $project->id_project . '?file=' . rawurlencode((string) $this->input->post('path', true)));
    }

    public function create_file($projectId)
    {
        $project = $this->findProject($projectId);

        try {
            $path = $this->filemanagerservice->createFile(
                $project,
                $this->input->post('parent_path', true),
                $this->input->post('file_name', true)
            );
            $this->session->set_flashdata('success', 'File berhasil dibuat.');
            redirect('editor/' . $project->id_project . '?file=' . rawurlencode($path));
        } catch (Throwable $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
            redirect('editor/' . $project->id_project);
        }
    }

    public function create_folder($projectId)
    {
        $project = $this->findProject($projectId);

        try {
            $this->filemanagerservice->createFolder(
                $project,
                $this->input->post('parent_path', true),
                $this->input->post('folder_name', true)
            );
            $this->session->set_flashdata('success', 'Folder berhasil dibuat.');
        } catch (Throwable $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
        }

        redirect('editor/' . $project->id_project);
    }

    public function rename($projectId)
    {
        $project = $this->findProject($projectId);

        try {
            $path = $this->filemanagerservice->rename(
                $project,
                $this->input->post('path', true),
                $this->input->post('new_name', true)
            );
            $this->session->set_flashdata('success', 'Nama berhasil diubah.');
            redirect('editor/' . $project->id_project . '?file=' . rawurlencode($path));
        } catch (Throwable $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
            redirect('editor/' . $project->id_project);
        }
    }

    public function delete($projectId)
    {
        $project = $this->findProject($projectId);

        try {
            $this->filemanagerservice->delete($project, $this->input->post('path', true));
            $this->session->set_flashdata('success', 'Item berhasil dihapus.');
        } catch (Throwable $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
        }

        redirect('editor/' . $project->id_project);
    }

    public function upload($projectId)
    {
        $project = $this->findProject($projectId);

        try {
            $this->filemanagerservice->uploadAsset(
                $project,
                $this->input->post('parent_path', true),
                isset($_FILES['asset']) ? $_FILES['asset'] : array()
            );
            $this->session->set_flashdata('success', 'Asset berhasil diupload.');
        } catch (Throwable $exception) {
            $this->session->set_flashdata('error', $exception->getMessage());
        }

        redirect('editor/' . $project->id_project);
    }

    protected function findProject($projectId)
    {
        $project = $this->Project_model->findByUserAndId($this->user()->id_user, $projectId);

        if ( ! $project) {
            show_404();
        }

        return $project;
    }
}
