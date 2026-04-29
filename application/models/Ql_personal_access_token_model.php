<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Ql_personal_access_token_model extends MY_Model
{
    protected $table = 'ql_personal_access_token';
    protected $primaryKey = 'ql_personal_access_token_id';
    protected $hidden = [];
    protected $timestamps = false;



    public function __construct()
    {
        parent::__construct();
    }

    public function deleteTokenByUserIds($userIds = array())
    {
        if (!empty($userIds))
            $this->whereIn('ql_nguoi_dung_id', $userIds)->delete();
    }
}
