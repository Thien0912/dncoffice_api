<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');


class Ql_quyen_model extends MY_Model
{
    protected $table = 'ql_quyen';
    protected $primaryKey = 'ql_quyen_id';
    protected $timestamps = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function getPermissionsByRole($roleId)
    {
        $permissions = $this->join('ql_vai_tro_quyen', 'ql_vai_tro_quyen.ql_quyen_id = ql_quyen.ql_quyen_id')
            ->join('ql_vai_tro', 'ql_vai_tro.ql_vai_tro_id = ql_vai_tro_quyen.ql_vai_tro_id')
            ->where('ql_vai_tro.ql_vai_tro_id', $roleId)
            ->get();
        return $permissions;
    }

    public function getSearch()
    {
        $searchValue = commonRequest('searchValue');

        $draw = commonRequest('draw');
        $created_at = commonRequest('created_at');

        $start = $this->input->get('start') ?? 0;
        $length = $this->input->get('length') ?? 10;

        $recordsTotal = $this->db->count_all('ql_quyen');

        if ($searchValue) {
            $this->db->group_start();
            $this->db->like('ql_quyen_ten', $searchValue);
            $this->db->or_like('ql_quyen_mo_ta', $searchValue);
            $this->db->or_like('ql_quyen_khoa', $searchValue);
            $this->db->group_end();
        }


        if ($created_at) {
            $this->db->where('created_at >=', "{$created_at} 00:00:00");
            $this->db->where('created_at <=', "{$created_at} 23:59:59");
        }

        $filteredQuery = clone $this->db;
        $recordsFiltered = $filteredQuery->count_all_results('ql_quyen', FALSE); // FALSE để không reset query

        $columns = [
            '',
            'ql_quyen_ten',
            'ql_quyen_mo_ta'
        ];


        if (commonRequest('order')) {
            $order = json_decode(commonRequest('order'), true);


            $orderColumnIndex = $order[0]['column'];
            $orderColumn = $columns[$orderColumnIndex];  // index starts at 0
            $orderDir = $order[0]['dir'];

            if (!empty($orderColumn) && !empty($orderDir)) {
                $this->db->order_by($orderColumn, $orderDir);
            }
        }


        if ($length != -1) $this->db->limit($length, $start);
        $query = $this->db->get('ql_quyen');

        $data = $query->result_array();

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $this->mapChildrenAndParent($data, true, true),
        ];
    }

    public function mapChildrenAndParent($permissions, $mapChildren = false, $mapParent = false)
    {
        // $permissions = array_map(function ($item) use ($mapChildren, $mapParent) {
        //     if ($mapChildren)
        //         $item['ql_quyen_children'] = $this->Ql_quyen_model->where('ql_quyen_parent_id', $item['ql_quyen_id'])->get();
        //     if ($mapParent)
        //         $item['ql_quyen_parent'] = $this->Ql_quyen_model->find($item['ql_quyen_parent_id']);
        //     return $item;
        // }, $permissions);
        // return $permissions;


        $permissionList = $this->Ql_quyen_model->get();
        $permissions = array_map(function ($item) use ($mapChildren, $mapParent, $permissionList) {
            $tmpChild = [];
            $tmpParent = null;
            foreach ($permissionList as $p) {
                if ($mapChildren) {
                    if ($item['ql_quyen_id'] == $p['ql_quyen_parent_id']) {
                        $tmpChild[] = $p;
                    }
                }
                if ($mapParent) {
                    if ($item['ql_quyen_parent_id'] == $p['ql_quyen_id']) {
                        $tmpParent = $p;
                        break; //break có thể gây ra lỗi cho children. nhưng hiện tại chỉ có 2 cấp nên nếu đã có parent thì nó đã là children nên sẽ không có thểm children
                    }
                }
            }
            $item['ql_quyen_children'] = $tmpChild;
            $item['ql_quyen_parent'] = $tmpParent;

            // 2 vòng lặp
            // if ($mapChildren) {
            //     $tmpChild = [];
            //     foreach ($permissionList as $p) {
            //         if ($item['ql_quyen_id'] == $p['ql_quyen_parent_id']) {
            //             $tmpChild[] = $p;
            //         }
            //     }
            //     $item['ql_quyen_children'] = $tmpChild;
            // }
            // if ($mapParent) {
            //     foreach ($permissionList as $p) {
            //         $item['ql_quyen_parent'] = null;
            //         if ($item['ql_quyen_parent_id'] == $p['ql_quyen_id']) {
            //             $item['ql_quyen_parent'] = $p;
            //             break;
            //         }
            //     }
            // }
            return $item;
        }, $permissions);
        return $permissions;
    }
}
