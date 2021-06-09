<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

class ConversionController extends Controller
{

    use Traits\UserAuthenticable;

    protected function index()
    {
        $this->sendJSON("Hello {$this->username()}! Welcome to convert");
    }

    protected function getCatchAll()
    {
        $id = $this->requirePathSegment(2, 'id');
        $status = null;
        if (!file_exists(Helper::conversionFolder($this->username(), $id))) {
            return throw new NotFoundException();
        }
        $jobFile = Helper::jobFile($this->username(), $id);
        if (file_exists($jobFile . '.done')) {
            $status = 'done';
        } elseif (file_exists(Helper::jobFile($this->username(), $id))) {
            $status = 'queued';
        }
        if (file_exists(Helper::jobFile($this->username(), $id) . '.pid')) {
            $status = 'processing';
        }
        return $this->sendJSON([
            'status' => $status,
            'download_url' => Helper::downloadFileUrl($this->username(), $id),
        ]);
    }

    protected function postCatchAll()
    {
        $toExtension = strtolower($this->requirePathSegment(2, 'target'));
        $file = $this->file($toExtension);
        if (!$file) {
            return $this->sendErrorMessage("Sending a file via multipart form is required", 400);
        }
        $id = $file['id'];

        $extension = strtolower($file['extension']);
        $args = [];
        try {
            // test creating converter
            Converter::createFromRequestParameters(fromExtension: $extension, toExtension: $toExtension, controller: $this);
            $args = Converter::createConvertArguments(fromExtension: $extension, toExtension: $toExtension, controller: $this);
        } catch (ConverterClassNotFound $e) {
            Helper::deleteConversion(username: $this->username(), id: $id, force: true);
            $this->sendErrorMessage("Converting from $extension to $toExtension is not supported", 501);
        }


        // store job file, i.e. queue job
        file_put_contents($file['jobfile'], json_encode([
            'file' => $file,
            'request' => $this->request,
            'user' => $this->username(),
            'id' => $id,
            'options' => $args,
            'target' => $toExtension,
            'deleteAfterDownload' => in_array($this->param('deleteAfterDownload'), ['true', '1'])
        ], JSON_PRETTY_PRINT));

        $this->sendJSON([
            'message' => 'queued',
            'status_url' => Helper::statusUrlForConversion($this->username(), $id),
            'download_url' => Helper::downloadFileUrl($this->username(), $id),
        ]);
    }

    protected function file(string $toExtension): ?array
    {
        $file = $this->files[array_keys($this->files)[0] ?? null] ?? null;
        $f = [];
        if (!isset($file['error'])) {
            throw new InvalidArgumentException('There was a general problem with your file upload. Maybe the upload file size is exceeded.');
        }
        if ($file['error'] > 0) {
            match ($file['error']) {
                1 => throw new InvalidArgumentException('File upload size is exceeded'),
                3 => throw new InvalidArgumentException('The uploaded file was only partially uploaded'),
                4 => throw new InvalidArgumentException('No file was uploaded'),
                default => throw new InvalidArgumentException("File Upload failed; " . $file['error']),
            };
        }
        if ($file) {
            $f['extension'] = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $f['sha1'] = sha1_file($file['tmp_name']);
            $f['filename'] = $file['name'];
            $id = sha1(implode('', [
                $f['sha1'],
                $f['extension'],
                $toExtension,
                json_encode($this->request())
            ]));
            $f['id'] = $id;
            $f['jobfile'] = Helper::jobFile($this->username(), $id);
            $f['filepath'] = Helper::conversionFolder($this->username(), $id . '/source.' . $f['extension']);
            if (!file_exists(dirname($f['filepath']))) {
                mkdir(dirname($f['filepath']), recursive: true);
            }
            if (file_exists($f['filepath']) && filesize($f['filepath']) > 0) {
                $url = Helper::statusUrlForConversion($this->username(), $id);
                return $this->sendErrorMessage(
                    'File already uploaded. Please check conversion status instead.',
                    409,
                    ['status_url' => $url]
                );
            }
            move_uploaded_file($file['tmp_name'], $f['filepath']);
        }
        return $f;
    }
}
