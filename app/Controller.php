<?php

declare(strict_types=1);

namespace App;

class Controller
{

    protected ?array $user = null;

    use Traits\SendJSON;
    use Traits\RequestParameters;

    function __construct(
        protected array $request,
        protected string $requestMethod,
        protected array $server,
        protected array $headers,
        protected array $files
    ) {
    }

    function run()
    {
        try {
            return $this->checkAuthAndDelegateAction();
        } catch (UnauthorizedException $e) {
            return $this->sendErrorMessage('Unauthorized', 401);
        } catch (MissingParameterException $e) {
            return $this->sendErrorMessage($e->getMessage(), 400);
        } catch (\InvalidArgumentException $e) {
            return $this->sendErrorMessage($e->getMessage(), 400);
        } catch (NotFoundException $e) {
            $msg = (!empty($e->getMessage())) ? $e->getMessage() : 'Not found';
            return $this->sendErrorMessage($msg, 404);
        }
    }

    private function checkAuthAndDelegateAction()
    {
        if (method_exists($this, 'authorize')) {
            $this->authorize();
        }
        $urlParts = array_values(array_filter(explode('/', $this->server['REQUEST_URI']), fn ($v) => !empty($v)));
        $methodName = $urlParts[1] ?? 'index';
        if ($this->requestMethod === 'GET') {
            if (method_exists($this, $methodName)) {
                return $this->$methodName();
            }
        }
        $methodName = strtolower($this->requestMethod) . ucfirst($methodName);
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }
        $methodName = strtolower($this->requestMethod).'CatchAll';
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }
        throw new NotFoundException();
    }

    protected function username(): string
    {
        return $this->user['email'];
    }

    protected function request(): array
    {
        return $this->request;
    }

    protected function requestMethod(): string
    {
        return $this->requestMethod;
    }

    protected function headers()
    {
        return $this->headers;
    }

    protected function files()
    {
        return $this->files;
    }

    protected function server()
    {
        return $this->server;
    }
}
