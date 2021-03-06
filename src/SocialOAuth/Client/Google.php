<?php

namespace SocialOAuth\Client;

use SocialOAuth\AbstractOAuth2Client;
use SocialOAuth\ClientOptions;
use Zend\Http\PhpEnvironment\Request;

class Google extends AbstractOAuth2Client {

    protected $providerName = 'google';

    public function getUrl() {

        $url = $this->options->getAuthUri() . '?'
                . 'redirect_uri=' . urlencode($this->options->getRedirectUri())
                . '&response_type=code'
                . '&client_id=' . $this->options->getClientId()
                . '&state=' . $this->generateState()
                . $this->getScope(' ');

        return $url;
    }

    public function getToken(Request $request) {

        if (isset($this->session->token)) {

            return true;
        } elseif (strlen($this->session->state) > 0 AND $this->session->state == $request->getQuery('state') AND strlen($request->getQuery('code')) > 5) {

            $client = $this->getHttpClient();

            $client->setUri($this->options->getTokenUri());

            $client->setMethod(Request::METHOD_POST);

            $client->setParameterPost(array(
                'code' => $request->getQuery('code'),
                'client_id' => $this->options->getClientId(),
                'client_secret' => $this->options->getClientSecret(),
                'redirect_uri' => $this->options->getRedirectUri(),
                'grant_type' => 'authorization_code'
            ));

            $retVal = $client->send()->getBody();

            try {

                $token = \Zend\Json\Decoder::decode($retVal);

                if (isset($token->access_token) AND $token->expires_in > 0) {

                    $this->session->token = $token;

                    return true;
                } else {

                    $this->error = array(
                        'internal-error' => 'Google settings error.',
                        'error' => $token->error,
                        'token' => $token
                    );

                    return false;
                }
            } catch (\Zend\Json\Exception\RuntimeException $e) {

                $this->error['internal-error'] = 'Unknown error.';
                $this->error['token'] = $retVal;

                return false;
            }
        } else {

            $this->error = array(
                'internal-error' => 'State error, request variables do not match the session variables.',
                'session-state' => $this->session->state,
                'request-state' => $request->getQuery('state'),
                'code' => $request->getQuery('code')
            );

            return false;
        }
    }

    public function getInfo() {
        if (is_object($this->session->info)) {
            $me = $this->session->info;
        } elseif (isset($this->session->token->access_token)) {
            $urlProfile = $this->options->getInfoUri() . '?access_token=' . $this->session->token->access_token;
            $client = $this->getHttpclient()
                    ->resetParameters(true)
                    // ->setHeaders(array('Accept-encoding' => ''))
                    ->setHeaders(array('Accept-encoding' => 'gzip, deflate, identity'))
                    ->setMethod(Request::METHOD_GET)
                    ->setUri($urlProfile);
            $response = $client->send();
            $retVal = $response->getBody();

            if (strlen(trim($retVal)) > 0) {
                // \Zend\Debug\Debug::dump($response->getBody());
                $this->session->info = \Zend\Json\Decoder::decode($retVal);
                $me = $this->session->info;
            } else {
                $this->error = array('internal-error' => 'Get info return value is empty.');
                return false;
            }
        } else {
            $this->error = array('internal-error' => 'Session access token not found.');
            return false;
        }
        // $this->getContacts();
        return $me;
    }

    public function getContacts() {
        $this->session->contacts = null;
        if (is_object($this->session->contacts)) {
            $contacts = $this->session->contacts;
        } elseif (isset($this->session->token->access_token)) {
            $urlProfile = 'https://www.google.com/m8/feeds/contacts/default/full?alt=json&v=3.0&oauth_token=' . $this->session->token->access_token;
            $client = $this->getHttpclient()
                    ->resetParameters(true)
                    // ->setHeaders(array('Accept-encoding' => ''))
                    ->setHeaders(array('Accept-encoding' => 'gzip, deflate, identity'))
                    ->setMethod(Request::METHOD_GET)
                    ->setUri($urlProfile);
            $response = $client->send();
            $retVal = $response->getBody();
            
            if (strlen(trim($retVal)) > 0) {
                // \Zend\Debug\Debug::dump($response->getBody());
                $this->session->contacts = \Zend\Json\Decoder::decode($retVal);
                $contacts = $this->session->contacts;
            } else {
                $this->error = array('internal-error' => 'Get info return value is empty.');
                return false;
            }
        } else {
            $this->error = array('internal-error' => 'Session access token not found.');
            return false;
        }
        echo '<pre>';
        print_R($contacts);
        echo '</pre>';
        exit();
        return $contacts;
    }

}
