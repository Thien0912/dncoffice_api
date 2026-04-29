<?php
defined('BASEPATH') or exit('No direct script access allowed');

$route['default_controller'] = 'welcome';

const prefixApi = 'api/v1/admin/';
const prefixController = 'api/v1/admin/';

$route[prefixApi . 'vanban/vanbanden'] = prefixController . 'vanban/vanbanden/index';
$route[prefixApi . 'vanban/vanbanden/create'] = prefixController . 'vanban/vanbanden/create';
$route[prefixApi . 'vanban/vanbanden/show/(:num)'] = prefixController . 'vanban/vanbanden/show/$1';
$route[prefixApi . 'vanban/vanbanden/tao_butphe/(:num)'] = prefixController . 'vanban/vanbanden/tao_butphe/$1';
$route[prefixApi . 'vanban/vanbanden/delete'] = prefixController . 'vanban/vanbanden/delete';
$route[prefixApi . 'vanban/vanbanden/xuly_vanban/(:num)'] = prefixController . 'vanban/vanbanden/xuly_vanban/$1';
$route[prefixApi . 'vanban/vanbanden/xuly_vanban_update/(:num)'] = prefixController . 'vanban/vanbanden/xuly_vanban_update/$1';
$route[prefixApi . 'vanban/vanbanden/update_butphe/(:num)'] = prefixController . 'vanban/vanbanden/update_butphe/$1';
$route[prefixApi . 'vanban/vanbanden/change_status/(:num)'] = prefixController . 'vanban/vanbanden/change_status/$1';
$route[prefixApi . 'vanban/vanbanden/update-files/(:num)'] = prefixController . 'vanban/vanbanden/update_files/$1';

$route[prefixApi . 'vanban/vanbanden/update/(:num)'] = prefixController . 'vanban/vanbanden/update/$1';
$route[prefixApi . 'vanban/vanbanden/delete/(:num)'] = prefixController . 'vanban/vanbanden/delete/$1';
$route[prefixApi . 'vanban/vanbanden/export'] = prefixController . 'vanban/vanbanden/export';
$route[prefixApi . 'vanban/vanbanden/send_mail_vbden/(:num)'] = prefixController . 'vanban/vanbanden/sendMailVBDen/$1';

$route[prefixApi . 'vanban/vanbandi'] = prefixController . 'vanban/vanbandi/index';
$route[prefixApi . 'vanban/vanbandi/create'] = prefixController . 'vanban/vanbandi/create';
$route[prefixApi . 'vanban/vanbandi/show/(:num)'] = prefixController . 'vanban/vanbandi/show/$1';
$route[prefixApi . 'vanban/vanbandi/update/(:num)'] = prefixController . 'vanban/vanbandi/update/$1';
$route[prefixApi . 'vanban/vanbandi/export'] = prefixController . 'vanban/vanbandi/export';
$route[prefixApi . 'vanban/vanbandi/delete'] = prefixController . 'vanban/vanbandi/delete';
$route[prefixApi . 'vanban/vanbandi/import'] = prefixController . 'vanban/vanbandi/import';
$route[prefixApi . 'vanban/vanbandi/baocaophanhoi/(:num)'] = prefixController . 'vanban/vanbandi/bao_cao_phan_hoi_create/$1';
$route[prefixApi . 'vanban/vanbandi/xembaocao/(:num)'] = prefixController . 'vanban/vanbandi/xembaocao/$1';
$route[prefixApi . 'vanban/vanbandi/send_mail_vbdi/(:num)'] = prefixController . 'vanban/vanbandi/sendMailVBDi/$1';

$route[prefixApi . 'vanban/vanbandidonvi'] = prefixController . 'vanban/vanbandidonvi/index';
$route[prefixApi . 'vanban/vanbandidonvi/create'] = prefixController . 'vanban/vanbandidonvi/create';
$route[prefixApi . 'vanban/vanbandidonvi/show/(:num)'] = prefixController . 'vanban/vanbandidonvi/show/$1';
$route[prefixApi . 'vanban/vanbandidonvi/update/(:num)'] = prefixController . 'vanban/vanbandidonvi/update/$1';
$route[prefixApi . 'vanban/vanbandidonvi/export'] = prefixController . 'vanban/vanbandidonvi/export';
$route[prefixApi . 'vanban/vanbandidonvi/delete'] = prefixController . 'vanban/vanbandidonvi/delete';
$route[prefixApi . 'vanban/vanbandidonvi/import'] = prefixController . 'vanban/vanbandidonvi/import';
$route[prefixApi . 'vanban/vanbandidonvi/baocaophanhoi/(:num)'] = prefixController . 'vanban/vanbandidonvi/bao_cao_phan_hoi_create/$1';
$route[prefixApi . 'vanban/vanbandidonvi/xembaocao/(:num)'] = prefixController . 'vanban/vanbandidonvi/xembaocao/$1';

$route[prefixApi . 'vanban/vanbandentudonvi'] = prefixController . 'vanban/vanbandentudonvi/index';

$route[prefixApi . 'vanban/vanbannoibo'] = prefixController . 'vanban/vanbannoibo/index';
$route[prefixApi . 'vanban/vanbannoibo/create'] = prefixController . 'vanban/vanbannoibo/create';
$route[prefixApi . 'vanban/vanbannoibo/show/(:num)'] = prefixController . 'vanban/vanbannoibo/show/$1';
$route[prefixApi . 'vanban/vanbannoibo/update/(:num)'] = prefixController . 'vanban/vanbannoibo/update/$1';
$route[prefixApi . 'vanban/vanbannoibo/export'] = prefixController . 'vanban/vanbannoibo/export';
$route[prefixApi . 'vanban/vanbannoibo/delete'] = prefixController . 'vanban/vanbannoibo/delete';


$route[prefixApi . 'vanban/vanbanden/butphe'] = prefixController . 'vanban/vanbanden/butphe';
$route[prefixApi . 'vanban/vanbanden/xulyvanban'] = prefixController . 'vanban/vanbanden/xulyvanban';
$route[prefixApi . 'vanban/vanbanden/baocaophanhoi'] = prefixController . 'vanban/vanbanden/baocaophanhoi';
$route[prefixApi . 'vanban/vanbanden/baocaophanhoi/phan-hoi/(:num)'] = prefixController . 'vanban/vanbanden/bao_cao_phan_hoi_create/$1';
$route[prefixApi . 'vanban/vanbandendonvi/baocaophanhoi/(:num)'] = prefixController . 'vanban/vanbandendonvi/bao_cao_phan_hoi_create/$1';
$route[prefixApi . 'vanban/vanbanden/danhsach-baocaophanhoi-theo-donvi'] = prefixController . 'vanban/vanbanden/danhsach_baocaophanhoi_theo_donvi';

$route[prefixApi . 'coquan'] = prefixController . 'danhmuc/coquan/index';
$route[prefixApi . 'coquan/create'] = prefixController . 'danhmuc/coquan/create';
$route[prefixApi . 'hinhthuc'] = prefixController . 'danhmuc/hinhthuc/index';
$route[prefixApi . 'hinhthuc/create'] = prefixController . 'danhmuc/hinhthuc/create';
$route[prefixApi . 'donvi'] = prefixController . 'danhmuc/donvi/index';
$route[prefixApi . 'donvi/theophongban'] = prefixController . 'danhmuc/donvi/theophongban';
$route[prefixApi . 'donvi/create'] = prefixController . 'danhmuc/donvi/create';
$route[prefixApi . 'trangthai'] = prefixController . 'danhmuc/trangthai/index';
$route[prefixApi . 'trangthai/create'] = prefixController . 'danhmuc/trangthai/create';
$route[prefixApi . 'tinhchat'] = prefixController . 'danhmuc/tinhchat/index';
$route[prefixApi . 'tinhchat/create'] = prefixController . 'danhmuc/tinhchat/create';
$route[prefixApi . 'baomat'] = prefixController . 'danhmuc/baomat/index';
$route[prefixApi . 'baomat/create'] = prefixController . 'danhmuc/baomat/create';
$route[prefixApi . 'loai'] = prefixController . 'danhmuc/loai/index';
$route[prefixApi . 'loai/create'] = prefixController . 'danhmuc/loai/create';
$route[prefixApi . 'khoicoquan'] = prefixController . 'danhmuc/khoicoquan/index';
$route[prefixApi . 'khoicoquan/create'] = prefixController . 'danhmuc/khoicoquan/create';

$route[prefixApi . 'nguoidung'] = prefixController . 'danhmuc/nguoidung';

$route[prefixApi . 'bophan'] = prefixController . 'danhmuc/bophan/index';
$route[prefixApi . 'province'] = prefixController . 'danhmuc/bophan/index';

$route[prefixApi . 'yeucaucapnhat'] = prefixController . 'profile/yeucaucapnhat_post';

$route[prefixApi . 'phucloi'] = prefixController . 'hrm/phucloi';
$route[prefixApi . 'phucloi/create'] = prefixController . 'hrm/phucloi/create';

//Chứng chỉ
$route[prefixApi . 'chungchi'] = prefixController . 'hrm/chungchi';
$route[prefixApi . 'chungchi/create'] = prefixController . 'hrm/chungchi/create';
$route[prefixApi . 'chungchi/show/(:num)'] = prefixController . 'hrm/chungchi/show/$1';
$route[prefixApi . 'chungchi/delete/(:num)'] = prefixController . 'hrm/chungchi/delete/$1';

//khen thưởng
$route[prefixApi . 'khenthuong'] = prefixController . 'hrm/khenthuong';
$route[prefixApi . 'khenthuong/create'] = prefixController . 'hrm/khenthuong/create';
$route[prefixApi . 'khenthuong/show/(:num)'] = prefixController . 'hrm/khenthuong/show/$1';
$route[prefixApi . 'khenthuong/update/(:num)'] = prefixController . 'hrm/khenthuong/update/$1';
$route[prefixApi . 'khenthuong/delete/(:num)'] = prefixController . 'hrm/khenthuong/delete/$1';

//Bằng cấp
$route[prefixApi . 'bangcap'] = prefixController . 'hrm/bangcap';
$route[prefixApi . 'bangcap/create'] = prefixController . 'hrm/bangcap/create';
$route[prefixApi . 'bangcap/show/(:num)'] = prefixController . 'hrm/bangcap/show/$1';
$route[prefixApi . 'bangcap/update/(:num)'] = prefixController . 'hrm/bangcap/update/$1';
$route[prefixApi . 'bangcap/delete/(:num)'] = prefixController . 'hrm/bangcap/delete/$1';

//Đánh giá
$route[prefixApi . 'danhgia/create'] = prefixController . 'hrm/danhgia/create';
$route[prefixApi . 'danhgia/show/(:num)'] = prefixController . 'hrm/danhgia/show/$1';
$route[prefixApi . 'danhgia/edit/(:num)'] = prefixController . 'hrm/danhgia/edit/$1';
$route[prefixApi . 'danhgia/delete/(:num)'] = prefixController . 'hrm/danhgia/delete/$1';

//Tài liệu đính kèm
$route[prefixApi . 'tailieudinhkem/create'] = prefixController . 'hrm/tailieudinhkem/create';
$route[prefixApi . 'tailieudinhkem/show/(:num)'] = prefixController . 'hrm/tailieudinhkem/show/$1';
$route[prefixApi . 'tailieudinhkem/update/(:num)'] = prefixController . 'hrm/tailieudinhkem/update/$1';

//Thông tin gia đình
$route[prefixApi . 'thongtingiadinh/create'] = prefixController . 'hrm/thongtingiadinh/create';
$route[prefixApi . 'thongtingiadinh/show/(:num)'] = prefixController . 'hrm/thongtingiadinh/show/$1';
$route[prefixApi . 'thongtingiadinh/edit/(:num)'] = prefixController . 'hrm/thongtingiadinh/edit/$1';
$route[prefixApi . 'thongtingiadinh/delete/(:num)'] = prefixController . 'hrm/thongtingiadinh/delete/$1';

//Thôi việc
$route[prefixApi . 'thoiviec'] = prefixController . 'hrm/thoiviec';
$route[prefixApi . 'thoiviec/danh-sach/nhan-vien'] = prefixController . 'hrm/thoiviec/thoi_viec_byIdNhanVien';
$route[prefixApi . 'thoiviec/create'] = prefixController . 'hrm/thoiviec/create';
$route[prefixApi . 'thoiviec/show/(:num)'] = prefixController . 'hrm/thoiviec/show/$1';
$route[prefixApi . 'thoiviec/delete/(:num)'] = prefixController . 'hrm/thoiviec/delete/$1';
$route[prefixApi . 'thoiviec/deletes'] = prefixController . 'hrm/thoiviec/deletes';
$route[prefixApi . 'thoiviec/update/(:num)'] = prefixController . 'hrm/thoiviec/update/$1';
$route[prefixApi . 'thoiviec/accept/(:num)'] = prefixController . 'hrm/thoiviec/accept/$1';
$route[prefixApi . 'thoiviec/addNhanvienthoiviec'] = prefixController . 'hrm/thoiviec/addNhanvienthoiviec';

//Nhóm thủ tục thôi việc
$route[prefixApi . 'nhomthutucthoiviec'] = prefixController . 'hrm/nhomthutucthoiviec/index';
$route[prefixApi . 'nhomthutucthoiviec/create'] = prefixController . 'hrm/nhomthutucthoiviec/create';
$route[prefixApi . 'nhomthutucthoiviec/show/(:num)'] = prefixController . 'hrm/nhomthutucthoiviec/show/$1';
$route[prefixApi . 'nhomthutucthoiviec/deletes'] = prefixController . 'hrm/nhomthutucthoiviec/deletes';
$route[prefixApi . 'nhomthutucthoiviec/delete/(:num)'] = prefixController . 'hrm/nhomthutucthoiviec/delete/$1';
$route[prefixApi . 'nhomthutucthoiviec/update/(:num)'] = prefixController . 'hrm/nhomthutucthoiviec/update/$1';

//Thủ tục thôi việc
$route[prefixApi . 'thutucthoiviec'] = prefixController . 'hrm/thutucthoiviec';
$route[prefixApi . 'thutucthoiviec/create'] = prefixController . 'hrm/thutucthoiviec/create';
$route[prefixApi . 'thutucthoiviec/delete/(:num)'] = prefixController . 'hrm/thutucthoiviec/delete/$1';
$route[prefixApi . 'thutucthoiviec/update/(:num)'] = prefixController . 'hrm/thutucthoiviec/update/$1';

//Tỷ lệ bảo hiểm
$route[prefixApi . 'tylebaohiem'] = prefixController . 'danhmuc/hrm/tylebaohiem/index';
$route[prefixApi . 'tylebaohiem/latest'] = prefixController . 'danhmuc/hrm/tylebaohiem/latest';
$route[prefixApi . 'tylebaohiem/create'] = prefixController . 'danhmuc/hrm/tylebaohiem/create';

//Vị trí công việc
$route[prefixApi . 'vitricongviec'] = prefixController . 'danhmuc/hrm/vitricongviec/index';
$route[prefixApi . 'vitricongviec/create'] = prefixController . 'danhmuc/hrm/vitricongviec/create';

//Phụ cấp
$route[prefixApi . 'phucap'] = prefixController . 'danhmuc/hrm/phucap/index';
$route[prefixApi . 'phucap/create'] = prefixController . 'danhmuc/hrm/phucap/create';
$route[prefixApi . 'phucap/show/(:num)'] = prefixController . 'danhmuc/hrm/phucap/show/$1';
$route[prefixApi . 'phucap/update/(:num)'] = prefixController . 'danhmuc/hrm/phucap/update/$1';
$route[prefixApi . 'phucap/delete'] = prefixController . 'danhmuc/hrm/phucap/delete';

//Bảng lương
$route[prefixApi . 'bangluong/xuatbangluong'] = prefixController . 'hrm/bangluong/xuatbangluong';


//Quá trình công tác
$route[prefixApi . 'quatrinhcongtac'] = prefixController . 'hrm/quatrinhcongtac';
$route[prefixApi . 'quatrinhcongtac/(:num)'] = prefixController . 'hrm/quatrinhcongtac/show/$1';
$route[prefixApi . 'quatrinhcongtac/create'] = prefixController . 'hrm/quatrinhcongtac/create';
$route[prefixApi . 'quatrinhcongtac/update/(:num)'] = prefixController . 'hrm/quatrinhcongtac/update/$1';
$route[prefixApi . 'quatrinhcongtac/delete/(:num)'] = prefixController . 'hrm/quatrinhcongtac/delete/$1';

//Ca làm việc
$route[prefixApi . 'calamviec'] = prefixController . 'danhmuc/hrm/calamviec/index';
$route[prefixApi . 'calamviec/create'] = prefixController . 'danhmuc/hrm/calamviec/create';

//Ngân hàng
$route[prefixApi . 'nganhang'] = prefixController . 'hrm/nganhang/index';
$route[prefixApi . 'nganhang/cap-nhat'] = prefixController . 'hrm/nganhang/update';
$route[prefixApi . 'nganhang/show/(:num)'] = prefixController . 'hrm/nganhang/show/$1';
