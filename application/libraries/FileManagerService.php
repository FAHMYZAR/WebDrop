<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FileManagerService
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('Project_file_model', 'Allowed_file_type_model'));
        $this->CI->load->library(array('PathResolver', 'FileValidator'));
    }

    public function tree($project)
    {
        return $this->CI->Project_file_model->findByProject($project->id_project);
    }

    public function projectAbsolutePath($project, $relativePath = '')
    {
        $relativePath = $this->CI->pathresolver->normalizeRelativePath($relativePath);
        $absolutePath = $this->CI->pathresolver->absolutePath($project->workspace_path, $relativePath);
        $this->CI->pathresolver->assertInsideBaseDirectory($project->workspace_path, $absolutePath);

        return $absolutePath;
    }

    public function findFileEntry($project, $relativePath)
    {
        $relativePath = $this->CI->pathresolver->normalizeRelativePath($relativePath);

        return $this->CI->Project_file_model->findByProjectAndPath($project->id_project, $relativePath);
    }

    public function readFile($project, $relativePath)
    {
        $relativePath = $this->CI->pathresolver->normalizeRelativePath($relativePath);
        $this->CI->filevalidator->assertEditable($relativePath);
        $path = $this->projectAbsolutePath($project, $relativePath);

        if ( ! is_file($path)) {
            throw new RuntimeException('File tidak ditemukan.');
        }

        return file_get_contents($path);
    }

    public function saveFile($project, $relativePath, $content)
    {
        $relativePath = $this->CI->pathresolver->normalizeRelativePath($relativePath);
        $this->CI->filevalidator->assertEditable($relativePath);
        $path = $this->projectAbsolutePath($project, $relativePath);

        if ( ! is_file($path)) {
            throw new RuntimeException('File tidak ditemukan.');
        }

        file_put_contents($path, $content);
        $this->syncProjectFiles($project);
    }

    public function createFile($project, $parentPath, $fileName)
    {
        $fileName = $this->CI->filevalidator->assertFileName($fileName);
        $relativePath = $this->joinPath($parentPath, $fileName);
        $this->CI->filevalidator->assertManagedExtension($relativePath);
        $path = $this->projectAbsolutePath($project, $relativePath);

        if (file_exists($path)) {
            throw new RuntimeException('File sudah ada.');
        }

        $this->ensureParentDirectory($path);
        file_put_contents($path, '');
        $this->syncProjectFiles($project);

        return $relativePath;
    }

    public function createFolder($project, $parentPath, $folderName)
    {
        $folderName = $this->CI->filevalidator->assertFileName($folderName);
        $relativePath = $this->joinPath($parentPath, $folderName);
        $path = $this->projectAbsolutePath($project, $relativePath);

        if (file_exists($path)) {
            throw new RuntimeException('Folder sudah ada.');
        }

        if ( ! mkdir($path, DIR_WRITE_MODE, true) && ! is_dir($path)) {
            throw new RuntimeException('Gagal membuat folder.');
        }

        $this->syncProjectFiles($project);

        return $relativePath;
    }

    public function rename($project, $relativePath, $newName)
    {
        $relativePath = $this->CI->pathresolver->normalizeRelativePath($relativePath);
        $newName = $this->CI->filevalidator->assertFileName($newName);
        $sourcePath = $this->projectAbsolutePath($project, $relativePath);

        if ( ! file_exists($sourcePath)) {
            throw new RuntimeException('Target tidak ditemukan.');
        }

        $parent = dirname($relativePath);
        $parent = $parent === '.' ? '' : str_replace('\\', '/', $parent);
        $targetRelativePath = $this->joinPath($parent, $newName);
        $this->CI->filevalidator->assertManagedExtension($targetRelativePath);

        $targetPath = $this->projectAbsolutePath($project, $targetRelativePath);

        if (file_exists($targetPath)) {
            throw new RuntimeException('Nama tujuan sudah dipakai.');
        }

        if ( ! rename($sourcePath, $targetPath)) {
            throw new RuntimeException('Gagal rename.');
        }

        $this->syncProjectFiles($project);

        return $targetRelativePath;
    }

    public function delete($project, $relativePath)
    {
        $relativePath = $this->CI->pathresolver->normalizeRelativePath($relativePath);
        $path = $this->projectAbsolutePath($project, $relativePath);

        if ( ! file_exists($path)) {
            throw new RuntimeException('Target tidak ditemukan.');
        }

        $this->deleteRecursive($path);
        $this->syncProjectFiles($project);
    }

    public function uploadAsset($project, $parentPath, $file)
    {
        if (empty($file['name']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload gagal.');
        }

        $fileName = $this->CI->filevalidator->assertFileName($file['name']);
        $this->CI->filevalidator->assertAllowedUpload($fileName, (int) $file['size']);
        $relativePath = $this->joinPath($parentPath, $fileName);
        $targetPath = $this->projectAbsolutePath($project, $relativePath);

        if (file_exists($targetPath)) {
            throw new RuntimeException('File asset sudah ada.');
        }

        $this->ensureParentDirectory($targetPath);

        if ( ! move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Gagal memindahkan file upload.');
        }

        $this->syncProjectFiles($project);

        return $relativePath;
    }

    public function syncProjectFiles($project)
    {
        $base = rtrim($project->workspace_path, DIRECTORY_SEPARATOR);

        if ( ! is_dir($base)) {
            throw new RuntimeException('Workspace project tidak ditemukan.');
        }

        $items = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            $absolutePath = $fileInfo->getPathname();
            $relativePath = str_replace($base . DIRECTORY_SEPARATOR, '', $absolutePath);
            $relativePath = str_replace('\\', '/', $relativePath);
            $items[] = array(
                'relative_path' => $relativePath,
                'is_folder' => $fileInfo->isDir() ? 1 : 0,
                'file_name' => $fileInfo->getFilename(),
                'file_extension' => $fileInfo->isDir() ? null : strtolower(pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION)),
                'file_size' => $fileInfo->isDir() ? 0 : (int) $fileInfo->getSize(),
                'is_editable' => $fileInfo->isDir() ? 0 : ($this->CI->filevalidator->isEditable($relativePath) ? 1 : 0),
                'depth' => substr_count($relativePath, '/'),
            );
        }

        usort($items, function ($left, $right) {
            if ($left['depth'] === $right['depth']) {
                if ($left['is_folder'] === $right['is_folder']) {
                    return strcmp($left['relative_path'], $right['relative_path']);
                }

                return $right['is_folder'] <=> $left['is_folder'];
            }

            return $left['depth'] <=> $right['depth'];
        });

        $this->CI->db->trans_start();
        $this->CI->Project_file_model->deleteByProject($project->id_project);
        $pathMap = array();

        foreach ($items as $item) {
            $parentPath = dirname($item['relative_path']);
            $parentPath = $parentPath === '.' ? '' : str_replace('\\', '/', $parentPath);
            $row = array(
                'id_project' => (int) $project->id_project,
                'parent_id' => $parentPath !== '' && isset($pathMap[$parentPath]) ? $pathMap[$parentPath] : null,
                'file_name' => $item['file_name'],
                'relative_path' => $item['relative_path'],
                'file_extension' => $item['file_extension'],
                'file_size' => $item['file_size'],
                'is_folder' => $item['is_folder'],
                'is_editable' => $item['is_editable'],
            );

            $this->CI->db->insert('project_files', $row);
            $pathMap[$item['relative_path']] = $this->CI->db->insert_id();
        }

        $this->CI->db->trans_complete();
    }

    public function assertProjectFileExists($project, $relativePath)
    {
        $absolutePath = $this->projectAbsolutePath($project, $relativePath);

        if ( ! file_exists($absolutePath)) {
            throw new RuntimeException('File atau folder tidak ditemukan.');
        }

        return $absolutePath;
    }

    protected function ensureParentDirectory($absolutePath)
    {
        $directory = dirname($absolutePath);

        if (is_dir($directory)) {
            return;
        }

        if ( ! mkdir($directory, DIR_WRITE_MODE, true) && ! is_dir($directory)) {
            throw new RuntimeException('Gagal membuat folder induk.');
        }
    }

    protected function joinPath($parentPath, $childName)
    {
        $parentPath = $this->CI->pathresolver->normalizeRelativePath($parentPath);
        $this->CI->filevalidator->assertFileName($childName);

        return $parentPath === '' ? $childName : $parentPath . '/' . $childName;
    }

    protected function deleteRecursive($path)
    {
        if (is_file($path)) {
            unlink($path);
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
