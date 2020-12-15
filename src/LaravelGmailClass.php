<?php

namespace Swandoola\LaravelGmail;

use Swandoola\LaravelGmail\Exceptions\AuthException;
use Swandoola\LaravelGmail\Services\Message;
use Illuminate\Support\Facades\Redirect;

class LaravelGmailClass extends GmailConnection
{
	public function __construct($config, $userId = null)
	{
		if (class_basename($config) === 'Application') {
			$config = $config['config'];
		}

        $config['state'] = auth()->user()->mailConfig->state_uuid;

		parent::__construct($config, $userId);
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
		return $this->getAuthUrl();
	}

	/**
	 * Gets the URL to authorize the user
	 *
	 * @return string
	 */
	public function getAuthUrl()
	{
		return $this->createAuthUrl();
	}

	public function logout()
	{
		$this->revokeToken();
		$this->deleteAccessToken();
	}

}
