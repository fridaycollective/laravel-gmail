<?php

namespace FridayCollective\LaravelGmail;

use FridayCollective\LaravelGmail\Traits\Configurable;
use Google_Client;
use Google_Service_Gmail;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class GmailConnection extends Google_Client
{

    use Configurable {
        __construct as configConstruct;
    }

    protected $emailAddress;
    protected $refreshToken;
    protected $app;
    protected $accessToken;
    protected $token;
    private $configuration;
    public $userId;

    public function __construct($config = null, $integrationConfig)
    {
        $this->app = Container::getInstance();

        $this->configConstruct($config, $integrationConfig);

        $this->configuration = $config;

        parent::__construct($this->getConfigs());

        $this->configApi();

        if ($this->checkPreviouslyLoggedIn()) {
            $this->refreshTokenIfNeeded();
        }

    }

    /**
     * Check and return true if the user has previously logged in without checking if the token needs to refresh
     *
     * @return bool
     */
    public function checkPreviouslyLoggedIn()
    {
        $credentials = $this->getClientGmailCredentials();

        $allowJsonEncrypt = $this->_config['allow_json_encrypt'];

        if ($credentials) {
            if ($allowJsonEncrypt) {
                $savedConfigToken = json_decode(decrypt($credentials->config), true);
            } else {
                $savedConfigToken = json_decode($credentials->config, true);
            }

            return !empty($savedConfigToken['access_token']);

        }

        return false;
    }

    /**
     * Refresh the auth token if needed
     *
     * @return mixed|null
     */
    private function refreshTokenIfNeeded()
    {
        if ($this->isAccessTokenExpired()) {
            $this->fetchAccessTokenWithRefreshToken($this->getRefreshToken());
            $token = $this->getAccessToken();
            $this->setBothAccessToken($token);

            return $token;
        }

        return $this->token;
    }

    /**
     * Check if token exists and is expired
     * Throws an AuthException when the auth file its empty or with the wrong token
     *
     *
     * @return bool Returns True if the access_token is expired.
     */
    public function isAccessTokenExpired()
    {
        $token = $this->getToken();

        if ($token) {
            $this->setAccessToken($token);
        }

        return parent::isAccessTokenExpired();
    }

    public function getToken()
    {
        return parent::getAccessToken() ?: $this->config();
    }

    public function setToken($token)
    {
        $this->setAccessToken($token);
    }

    public function getAccessToken()
    {
        $token = parent::getAccessToken() ?: $this->config();

        return $token;
    }

    /**
     * @param array|string $token
     */
    public function setAccessToken($token)
    {
        parent::setAccessToken($token);
    }

    /**
     * @param $token
     */
    public function setBothAccessToken($token)
    {
        $this->setAccessToken($token);
        $this->saveAccessToken($token);
    }

    /**
     * Save the credentials in a file
     *
     * @param array $config
     */
    public function saveAccessToken(array $config)
    {
        $credentials = $this->getClientGmailCredentials();

        if ($credentials) {
            $allowJsonEncrypt = $this->_config['allow_json_encrypt'];

            $config['email'] = $this->emailAddress;

            if (empty($config['email'])) {
                if ($allowJsonEncrypt) {
                    $savedConfigToken = json_decode(decrypt($credentials->config), true);
                } else {
                    $savedConfigToken = json_decode($credentials->config, true);
                }
                if (isset($savedConfigToken['email'])) {
                    $config['email'] = $savedConfigToken['email'];
                }
            }

            $credentials->config = null;
            $credentials->save();

            if ($allowJsonEncrypt) {
                $credentials->config = encrypt(json_encode($config));
                $credentials->save();
            } else {
                $credentials->config = json_encode($config);
                $credentials->save();
            }
        }

    }

    /**
     * @return array|string
     * @throws \Exception
     */
    public function makeToken()
    {
        if (!$this->check()) {
            $request = Request::capture();
            $code = (string)$request->input('code', null);
            if (!is_null($code) && !empty($code)) {
                $accessToken = $this->fetchAccessTokenWithAuthCode($code);
                $me = $this->getProfile();
                if (property_exists($me, 'emailAddress')) {
                    $this->emailAddress = $me->emailAddress;
                    $accessToken['email'] = $me->emailAddress;
                }

                $this->setBothAccessToken($accessToken);

                return $accessToken;
            } else {
                throw new \Exception('No access token');
            }
        } else {
            return $this->getAccessToken();
        }
    }

    /**
     * Check
     *
     * @return bool
     */
    public function check()
    {
        return !$this->isAccessTokenExpired();
    }

    /**
     * Gets user profile from Gmail
     *
     * @return \Google_Service_Gmail_Profile
     */
    public function getProfile()
    {
        $service = new Google_Service_Gmail($this);

        return $service->users->getProfile('me');
    }

    /**
     * Revokes user's permission and logs them out
     */
    public function logout()
    {
        $this->revokeToken();
    }

    /**
     * Delete the credentials in a file
     */
    public function deleteAccessToken()
    {
        $credentials = $this->getClientGmailCredentials();

        $allowJsonEncrypt = $this->_config['allow_json_encrypt'];

        if ($credentials) {
            $credentials->config = null;
            $credentials->save();
        }

        $credentials->delete();
    }

}
