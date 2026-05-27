<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    protected $currentUser;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->currentUser = $this->resolveCurrentUser();
    }

    protected function resolveCurrentUser()
    {
        $userId = (int) $this->session->userdata('id_user');

        if ($userId < 1) {
            return null;
        }

        return $this->User_model->findActiveById($userId);
    }

    public function user()
    {
        return $this->currentUser;
    }

    protected function guestOnly()
    {
        if ($this->user()) {
            redirect('dashboard');
        }
    }

    protected function requireAuth()
    {
        if ($this->user()) {
            return;
        }

        $this->session->set_flashdata('error', 'Silakan login dulu.');
        redirect('login');
    }

    protected function render($view, array $data = array())
    {
        $data['auth_user'] = $this->user();
        $data['flash_error'] = $this->session->flashdata('error');
        $data['flash_success'] = $this->session->flashdata('success');
        $data['content_view'] = $view;
        $this->load->view('layouts/app', $data);
    }

    protected function backTo($fallback)
    {
        $target = $this->input->server('HTTP_REFERER');
        redirect($target ?: $fallback);
    }

    protected function jsonResponse($success, $message, array $data = array(), $statusCode = 200)
    {
        $payload = array(
            'success' => (bool) $success,
            'message' => $message,
            'data' => $data,
            'csrf' => array(
                'name' => $this->security->get_csrf_token_name(),
                'hash' => $this->security->get_csrf_hash(),
            ),
        );

        $this->output
            ->set_status_header($statusCode)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

    protected function requireProjectForUser($projectId)
    {
        $this->load->model('Project_model');
        $project = $this->Project_model->findByUserAndId($this->user()->id_user, (int) $projectId);

        if ( ! $project) {
            throw new RuntimeException('Project tidak ditemukan.');
        }

        return $project;
    }

    protected function projectPayload($project)
    {
        if ( ! $project) {
            return null;
        }

        return array(
            'id_project' => (int) $project->id_project,
            'project_name' => $project->project_name,
            'slug' => $project->slug,
            'status' => $project->status,
            'public_url' => $project->public_url,
            'workspace_path' => $project->workspace_path,
            'published_path' => $project->published_path,
            'updated_at' => $project->updated_at,
            'last_published_at' => $project->last_published_at,
        );
    }

    protected function filePayload($entry, $content = null, $includeContent = false)
    {
        if ( ! $entry) {
            return null;
        }

        $payload = array(
            'id_file' => (int) $entry->id_file,
            'id_project' => (int) $entry->id_project,
            'parent_id' => $entry->parent_id ? (int) $entry->parent_id : null,
            'file_name' => $entry->file_name,
            'relative_path' => $entry->relative_path,
            'file_extension' => $entry->file_extension,
            'file_size' => (int) $entry->file_size,
            'is_folder' => (int) $entry->is_folder,
            'is_editable' => (int) $entry->is_editable,
            'created_at' => isset($entry->created_at) ? $entry->created_at : null,
            'updated_at' => isset($entry->updated_at) ? $entry->updated_at : null,
        );

        if ($includeContent) {
            $payload['content'] = $content;
        }

        return $payload;
    }

    protected function resolveMimeType($extension)
    {
        $mimes = $this->config->item('mimes');
        $mime = isset($mimes[$extension]) ? $mimes[$extension] : 'text/plain';

        return is_array($mime) ? $mime[0] : $mime;
    }

    protected function injectBaseHref($content, $baseHref)
    {
        if (stripos($content, '<base ') !== false) {
            return $content;
        }

        $baseTag = '<base href="' . html_escape($baseHref) . '">';

        if (preg_match('/<head[^>]*>/i', $content)) {
            return preg_replace('/<head([^>]*)>/i', '<head$1>' . $baseTag, $content, 1);
        }

        if (preg_match('/<html[^>]*>/i', $content)) {
            return preg_replace('/<html([^>]*)>/i', '<html$1><head>' . $baseTag . '</head>', $content, 1);
        }

        return '<!doctype html><html><head>' . $baseTag . '</head><body>' . $content . '</body></html>';
    }
}

class Public_Controller extends MY_Controller
{
}

class Auth_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }
}

class App_Controller extends Auth_Controller
{
}
