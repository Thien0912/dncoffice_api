<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require APPPATH . 'libraries/REST_INSTANCE_Controller.php';

/**
 * @property Ql_phien_ban_model $Ql_phien_ban_model
 */
class Patchnote extends REST_INSTANCE_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->model('Ql_phien_ban_model');
  }

  public function all_get()
  {

    $data = $this->Ql_phien_ban_model->getAllOrderLatest();

    $this->response([
      'status' => REST_INSTANCE_Controller::HTTP_OK,
      'message' => 'Success',
      'success' => true,
      'data' => $data
    ], REST_INSTANCE_Controller::HTTP_OK);
  }

  public function find_by_slug_get()
  {

    $slug = commonRequest("slug");

    $data = $this->Ql_phien_ban_model->getBySlug($slug);

    $this->response([
      'status' => REST_INSTANCE_Controller::HTTP_OK,
      'message' => 'Success',
      'success' => true,
      'data' => $data
    ], REST_INSTANCE_Controller::HTTP_OK);
  }

  public function headers_content_get()
  {
    $content = commonRequest("content");

    $data = $this->Ql_phien_ban_model->generateScrollspy($content);

    $this->response([
      'status' => REST_INSTANCE_Controller::HTTP_OK,
      'message' => 'Success',
      'success' => true,
      'data' => $data
    ], REST_INSTANCE_Controller::HTTP_OK);
  }
}
