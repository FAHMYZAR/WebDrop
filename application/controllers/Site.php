<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Site extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Project_model');
        $this->load->library('PathResolver');
        $this->load->helper('file');
        $this->load->config('mimes');
    }

    public function preview($projectId, $relativePath = '')
    {
        $project = $this->Project_model->findByUserAndId($this->user()->id_user, $projectId);

        if ( ! $project) {
            show_404();
        }

        try {
            $relativePath = $relativePath === '' ? 'index.html' : $relativePath;
            $relativePath = $this->pathresolver->normalizeRelativePath($relativePath);
            $absolutePath = $this->pathresolver->absolutePath($project->workspace_path, $relativePath);

            if ( ! is_file($absolutePath)) {
                $this->renderNotFoundPage($project);
                return;
            }

            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
            $mime = $this->resolveMimeType($extension);
            $content = file_get_contents($absolutePath);

            if ($extension === 'html') {
                $content = $this->injectBaseHref($content, site_url('preview/' . $project->id_project . '/'));
            }

            $this->output
                ->set_content_type($mime)
                ->set_output($content);
        } catch (Throwable $exception) {
            show_error($exception->getMessage(), 422);
        }
    }

    protected function renderNotFoundPage($project)
    {
        $notFoundFile = FCPATH . 'sites' . DIRECTORY_SEPARATOR . '404.html';
        $content = is_file($notFoundFile) ? file_get_contents($notFoundFile) : '';

        if ($content === '') {
            $content = '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>WebDrop - 404</title><style>body{margin:0;font-family:Arial,sans-serif;background:#f4f4f4;color:#161616;min-height:100vh;display:flex;align-items:center;justify-content:center} .wrap{max-width:640px;width:calc(100% - 32px);background:#fff;border:1px solid #e0e0e0;box-shadow:0 18px 50px rgba(0,0,0,.08);padding:32px} .kicker{font-size:12px;letter-spacing:.18em;text-transform:uppercase;color:#0f62fe;font-weight:700} h1{margin:12px 0 8px;font-size:32px;line-height:1.1} p{margin:0;color:#525252;line-height:1.6}</style></head><body><main class="wrap"><div class="kicker">WebDrop 404</div><h1>OOPS, halaman tidak ditemukan</h1><p>Halaman yang kamu cari tidak tersedia atau sudah dipindah.</p></main></body></html>';
        }

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
