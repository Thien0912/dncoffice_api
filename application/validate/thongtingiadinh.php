<?php
$config = array(
    'create' => array(
        //Thông tin chung
        array(
            'field' => 'ho_ten',
            'label' => l('Full name'),
            'rules' => 'trim|required',
            'errors' => array(
                'required' => l('Full name is required')
            )
        ),
        array(
            'field' => 'moi_quan_he',
            'label' => l('Relationship'),
            'rules' => 'trim|required',
            'errors' => array(
                'required' => l('Relationship is required')
            )
        ),
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
);
