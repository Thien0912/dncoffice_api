<?php
$config = array(
    'create' => array(
        //Thông tin chung
        array(
            'field' => 'ma_nhan_vien',
            'label' => 'Mã nhân sự',
            'rules' => 'trim|required|is_unique[hrm_nhan_vien.ma_nhan_vien]',
            'errors' => array(
                'required' => 'Mã nhân sự là bắt buộc',
                'is_unique' => 'Mã nhân sự đã tồn tại trong hệ thống'
            )
        ),
        array(
            'field' => 'ho_va_ten',
            'label' => l('Full name'),
            'rules' => 'trim|required',
            'errors' => array(
                'required' => l('Full name is required')
            )
        ),
        array(
            'field' => 'cccd_so',
            'label' => l('CCCD'),
            'rules' => 'trim|required',
            'errors' => array(
                'required' => l('CCCD is required')
            )
        ),
        // array(
        //     'field' => 'email',
        //     'label' => l('Email'),
        //     'rules' => 'trim|required|valid_email|is_unique[hrm_nhan_vien.email]|is_unique[ql_nguoi_dung.ql_nguoi_dung_email]',
        //     'errors' => array(
        //         'required' => l('Email is required'),
        //         'valid_email' => l('Invalid email'),
        //         'is_unique'   => l('Email already exists')
        //     )
        // ),
        array(
            'field' => 'id_ca_lam_viec',
            'label' => l('Work shift'),
            'rules' => 'required',
            'errors' => array(
                'required' => l('Work shift is required')
            )
        ),
        // array(
        //     'field' => 'ngay_sinh',
        //     'label' => l('Birthday'),
        //     'rules' => 'trim|date',
        //     'errors' => array(
        //         'date' => l('Birthday is invalid')
        //     )
        // ),
        // array(
        //     'field' => 'ngay_vao_lam',
        //     'label' => l('Start date'),
        //     'rules' => 'trim|date',
        //     'errors' => array(
        //         'date' => l('Start date is invalid')
        //     )
        // ),
        // array(
        //     'field' => 'gioi_tinh',
        //     'label' => l('Sex'),
        //     'rules' => 'trim|in_list[F,M]',
        //     'errors' => array(
        //         'in_list' => l('Sex is invalid')
        //     )
        // )
    ),
    'create_cong_viec' => array(
        // array(
        //     'field' => 'id_don_vi_cong_tac',
        //     'label' => l('work unit'),
        //     'rules' => 'trim|required',
        //     'errors' => array(
        //         'required' => l('Work unit is required')
        //     )
        // ),
        // array(
        //     'field' => 'id_vi_tri_cong_viec',
        //     'label' => l('Work position'),
        //     'rules' => 'trim|required',
        //     'errors' => array(
        //         'required' => l('Work position is required')
        //     )
        // ),
        array(
            'field' => 'trang_thai',
            'label' => l('Status'),
            'rules' => 'trim|required',
            'errors' => array(
                'required' => l('Work status is required')
            )
        ),
        array(
            'field' => 'loai_hop_dong',
            'label' => l('Contract'),
            'rules' => 'required',
            'errors' => array(
                'required' => l('Contract is required')
            )
        ),
    ),
    'update' => array(
        //Thông tin chung
        array(
            'field' => 'ho_va_ten',
            'label' => l('Full name'),
            'rules' => 'trim|required',
            'errors' => array(
                'required' => l('Full name is required')
            )
        ),
        // array(
        //     'field' => 'email',
        //     'label' => l('Email'),
        //     'rules' => "trim|valid_email|is_unique[hrm_nhan_vien.email,id_nhan_vien,$id]|is_unique[ql_nguoi_dung.ql_nguoi_dung_email,ql_nguoi_dung_id,$nhanvienId]",
        //     'errors' => array(
        //         'valid_email' => l('Invalid email'),
        //         'is_unique'   => l('Email already exists')
        //     )
        // ),
        array(
            'field' => 'id_ca_lam_viec',
            'label' => l('Work shift'),
            'rules' => 'required',
            'errors' => array(
                'required' => l('Work shift is required')
            )
        ),
    ),
    'update_cong_viec' => array(
        array(
            'field' => 'id_don_vi_cong_tac',
            'label' => l('work unit'),
            'rules' => 'trim|required',
            'errors' => array(
                'required' => l('Work unit is required')
            )
        ),
        array(
            'field' => 'id_vi_tri_cong_viec',
            'label' => l('Work position'),
            'rules' => 'trim|required',
            'errors' => array(
                'required' => l('Work position is required')
            )
        ),
        array(
            'field' => 'trang_thai',
            'label' => l('Status'),
            'rules' => 'trim|required',
            'errors' => array(
                'required' => l('Work status is required')
            )
        ),
    ),
);
