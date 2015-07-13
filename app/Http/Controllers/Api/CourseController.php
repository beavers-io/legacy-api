<?php namespace Courses\Http\Controllers\Api;

use Courses\Http\Controllers\Controller;
use Courses\Repositories\Course\CourseRepositoryInterface;
use Courses\Transformers\CourseTransformer;

class CourseController extends Controller
{

    use TraitTransformer;

    protected $transformer;

    public function __construct(CourseTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function index(CourseRepositoryInterface $courseRepo, $subject_id)
    {
        return $this->createJsonResponse(
            $courseRepo->findBySubjectId($subject_id)->get()->all()
        );
    }

    public function show(CourseRepositoryInterface $courseRepo, $subject_id, $course_id)
    {
        return $this->createJsonResponse(
            $courseRepo->find($course_id)
        );
    }

}
