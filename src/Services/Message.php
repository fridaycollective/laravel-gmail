<?php

namespace FridayCollective\LaravelGmail\Services;

use Google_Service_Gmail;
use FridayCollective\LaravelGmail\LaravelGmail;
use FridayCollective\LaravelGmail\Services\Message\Mail;
use FridayCollective\LaravelGmail\Traits\Filterable;
use FridayCollective\LaravelGmail\Traits\SendsParameters;

class Message
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

    /**
     * Message constructor.
     *
     * @param LaravelGmail $client
     */
    public function __construct(LaravelGmail $client)
    {
        $this->client = $client;
        $this->service = new Google_Service_Gmail($client);
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
            return $this->all($this->pageToken);
        } else {
            return new MessageCollection([], $this);
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
    public function all($pageToken = null)
    {
        if (!is_null($pageToken)) {
            $this->add($pageToken, 'pageToken');
        }

        $messages = [];
        $response = $this->getMessagesResponse();
        $this->pageToken = method_exists($response, 'getNextPageToken') ? $response->getNextPageToken() : null;

        $allMessages = $response->getMessages();

        if (!$this->preload) {
            foreach ($allMessages as $message) {
                $messages[] = new Mail(
                    $message,
                    $this->preload,
                    $this->client->_integrationConfig,
                    $message->historyId
                );
            }
        } else {
            $messages = $this->batchRequest($allMessages);
        }

        $all = new MessageCollection($messages, $this);

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
     * Limit the messages coming from the queryxw
     *
     * @param int $number
     *
     * @return Message
     */
    public function take($number)
    {
        $this->params['maxResults'] = abs((int)$number);

        return $this;
    }

    /**
     * @param $id
     *
     * @return Mail
     */
    public function get($id)
    {
        $message = $this->getRequest($id);

        return new Mail($message, null, $this->client->_integrationConfig);
    }

    /**
     * Creates a batch request to get all emails in a single call
     *
     * @param $allMessages
     *
     * @return array|null
     */
    public function batchRequest($allMessages)
    {
        $this->client->setUseBatch(true);

        $batch = $this->service->createBatch();

        foreach ($allMessages as $key => $message) {
            $batch->add($this->getRequest($message->getId()), $key);
        }

        $messagesBatch = $batch->execute();

        $this->client->setUseBatch(false);

        $messages = [];

        foreach ($messagesBatch as $message) {
            $messages[] = new Mail(
                $message,
                null,
                $this->client->_integrationConfig,
                $message->historyId
            );
        }

        return $messages;
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
    private function getMessagesResponse()
    {
        $responseOrRequest = $this->service->users_messages->listUsersMessages('me', $this->params);

        if (get_class($responseOrRequest) === "GuzzleHttp\Psr7\Request") {
            $response = $this->service->getClient()->execute($responseOrRequest, 'Google_Service_Gmail_ListMessagesResponse');

            return $response;
        }

        return $responseOrRequest;
    }
}
