<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**

 * @property DB_query_builder $db
 * @property Hrm_bieu_thue_tich_luy_model $Hrm_bieu_thue_tich_luy_model
 * @property Fileupload $fileupload
 */



class Bieuthuetichluy extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->permissionMiddleware();
        $this->load->helper('url');
        $this->load->model(['Hrm_bieu_thue_tich_luy_model']);
    }

    public function bieuthue_get()
    {
        $data = $this->Hrm_bieu_thue_tich_luy_model->get_all();
        resSuccess(
            $data,
            'Success',
            REST_Controller::HTTP_OK,
            true,
        );
    }

}
