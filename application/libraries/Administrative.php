<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Administrative_model $Administrative_model
 */

class Administrative extends REST_INSTANCE_Controller
{

    public function __construct()
    {
        parent::__construct();
        // Load necessary models, libraries, etc.
        $this->load->model('Administrative_model');
        $this->load->helper('url');
    }

    public function province_get()
    {
        $data = $this->Administrative_model->get_province();
        resSuccess(
            $data,
            'Success',
            REST_Controller::HTTP_OK,
            true,
        );
    }

    public function district_get()
    {
        $province_id = commonRequest('province_id') ? commonRequest('province_id') : null;

        if (!$province_id) {
            resError('Chưa có id tỉnh thành phố', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $data = $this->Administrative_model->get_district($province_id);
        resSuccess(
            $data,
            'Success',
            REST_Controller::HTTP_OK,
            true,
        );
    }

    public function districtById_get()
    {
        $id = commonRequest('id') ? commonRequest('id') : null;

        if (!$id) {
            resError('Chưa có id quận/huyện', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }
        $data = $this->Administrative_model->get_district_by_id($id);
        resSuccess(
            $data,
            'Success',
            REST_Controller::HTTP_OK,
            true,
        );
    }

    public function ward_get()
    {
        $district_id = commonRequest('district_id') ? commonRequest('district_id') : null;

        if (!$district_id) {
            resError('Chưa có id quận/huyện', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $data = $this->Administrative_model->get_ward($district_id);
        resSuccess(
            $data,
            'Success',
            REST_Controller::HTTP_OK,
            true,
        );
    }

    public function wardById_get()
    {
        $id = commonRequest('id') ? commonRequest('id') : null;

        if (!$id) {
            resError('Chưa có id phường/xã', REST_INSTANCE_Controller::HTTP_NOT_FOUND);
        }

        $data = $this->Administrative_model->get_ward_by_id($id);
        resSuccess(
            $data,
            'Success',
            REST_Controller::HTTP_OK,
            true,
        );
    }
}
