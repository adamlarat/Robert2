<?php
declare(strict_types=1);

namespace Robert2\API\Controllers;

use Robert2\API\Config\Config;
use Robert2\API\Controllers\Traits\Taggable;
use Robert2\API\Controllers\Traits\WithCrud;
use Robert2\API\Errors;
use Robert2\API\Models\Document;
use Robert2\API\Models\Event;
use Robert2\API\Models\Material;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;

class MaterialController extends BaseController
{
    use WithCrud, Taggable {
        Taggable::getAll insteadof WithCrud;
    }

    /** @var Material */
    protected $model;

    // ——————————————————————————————————————————————————————
    // —
    // —    Getters
    // —
    // ——————————————————————————————————————————————————————

    public function getAll(Request $request, Response $response): Response
    {
        $searchTerm = $request->getQueryParam('search', null);
        $searchField = $request->getQueryParam('searchBy', null);
        $parkId = $request->getQueryParam('park', null);
        $categoryId = $request->getQueryParam('category', null);
        $subCategoryId = $request->getQueryParam('subCategory', null);
        $dateForQuantities = $request->getQueryParam('dateForQuantities', null);
        $withDeleted = (bool)$request->getQueryParam('deleted', false);
        $tags = $request->getQueryParam('tags', []);

        $options = [];
        if ($parkId) {
            $options['park_id'] = (int)$parkId;
        }
        if ($categoryId) {
            $options['category_id'] = (int)$categoryId;
        }
        if ($subCategoryId) {
            $options['sub_category_id'] = (int)$subCategoryId;
        }

        $orderBy = $request->getQueryParam('orderBy', null);
        $ascending = (bool)$request->getQueryParam('ascending', true);

        $model = $this->model
            ->setOrderBy($orderBy, $ascending)
            ->setSearch($searchTerm, $searchField);

        if (empty($options) && empty($tags)) {
            $model = $model->getAll($withDeleted);
        } else {
            $model = $model->getAllFilteredOrTagged($options, $tags, $withDeleted);
        }

        $results = $model->paginate($this->itemsCount);

        $basePath = $request->getUri()->getPath();
        $params = $request->getQueryParams();
        $results = $results->withPath($basePath)->appends($params);
        $results = static::formatPagination($results);

        if ($dateForQuantities) {
            $results['data'] = $this->model->recalcQuantitiesForPeriod(
                $results['data'],
                $dateForQuantities,
                $dateForQuantities,
                null
            );
        }

        return $response->withJson($results);
    }

    public function getAllWhileEvent(Request $request, Response $response): Response
    {
        $eventId = (int)$request->getAttribute('eventId');

        $Event = new Event();
        $currentEvent = $Event->find($eventId);
        if (!$currentEvent) {
            throw new Errors\NotFoundException(
                sprintf("Event #%d was not found.", $eventId)
            );
        }

        $results = $this->model
            ->setOrderBy('reference', true)
            ->getAll()
            ->get()
            ->toArray();

        if ($results && count($results) > 0) {
            $results = $this->model->recalcQuantitiesForPeriod(
                $results,
                $currentEvent->start_date,
                $currentEvent->end_date,
                $eventId
            );
        }

        return $response->withJson($results);
    }

    // ------------------------------------------------------
    // -
    // -    Setters
    // -
    // ------------------------------------------------------

    public function create(Request $request, Response $response): Response
    {
        $postData = $request->getParsedBody();

        $result = $this->_saveMaterial(null, $postData);
        return $response->withJson($result, SUCCESS_CREATED);
    }

    public function update(Request $request, Response $response): Response
    {
        $id = (int)$request->getAttribute('id');
        $model = $this->model->find($id);
        if (!$model) {
            throw new Errors\NotFoundException;
        }

        $postData = $request->getParsedBody();

        $result = $this->_saveMaterial($id, $postData);
        return $response->withJson($result, SUCCESS_OK);
    }

    // ------------------------------------------------------
    // —
    // —    Internal Methods
    // —
    // ------------------------------------------------------

    protected function _saveMaterial(?int $id, array $postData): array
    {
        if (!is_array($postData) || empty($postData)) {
            throw new \InvalidArgumentException(
                "Missing request data to process validation",
                ERROR_VALIDATION
            );
        }

        if (array_key_exists('stock_quantity', $postData)) {
            $stockQuantity = $postData['stock_quantity'];
            if ($stockQuantity !== null && (int)$stockQuantity < 0) {
                $postData['stock_quantity'] = 0;
            }
        }

        if (array_key_exists('out_of_order_quantity', $postData)) {
            $stockQuantity = (int)($postData['stock_quantity'] ?? 0);
            $outOfOrderQuantity = (int)$postData['out_of_order_quantity'];
            if ($outOfOrderQuantity > $stockQuantity) {
                $outOfOrderQuantity = $stockQuantity;
                $postData['out_of_order_quantity'] = $outOfOrderQuantity;
            }
            if ($outOfOrderQuantity <= 0) {
                $postData['out_of_order_quantity'] = null;
            }
        }

        $result = $this->model->edit($id, $postData);

        if (isset($postData['attributes'])) {
            $attributes = [];
            foreach ($postData['attributes'] as $attribute) {
                if (empty($attribute['value'])) {
                    continue;
                }

                $attributes[$attribute['id']] = [
                    'value' => (string)$attribute['value']
                ];
            }
            $result->Attributes()->sync($attributes);
        }

        $model = $this->model->find($result->id);
        return $model->toArray();
    }

    public function getAllDocuments(Request $request, Response $response): Response
    {
        $id = (int)$request->getAttribute('id');
        $model = $this->model->find($id);
        if (!$model) {
            throw new Errors\NotFoundException;
        }

        return $response->withJson($model->documents, SUCCESS_OK);
    }

    public function handleUploadDocuments(Request $request, Response $response): Response
    {
        $id = (int)$request->getAttribute('id');
        $model = $this->model->find($id);
        if (!$model) {
            throw new Errors\NotFoundException;
        }

        $uploadedFiles = $request->getUploadedFiles();
        $destDirectory = Document::getFilePath($id);

        $errors = [];
        $files = [];
        foreach ($uploadedFiles as $file) {
            if ($file->getError() !== UPLOAD_ERR_OK) {
                $errors[$file->getClientFilename()] = 'File upload failed.';
                continue;
            }

            $fileType = $file->getClientMediaType();
            if (!in_array($fileType, Config::getSettings('authorizedFileTypes'))) {
                $errors[$file->getClientFilename()] = 'This file type is not allowed.';
                continue;
            }

            $filename = moveUploadedFile($destDirectory, $file);
            if (!$filename) {
                $errors[$file->getClientFilename()] = 'Saving file failed.';
                continue;
            }

            $files[] = [
                'material_id' => $id,
                'name' => $filename,
                'type' => $fileType,
                'size' => $file->getSize(),
            ];
        }

        foreach ($files as $document) {
            try {
                Document::updateOrCreate(
                    ['material_id' => $id, 'name' => $document['name']],
                    $document
                );
            } catch (\Exception $e) {
                $filePath = Document::getFilePath($id, $document['name']);
                unlink($filePath);
                $errors[$document['name']] = sprintf(
                    'Document could not be saved in database: %s',
                    $e->getMessage()
                );
            }
        }

        if (count($errors) > 0) {
            throw new \Exception(implode("\n", $errors));
        }

        $result = [
            'saved_files' => $files,
            'errors' => $errors,
        ];
        return $response->withJson($result, SUCCESS_OK);
    }
}
