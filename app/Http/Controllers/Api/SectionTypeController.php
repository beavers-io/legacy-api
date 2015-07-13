<?php namespace Courses\Http\Controllers\Api;

use Courses\Http\Controllers\Controller;
use Courses\Repositories\SectionType\SectionTypeRepositoryInterface;
use Courses\Transformers\SectionTypeTransformer;

class SectionTypeController extends Controller
{

    use TraitTransformer;

    protected $transformer;

    public function __construct(SectionTypeTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function index(SectionTypeRepositoryInterface $sectionTypeRepo)
    {
        return $this->createJsonResponse(
            $sectionTypeRepo->paginateResults()->all()
        );
    }

    public function show(SectionTypeRepositoryInterface $sectionTypeRepo, $sectionTypeId)
    {
        return $this->createJsonResponse(
            $sectionTypeRepo->find($sectionTypeId)
        );
    }

}
