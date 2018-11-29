<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Location;

class Question extends Model
{
    protected $fillable = [
        'question_html',
    ];

    public $timestamps = false;
    protected $table = 'question';

    private static function setQuestionRatingInCache(int $question_id, $location, $new_value)
    {
        if (is_string($location)) {
            // If location is just the id of a location, look it up.
            $location = Location::find($location);
        }
        $ratings_cache = $location->ratings_cache;
        if ($location->ratings_cache === null) {
            $ratings_cache = [];
        }
        $ratings_cache[''.$question_id] = $new_value;
        $location->ratings_cache = $ratings_cache;
        $location->save();
    }

    public function getAccessibilityRating($location_id, $ratingSystem)
    {
        // See if the value is in the location's ratings_cache.
        $location = Location::find($location_id);
        if ($location->ratings_cache && isset($location->ratings_cache[''.$this->id])) {
            return $location->ratings_cache[$this->id];
        }

        $answers = $this->answers()
            ->whereNull('deleted_at')
            ->where('location_id', '=', $location_id)
            ->where('user_answer.location_id', '=', $location_id)
            ->get(['answer_value']);
        $sum = 0;
        $totalCount = 0;
        foreach ($answers as $answer) {
            $individualRating = intval($answer->answer_value);

            // count N/A the same as yes(1).
            if ($individualRating === 2) {
                $individualRating = 1;
            }

            // Skip the "I didn't look at this" values.
            if ($individualRating !== 3) {
                $sum = $sum + $individualRating;
                $totalCount = $totalCount + 1;
            }
        }
        $rating_value = 0;
        if ($totalCount !== 0) {
            $rating_value = $sum * 100 / $totalCount;
        } else {
            // Use the associated group's ratings cache if there is one.
            if ($location->location_group_id) {
                $location_group = $location->locationGroup()->get()->first();
            } else {
                $location_group = LocationGroup::getRootLocationGroup();
            }
            if ($location_group && $location_group->ratings_cache !== null) {
                $ratings_cache = json_decode($location_group->ratings_cache, true);
                if (isset($ratings_cache['' . $this->id]) && is_numeric($ratings_cache['' . $this->id])) {
                    $rating_value = $ratings_cache['' . $this->id];
                }
            }
        }
        self::setQuestionRatingInCache($this->id, $location, $rating_value);
        return $rating_value;
    }

    public function answers()
    {
        return $this->hasMany('App\UserAnswer');
    }
}
