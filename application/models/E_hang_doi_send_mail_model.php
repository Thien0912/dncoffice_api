<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class E_hang_doi_send_mail_model extends MY_Model
{
    protected $table = 'e_hang_doi_send_mail';
    protected $primaryKey = 'e_hang_doi_send_mail_id';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }
}
