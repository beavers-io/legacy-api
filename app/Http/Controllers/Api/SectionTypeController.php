<?php

namespace Courses\Http\Controllers\Api;

use Courses\Repositories\SectionType\SectionTypeRepositoryInterface;
use Courses\Transformers\SectionTypeTransformer;

class SectionTypeController extends ApiController
{

    public function __construct(SectionTypeTransformer $transformer, SectionTypeRepositoryInterface $repository)
    {
        parent::__construct($transformer, $repository);
    }

}
