<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(array('file', 'text'));
        $this->load->model(array('Project_model', 'Activity_log_model'));
        $this->load->library(array('ProjectManager', 'FileManagerService', 'StaticPublisher', 'PathResolver', 'FileValidator'));
        $this->load->config('mimes');
    }

    public function create()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $projectName = $this->input->post('project_name', true);
            $project = $this->projectmanager->createProject($this->user(), $projectName);

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
            $projectId = (int) $this->input->post('id_project');
            $project = $this->requireProject($projectId);
            $this->projectmanager->deleteProject($project, $this->user());
            $this->jsonResponse(true, 'Project berhasil dihapus.');
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 500);
        }
    }

    public function editor($projectId)
    {
        $project = $this->requireProject((int) $projectId);
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
            'preview_url' => site_url('projects/preview/' . $project->id_project),
            'layout_variant' => 'editor',
            'body_class' => 'app-body h-screen overflow-hidden',
        ));
    }

    public function get_file_content()
    {
        if ($this->input->method() !== 'get') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProject((int) $this->input->get('id_project'));
            $relativePath = $this->input->get('relative_path', true);
            $entry = $this->filemanagerservice->findFileEntry($project, $relativePath);

            if ( ! $entry || (int) $entry->is_folder === 1) {
                throw new RuntimeException('File tidak ditemukan.');
            }

            if ((int) $entry->is_editable !== 1) {
                $this->jsonResponse(true, 'File non-editable.', array(
                    'file' => $this->filePayload($entry, null, false),
                ));

                return;
            }

            $content = $this->filemanagerservice->readFile($project, $relativePath);
            $this->jsonResponse(true, 'OK', array(
                'file' => $this->filePayload($entry, $content, true),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function save_file()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProject((int) $this->input->post('id_project'));
            $relativePath = $this->input->post('relative_path', true);
            $content = (string) $this->input->post('content');
            $this->filemanagerservice->saveFile($project, $relativePath, $content);
            $entry = $this->filemanagerservice->findFileEntry($project, $relativePath);

            $this->jsonResponse(true, 'File berhasil disimpan.', array(
                'file' => $this->filePayload($entry, $content, true),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function create_file()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProject((int) $this->input->post('id_project'));
            $path = $this->filemanagerservice->createFile(
                $project,
                $this->input->post('parent_path', true),
                $this->input->post('file_name', true)
            );
            $entry = $this->filemanagerservice->findFileEntry($project, $path);

            $this->jsonResponse(true, 'File berhasil dibuat.', array(
                'file' => $this->filePayload($entry, '', true),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function create_folder()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProject((int) $this->input->post('id_project'));
            $path = $this->filemanagerservice->createFolder(
                $project,
                $this->input->post('parent_path', true),
                $this->input->post('folder_name', true)
            );
            $entry = $this->filemanagerservice->findFileEntry($project, $path);

            $this->jsonResponse(true, 'Folder berhasil dibuat.', array(
                'file' => $this->filePayload($entry, null, false),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function rename_file()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProject((int) $this->input->post('id_project'));
            $path = $this->filemanagerservice->rename(
                $project,
                $this->input->post('relative_path', true),
                $this->input->post('new_name', true)
            );
            $entry = $this->filemanagerservice->findFileEntry($project, $path);

            $this->jsonResponse(true, 'Item berhasil diubah.', array(
                'file' => $this->filePayload($entry, null, (int) $entry->is_folder === 0),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function delete_file()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProject((int) $this->input->post('id_project'));
            $relativePath = $this->input->post('relative_path', true);
            $this->filemanagerservice->delete($project, $relativePath);
            $this->jsonResponse(true, 'Item berhasil dihapus.');
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function upload_file()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProject((int) $this->input->post('id_project'));
            $uploadedFile = isset($_FILES['asset']) ? $_FILES['asset'] : array();
            $path = $this->filemanagerservice->uploadAsset(
                $project,
                $this->input->post('parent_path', true),
                $uploadedFile
            );
            $entry = $this->filemanagerservice->findFileEntry($project, $path);

            $this->jsonResponse(true, 'File berhasil diupload.', array(
                'file' => $this->filePayload($entry, null, false),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function preview($projectId, $relativePath = '')
    {
        $project = $this->requireProject((int) $projectId);

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
                $content = $this->injectBaseHref($content, site_url('projects/preview/' . $project->id_project . '/'));
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
            case 'htm':
                return 'text/html';
            case 'css':
                return 'text/css';
            case 'js':
                return 'application/javascript';
            case 'json':
                return 'application/json';
            case 'txt':
                return 'text/plain';
            case 'xml':
                return 'application/xml';
            case 'svg':
                return 'image/svg+xml';
            case 'png':
                return 'image/png';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'gif':
                return 'image/gif';
            case 'webp':
                return 'image/webp';
            case 'ico':
                return 'image/x-icon';
            default:
                return 'application/octet-stream';
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
            case 'xml':
                return 'UTF-8';
            default:
                return null;
        }
    }

    public function publish($projectId)
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProject((int) $projectId);
            $publicUrl = $this->staticpublisher->publish($project, $this->user());
            $updatedProject = $this->Project_model->findById($project->id_project);

            $this->jsonResponse(true, 'Project published successfully!', array(
                'public_url' => $publicUrl,
                'project' => $this->projectPayload($updatedProject),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function savedraft()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProject((int) $this->input->post('id_project'));

            $this->projectmanager->unpublishProject($project);
            
            $updatedProject = $this->Project_model->findById($project->id_project);

            $this->jsonResponse(true, 'Project saved as draft.', array(
                'project' => $this->projectPayload($updatedProject),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
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

    protected function requireProject($projectId)
    {
        $project = $this->Project_model->findByUserAndId($this->user()->id_user, $projectId);

        if ( ! $project) {
            throw new RuntimeException('Project tidak ditemukan.');
        }

        return $project;
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
