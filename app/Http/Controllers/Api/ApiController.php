<?php

namespace Courses\Http\Controllers\Api;

use Courses\Http\Controllers\Controller;
use Courses\Transformers\Abstract;
use Courses\Repositories\RepositoryInterface;
use League\Fractal\TransformerAbstract;

class ApiController extends Controller
{

    use TraitTransformer;

    protected $transformer;

    protected $repository;

    public function __construct(TransformerAbstract $transformer, RepositoryInterface $repository)
    {
        $this->transformer = $transformer;
        $this->repository = $repository;
    }

    public function index()
    {
        return $this->createJsonResponse(
            $this->repository->paginateResults()->all()
        );
    }

    public function show($primaryKey)
    {
        return $this->createJsonResponse(
            $this->repository->find($primaryKey)
        );
    }

}
