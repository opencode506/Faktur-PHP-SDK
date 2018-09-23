<?php

    use opencode506\Faktur\Auth;
    use PHPUnit\Framework\TestCase;

    final class TokenTest extends TestCase 
    {

        private $refreshToken;

        public function testGetToken()
        {
            $common = new Auth('DEV');
            $token = $common->token([
                'username' => 'cpj-3-101-753619@stag.comprobanteselectronicos.go.cr',
                'password' => ')^Qa!Qq5/]M;_iVW)p+)'
            ], 'password');

            $success = isset($token['body']['refresh_token']);
            $this->assertTrue($success);

        }
        
        public function testRefreshToken()
        {
            $common = new Auth('DEV');
            $token = $common->token([
                'username' => 'cpj-3-101-753619@stag.comprobanteselectronicos.go.cr',
                'password' => ')^Qa!Qq5/]M;_iVW)p+)'
            ], 'password');

            $this->refreshToken = $token['body']['refresh_token'];
            
            $refreshToken = $common->token([
                'refresh_token' => $this->refreshToken
            ], 'refresh_token');

            $success = isset($refreshToken['body']['refresh_token']);
            $this->assertTrue($success);

        }
        
    }