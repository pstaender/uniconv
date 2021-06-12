<?php

declare(strict_types=1);

namespace App;

final class Helper
{
    public static function jobFile(string $username, string $id): string
    {
        return "jobs/$username.$id.json";
    }

    public static function jobFileData(string $username, string $id): ?array
    {
        $jobFile = self::jobFile($username, $id);
        $data = null;
        if (file_exists($jobFile)) {
            $data = json_decode(file_get_contents($jobFile), true);
        } elseif (file_exists($jobFile . '.done')) {
            $data = json_decode(file_get_contents($jobFile . '.done'), true);
        }
        return $data;
    }

    public static function conversionFolder(string $username, string $id): string
    {
        return "files/$username/$id";
    }

    public static function downloadFileUrl(string $username, string $id): ?string
    {
        $data = self::jobFileData($username, $id);
        if ($data) {
            return self::baseUrl() . "/file/" . urlencode($id) . "/" . urlencode(pathinfo($data['file']['filename'], PATHINFO_FILENAME)) . '.' . urlencode($data['target']);
        } else {
            return null;
        }
    }

    public static function sourceAndTargetFilePath(string $username, string $id): ?string
    {
        return "/file/$username/$id";
    }

    public static function deleteConversion(string $username, string $id, bool $deleteJobFile = false, bool $force = false): bool
    {
        $jobFile = self::jobFile($username, $id) . '.done';
        if (!file_exists($jobFile) && !$force) {
            return false;
        }

        if ($force && file_exists(self::jobFile($username, $id))) {
            // delete job json file
            unlink(self::jobFile($username, $id));
        }

        if ($deleteJobFile) {
            unlink($jobFile);
        } else {
            if (!file_exists('jobs/deleted')) {
                mkdir(directory: 'jobs/deleted', recursive: true);
      }
            if (file_exists($jobFile)) {
                // move job file to deleted folder, i.e. soft delete
                rename($jobFile, 'jobs/deleted/' . basename($jobFile) . '.' . time());
            }
        }

        $folder = realpath(__DIR__ . '/../' . Helper::conversionFolder($username, $id));

        if (!file_exists($folder)) {
            throw new NotFoundException('File does not exists');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($folder);
        return true;
    }

    public static function statusUrlForConversion(string $username, string $id): string
    {
        return self::baseUrl() . "/conversion/" . urlencode($id);
    }

    public static function baseUrl(): string
    {
        return ((($_SERVER['HTTPS'] ?? 'off') !== 'off') ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'];
    }

    public static function baseDir(): string
    {
        return realpath(__DIR__ . '/../');
    }
}
