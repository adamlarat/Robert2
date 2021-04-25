<?php
declare(strict_types=1);

namespace Robert2\API\Controllers;

use Robert2\API\Controllers\Traits\WithCrud;
use Robert2\API\Errors;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;

class TagController extends BaseController
{
    use WithCrud;

    public function getPersons(Request $request, Response $response): Response
    {
        $id = (int)$request->getAttribute('id');
        if (!$this->model->exists($id)) {
            throw new Errors\NotFoundException;
        }

        $Tag       = $this->model->find($id);
        $materials = $Tag->Persons()->paginate($this->itemsCount);

        $basePath = $request->getUri()->getPath();
        $materials->withPath($basePath);

        $results = static::formatPagination($materials);
        return $response->withJson($results);
    }

    public function getMaterials(Request $request, Response $response): Response
    {
        $id = (int)$request->getAttribute('id');
        if (!$this->model->exists($id)) {
            throw new Errors\NotFoundException;
        }

        $Tag       = $this->model->find($id);
        $materials = $Tag->Materials()->paginate($this->itemsCount);

        $basePath = $request->getUri()->getPath();
        $materials->withPath($basePath);

        $results = static::formatPagination($materials);
        return $response->withJson($results);
    }
}
