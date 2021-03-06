<?php namespace Courses\Http\Controllers\Frontend;

use Courses\Repositories\Course\CourseRepositoryInterface;
use Courses\Repositories\Section\SectionRepositoryInterface;

class CourseController extends FrontendController
{

    public function index(CourseRepositoryInterface $repo)
    {
        return $this->view->make('frontend.courses.index', [
            'courses' => array_get($repo->all(), 'data'),
        ]);
    }

    public function show(
        CourseRepositoryInterface $repo,
        SectionRepositoryInterface $sectionRepo,
        $subject_id,
        $course_id
    ) {
        $course = $repo->find($course_id);

        $sections = $sectionRepo->all($course_id);

        $terms = [];
        foreach ($sections as $section) {
            if (!array_key_exists($section['term'], $terms)) {
                $terms[$section['term']] = [
                    'id'       => $section['term'],
                    'name'     => term_name($section['term']),
                    'sections' => [],
                ];
            }

            $terms[$section['term']]['sections'][] = $section;
        }

        array_set($course, 'sections', $sections);

        return $this->view->make('frontend.courses.show', [
            'course'      => $course,
            'single_page' => true,
            'terms'       => $terms,
            'title'       => sprintf('Which Course For Me | %s - %s', $course['id'], title_case($course['title'])),
        ]);
    }

}
