<?php
defined('BASEPATH') or exit('No direct script access allowed');
date_default_timezone_set('Asia/Ho_Chi_Minh');


$config['protocol'] = 'smtp';
$config['smtp_host'] = 'ssl://smtp.gmail.com';
$config['smtp_port'] = '465';
$config['smtp_timeout'] = '60';
$config['smtp_user'] = 'noreply@tchc.nctu.edu.vn';
$config['smtp_pass'] = 'hlnw zvpb wscz nflx';
$config['charset'] = 'utf-8';
$config['newline'] = "\r\n";
$config['mailtype'] = 'html';
$config['validation'] = TRUE;
$config['smtp_debug'] = 0;
