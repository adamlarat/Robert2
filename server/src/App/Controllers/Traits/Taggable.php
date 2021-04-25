<?php
declare(strict_types=1);

namespace Robert2\API\Controllers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Robert2\API\Controllers\Traits\WithModel;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;

trait Taggable
{
    use WithModel;

    public function getAll(Request $request, Response $response): Response
    {
        $searchTerm = $request->getQueryParam('search', null);
        $searchField = $request->getQueryParam('searchBy', null);
        $tags = $request->getQueryParam('tags', []);
        $orderBy = $request->getQueryParam('orderBy', null);
        $limit = $request->getQueryParam('limit', null);
        $ascending = (bool)$request->getQueryParam('ascending', true);
        $withDeleted = (bool)$request->getQueryParam('deleted', false);

        $model = $this->getModel();
        if (!method_exists($model, 'getAllFilteredOrTagged')) {
            throw new \LogicException("Missing `getAllFilteredOrTagged` method in model.");
        }

        /** @var Builder $query */
        $query = $model
            ->setOrderBy($orderBy, $ascending)
            ->setSearch($searchTerm, $searchField)
            ->getAllFilteredOrTagged([], $tags, $withDeleted);

        $paginated = $this->paginate($request, $query, $limit);
        return $response->withJson($paginated);
    }

    public function getTags(Request $request, Response $response): Response
    {
        $id = (int)$request->getAttribute('id');
        $model = $this->getModelClass()::find($id);
        if (!$model) {
            throw new HttpNotFoundException($request);
        }

        return $response->withJson($model->tags);
    }

    // ------------------------------------------------------
    // -
    // -    Abstract methods
    // -
    // ------------------------------------------------------

    abstract protected function paginate(Request $request, $query, ?int $limit = null): array;
}
