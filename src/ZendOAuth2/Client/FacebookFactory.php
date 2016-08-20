<?php
namespace SocialOAuth\Client;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FacebookFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator) {
        $me = new \SocialOAuth\Client\Facebook;
        $cf = $serviceLocator->get('Config');
        $me->setOptions(new \SocialOAuth\ClientOptions($cf['zendoauth2']['facebook']));
        return $me;
    }
}