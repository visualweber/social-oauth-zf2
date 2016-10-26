<?php

return [
    'service_manager' => [
        'factories' => [
            'SocialOAuth\Google' => 'SocialOAuth\Client\GoogleFactory',
            'SocialOAuth\LinkedIn' => 'SocialOAuth\Client\LinkedInFactory',
            'SocialOAuth\Github' => 'SocialOAuth\Client\GithubFactory',
            'SocialOAuth\Facebook' => 'SocialOAuth\Client\FacebookFactory'
        ],
        'invokables' => [
            'SocialOAuth\Auth\Adapter' => 'SocialOAuth\Authentication\Adapter\SocialOAuth',  
        ],
        
    ],
    'socialoauth' => [
        'google' => [
            'scope' => [
                'https://www.googleapis.com/auth/userinfo.profile',
                'https://www.googleapis.com/auth/userinfo.email'   
            ],
            'auth_uri'      => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri'     => 'https://accounts.google.com/o/oauth2/token',
            'info_uri'      => 'https://www.googleapis.com/oauth2/v1/userinfo',
            'client_id'     => 'your id',
            'client_secret' => 'your secret',
            'redirect_uri'  => 'your callback url which links to your controller',
        ],
        'facebook' => [
            'scope' => [
                /*
                'user_about_me',
                'user_activities',
                'user_birthday',
                'read_friendlists',
                //'...'
                */
            ],
            'auth_uri'      => 'https://www.facebook.com/dialog/oauth',
            'token_uri'     => 'https://graph.facebook.com/oauth/access_token',
            'info_uri'      => 'https://graph.facebook.com/me',
            'client_id'     => 'your id',
            'client_secret' => 'your secret',
            'redirect_uri'  => 'your callback url which links to your controller',
        ],
        'github' => [
            'scope' => [
                /*
                'user',
                'public_repo',
                'repo',
                'repo:status',
                'delete_repo',
                'gist'
                */
            ],
            'auth_uri'      => 'https://github.com/login/oauth/authorize',
            'token_uri'     => 'https://github.com/login/oauth/access_token',
            'info_uri'      => 'https://api.github.com/user',
            'client_id'     => 'your id',
            'client_secret' => 'your secret',
            'redirect_uri'  => 'your callback url which links to your controller',
        ],
        'linkedin' => [
            'scope' => [],
            'auth_uri'      => 'https://www.linkedin.com/uas/oauth2/authorization',
            'token_uri'     => 'https://www.linkedin.com/uas/oauth2/accessToken',
            'info_uri'      => 'https://api.linkedin.com/v1/people/~:(id,num-connections,picture-url,email-address,first-name,last-name,headline,site-standard-profile-request)',
            'client_id'     => 'your api key',
            'client_secret' => 'your api secret',
            'redirect_uri'  => 'your callback url which links to your controller',
        ],

    ],
    
];