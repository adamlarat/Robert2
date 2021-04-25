<?php
declare(strict_types=1);

namespace Robert2\API\Errors;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Robert2\API\Errors\Renderer\JsonErrorRenderer;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler as CoreErrorHandler;

class ErrorHandler extends CoreErrorHandler
{
    protected $defaultErrorRendererContentType = 'application/json';
    protected $defaultErrorRenderer = JsonErrorRenderer::class;

    protected $errorRenderers = [
        'application/json' => JsonErrorRenderer::class,
    ];

    protected function determineStatusCode(): int
    {
        if ($this->method === 'OPTIONS') {
            return 200;
        }

        if ($this->exception instanceof ModelNotFoundException) {
            return 404;
        }

        if ($this->exception instanceof HttpException) {
            return $this->exception->getCode();
        }

        $errorCode = $this->exception->getCode() ?: ERROR_SERVER;
        if ($errorCode >= 100 and $errorCode <= 599) {
            return $errorCode;
        }

        return ERROR_SERVER;
    }

    protected function writeToErrorLog(): void
    {
        if ($this->exception instanceof HttpException) {
            return;
        }
        parent::writeToErrorLog();
    }
}
