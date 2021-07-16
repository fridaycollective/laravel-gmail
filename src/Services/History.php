<?php

namespace FridayCollective\LaravelGmail\Services;

use Google_Service_Gmail;
use FridayCollective\LaravelGmail\LaravelGmail;
use FridayCollective\LaravelGmail\Services\Message\Mail;
use FridayCollective\LaravelGmail\Traits\Filterable;
use FridayCollective\LaravelGmail\Traits\SendsParameters;
use Illuminate\Support\Facades\Log;

class History
{

    use SendsParameters,
        Filterable;

    public $service;

    public $preload = false;

    public $pageToken;

    public $client;

    /**
     * Optional parameter for getting single and multiple emails
     *
     * @var array
     */
    protected $params = [];

    protected $startHistoryId;

    /**
     * Message constructor.
     *
     * @param LaravelGmail $client
     */
    public function __construct(LaravelGmail $client, $startHistoryId = null)
    {
        $this->client = $client;
        $this->service = new Google_Service_Gmail($client);
        $this->startHistoryId = $startHistoryId;
    }

    /**
     * Returns next page if available of messages or an empty collection
     *
     * @return \Illuminate\Support\Collection
     * @throws \Google_Exception
     */
    public function next()
    {
        if ($this->pageToken) {
            return $this->getHistory($this->pageToken);
        } else {
            return new HistoryCollection([], $this);
        }
    }

    /**
     * Returns a collection of Mail instances
     *
     * @param null|string $pageToken
     *
     * @return \Illuminate\Support\Collection
     * @throws \Google_Exception
     */
    public function getHistory($pageToken = null)
    {
        if (!is_null($pageToken)) {
            $this->add($pageToken, 'pageToken');
        }

        $response = $this->getHistoryResponse($this->startHistoryId);

        $this->pageToken = method_exists($response, 'getNextPageToken') ? $response->getNextPageToken() : null;

        $all = new HistoryCollection($response->history, $this);

        return $all;
    }

    /**
     * Returns boolean if the page token variable is null or not
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return !!$this->pageToken;
    }


    /**
     * Preload the information on each Mail objects.
     * If is not preload you will have to call the load method from the Mail class
     * @return $this
     * @see Mail::load()
     *
     */
    public function preload()
    {
        $this->preload = true;

        return $this;
    }

    public function getUser()
    {
        return $this->client->user();
    }

    /**
     * @param $id
     *
     * @return \Google_Service_Gmail_Message
     */
    private function getRequest($id)
    {
        return $this->service->users_messages->get('me', $id);
    }


    /**
     * @return \Google_Service_Gmail_ListMessagesResponse|object
     * @throws \Google_Exception
     */
    private function getHistoryResponse($startHistoryId)
    {
        $responseOrRequest = $this->service->users_history->listUsersHistory('me', [
            'startHistoryId' => $startHistoryId
        ]);

        if (get_class($responseOrRequest) === "GuzzleHttp\Psr7\Request") {
            $response = $this->service->getClient()->execute($responseOrRequest, 'Google_Service_Gmail_ListHistoryResponse');

            return $response;
        }

        return $responseOrRequest;
    }
}
