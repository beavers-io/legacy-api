<?php namespace Courses\Http\Controllers\Api;

use Courses\Http\Controllers\Controller;
use Courses\Repositories\Subject\SubjectRepositoryInterface;
use Courses\Transformers\SubjectTransformer;

class SubjectController extends Controller
{

    use TraitTransformer;

    protected $transformer;

    public function __construct(SubjectTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function index(SubjectRepositoryInterface $subjectRepo)
    {
        return $this->createJsonResponse(
            $subjectRepo->paginateResults()->all()
        );
    }

    public function show(SubjectRepositoryInterface $subjectRepo, $subject_id)
    {
        return $this->createJsonResponse(
            $subjectRepo->find($subject_id)
        );
    }

}
