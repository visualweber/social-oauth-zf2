<?php

namespace ZendOAuth2Test\Client;

use ZendOAuth2Test\Bootstrap;
use PHPUnit_Framework_TestCase;

class GithubClientTest extends PHPUnit_Framework_TestCase
{
    
    protected $providerName = 'Github';
    
    public function setup()
    {
         $this->client = $this->getClient();
    }
    
    public function tearDown()
    {

        unset($this->client->getSessionContainer()->token);
        unset($this->client->getSessionContainer()->state);
        unset($this->client->getSessionContainer()->info);
        
    }
    
    public function getClient()
    {
        $me = new \SocialOAuth\Client\Github;
        $cf = Bootstrap::getServiceManager()->get('Config');
        $me->setOptions(new \SocialOAuth\ClientOptions($cf['zendoauth2']['github']));
        return $me;
    }
    
    public function testInstanceTypes()
    {
        $this->assertInstanceOf('SocialOAuth\AbstractOAuth2Client', $this->client);
        $this->assertInstanceOf('SocialOAuth\Client\\'.$this->providerName, $this->client);
        $this->assertInstanceOf('SocialOAuth\ClientOptions', $this->client->getOptions());
        $this->assertInstanceOf('Zend\Session\Container', $this->client->getSessionContainer());
        $this->assertInstanceOf('SocialOAuth\OAuth2HttpClient', $this->client->getHttpClient());
    }
    
    public function testGetProviderName()
    {
        $this->assertSame(strtolower($this->providerName), $this->client->getProvider());
    }
    
    public function testSetHttpClient()
    {
        $httpClientMock = $this->getMock(
                '\SocialOAuth\OAuth2HttpClient',
                null,
                array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $this->client->setHttpClient($httpClientMock);
    }
    
    public function testFailSetHttpClient()
    {
        $this->setExpectedException('SocialOAuth\Exception\HttpClientException');
        $this->client->setHttpClient(new \Zend\Http\Client);
    }
    
    public function testSessionState()
    {
        
        $this->assertEmpty($this->client->getState());
        $this->client->getUrl();
        $this->assertEquals(strlen($this->client->getState()), 32);
        
    }
    
    public function testLoginUrlCreation()
    {
        
        $uri = \Zend\Uri\UriFactory::factory($this->client->getUrl());
        $this->assertTrue($uri->isValid());
        
    }
    
    public function testGetScope()
    {

        if(count($this->client->getOptions()->getScope()) > 0) {
            $this->assertTrue(strlen($this->client->getScope()) > 0);
        } else {
            $this->assertTrue(strlen($this->client->getScope()) == 0);
            $this->client->getOptions()->setScope(array('some', 'scope'));
            $this->assertTrue(strlen($this->client->getScope()) > 0);
        }
        
    }
    
    public function testFailGetToken()
    {

        $request = new \Zend\Http\PhpEnvironment\Request;
        
        $this->client->getUrl();
        
        $this->assertFalse($this->client->getToken($request));
        $error = $this->client->getError();
        $this->assertStringEndsWith('variables do not match the session variables.', $error['internal-error']);
        
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => 'some state')));
        $this->assertFalse($this->client->getToken($request));
        $error = $this->client->getError();
        $this->assertStringEndsWith('variables do not match the session variables.', $error['internal-error']);
        
        $this->client->getOptions()->setRedirectUri('http://reverse.si/');
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
        $this->assertFalse($this->client->getToken($request));
        $error = $this->client->getError();
        
        $this->assertStringEndsWith('Unknown error.', $error['internal-error']);
        
        
    }
    
    public function testFailGetTokenMocked()
    {
        
        $this->client->getUrl();
        
        $httpClientMock = $this->getMock(
            '\SocialOAuth\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $httpClientMock->expects($this->any())
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getFaultyMockedTokenResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
        
        $this->assertFalse($this->client->getToken($request));
                
        $error = $this->client->getError();
        $this->assertStringEndsWith('Unknown error.', $error['internal-error']);
         
    }
    
    public function testGetTokenMocked()
    {
        
        $this->client->getUrl();
        
        $httpClientMock = $this->getMock(
            '\SocialOAuth\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $httpClientMock->expects($this->exactly(1))
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedTokenResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $request = new \Zend\Http\PhpEnvironment\Request;
        $request->setQuery(new \Zend\Stdlib\Parameters(array('code' => 'some code', 'state' => $this->client->getState())));
        
        $this->assertTrue($this->client->getToken($request));
        
        $this->assertTrue($this->client->getToken($request)); // from session
        
        $this->assertTrue(strlen($this->client->getSessionToken()->access_token) > 0);
        
    }
    
    public function testGetInfo()
    {

        $httpClientMock = $this->getMock(
            '\SocialOAuth\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $httpClientMock->expects($this->exactly(1))
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedInfoResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $token = new \stdClass; // fake the session token exists
        $token->access_token = 'some';
        $this->client->getSessionContainer()->token = $token;
        
        $rs = $this->client->getInfo();
        $this->assertSame('500', $rs->id);
        
        $rs = $this->client->getInfo(); // from session
        $this->assertSame('500', $rs->id);
        
    }
    
    public function testFailNoReturnGetInfo()
    {

        $httpClientMock = $this->getMock(
            '\SocialOAuth\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
    
        $httpClientMock->expects($this->any())
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedInfoResponseEmpty')));
    
        
        $token = new \stdClass; // fake the session token exists
        $token->access_token = 'some';
        $this->client->getSessionContainer()->token = $token;
        
        $this->client->setHttpClient($httpClientMock);
    
        $this->assertFalse($this->client->getInfo());
    
        $error = $this->client->getError();
        $this->assertSame('Get info return value is empty.', $error['internal-error']);
    
    }
    
    public function testFailNoTokenGetInfo()
    {

        $httpClientMock = $this->getMock(
            '\SocialOAuth\OAuth2HttpClient',
            array('send'),
            array(null, array('timeout' => 30, 'adapter' => '\Zend\Http\Client\Adapter\Curl'))
        );
        
        $httpClientMock->expects($this->any())
                        ->method('send')
                        ->will($this->returnCallback(array($this, 'getMockedInfoResponse')));
        
        $this->client->setHttpClient($httpClientMock);
        
        $this->assertFalse($this->client->getInfo());
        
        $error = $this->client->getError();
        $this->assertSame('Session access token not found.', $error['internal-error']);
        
    }
    
    public function getMockedTokenResponse()
    {
        
        $response = new \Zend\Http\Response;
        
        $response->setContent('access_token=AAAEDkf9KDoQBABLZCBBLuCOgWLQpyMUxZBQjkrCZC4Fw3C6EJWTeF7zZB0ymBTdPejD4gae08AZDZF&expires=5117581');
        
        return $response;
        
    }
    
    public function getFaultyMockedTokenResponse()
    {
    
        $response = new \Zend\Http\Response;
    
        $response->setContent('token=AAAEDkf9KDoQBABLbTeTDEe9kvfZCBBLuCOgWLQpyMUxZBQjkrCZC4Fw3C6EJWTeF7zZB0ymBTdPejD4gae08AZDZg&expires=1');
    
        return $response;
    
    }
    
    public function getMockedInfoResponse()
    {
    
        $content = '{
            "id": "500",
            "name": "John Doe",
            "first_name": "John",
            "last_name": "Doe",
            "link": "http:\/\/www.facebook.com\/john.doe",
            "username": "john.doe",
            "gender": "male",
            "timezone": 1,
            "locale": "sl_SI",
            "verified": true,
            "updated_time": "2012-09-14T12:37:27+0000"
        }';
    
        $response = new \Zend\Http\Response;
    
        $response->setContent($content);
    
        return $response;
    
    }
    
    public function getMockedInfoResponseEmpty()
    {
        
        $response = new \Zend\Http\Response;    
        return $response;
    
    }
    
}
