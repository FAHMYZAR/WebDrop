<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FileManagerService
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model(array('File_model', 'Allowed_file_type_model'));
        $this->CI->load->library(array('PathResolver', 'FileValidator'));
    }

    public function tree($project)
    {
        return $this->CI->File_model->findByProject($project->id_project);
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

        return $this->CI->File_model->findByProjectAndPath($project->id_project, $relativePath);
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

        $oldSize = (int) filesize($path);
        $newSize = strlen($content);
        $sizeDiff = $newSize - $oldSize;

        if ($sizeDiff > 0) {
            $this->CI->filevalidator->assertQuota($project, $sizeDiff);
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

        if ( ! is_dir($sourcePath)) {
            $sourceExtension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
            $targetExtension = strtolower(pathinfo($targetRelativePath, PATHINFO_EXTENSION));

            if ($sourceExtension !== $targetExtension) {
                throw new RuntimeException('Rename file tidak boleh mengubah ekstensi.');
            }

            $this->CI->filevalidator->assertManagedExtension($targetRelativePath);
        }

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
        $fileSize = (int) $file['size'];

        $this->CI->filevalidator->assertAllowedUpload($fileName, $fileSize);
        $this->CI->filevalidator->assertQuota($project, $fileSize);

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

    public function uploadZipImport($project, $file)
    {
        if (empty($file['name']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload ZIP gagal.');
        }

        $fileName = $this->CI->filevalidator->assertFileName($file['name']);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($extension !== 'zip') {
            throw new RuntimeException('File import harus berformat ZIP.');
        }

        $this->CI->filevalidator->assertAllowedUpload($fileName, (int) $file['size']);
        $importDirectory = $this->zipImportDirectory($project);
        $token = date('YmdHis') . '-' . bin2hex(random_bytes(8));
        $storedName = $token . '.zip';
        $targetPath = $importDirectory . $storedName;

        if ( ! is_dir($importDirectory) && ! mkdir($importDirectory, DIR_WRITE_MODE, true) && ! is_dir($importDirectory)) {
            throw new RuntimeException('Gagal membuat folder import sementara.');
        }

        if ( ! move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Gagal menyimpan ZIP import.');
        }

        return array(
            'token' => $token,
            'file_name' => $fileName,
            'stored_name' => $storedName,
            'size' => (int) filesize($targetPath),
        );
    }

    public function cancelZipImport($project, $token)
    {
        $zipPath = $this->zipImportPath($project, $token);

        if (is_file($zipPath)) {
            unlink($zipPath);
        }
    }

    public function extractZipImport($project, $token)
    {
        $zipPath = $this->zipImportPath($project, $token);

        if ( ! is_file($zipPath)) {
            throw new RuntimeException('ZIP import tidak ditemukan.');
        }

        if ( ! class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif.');
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('ZIP import tidak bisa dibuka.');
        }

        $scan = $this->scanZipImport($project, $zip);

        if ($scan['success_count'] < 1) {
            $zip->close();
            throw new RuntimeException('Extract dibatalkan karena tidak ada file valid.');
        }

        $this->CI->filevalidator->assertQuota($project, $scan['total_size']);
        $this->extractScannedZipEntries($project, $zip, $scan['valid_files']);
        $zip->close();
        unlink($zipPath);
        $this->syncProjectFiles($project);

        return array(
            'success_count' => $scan['success_count'],
            'rejected_count' => count($scan['rejected']),
            'conflict_count' => count($scan['conflicts']),
            'total_size' => $scan['total_size'],
            'rejected' => array_slice($scan['rejected'], 0, 20),
            'conflicts' => array_slice($scan['conflicts'], 0, 20),
        );
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
        $this->CI->File_model->deleteByProject($project->id_project);
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

    protected function zipImportDirectory($project)
    {
        return FCPATH . 'storage' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'imports' . DIRECTORY_SEPARATOR . (int) $project->id_user . DIRECTORY_SEPARATOR . (int) $project->id_project . DIRECTORY_SEPARATOR;
    }

    protected function zipImportPath($project, $token)
    {
        $token = preg_replace('/[^a-zA-Z0-9_\-]/', '', $token);

        return $this->zipImportDirectory($project) . $token . '.zip';
    }

    protected function scanZipImport($project, ZipArchive $zip)
    {
        $maxFiles = 300;
        $maxDepth = 8;
        $validFiles = array();
        $rejected = array();
        $conflicts = array();
        $totalSize = 0;
        $totalEntries = $zip->numFiles;

        if ($totalEntries > $maxFiles) {
            throw new RuntimeException('ZIP mengandung lebih dari ' . $maxFiles . ' file.');
        }

        $workspaceBase = rtrim($project->workspace_path, DIRECTORY_SEPARATOR);

        for ($i = 0; $i < $totalEntries; $i++) {
            $stat = $zip->statIndex($i);

            if ($stat === false) {
                continue;
            }

            $entryName = str_replace('\\', '/', $stat['name']);
            $entryName = ltrim($entryName, '/');

            if ($entryName === '' || substr($entryName, -1) === '/') {
                continue;
            }

            if (strpos($entryName, '../') !== false || strpos($entryName, '..\\') !== false) {
                $rejected[] = array('file' => $entryName, 'reason' => 'Path traversal');
                continue;
            }

            if (preg_match('/^[A-Za-z]:[\\/]/', $entryName)) {
                $rejected[] = array('file' => $entryName, 'reason' => 'Absolute path');
                continue;
            }

            $depth = substr_count($entryName, '/');

            if ($depth > $maxDepth) {
                $rejected[] = array('file' => $entryName, 'reason' => 'Kedalaman folder melebihi batas');
                continue;
            }

            $segments = explode('/', $entryName);
            $segmentInvalid = false;

            foreach ($segments as $segment) {
                if ($segment === '' || $segment === '.' || $segment === '..' || preg_match('/[<>:"|?*]/', $segment)) {
                    $segmentInvalid = true;
                    break;
                }
            }

            if ($segmentInvalid) {
                $rejected[] = array('file' => $entryName, 'reason' => 'Nama file/folder tidak valid');
                continue;
            }

            $baseName = basename($entryName);
            $ext = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));

            if ($ext === 'zip') {
                $rejected[] = array('file' => $entryName, 'reason' => 'Nested ZIP tidak diizinkan');
                continue;
            }

            if ($this->CI->filevalidator->isBlockedExtension($ext)) {
                $rejected[] = array('file' => $entryName, 'reason' => 'Ekstensi diblokir');
                continue;
            }

            if ($baseName === '.htaccess' || $baseName === '.env') {
                $rejected[] = array('file' => $entryName, 'reason' => 'File konfigurasi diblokir');
                continue;
            }

            if ( ! $this->CI->filevalidator->isEditableExtension($ext) && ! $this->CI->filevalidator->isAssetExtension($ext)) {
                $allowed = $this->CI->Allowed_file_type_model->findActiveByExtension($ext);

                if ( ! $allowed) {
                    $rejected[] = array('file' => $entryName, 'reason' => 'Ekstensi tidak didukung');
                    continue;
                }

                $maxBytes = ((int) $allowed->max_size_mb) * 1024 * 1024;

                if ((int) $stat['size'] > $maxBytes) {
                    $rejected[] = array('file' => $entryName, 'reason' => 'Ukuran melebihi batas');
                    continue;
                }
            }

            $targetAbsPath = $workspaceBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entryName);

            if (file_exists($targetAbsPath)) {
                $conflicts[] = array('file' => $entryName, 'reason' => 'File sudah ada di workspace');
                continue;
            }

            $totalSize += (int) $stat['size'];
            $validFiles[] = array('index' => $i, 'path' => $entryName, 'size' => (int) $stat['size']);
        }

        return array(
            'valid_files' => $validFiles,
            'rejected' => $rejected,
            'conflicts' => $conflicts,
            'success_count' => count($validFiles),
            'total_size' => $totalSize,
        );
    }

    protected function extractScannedZipEntries($project, ZipArchive $zip, array $validFiles)
    {
        $workspaceBase = rtrim($project->workspace_path, DIRECTORY_SEPARATOR);

        foreach ($validFiles as $entry) {
            $targetPath = $workspaceBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entry['path']);
            $this->ensureParentDirectory($targetPath);
            $content = $zip->getFromIndex($entry['index']);

            if ($content === false) {
                continue;
            }

            file_put_contents($targetPath, $content);
        }
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
