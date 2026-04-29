<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @property CI_Output $output 
 */

class Keys extends CI_Controller
{
    public function getPublicKey()
    {
        $publicKey = file_get_contents(APPPATH . '../public_key.pem');
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['publicKey' => $publicKey]));
    }
}
