<?php
declare(strict_types=1);

namespace Robert2\API\Controllers;

use Robert2\API\Controllers\Traits\WithCrud;
use Robert2\API\Models\Park;

class ParkController extends BaseController
{
    use WithCrud;

    /** @var Park */
    protected $model;
}
