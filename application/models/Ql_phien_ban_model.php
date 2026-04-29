<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Ql_phien_ban_model extends MY_Model
{
  protected $table = 'ql_phien_ban';
  protected $primaryKey = 'ql_phien_ban_id ';
  protected $timestamps = false;

  public function __construct()
  {
    parent::__construct();
  }

  // public function get_all($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = [])
  // {

  //   $this->db->start_cache(); // Start caching query builder state
  //   $this->db->select("*")->from("ql_phien_ban")
  //     ->where("deleted_at IS NULL");


  //   if (!empty($searchKey)) {
  //     foreach ($searchKey as $key => $value) {
  //       $this->db->where("ql_phien_ban.$key", $value);
  //     }
  //   }
  //   $this->db->stop_cache(); // Stop caching before count queries

  //   // Get total records count
  //   $recordsTotal = $this->db->count_all_results();

  //   // Get filtered records count
  //   $recordsFiltered = $this->db->count_all_results();

  //   // Ordering logic
  //   if (!empty($orderBy) && isset($orderBy['order']) && isset($orderBy['columns'])) {
  //     $order = $orderBy['order'];
  //     $orderColumnIndex = $order[0]['column'] ?? 0;
  //     $orderDir = $order[0]['dir'] ?? 'asc';

  //     $columns = $orderBy['columns'];
  //     $filed = $columns[$orderColumnIndex]['data'] ?? 'id';

  //     if (!empty($orderDir)) {
  //       $this->db->order_by("ql_phien_ban.$filed", $orderDir);
  //     }
  //   }

  //   // Default ordering
  //   $this->db->order_by("ql_phien_ban.id", "desc");

  //   // Pagination
  //   if ($length != '-1') {
  //     $this->db->limit($length, $start);
  //   }

  //   // Execute final query
  //   $query = $this->db->get();
  //   $data = $query->result_array();

  //   $this->db->flush_cache(); // Clear cached query builder state

  //   return [
  //     'recordsTotal' => $recordsTotal,
  //     'recordsFiltered' => $recordsFiltered,
  //     'data' => $data
  //   ];
  // }

  public function getAll($start = 0, $length = 10, $searchValue = null, $orderBy = [], $searchKey = array())
  {
    $this->db->from('ql_phien_ban')->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_phien_ban.ql_nguoi_dung_id');
    $this->db->where('ql_phien_ban.deleted_at IS NULL');

    $totalRecordsQuery = clone $this->db;
    $recordsTotal = $totalRecordsQuery->count_all_results('', FALSE);

    if ($searchValue) {
      $this->db->group_start();
      $this->db->like('ql_phien_ban.ql_phien_ban_tieu_de', $searchValue);
      $this->db->or_like('ql_phien_ban.ql_phien_ban_so', $searchValue);
      $this->db->or_like('ql_phien_ban.ql_phien_ban_slug', $searchValue);
      $this->db->or_like('ql_phien_ban.ql_phien_ban_noi_dung', $searchValue);
      $this->db->or_like('ql_phien_ban.ql_nguoi_dung_id', $searchValue);
      $this->db->group_end();
    }

    if (!empty($searchKey)) {
      foreach ($searchKey as $key => $value) {
        $this->db->where('ql_phien_ban.' . $key, $value);
      }
    }

    $filteredQuery = clone $this->db;
    $recordsFiltered = $filteredQuery->count_all_results('', FALSE); // FALSE để không reset query

    if (!empty($orderBy)) {
      $order = $orderBy['order'];
      $orderColumnIndex = $order[0]['column'];
      $orderDir = $order[0]['dir'];

      $columns = $orderBy['columns'];
      $filed = $columns[$orderColumnIndex]['data'];

      if (!empty($orderDir)) {
        $this->db->order_by('ql_phien_ban.' . $filed, $orderDir);
      }
    } else {
      $this->db->order_by('ql_phien_ban.ql_phien_ban_id', 'desc');
    }


    if ($length != '-1') {
      $this->db->limit($length, $start);
    }

    $this->db->select('ql_phien_ban.*, ql_nguoi_dung.ql_nguoi_dung_ho_ten');
    $query = $this->db->get();
    $data = $query->result_array();


    return [
      'recordsTotal' => $recordsTotal,
      'recordsFiltered' => $recordsFiltered,
      'data' => $data
    ];
  }

  public function getAllOrderLatest()
  {
    $this->db->from('ql_phien_ban')->join('ql_nguoi_dung', 'ql_nguoi_dung.ql_nguoi_dung_id = ql_phien_ban.ql_nguoi_dung_id');
    $this->db->where('ql_phien_ban.deleted_at IS NULL');


    $query = $this->db->get();
    $data = $query->result_array();

    return $data;
  }

  public function getBySlug($slug = null)
  {
    $this->db->from('ql_phien_ban');

    if (!empty($slug)) {
      $this->db->where('ql_phien_ban_slug', $slug);
    } else {
      $this->db->order_by('ql_phien_ban_id', 'desc');
      $this->db->limit(1);
    }

    $query = $this->db->get()->row();

    return $query;
  }
  public function getById($id = null)
  {
    $this->db->from('ql_phien_ban');
    $this->db->where('ql_phien_ban_id', $id);
    $query = $this->db->get()->row();
    return $query;
  }

  public function generateScrollspy($content)
  {
    $this->load->helper('text'); // Đảm bảo helper text đã được load
    $dom = new DOMDocument();
    $scrollspyList = [];

    // Tắt lỗi khi xử lý HTML không chuẩn
    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    // Lấy tất cả thẻ tiêu đề (h1 - h6)
    $headings = $dom->getElementsByTagName('*');

    foreach ($headings as $element) {
      if (preg_match('/^h[1-6]$/', $element->tagName)) {
        $id = $element->getAttribute('id');
        if (!$id) {
          $id = url_title(convert_accented_characters(trim($element->textContent)), 'dash', true);
          $element->setAttribute('id', $id);
        }

        // Thêm vào danh sách Scrollspy
        $scrollspyList[] = [
          'tag' => $element->tagName,
          'id' => $id,
          'text' => trim($element->textContent),
        ];
      }
    }

    // Xuất lại nội dung HTML đã cập nhật
    $updatedContent = $dom->saveHTML();

    return [
      'content' => $updatedContent,
      'scrollspy' => $scrollspyList,
    ];
  }
}
