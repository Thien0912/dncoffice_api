<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Administrative_model extends MY_Model
{
    protected $table = '';

    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function get_province()
    {
        $this->db->select('id, name');
        $this->db->from('province');
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_district($province_id)
    {
        $this->db->select('id, name');
        $this->db->from('district');
        $this->db->where('province_id', $province_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_ward($district_id)
    {
        $this->db->select('id, name');
        $this->db->from('wards');
        $this->db->where('district_id', $district_id);
        $query = $this->db->get();
        return $query->result_array();
    }

    public function get_district_by_id($id)
    {
        $this->db->select('id, name');
        $this->db->from('district');
        $this->db->where('id', $id);
        $query = $this->db->get();
        return $query->row();
    }

    public function get_ward_by_id($id)
    {
        $this->db->select('id, name');
        $this->db->from('wards');
        $this->db->where('id', $id);
        $query = $this->db->get();
        return $query->row();
    }

    public function provinces_new()
    {
        $this->db->select('*');
        $this->db->from('provinces');
        $query = $this->db->get();
        return $query->result_array();
    }
    public function wards_new($province_code)
    {
        $this->db->select('*');
        $this->db->from('wards');
        $this->db->where('province_code', $province_code);
        $query = $this->db->get();
        return $query->result_array();
    }
}
