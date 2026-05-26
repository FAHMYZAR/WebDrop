<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PathResolver
{
    public function workspaceDirectory($username, $slug)
    {
        return WORKSPACES_PATH . $username . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR;
    }

    public function siteDirectory($username, $slug)
    {
        return SITES_PATH . $username . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR;
    }

    public function publicUrl($username, $slug)
    {
        return WEBROOT_SITES_URL . rawurlencode($username) . '/' . rawurlencode($slug);
    }

    public function normalizeRelativePath($relativePath)
    {
        $relativePath = trim((string) $relativePath);

        if ($relativePath === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]:[\\\\\/]/', $relativePath) || $relativePath[0] === '/' || strpos($relativePath, '\\') !== false) {
            throw new RuntimeException('Path tidak valid.');
        }

        $relativePath = trim($relativePath, '/');
        $segments = array();

        foreach (explode('/', $relativePath) as $segment) {
            $segment = trim($segment);

            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException('Path tidak valid.');
            }

            if (preg_match('/[<>:"|?*]/', $segment)) {
                throw new RuntimeException('Nama file mengandung karakter terlarang.');
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    public function absolutePath($baseDirectory, $relativePath = '')
    {
        $relativePath = $this->normalizeRelativePath($relativePath);

        if ($relativePath === '') {
            return rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        return rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    public function assertInsideBaseDirectory($baseDirectory, $absolutePath)
    {
        $baseRealPath = realpath($baseDirectory);

        if ($baseRealPath === false) {
            throw new RuntimeException('Workspace tidak ditemukan.');
        }

        $baseRealPath = $this->normalizeAbsolutePath($baseRealPath);
        $absolutePath = $this->normalizeAbsolutePath($absolutePath);
        $pathToCheck = file_exists($absolutePath) ? realpath($absolutePath) : realpath(dirname($absolutePath));

        if ($pathToCheck === false) {
            throw new RuntimeException('Path tidak valid.');
        }

        $pathToCheck = $this->normalizeAbsolutePath($pathToCheck);

        if ($pathToCheck !== $baseRealPath && strpos($pathToCheck, $baseRealPath . '/') !== 0) {
            throw new RuntimeException('Path keluar dari workspace project.');
        }

        return true;
    }

    public function normalizeAbsolutePath($path)
    {
        return str_replace('\\', '/', $path);
    }
}
