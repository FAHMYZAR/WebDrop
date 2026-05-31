<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProjectFiles extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('FileManagerService');
    }

    public function get_file_content()
    {
        if ($this->input->method() !== 'get') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProjectForUser((int) $this->input->get('id_project'));
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
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
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
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $path = $this->filemanagerservice->createFile($project, $this->input->post('parent_path', true), $this->input->post('file_name', true));
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
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $path = $this->filemanagerservice->createFolder($project, $this->input->post('parent_path', true), $this->input->post('folder_name', true));
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
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $path = $this->filemanagerservice->rename($project, $this->input->post('relative_path', true), $this->input->post('new_name', true));
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
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $this->filemanagerservice->delete($project, $this->input->post('relative_path', true));
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
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $path = $this->filemanagerservice->uploadAsset($project, $this->input->post('parent_path', true), isset($_FILES['asset']) ? $_FILES['asset'] : array());
            $entry = $this->filemanagerservice->findFileEntry($project, $path);

            $this->jsonResponse(true, 'File berhasil diupload.', array(
                'file' => $this->filePayload($entry, null, false),
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function upload_zip()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $uploaded = isset($_FILES['asset']) ? $_FILES['asset'] : array();
            $importInfo = $this->filemanagerservice->uploadZipImport($project, $uploaded);

            $this->jsonResponse(true, 'ZIP berhasil diupload ke import queue.', array(
                'import' => $importInfo,
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function extract_zip()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $token = $this->input->post('token', true);
            $report = $this->filemanagerservice->extractZipImport($project, $token);

            $message = sprintf('Extract selesai: %d file berhasil, %d ditolak, %d konflik.', $report['success_count'], $report['rejected_count'], $report['conflict_count']);

            $this->jsonResponse(true, $message, array(
                'report' => $report,
            ));
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }

    public function cancel_zip_import()
    {
        if ($this->input->method() !== 'post') {
            $this->jsonResponse(false, 'Method not allowed.', array(), 405);
            return;
        }

        try {
            $project = $this->requireProjectForUser((int) $this->input->post('id_project'));
            $token = $this->input->post('token', true);
            $this->filemanagerservice->cancelZipImport($project, $token);
            $this->jsonResponse(true, 'Import dibatalkan.');
        } catch (Throwable $exception) {
            $this->jsonResponse(false, $exception->getMessage(), array(), 422);
        }
    }
}
