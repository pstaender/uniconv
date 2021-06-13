<?php

declare(strict_types=1);

namespace App;

class FileController extends Controller
{

    use Traits\UserAuthenticable;

    protected function getCatchAll()
    {
        $id = $this->requirePathSegment(2, 'id');
        $this->stopsWithErrorMessageIfConversionIsStillInProgress($this->username(), $id);

        [
            'id' => $id,
            'filename' => $filename,
            'jobData' => $data
        ] = $this->getConversionStatusRequiredParams();

        if (empty($filename)) {
            $filename = pathinfo($data['file']['filename'], PATHINFO_FILENAME) . '.' . $data['target'];
        }

        $file = __DIR__ . '/../' . Helper::conversionFolder($this->username(), $data['id']) . '/target.' . $data['target'];

        if (!file_exists($file)) {
            $this->sendErrorMessage('File is not converted, yet', 404);
        }

        $file = realpath($file);

        $ext = new \FileEye\MimeMap\Extension($data['target']);
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $ext->getDefaultType());
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        if ($data['keepFilesAfterDownload'] !== true) {
            Helper::deleteConversion($this->username(), $id);
        }
        exit();
    }

    protected function deleteCatchAll()
    {
        $id = $this->requirePathSegment(2, 'id');
        // $this->stopsWithErrorMessageIfConversionIsStillInProgress($this->username(), $id);
        Helper::deleteConversion(username: $this->username(), id: $id, force: true);
    }

    protected function stopsWithErrorMessageIfConversionIsStillInProgress(string $username, string $id)
    {
        if (file_exists(Helper::jobFile($this->username(), $id) . '.pid')) {
            $this->sendErrorMessage('Conversion is in progress', 409);
        }
    }

    private function getConversionStatusRequiredParams(): array
    {
        $id = $this->requirePathSegment(2, 'id');
        $filename = $this->pathSegments()[2] ?? null;
        $data = Helper::jobFileData($this->username(), $id);
        if (!$data) {
            throw new NotFoundException();
        }
        return ['id' => $id, 'filename' => $filename, 'jobData' => $data];
    }
}
