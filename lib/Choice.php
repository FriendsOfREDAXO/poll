<?php

namespace Poll\Question;

use Poll\Question;
use Poll\Vote\Answer;

class Choice extends \rex_yform_manager_dataset
{
    /**
     * @return \rex_yform_manager_dataset|Question
     */
    public function getQuestion()
    {
        return $this->getRelatedDataset('question_id');
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getHits(): int
    {
        $hits = Answer::query()
            ->alias('a')
            ->joinRelation('vote_id', 'v')
            ->where('a.question_id', $this->getQuestion()->getId())
            ->where('a.question_choice_id', $this->getId())
            ->where('v.status', 1)
            ->find();

        return count($hits);
    }
}
