<?php

namespace Courses\Http\Controllers\Api;

use Courses\Repositories\Instructor\InstructorRepositoryInterface;
use Courses\Transformers\InstructorTransformer;

class InstructorController extends ApiController
{

    public function __construct(InstructorTransformer $transformer, InstructorRepositoryInterface $repository)
    {
        parent::__construct($transformer, $repository);
    }

}
