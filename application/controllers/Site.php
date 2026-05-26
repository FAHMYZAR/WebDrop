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
                show_404();
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
