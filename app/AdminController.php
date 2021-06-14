<?php

declare(strict_types=1);

namespace App;

use Uniconv\ConverterInterface;

/**
 * Class ExchangeController
 * @package App
 *
 * This controller is **only** for internal use for a dockerized converter service
 */
class AdminController extends Controller
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
        $this->sendJSON("Written file " . basename($filename));
    }

    function getDockerfile()
    {
        echo $this->converterFromParameters()->dockerFile();
        exit();
    }

    function getConvertShellScript()
    {
        [
            'target' => $toExtension,
            'source' => $fromExtension,
        ] = $this->fileParameters();
        echo Helper::conversionShellScript($this->converterFromParameters(), $fromExtension, $toExtension);
        exit();
    }

    private function converterFromParameters(): ConverterInterface
    {
        [
            'target' => $toExtension,
            'source' => $fromExtension,
        ] = $this->fileParameters();
        return Converter::create(
            fromExtension: $fromExtension,
            toExtension: $toExtension,
            options: []
        );
    }

    private function fileParameters(): array
    {
        $username = $this->requirePathSegment(3, 'user');
        $id = $this->requirePathSegment(4, 'id');
        $data = Helper::jobFileData($username, $id);
        $file = __DIR__ . '/../' . $data['file']['filepath'];
        $file = realpath($file);
        $filename = basename($file);
        $fromExtension = pathinfo($filename, PATHINFO_EXTENSION);
        return [
            'sourceFilename' => $filename,
            'sourceFile' => $file,
            'target' => $data['target'],
            'source' => $fromExtension,
        ];
    }
}
