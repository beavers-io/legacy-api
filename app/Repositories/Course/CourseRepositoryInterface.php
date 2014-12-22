<?php namespace Courses\Repositories\Course;

use Courses\Repositories\RepositoryInterface;

interface CourseRepositoryInterface extends RepositoryInterface {

	public function find($id);
	public function paginateResults($subject_id);

}
