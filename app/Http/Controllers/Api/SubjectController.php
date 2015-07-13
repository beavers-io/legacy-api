<?php

namespace Courses\Http\Controllers\Api;

use Courses\Repositories\Subject\SubjectRepositoryInterface;
use Courses\Transformers\SubjectTransformer;

class SubjectController extends ApiController
{

    public function __construct(SubjectTransformer $transformer, SubjectRepositoryInterface $subjectRepo)
    {
        parent::__construct($transformer, $repository);
    }

}
