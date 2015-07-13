<?php namespace Courses\Jobs\Scraper;

use Courses\Subject;
use Illuminate\Database\Eloquent\Model;

class ScrapeSubjects
{

    const CATALOG_URL  = 'http://catalog.oregonstate.edu/SOC.aspx';
    const QUERY_PARAMS = '?level=all&amp;campus=%s&amp;term=%s';

    public function fire($job, $data)
    {
        $url      = sprintf(self::CATALOG_URL);
        $contents = file_get_contents($url);

        $terms    = $this->getTerms($contents);
        $campuses = $this->getCampuses($contents);

        $this->dispatchCampusJobs($terms, $campuses[0]);

        $job->delete();
    }

    private function dispatchCampusJobs($terms, $campus)
    {
        collect($terms)->each(function ($term) use ($campus) {
            \Queue::push('\Courses\Jobs\Scraper\ScrapeSubjects@scrape', [
                'campus' => $campus,
                'term'   => $term,
            ]);
        });
    }

    private function extractMatches($index, $matches)
    {
        if (sizeof($matches) <= 2) {
            return trim($matches[1][$index]);
        }

        // if there is more than one group in the regex, return the
        // group values as an array

        $details = [];
        for ($j = 1; $j < sizeof($matches); $j++) {
            $details[] = trim($matches[$j][$index]);
        }
        return $details;

    }

    private function getMatches($contents, $delimiter, $pattern)
    {
        $subbed  = substr($contents, strpos($contents, $delimiter));
        $results = preg_match_all($pattern, $subbed, $matches);

        $values = [];
        for ($index = 0; $index < $results; $index++) {
            $values[] = $this->extractMatches($index, $matches);
        }
        return $values;
    }

    private function getTerms($contents)
    {
        return $this->getMatches($contents,
            'ctl00_ContentPlaceHolder1_ddlTerm',
            '~<option[^>]+value="(\d+)">[^<]+\d+</option>~');
    }

    private function getCampuses($contents)
    {
        return $this->getMatches($contents,
            'ctl00$ContentPlaceHolder1$ddlCampus',
            '~<option[^>]+value="([^"]+)">[^<]+</option>~');
    }

    private function getSubjects($contents)
    {
        $subjects = $this->getMatches($contents,
            'ctl00_ContentPlaceHolder1_dlSubjects',
            '~<a href="[^"]+subjectcode=([A-Z]+)">([^\(]+)\s+\(~');

        return array_map(function ($subj) {
            return [
                'id'   => $subj[0],
                'name' => $subj[1],
            ];
        }, $subjects);
    }

    public function scrape($job, $data)
    {
        $url = sprintf(self::CATALOG_URL . self::QUERY_PARAMS,
            $data['campus'] ?: 'corvallis', $data['term'] ?: '');

        $contents = file_get_contents($url);

        Model::unguard();

        array_map(function ($subj) {
            try
            {
                if (is_null(Subject::find($subj)->first())) {
                    $subj = Subject::create($subj);
                    print_r($subj->toArray());
                }

                \Queue::push('Courses\Jobs\Scraper\ScrapeSubject', [
                    'subject' => $subj['id'],
                ]);
            } catch (Illuminate\Database\QueryException $e) {}
        }, $this->getSubjects($contents));

        $job->delete();
    }
}
