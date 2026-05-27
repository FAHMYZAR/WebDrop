<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProjectPreview extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('FileManagerService', 'PathResolver'));
    }

    public function index($projectId, $relativePath = '')
    {
        $project = $this->requireProjectForUser((int) $projectId);

        try {
            $relativePath = $relativePath === '' ? 'index.html' : $relativePath;
            $relativePath = $this->pathresolver->normalizeRelativePath($relativePath);
            $absolutePath = $this->filemanagerservice->projectAbsolutePath($project, $relativePath);

            if ( ! is_file($absolutePath)) {
                $this->servePreviewNotFound($relativePath);
                return;
            }

            $content = file_get_contents($absolutePath);
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

            if ($extension === 'html' || $extension === 'htm') {
                $content = $this->injectBaseHref($content, site_url('project-preview/' . $project->id_project . '/'));
            }

            $this->output
                ->set_content_type($this->previewMimeType($extension), $this->previewCharset($extension))
                ->set_output($content);
        } catch (Throwable $exception) {
            $this->output
                ->set_status_header(422)
                ->set_content_type('text/html', 'UTF-8')
                ->set_output('<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Preview</title><style>body{font-family:Arial,sans-serif;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;background:#fff;color:#161616}div{border:1px solid #e0e0e0;padding:24px 28px}</style></head><body><div>' . html_escape($exception->getMessage()) . '</div></body></html>');
        }
    }

    protected function servePreviewNotFound($relativePath)
    {
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        if ($extension === 'html' || $extension === 'htm' || $extension === '') {
            $this->output
                ->set_status_header(404)
                ->set_content_type('text/html', 'UTF-8')
                ->set_output('<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Preview</title><style>body{font-family:Arial,sans-serif;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;background:#fff;color:#161616}div{border:1px solid #e0e0e0;padding:24px 28px}</style></head><body><div>File tidak ditemukan.</div></body></html>');

            return;
        }

        $this->output
            ->set_status_header(404)
            ->set_content_type($this->previewMimeType($extension), $this->previewCharset($extension))
            ->set_output('');
    }

    protected function previewMimeType($extension)
    {
        switch (strtolower($extension)) {
            case 'html':
            case 'htm': return 'text/html';
            case 'css': return 'text/css';
            case 'js': return 'application/javascript';
            case 'json': return 'application/json';
            case 'txt': return 'text/plain';
            case 'xml': return 'application/xml';
            case 'svg': return 'image/svg+xml';
            case 'png': return 'image/png';
            case 'jpg':
            case 'jpeg': return 'image/jpeg';
            case 'gif': return 'image/gif';
            case 'webp': return 'image/webp';
            case 'ico': return 'image/x-icon';
            default: return 'application/octet-stream';
        }
    }

    protected function previewCharset($extension)
    {
        switch (strtolower($extension)) {
            case 'html':
            case 'htm':
            case 'css':
            case 'js':
            case 'json':
            case 'txt':
            case 'xml': return 'UTF-8';
            default: return null;
        }
    }
}
