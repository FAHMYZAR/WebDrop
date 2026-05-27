<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Site extends Public_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Project_model');
        $this->load->model('User_model');
        $this->load->config('mimes');
    }

    public function view($username, $slug, $relativePath = '')
    {
        $project = $this->Project_model->findByUserAndSlug($this->resolveUserId($username), $slug);

        if ( ! $project || $project->status !== 'published' || empty($project->published_path)) {
            show_404();
        }

        try {
            $relativePath = $relativePath === '' ? 'index.html' : $relativePath;
            $relativePath = $this->sanitizeRelativePath($relativePath);
            $absolutePath = rtrim($project->published_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if ( ! is_file($absolutePath)) {
                $this->renderNotFoundPage();
                return;
            }

            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
            $mime = $this->resolveMimeType($extension);
            $content = file_get_contents($absolutePath);

            if ($extension === 'html' || $extension === 'htm') {
                $content = $this->injectBaseHref($content, site_url('site/' . rawurlencode($username) . '/' . rawurlencode($slug) . '/'));
            }

            $this->output
                ->set_content_type($mime)
                ->set_output($content);
        } catch (Throwable $exception) {
            show_error($exception->getMessage(), 422);
        }
    }

    protected function resolveUserId($username)
    {
        $user = $this->User_model->findByUsername($username);

        if ( ! $user || (int) $user->is_active !== 1) {
            return 0;
        }

        return (int) $user->id_user;
    }

    protected function sanitizeRelativePath($relativePath)
    {
        $relativePath = trim((string) $relativePath);

        if ($relativePath === '') {
            return 'index.html';
        }

        if (preg_match('/^[A-Za-z]:[\\\/]/', $relativePath) || $relativePath[0] === '/' || strpos($relativePath, '\\') !== false) {
            throw new RuntimeException('Path tidak valid.');
        }

        $relativePath = trim($relativePath, '/');
        $segments = array();

        foreach (explode('/', $relativePath) as $segment) {
            $segment = trim($segment);

            if ($segment === '' || $segment === '.' || $segment === '..' || preg_match('/[<>:"|?*]/', $segment)) {
                throw new RuntimeException('Path tidak valid.');
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    protected function renderNotFoundPage()
    {
        $notFoundFile = FCPATH . 'sites' . DIRECTORY_SEPARATOR . '404.html';
        $content = is_file($notFoundFile) ? file_get_contents($notFoundFile) : '';

        $this->output
            ->set_status_header(404)
            ->set_content_type('text/html', 'UTF-8')
            ->set_output($content);
    }

    protected function resolveMimeType($extension)
    {
        $mimes = $this->config->item('mimes');
        $mime = isset($mimes[$extension]) ? $mimes[$extension] : 'text/plain';

        return is_array($mime) ? $mime[0] : $mime;
    }
}
