<?php

namespace SocialOAuth;

use \Zend\Http\Client;
use SocialOAuth\Uri\OAuthHttp as Http;
class OAuth2HttpClient extends Client {

    public function send(\Zend\Http\Request $request = null) {

        if ($request !== null) {
            $this->setRequest($request);
        }

        $this->redirectCounter = 0;

        $adapter = $this->getAdapter();

        // Send the first request. If redirected, continue.
        do {
            // uri
            $uri = $this->getUri();
            // query
            $query = $this->getRequest()->getQuery();

            if (!empty($query)) {
                $queryArray = $query->toArray();

                if (!empty($queryArray)) {
                    $newUri = $uri->toString();
                    $newUri = str_replace('%28', '(', $newUri);
                    $newUri = str_replace('%29', ')', $newUri);
                    $queryString = http_build_query($queryArray, null, $this->getArgSeparator());

                    if ($this->config['rfc3986strict']) {
                        $queryString = str_replace('+', '%20', $queryString);
                    }

                    if (strpos($newUri, '?') !== false) {
                        $newUri .= $this->getArgSeparator() . $queryString;
                    } else {
                        $newUri .= '?' . $queryString;
                    }

                    $uri = new Http($newUri);
                }
            }
            // If we have no ports, set the defaults
            if (!$uri->getPort()) {
                $uri->setPort($uri->getScheme() == 'https' ? 443 : 80);
            }
            // method
            $method = $this->getRequest()->getMethod();

            // this is so the correct Encoding Type is set
            $this->setMethod($method);

            // body
            $body = $this->prepareBody();

            // headers
            $headers = $this->prepareHeaders($body, $uri);

            $secure = $uri->getScheme() == 'https';

            // cookies
            $cookie = $this->prepareCookies($uri->getHost(), $uri->getPath(), $secure);
            if ($cookie->getFieldValue()) {
                $headers['Cookie'] = $cookie->getFieldValue();
            }

            // check that adapter supports streaming before using it
            if (is_resource($body) && !($adapter instanceof Client\Adapter\StreamInterface)) {
                throw new Client\Exception\RuntimeException('Adapter does not support streaming');
            }

            // calling protected method to allow extending classes
            // to wrap the interaction with the adapter
            $response = $this->doRequest($uri, $method, $secure, $headers, $body);
            if (!$response) {
                throw new Exception\RuntimeException('Unable to read response, or response is empty');
            }

            if ($this->config['storeresponse']) {
                $this->lastRawResponse = $response;
            } else {
                $this->lastRawResponse = null;
            }

            if ($this->config['outputstream']) {
                $stream = $this->getStream();
                if (!is_resource($stream) && is_string($stream)) {
                    $stream = fopen($stream, 'r');
                }
                $streamMetaData = stream_get_meta_data($stream);
                if ($streamMetaData['seekable']) {
                    rewind($stream);
                }
                // cleanup the adapter
                $adapter->setOutputStream(null);
                $response = Response\Stream::fromStream($response, $stream);
                $response->setStreamName($this->streamName);
                if (!is_string($this->config['outputstream'])) {
                    // we used temp name, will need to clean up
                    $response->setCleanup(true);
                }
            } else {
                $response = $this->getResponse()->fromString($response);
            }

            // Get the cookies from response (if any)
            $setCookies = $response->getCookie();
            if (!empty($setCookies)) {
                $this->addCookie($setCookies);
            }

            // If we got redirected, look for the Location header
            if ($response->isRedirect() && ($response->getHeaders()->has('Location'))) {
                // Avoid problems with buggy servers that add whitespace at the
                // end of some headers
                $location = trim($response->getHeaders()->get('Location')->getFieldValue());

                // Check whether we send the exact same request again, or drop the parameters
                // and send a GET request
                if ($response->getStatusCode() == 303 ||
                        ((!$this->config['strictredirects']) && ($response->getStatusCode() == 302 ||
                        $response->getStatusCode() == 301))) {
                    $this->resetParameters(false, false);
                    $this->setMethod(Request::METHOD_GET);
                }

                // If we got a well formed absolute URI
                if (($scheme = substr($location, 0, 6)) &&
                        ($scheme == 'http:/' || $scheme == 'https:')) {
                    // setURI() clears parameters if host changed, see #4215
                    $this->setUri($location);
                } else {
                    // Split into path and query and set the query
                    if (strpos($location, '?') !== false) {
                        list($location, $query) = explode('?', $location, 2);
                    } else {
                        $query = '';
                    }
                    $this->getUri()->setQuery($query);

                    // Else, if we got just an absolute path, set it
                    if (strpos($location, '/') === 0) {
                        $this->getUri()->setPath($location);
                        // Else, assume we have a relative path
                    } else {
                        // Get the current path directory, removing any trailing slashes
                        $path = $this->getUri()->getPath();
                        $path = rtrim(substr($path, 0, strrpos($path, '/')), "/");
                        $this->getUri()->setPath($path . '/' . $location);
                    }
                }
                ++$this->redirectCounter;
            } else {
                // If we didn't get any location, stop redirecting
                break;
            }
        } while ($this->redirectCounter <= $this->config['maxredirects']);

        $this->response = $response;
        return $response;
    }

}
