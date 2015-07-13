<?php namespace Courses\Jobs\Scraper;

use Courses\Course;
use Courses\Instructor;
use Courses\Section;
use Courses\SectionType;
use Courses\Subject;
use Illuminate\Database\Eloquent\Model;

class ScrapeCatalog
{

    const CATALOG_URL = 'http://catalog.oregonstate.edu/CourseDetail.aspx?Columns=afghjklmopqrstuvz&SubjectCode=%s&CourseNumber=%s';

    // http://stackoverflow.com/a/2087136/758310
    private function DOMinnerHTML(\DOMElement $element)
    {
        $innerHTML = "";
        $children  = $element->childNodes;

        foreach ($children as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }

        return trim($innerHTML);
    }

    public function fire($job, $data)
    {
        try
        {
            $this->work($data);
            $job->delete();
        } catch (\ErrorException $e) {
            $job->release();
            throw $e;
        }
    }

    private function extractCourseInfo(\DOMDocument $dom, $data)
    {
        $key = $dom->getElementsByTagName('h3')->item(0);

        preg_match('~\s+([A-Z]+\s+\d+[A-Z]*)\.\s+([^\n]+)\s+\((\d+).*\)\.\s+~', $key->textContent, $matches);

        $course_id = $data['subject'] . $data['level'];

        $course = Course::firstOrNew(['id' => $course_id]);

        $course_info = [
            'id'          => $course_id,
            'level'       => $data['level'],
            'title'       => trim($matches[2]),
            'description' => trim($key->nextSibling->wholeText),
            'subject_id'  => Subject::find($data['subject'])->id,
        ];

        if (!is_null($dom->getElementById('ctl00_ContentPlaceHolder1_lblCoursePrereqs'))) {
            $course_info['prereqs'] = trim($dom->getElementById('ctl00_ContentPlaceHolder1_lblCoursePrereqs')->nextSibling->wholeText);
        }

        foreach ($course_info as $key => $value) {
            $course->$key = $value;
        }

        $course->save();

        return $course_info;
    }

    private function extractEnrollmentData($row, $offset)
    {
        return [
            'cap'       => intval($row->childNodes->item($offset)->textContent),
            'current'   => intval($row->childNodes->item($offset + 1)->textContent),
            'available' => intval($row->childNodes->item($offset + 2)->textContent),
        ];
    }

    private function createOrUpdateEnrollment(Section $sec, $enrollmentInfo, $enrollmentKey)
    {
        if (is_null($sec->{$enrollmentKey})) {
            $sec->{$enrollmentKey} = SectionEnrollment::create($enrollmentInfo)->id;
        }

        $enrollment = SectionEnrollment::find($sec->{$enrollmentKey});
        foreach ($enrollmentInfo as $key => $value) {
            $enrollment->$key = $value;
        }
        $enrollment->save();
    }

    private function extractSection($row, $section_info)
    {
        $sec = Section::firstOrNew($section_info);

        $values = [
            'term'            => trim($row->childNodes->item(0)->textContent),
            'section_number'  => intval($row->childNodes->item(2)->textContent),
            'credits'         => intval($row->childNodes->item(3)->textContent),
            'raw_times'       => $this->DOMinnerHTML($row->childNodes->item(5)->childNodes->item(0)),
            'raw_locations'   => $this->DOMinnerHTML($row->childNodes->item(6)->childNodes->item(0)),
            'instructor_id'   => Instructor::firstOrCreate(['name' => $row->childNodes->item(4)->textContent])->id,
            'section_type_id' => SectionType::firstOrCreate(['name' => $row->childNodes->item(8)->textContent])->id,
        ];

        foreach ($values as $key => $value) {
            $sec->$key = $value;
        }

        $this->createOrUpdateEnrollment($sec, $this->extractEnrollmentData($row, 10), 'current_enrollment_id');
        $this->createOrUpdateEnrollment($sec, $this->extractEnrollmentData($row, 13), 'waitlist_enrollment_id');

        return $sec;
    }

    public function work($data)
    {
        Model::unguard();

        $url      = sprintf(self::CATALOG_URL, $data['subject'], $data['level']);
        $contents = file_get_contents($url);

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument;
        $dom->loadHTML($contents);

        $course_info = $this->extractCourseInfo($dom, $data);
        $course_id   = $course_info['id'];

        $table = $dom->getElementById("ctl00_ContentPlaceHolder1_SOCListUC1_gvOfferings");

        if (is_null($table)) {
            return;
        }

        $rowNumber = 0;
        foreach ($table->getElementsByTagName('tr') as $row) {
            if ($rowNumber++ == 0) {
                continue;
            }

            $sec = $this->extractSection($row, [
                'id'        => intval($row->childNodes->item(1)->textContent),
                'course_id' => $course_id,
            ]);

            $sec->save();
        }
    }
}
