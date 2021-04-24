<?php
declare(strict_types=1);

namespace Robert2\API\Controllers;

use Robert2\API\Controllers\Traits\Taggable;
use Robert2\API\Controllers\Traits\WithCrud;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;

class CompanyController extends BaseController
{
    use WithCrud, Taggable {
        Taggable::getAll insteadof WithCrud;
    }

    // ——————————————————————————————————————————————————————
    // —
    // —    Model dedicated methods
    // —
    // ——————————————————————————————————————————————————————

    public function getPersons(Request $request, Response $response): Response
    {
        $id = (int)$request->getAttribute('id');
        if (!$this->model->exists($id)) {
            throw new HttpNotFoundException($request);
        }

        $results = $this->model->find($id)->Persons();
        return $response->withJson($this->paginate($request, $results));
    }
}
