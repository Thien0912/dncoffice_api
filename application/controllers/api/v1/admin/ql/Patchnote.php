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

  public function index_get()
  {

    $start = commonRequest('start') ? commonRequest('start') : 0;
    $length = commonRequest('length') ? commonRequest('length') : 10;
    $searchValue = commonRequest('searchValue') ? commonRequest('searchValue') : null;

    $orderBy = (commonRequest('order') && commonRequest('columns')) ? [
      'order' => commonRequest('order'),
      'columns' => commonRequest('columns')
    ] : [];

    $searchKey = commonRequest('searchKey') ? commonRequest('searchKey') : [];


    $data = $this->Ql_phien_ban_model->getAll($start, $length, $searchValue, $orderBy, $searchKey);

    resSuccess($data['data'], 'Success', REST_Controller::HTTP_OK, true, [
      'recordsTotal' => $data['recordsTotal'],
      'recordsFiltered' => $data['recordsFiltered']
    ]);
  }

  public function create_post()
  {
    $data = commonRequest("data");

    $userCurrent = $this->getUserLogin();

    $data->ql_nguoi_dung_id = $userCurrent['ql_nguoi_dung_id'];

    $this->Ql_phien_ban_model->insert($data);

    resSuccess($data, 'Success', REST_Controller::HTTP_OK, true);
  }

  public function update_post()
  {
    $data = commonRequest("data");
    $id = commonRequest("id");

    $this->Ql_phien_ban_model->where("ql_phien_ban_id", $id)->update($data);

    resSuccess($data, 'Success', REST_Controller::HTTP_OK, true);
  }

  public function edit_get()
  {
    $id = commonRequest("id");

    $data = $this->Ql_phien_ban_model->getById($id);

    resSuccess($data, 'Success', REST_Controller::HTTP_OK, true);
  }

  public function delete_get()
  {
    $id = commonRequest("id");

    $this->Ql_phien_ban_model->where("ql_phien_ban_id", $id)->update(['deleted_at' => date('d-m-y H:i:s')]);

    resSuccess(null, 'Success', REST_Controller::HTTP_OK, true);
  }
}
