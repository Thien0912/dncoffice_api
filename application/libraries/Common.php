<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Common
{
    const VAN_BAN_DEN = 1;

    const VAN_BAN_DI = 2;

    const VAN_BAN_NOI_BO = 3;

    const STATUS_VAN_BAN_DEN = [
        'TIEP_NHAN' => [
            'label' => 'Tiếp nhận',
            'value' => 1,
            'color' => 'teal'
        ],
        'CHO_LANH_DAO_BUT_PHE' => [
            'label' => 'Chờ lãnh đạo bút phê',
            'value' => 2,
            'color' => 'blue'
        ],
        'DA_BUT_PHE' => [
            'label' => 'Đã bút phê',
            'value' => 3,
            'color' => 'orange'
        ],
        'CHO_XU_LY' => [
            'label' => 'Chờ xử lý',
            'value' => 4,
            'color' => 'yellow'
        ],
        'DA_XU_LY' => [
            'label' => 'Đã xử lý',
            'value' => 5,
            'color' => 'green'
        ],

        'LUU_TRU' => [
            'label' => 'Lưu trữ',
            'value' => 6,
            'color' => 'cyan'
        ],

        'CHUA_PHAN_HOI' => [
            'label' => 'Chưa phản hồi',
            'value' => 7,
            'color' => 'red'
        ],
        'DA_PHAN_HOI' => [
            'label' => 'Đã phản hồi',
            'value' => 8,
            'color' => 'green'
        ],
        'DA_XEM' => [
            'label' => 'Đã xem',
            'value' => 9,
            'color' => ''
        ],
        'HOAN_THANH' => [
            'label' => 'Hoàn thành',
            'value' => 10,
            'color' => 'teal'
        ],
    ];

    const STATUS_VAN_BAN_DI = [
        'TAO_MOI' => [
            'label' => 'Tạo mới',
            'value' => 1,
            'color' => 'teal'
        ],
        // 'DE_XUAT_DUYET' => [
        //     'label' => 'Đề xuất duyệt',
        //     'value' => 2,
        //     'color' => 'yellow'
        // ],
        // 'KHONG_DUYET' => [
        //     'label' => 'Không duyệt',
        //     'value' => 3,
        //     'color' => 'red'
        // ],
        'DA_BAN_HANH' => [
            'label' => 'Đã ban hành',
            'value' => 2,
            'color' => 'blue'
        ],
        'CHO_XU_LY' => [
            'label' => 'Chờ xử lý',
            'value' => 3,
            'color' => 'yellow'
        ],
        'DA_PHAN_HOI' => [
            'label' => 'Đã phản hồi',
            'value' => 4,
            'color' => 'green'
        ],
        'CHUA_PHAN_HOI' => [
            'label' => 'Chưa phản hồi',
            'value' => 5,
            'color' => 'red'
        ],
        'LUU_TRU' => [
            'label' => 'Lưu trữ',
            'value' => 6,
            'color' => 'brown'
        ],
        'HOAN_THANH' => [
            'label' => 'Hoàn thành',
            'value' => 7,
            'color' => 'teal'
        ],
    ];

    const STATUS_VAN_BAN_DI_DON_VI = [
        'TAO_MOI' => [
            'label' => 'Tạo mới',
            'value' => 1,
            'color' => 'teal'
        ],
        // 'DE_XUAT_DUYET' => [
        //     'label' => 'Đề xuất duyệt',
        //     'value' => 2,
        //     'color' => 'yellow'
        // ],
        // 'KHONG_DUYET' => [
        //     'label' => 'Không duyệt',
        //     'value' => 3,
        //     'color' => 'red'
        // ],
        'DA_BAN_HANH' => [
            'label' => 'Đã ban hành',
            'value' => 2,
            'color' => 'blue'
        ],
        'CHO_XU_LY' => [
            'label' => 'Chờ xử lý',
            'value' => 3,
            'color' => 'yellow'
        ],
        'DA_PHAN_HOI' => [
            'label' => 'Đã phản hồi',
            'value' => 4,
            'color' => 'green'
        ],
        'CHUA_PHAN_HOI' => [
            'label' => 'Chưa phản hồi',
            'value' => 5,
            'color' => 'red'
        ],
        'LUU_TRU' => [
            'label' => 'Lưu trữ',
            'value' => 6,
            'color' => 'brown'
        ],
        'HOAN_THANH' => [
            'label' => 'Hoàn thành',
            'value' => 7,
            'color' => 'teal'
        ],
    ];

    const STATUS_VAN_BAN_NOI_BO = [
        'TAO_MOI' => [
            'label' => 'Tạo mới',
            'value' => 1
        ]
    ];

    const LOAI_HOP_DONG = [
        'Hoc_viec' => [
            'label' => 'Học việc',
            'value' => 'Hoc_viec',
            'color' => 'cyan',
            'time'  => '2',
            'percent' => '100'
        ],
        'Thu_viec' => [
            'label' => 'Thử việc',
            'value' => 'Thu_viec',
            'color' => 'red',
            'time'  => '2',
            'percent' => '85'
        ],
        'Co_thoi_han' => [
            'label' => 'Chính thức',
            'value' => 'Co_thoi_han',
            'color' => 'yellow',
            'time'  => '12',
            'percent' => '100'
        ],
        'Khong_thoi_han' => [
            'label' => 'Không xác định thời hạn',
            'value' => 'Khong_thoi_han',
            'color' => 'green',
            'time'  => '',
            'percent' => '100'
        ],
        'Dao_tao' => [
            'label' => 'Hợp đồng đào tạo',
            'value' => 'Dao_tao',
            'color' => 'blue',
            'time'  => '',
            'percent' => '50'
        ],
        'Co_van' => [
            'label' => 'Hợp đồng cố vấn',
            'value' => 'Co_van',
            'color' => 'blue',
            'time'  => '',
            'percent' => '0'
        ],
    ];

    const LOAI_NGAY = [
        'Ngay_thuong' => [
            'label' => 'Ngày thường',
            'value' => 'Ngay_thuong',
            'color' => 'green'
        ],
        'Ngay_nghi' => [
            'label' => 'Ngày nghỉ',
            'value' => 'Ngay_nghi',
            'color' => 'yellow'
        ],
        'Ngay_le' => [
            'label' => 'Ngày lễ',
            'value' => 'Ngay_le',
            'color' => 'red'
        ]
    ];

    const STATUS_DANG_KY_LAM_THEM = [
        'Cho_duyet' => [
            'label' => 'Chờ duyệt',
            'value' => 'Cho_duyet',
            'color' => 'yellow'
        ],
        'Da_duyet' => [
            'label' => 'Đã duyệt',
            'value' => 'Da_duyet',
            'color' => 'green'
        ],
        'Tu_choi' => [
            'label' => 'Từ chối',
            'value' => 'Tu_choi',
            'color' => 'red'
        ]
    ];

    const LOAI_DON_VI = [
        'LANH_DAO' => [
            'label' => 'Lãnh đạo',
            'value' => 'LANH_DAO',
            'color' => 'red'
        ],
        'PHONG' => [
            'label' => 'Phòng',
            'value' => 'PHONG',
            'color' => 'green'
        ],
        'KHOA_BOMON' => [
            'label' => 'Khoa/Bộ môn',
            'value' => 'KHOA_BOMON',
            'color' => 'yellow'
        ],
        'BAN' => [
            'label' => 'Ban',
            'value' => 'BAN',
            'color' => 'red'
        ],
        'VIEN' => [
            'label' => 'Viện',
            'value' => 'VIEN',
            'color' => 'red'
        ],
        'TRUNG_TAM' => [
            'label' => 'Trung tâm',
            'value' => 'TRUNG_TAM',
            'color' => 'red'
        ],
        'DON_VI_KHAC' => [
            'label' => 'Đơn vị khác',
            'value' => 'DON_VI_KHAC',
            'color' => 'red'
        ],
        'DOANH_NGHIEP' => [
            'label' => 'Doanh nghiệp',
            'value' => 'DOANH_NGHIEP',
            'color' => 'cyan'
        ],
        // Các loại đơn vị mới cho danh mục dùng chung
        'PHONG_BAN' => [
            'label' => 'Phòng ban',
            'value' => 'PHONG_BAN',
            'color' => 'green'
        ],
        'KHOA' => [
            'label' => 'Khoa',
            'value' => 'KHOA',
            'color' => 'primary'
        ],
    ];

    const TRANG_THAI_CONG_VIEC = [
        'DANG_HOC_VIEC' => [
            'label' => 'Đang học việc',
            'value' => 'DANG_HOC_VIEC',
            'color' => '#3577f1',
        ],
        'DANG_THU_VIEC' => [
            'label' => 'Đang thử việc',
            'value' => 'DANG_THU_VIEC',
            'color' => '#3577f1',
        ],
        'DANG_LAM_VIEC' => [
            'label' => 'Đang làm việc',
            'value' => 'DANG_LAM_VIEC',
            'color' => '#45cb85',
        ],
        'TAM_NGHI' => [
            'label' => 'Tạm nghỉ',
            'value' => 'TAM_NGHI',
            'color' => '#ffbe0b',
        ],
        'DANG_LAM_THU_TUC_THOI_VIEC' => [
            'label' => 'Đang làm thủ tục thôi việc',
            'value' => 'DANG_LAM_THU_TUC_THOI_VIEC',
            'color' => '#f4a261',
        ],
        'NGHI_VIEC' => [
            'label' => 'Nghỉ việc',
            'value' => 'NGHI_VIEC',
            'color' => '#e93c3cff',
        ]
    ];

    const ICON_TEP_TIN = [
        'pdf' => [
            'label' => 'fas fa-file-pdf',
            'color' => 'text-danger'
        ],
        'doc' => [
            'label' => 'fas fa-file-word',
            'color' => 'text-primary'
        ],
        'docx' => [
            'label' => 'fas fa-file-word',
            'color' => 'text-primary'
        ],
        'xls' => [
            'label' => 'fas fa-file-excel',
            'color' => 'text-success'
        ],
        'xlsx' => [
            'label' => 'fas fa-file-excel',
            'color' => 'text-success'
        ],
        'ppt' => [
            'label' => 'fas fa-file-powerpoint',
            'color' => 'text-danger'
        ],
        'pptx' => [
            'label' => 'fas fa-file-powerpoint',
            'color' => 'text-danger'
        ],
        'jpg' => [
            'label' => 'fas fa-file-image',
            'color' => 'text-info'
        ],
        'jpeg' => [
            'label' => 'fas fa-file-image',
            'color' => 'text-info'
        ],
        'png' => [
            'label' => 'fas fa-file-image',
            'color' => 'text-info'
        ],
        'gif' => [
            'label' => 'fas fa-file-image',
            'color' => 'text-info'
        ],
        'zip' => [
            'label' => 'fas fa-file-archive',
            'color' => 'text-secondary'
        ],
        'rar' => [
            'label' => 'fas fa-file-archive',
            'color' => 'text-secondary'
        ],
        'txt' => [
            'label' => 'fas fa-file-alt',
            'color' => 'text-secondary'
        ],
    ];

    const HINH_THUC_LAM_VIEC = [
        'TOAN_THOI_GIAN' => [
            'label' => 'Toàn thời gian',
            'value' => 'TOAN_THOI_GIAN',
            'color' => '#3577f1',
        ],
        'BAN_THOI_GIAN' => [
            'label' => 'Bán thời gian',
            'value' => 'BAN_THOI_GIAN',
            'color' => '#45cb85',
        ],
        'CONG_TAC_VIEN' => [
            'label' => 'Cộng tác viên',
            'value' => 'CONG_TAC_VIEN',
            'color' => '#ffbe0b',
        ],
        'LAM_VIEC_TU_XA' => [
            'label' => 'Làm việc từ xa',
            'value' => 'LAM_VIEC_TU_XA',
            'color' => '#f4a261',
        ],
    ];

    const STATUS_BANG_LUONG_THANG = [
        'Chua_duyet' => [
            'label' => 'Chưa duyệt',
            'value' => 'Chua_duyet',
            'color' => 'yellow',
        ],
        'Da_duyet' => [
            'label' => 'Đã duyệt',
            'value' => 'Da_duyet',
            'color' => 'green',
        ],
        'Tu_choi' => [
            'label' => 'Từ chối',
            'value' => 'Tu_choi',
            'color' => 'brown',
        ],
        'Huy_duyet' => [
            'label' => 'Hủy duyệt',
            'value' => 'Huy_duyet',
            'color' => 'red',
        ],
    ];

    const STATUS_NGHI_PHEP = [
        'Cho_duyet' => [
            'label' => 'Chưa duyệt',
            'value' => 'Cho_duyet',
            'color' => 'yellow',
        ],
        'Da_duyet' => [
            'label' => 'Đã duyệt',
            'value' => 'Da_duyet',
            'color' => 'green',
        ],
        'Tu_choi' => [
            'label' => 'Từ chối',
            'value' => 'Tu_choi',
            'color' => 'red',
        ],
    ];

    const LOAI_PHEP = [
        'Nam' => [
            'label' => 'Năm',
            'value' => 'Nam',
            'color' => 'yellow',
        ],
        'Khong_luong' => [
            'label' => 'Không lương',
            'value' => 'Khong_luong',
            'color' => 'green',
        ],
        'Om' => [
            'label' => 'Ốm',
            'value' => 'Om',
            'color' => 'red',
        ],
    ];

    const TRANG_THAI_BANG_CHAM_CONG = [
        'DANG_CHO_DUYET' => [
            'label' => 'Đang chờ duyệt',
            'value' => 'DANG_CHO_DUYET',
            'color' => '#3577f1',
        ],
        'DA_DUYET' => [
            'label' => 'Đã duyệt',
            'value' => 'DA_DUYET',
            'color' => '#45cb85',
        ],
        'BI_TU_cHOI' => [
            'label' => 'Bị từ chối',
            'value' => 'BI_TU_CHOI',
            'color' => '#ffbe0b',
        ],
        'KHOA' => [
            'label' => 'Khóa',
            'value' => 'KHOA',
            'color' => '#f06548',
        ]
    ];

    const TAM_UNG = [
        'CHUA_DUYET' => [
            'label' => 'Chưa duyệt',
            'value' => 0,
            'color' => '#3577f1',
        ],
        'DA_DUYET' => [
            'label' => 'Đã duyệt',
            'value' => 1,
            'color' => '#3577f1',
        ],
        'DA_TAT_TOAN' => [
            'label' => 'Đã tất toán',
            'value' => 2,
            'color' => '#45cb85',
        ]
    ];

    const HINH_THUC_HOAN_TRA = [
        'MOT_LAN' => [
            'label' => 'Một lần',
            'value' => '0',
            'color' => '#3577f1',
        ],
        'TUNG_THANG' => [
            'label' => 'Từng tháng',
            'value' => '1',
            'color' => '#45cb85',
        ]
    ];

    const STATUS_HOP_DONG = [
        'Chua_duyet' => [
            'label' => 'Chưa duyệt',
            'value' => 'Chua_duyet',
            'color' => 'yellow'
        ],
        'Da_duyet' => [
            'label' => 'Đã duyệt',
            'value' => 'Da_duyet',
            'color' => 'green'
        ],
        'Tu_choi' => [
            'label' => 'Từ chối',
            'value' => 'Tu_choi',
            'color' => 'red'
        ],
        'Huy_duyet' => [
            'label' => 'Hủy duyệt',
            'value' => 'Huy_duyet',
            'color' => 'orange'
        ]
    ];

    const VAI_TRO_SUPER_ADMIN = [
        'SUPER_ADMIN'
    ];

    const TRUONG = [
        'TRUONG_SUC_KHOE' => [
            'label' => 'Trường khoa học sức khỏe',
            'value' => 'TRUONG_SUC_KHOE',
        ],
        'TRUONG_CN_KT' => [
            'label' => 'Trường Công nghệ - Kỹ thuật',
            'value' => 'TRUONG_CN_KT',
        ],
        'TRUONG_CNS_TTNT' => [
            'label' => 'Trường Công nghệ số & Trí tuệ nhân tạo',
            'value' => 'TRUONG_CNS_TTNT',
        ],
        'TRUONG_LUAT_KT' => [
            'label' => 'Trường Luật - Kinh tế',
            'value' => 'TRUONG_LUAT_KT',
        ],
        'TRUONG_SONG_NGU_DNC' => [
            'label' => 'Trường Tiểu học, THCS & THPT Song ngữ DNC',
            'value' => 'TRUONG_SONG_NGU_DNC',
        ],
    ];
}
