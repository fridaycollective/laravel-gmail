<?php

namespace FridayCollective\LaravelGmail;

use FridayCollective\LaravelGmail\Exceptions\AuthException;
use FridayCollective\LaravelGmail\Services\History;
use FridayCollective\LaravelGmail\Services\Message;
use Illuminate\Support\Facades\Config;

class LaravelGmail extends GmailConnection
{
    protected $service;

    public function __construct($integrationConfig)
    {
        $config = Config::get('gmail');
        $config['redirect_url'] = env('GOOGLE_REDIRECT_URI');
        $config['state'] = $integrationConfig->state_uuid;

        parent::__construct($config, $integrationConfig);
    }

    /**
     * @return Message
     * @throws AuthException
     */
    public function message()
    {
        if (!$this->getToken()) {
            throw new AuthException('No credentials found.');
        }

        return new Message($this);
    }

    /**
     * @return History
     * @throws AuthException
     */
    public function history($startHistoryId)
    {
        if (!$this->getToken()) {
            throw new AuthException('No credentials found.');
        }

        return new History($this, $startHistoryId);
    }

    /**
     * Returns the Gmail user email
     *
     * @return \Google_Service_Gmail_Profile
     */
    public function user()
    {
        return $this->config('email');
    }

    /**
     * Updates / sets the current userId for the service
     *
     * @return \Google_Service_Gmail_Profile
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function redirect()
    {
        return $this->createAuthUrl($this->prepareScopes());
    }

    public function prepareCalendarScopes()
    {

    }

    public function logout()
    {
        $this->revokeToken();
        $this->deleteAccessToken();
    }

}
