<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property CI_Output $output 
 */

class Keys extends REST_INSTANCE_Controller
{
    protected $methods = [
        'getPublicKey_get' => ['key' => false],
        'getEnscryptedKey_post' => ['key' => false]
    ];

    public function __construct()
    {
        parent::__construct();
        // REST_Controller handles CORS via config/rest.php, but if we need Credentials: true explicitly and config doesn't cover it:
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Credentials: true');
        }
    }

    public function getPublicKey_get()
    {
        $publicKey = file_get_contents(APPPATH . '../public_key.pem');
        $this->response(['publicKey' => $publicKey], REST_Controller::HTTP_OK);
    }

    public function getEnscryptedKey_post()
    {
        $key = $this->post('key');
        if (!$key) {
            $key = commonRequest('key');
        }
        
        $encryptedKey = encryptString($key);
        $this->response(['encryptedKey' => $encryptedKey], REST_Controller::HTTP_OK);
    }
}
