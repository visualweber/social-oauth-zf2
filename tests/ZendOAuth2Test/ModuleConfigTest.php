<?php

use ZendOAuth2Test\Bootstrap;

namespace ZendOAuth2Test;

use PHPUnit_Framework_TestCase;

class ModuleConfigTest extends PHPUnit_Framework_TestCase
{
    
    public function testModuleConfig()
    {
        
        $githubClient = Bootstrap::getServiceManager()->get('SocialOAuth\Github');   
        $facebookClient = Bootstrap::getServiceManager()->get('SocialOAuth\Facebook');   
        $googleClient = Bootstrap::getServiceManager()->get('SocialOAuth\Google');   
        $linkedInClient = Bootstrap::getServiceManager()->get('SocialOAuth\LinkedIn');

        $this->assertSame('github', $githubClient->getProvider());
        $this->assertSame('facebook', $facebookClient->getProvider());
        $this->assertSame('google', $googleClient->getProvider());
        $this->assertSame('linkedin', $linkedInClient->getProvider());

    }
    
}
