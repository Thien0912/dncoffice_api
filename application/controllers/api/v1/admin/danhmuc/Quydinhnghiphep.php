<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property  Hrm_quy_dinh_nghi_phep_model $Hrm_quy_dinh_nghi_phep_model
 * @property  CI_DB_query_builder $db
 */

class Quydinhnghiphep extends REST_INSTANCE_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Hrm_quy_dinh_nghi_phep_model');
        // $this->permissionMiddleware(); // check permissions access
    }

    public function index_get()
    {
        $data = $this->Hrm_quy_dinh_nghi_phep_model->getSearch();

        resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
            'recordsTotal' => $data['recordsTotal'],
            'recordsFiltered' => $data['recordsFiltered']
        ]);
    }
}
