<?php

namespace Poll;

use Poll\Question\Choice;
use Poll\Vote\Answer;

class Question extends \rex_yform_manager_dataset
{
    /**
     * @param \rex_yform_manager_collection $items
     *
     * @return \rex_yform_manager_collection
     */
    public static function populateChoices(\rex_yform_manager_collection $items)
    {
        return $items->populateRelation('choices');
    }

    /**
     * @return \rex_yform_manager_collection|Choice[]
     */
    public function getChoices()
    {
        return $this->getRelatedCollection('choices');
    }
    /**
     * @return \rex_yform_manager_dataset|Poll
     */
    public function getPoll()
    {
        return $this->getRelatedDataset('poll_id');
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMedia(): string
    {
        return $this->media;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHits(): int
    {
        $hits = Answer::query()
            ->alias('a')
            ->joinRelation('vote_id', 'v')
            ->where('a.question_id', $this->getId())
            ->where('v.status', 1)
            ->groupBy('a.vote_id')
            ->find();

        return count($hits);
    }

    public function getAnswers(): \rex_yform_manager_collection
    {
        return Answer::query()
            ->alias('a')
            ->joinRelation('vote_id', 'v')
            ->where('a.question_id', $this->getId())
            ->where('a.question_choice_id', 0)
            ->where('a.text', '', '!=')
            ->where('v.status', 1)
            ->find();
    }
}
