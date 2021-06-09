<?php

namespace App\Traits;

trait RequestParameters
{
    public function requireParam($param, ?string $type = null)
    {
        $val = $this->param($param, $type);
        if (empty($val)) {
            throw new \App\MissingParameterException("Parameter '$param' is required");
        }
        return $val;
    }

    public function param($param, ?string $type = null)
    {
        $val = $this->params()[$param] ?? null;
        if ($type && !empty($val)) {
            if ($type === 'int') {
                $type = 'integer';
            }
            if ($type[0] === '?') {
                if ($val === null) {
                    return null;
                }
                $type = substr($type, 1);
            }
            if (($type === 'integer' || $type === 'int') && ((int) $val == $val)) {
                $val = (int) $val;
            } else if ($type === 'float' && is_numeric($val)) {
                $val = (float) $val;
            } else if ($type === 'boolean' && ($val === 'true' || $val === '1' || $val === 'false' || $val === '0')) {
                $val = (string) $val;
                $val = (!empty($val) && $val !== 'false' && $val !== '0');
            } else {
                $val = (string) $val;
            }
            if (gettype($val) !== $type) {
                return $this->sendErrorMessage("Parameter '$param' needs to be of type $type (".strtolower(gettype($val))." given)", 400);
            }
        }
        return $val;
    }

    protected function params(): array
    {
        if ($this->requestMethod === 'GET') {
            return $this->request;
        } else {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? null;
            if (!empty($contentType) && str_contains(strtolower($contentType), 'application/json')) {
                return json_decode(file_get_contents("php://input"), true);
            } else {
                return $this->request;
            }
        }
    }

    protected function pathSegments(): array
    {
        return explode('/', trim($this->server['REQUEST_URI'], '/'));
    }

    protected function requirePathSegment(int $index, $name = '')
    {
        if (isset($this->pathSegments()[$index - 1])) {
            return $this->pathSegments()[$index - 1];
        } else {
            if (empty($name)) {
                $name = match ($index) {
                    1 => '1st',
                    2 => '2snd',
                    3 => '3rd',
                    default => $index.'th',
                };
            }
            throw new \App\MissingParameterException("The $name segment in the path is required");
        }
    }
}
