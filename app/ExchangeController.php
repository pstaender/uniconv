<?php

declare(strict_types=1);

namespace App;

/**
 * Class ExchangeController
 * @package App
 *
 * This controller is **only** for internal use for a dockerized converter service
 */

class ExchangeController extends Controller
{
    use Traits\AdminAuthenticable;

    function getFiles()
    {
        ['sourceFile' => $file, 'sourceFilename' => $filename] = $this->fileParameters();
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit();
    }

    function getJobs()
    {
        $username = $this->requirePathSegment(3, 'user');
        $id = $this->requirePathSegment(4, 'id');
        $data = Helper::jobFileData($username, $id);
        if (!$data) {
            throw new NotFoundException('Job not found');
        }
        return $this->sendJSON($data);
    }


    function postFiles()
    {
        $mode = $this->requirePathSegment(5, 'mode');
        if (!str_starts_with($mode, 'target')) {
            throw new InvalidArgumentException('Currently only `target` mode is supported');
        }
        ['sourceFile' => $sourceFile, 'target' => $target] = $this->fileParameters();
        $filename = dirname($sourceFile) . '/target.' . $target;
        $file = $this->files()[array_keys($this->files)[0] ?? null] ?? null;
        if (!$file) {
            throw new MissingParameterException('File is required as multipart form');
        }
        move_uploaded_file($file['tmp_name'], $filename);
        $this->sendJSON("Written file ".basename($filename));
    }

    private function fileParameters(): array
    {
        $username = $this->requirePathSegment(3, 'user');
        $id = $this->requirePathSegment(4, 'id');
        $data = Helper::jobFileData($username, $id);
        $file = realpath(__DIR__ . '/../' . $data['file']['filepath']);
        $filename = basename($file);
        return [
            'sourceFilename' => $filename,
            'sourceFile' => $file,
            'target' => $data['target'],
        ];
    }
}
