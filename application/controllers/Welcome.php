<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Welcome extends CI_Controller
{

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     * 		http://example.com/index.php/welcome
     *	- or -
     * 		http://example.com/index.php/welcome/index
     *	- or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/userguide3/general/urls.html
     */
    public function index()
    {
        $this->load->view('welcome_message');
    }
    public function testmail()
    {
        $this->load->library('email');

        $config['protocol'] = 'smtp';
        $config['smtp_host'] = 'ssl://smtp.gmail.com';
        $config['smtp_port'] = '465';
        $config['smtp_timeout'] = '180';
        $config['smtp_user'] = 'toandevelop@gmail.com';
        $config['smtp_pass'] = 'ydcq bvbw njdo zvao';


        $config['charset'] = 'utf-8';
        $config['newline'] = "\r\n";
        $config['mailtype'] = 'html';
        ///$config['validation'] = TRUE;

        $this->email->initialize($config);
        $this->email->set_mailtype("html");
        $this->email->from('toandevelop@gmail.com');
        $this->email->to('toandevelop@gmail.com');
        $this->email->subject('ĐĂNG KÝ XÉT TUYỂN TRƯỜNG ĐH NAM CẦN THƠ THÀNH CÔNG');
        $this->email->message('<p>test                d d d  d d d d  d d d d d  d d d d d d d d  d d d d</p>');

        if ($this->email->send()) {
            echo 'Gửi thành công';
        } else {
            print_r($this->email->print_debugger());
        }
    }
}
