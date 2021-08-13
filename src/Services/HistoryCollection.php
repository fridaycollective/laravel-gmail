<?php

namespace FridayCollective\LaravelGmail\Services;

use Illuminate\Support\Collection;

class HistoryCollection extends Collection
{
    /**
     * @var History
     */
    private $history;

    /**
     * HistoryCollection constructor.
     *
     * @param array $items
     * @param History|null $history
     */
    public function __construct($items = [], History $history = null)
    {
        parent::__construct($items);
        $this->history = $history;
    }

    public function next()
    {
        return $this->history->next();
    }

    /**
     * Returns boolean if the page token variable is null or not
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return !!$this->history->pageToken;
    }
}
