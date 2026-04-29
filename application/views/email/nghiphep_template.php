<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đơn xin nghỉ phép</title>
</head>

<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;">
    <table cellpadding="0" cellspacing="0" width="100%" style="background:#f5f5f5;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" width="800" style="max-width:800px;background:#eee;border-radius:6px;overflow:hidden;">
                    <tr>
                        <td>
                            <h1 style="background:#34A853;color:#ffffff;text-align:center;padding:24px 0;margin:0;">
                                ĐƠN XIN NGHỈ PHÉP
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p>
                                <?php
                                if (isset($data['trang_thai_cap_hai']) && $data['trang_thai_cap_hai'] == 'Da_duyet') {
                                    echo 'Đơn xin nghỉ phép đã được cấp tổ chức duyệt';
                                } elseif (isset($data['trang_thai_cap_mot']) && $data['trang_thai_cap_mot'] == 'Da_duyet') {
                                    echo 'Đơn xin nghỉ phép đã được cấp lãnh đạo duyệt và đang chờ cấp tổ chức phê duyệt';
                                } else {
                                    echo 'Đơn xin nghỉ phép đã được gửi và đang chờ phê duyệt.';
                                }
                                ?>
                            </p>

                            <?php
                            // Tính từ ngày và đến ngày
                            $tuNgay = '';
                            $denNgay = '';
                            if (isset($data['chi_tiet_ngay_nghi']) && is_array($data['chi_tiet_ngay_nghi']) && count($data['chi_tiet_ngay_nghi']) > 0) {
                                $tuNgay = $data['chi_tiet_ngay_nghi'][0]['ngay_nghi'];
                                $denNgay = $data['chi_tiet_ngay_nghi'][count($data['chi_tiet_ngay_nghi']) - 1]['ngay_nghi'];
                            }
                            ?>

                            <table width="100%" style="border-collapse:collapse;margin:16px 0;">
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Họ và tên</td>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;"><?= isset($data['ho_va_ten']) ? $data['ho_va_ten'] : '' ?></td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Mã nhân viên</td>
                                    <td style="border:1px solid #333;padding:6px 12px;"><?= isset($data['ma_nhan_vien']) ? $data['ma_nhan_vien'] : '' ?></td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Đơn vị</td>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;"><?= isset($data['ten_don_vi']) ? $data['ten_don_vi'] : '' ?></td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Loại nghỉ phép</td>
                                    <td style="border:1px solid #333;padding:6px 12px;"><?= isset($data['ten_loai_phep']) ? $data['ten_loai_phep'] : '' ?></td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Hình thức nghỉ</td>
                                    <td style="border:1px solid #333;padding:6px 12px;"><?= isset($data['loai_nghi']) ? ($data['loai_nghi'] == 'Binh_thuong' ? 'Bình thường' : 'Đột xuất') : '' ?></td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Từ ngày</td>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;"><?= $tuNgay ? date('d/m/Y', strtotime($tuNgay)) : '' ?></td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Đến ngày</td>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;"><?= $denNgay ? date('d/m/Y', strtotime($denNgay)) : '' ?></td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Số ngày nghỉ</td>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;"><?= isset($data['so_ngay_nghi']) ? $data['so_ngay_nghi'] : '' ?> ngày</td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Lý do nghỉ phép</td>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;color:#34A853"><?= isset($data['ly_do_nghi']) ? $data['ly_do_nghi'] : '' ?></td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Mã đơn</td>
                                    <td style="border:1px solid #333;padding:6px 12px;"><?= isset($data['uuid_nghi_phep']) ? $data['uuid_nghi_phep'] : '' ?></td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Trạng thái</td>
                                    <td style="border:1px solid #333;padding:6px 12px;">
                                        <?php
                                        if (isset($data['trang_thai_cap_mot'])) {
                                            if ($data['trang_thai_cap_mot'] == 'Cho_duyet') echo 'Chờ duyệt';
                                            elseif ($data['trang_thai_cap_mot'] == 'Da_duyet') echo 'Đã duyệt';
                                            elseif ($data['trang_thai_cap_mot'] == 'Tu_choi') echo 'Từ chối';
                                        }
                                        ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="border:1px solid #333;padding:6px 12px;font-weight:bold;">Tạo lúc</td>
                                    <td style="border:1px solid #333;padding:6px 12px;"><?= isset($data['created_at']) ? $data['created_at'] : '' ?></td>
                                </tr>
                            </table>

                            <?php if (isset($data['chi_tiet_ngay_nghi']) && is_array($data['chi_tiet_ngay_nghi']) && count($data['chi_tiet_ngay_nghi']) > 0): ?>
                                <p><strong>Chi tiết ngày nghỉ</strong></p>
                                <table width="100%" style="border-collapse:collapse;margin:16px 0;">
                                    <tr>
                                        <th style="border:1px solid #333;padding:6px 12px;background:#34A853;color:#fff;">Ngày nghỉ</th>
                                        <th style="border:1px solid #333;padding:6px 12px;background:#34A853;color:#fff;">Buổi nghỉ</th>
                                        <th style="border:1px solid #333;padding:6px 12px;background:#34A853;color:#fff;">Số ngày</th>
                                    </tr>
                                    <?php foreach ($data['chi_tiet_ngay_nghi'] as $ngay): ?>
                                        <tr>
                                            <td style="border:1px solid #333;padding:6px 12px;"><?= date('d/m/Y', strtotime($ngay['ngay_nghi'])) ?></td>
                                            <td style="border:1px solid #333;padding:6px 12px;">
                                                <?php
                                                if ($ngay['buoi_nghi'] == 'Ca_ngay') echo 'Cả ngày';
                                                elseif ($ngay['buoi_nghi'] == 'Sang') echo 'Sáng';
                                                elseif ($ngay['buoi_nghi'] == 'Chieu') echo 'Chiều';
                                                ?>
                                            </td>
                                            <td style="border:1px solid #333;padding:6px 12px;"><?= $ngay['so_ngay_nghi'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>

                            <?php if (isset($data['danh_sach_nguoi_duyet']) && is_array($data['danh_sach_nguoi_duyet']) && count($data['danh_sach_nguoi_duyet']) > 0): ?>
                                <?php 
                                // Kiểm tra xem có người cấp 1 đã duyệt chưa
                                $co_nguoi_cap1_da_duyet = false;
                                // Kiểm tra xem có người cấp 2 đã duyệt chưa
                                $co_nguoi_cap2_da_duyet = false;
                                
                                foreach ($data['danh_sach_nguoi_duyet'] as $nguoi) {
                                    if (isset($nguoi['da_duyet']) && $nguoi['da_duyet'] == 1) {
                                        if (isset($nguoi['cap_duyet']) && $nguoi['cap_duyet'] == 1) {
                                            $co_nguoi_cap1_da_duyet = true;
                                        }
                                        if (isset($nguoi['cap_duyet']) && $nguoi['cap_duyet'] == 2) {
                                            $co_nguoi_cap2_da_duyet = true;
                                        }
                                    }
                                }
                                ?>
                                <p><strong>Thông tin phê duyệt</strong></p>
                                <table width="100%" style="border-collapse:collapse;margin:16px 0;">
                                    <tr>
                                        <th style="border:1px solid #333;padding:6px 12px;background:#34A853;color:#fff;">Người duyệt</th>
                                        <th style="border:1px solid #333;padding:6px 12px;background:#34A853;color:#fff;">Cấp duyệt</th>
                                        <th style="border:1px solid #333;padding:6px 12px;background:#34A853;color:#fff;">Trạng thái</th>
                                        <th style="border:1px solid #333;padding:6px 12px;background:#34A853;color:#fff;">Lý do</th>
                                        <th style="border:1px solid #333;padding:6px 12px;background:#34A853;color:#fff;">Thời gian</th>
                                    </tr>
                                    <?php foreach ($data['danh_sach_nguoi_duyet'] as $nguoi): ?>
                                        <tr>
                                            <td style="border:1px solid #333;padding:6px 12px;"><?= isset($nguoi['ho_ten']) ? $nguoi['ho_ten'] : '' ?></td>
                                            <td style="border:1px solid #333;padding:6px 12px;">
                                                <?php 
                                                if (isset($nguoi['cap_duyet'])) {
                                                    echo $nguoi['cap_duyet'] == 1 ? 'Cấp đơn vị' : 'Cấp tổ chức';
                                                }
                                                ?>
                                            </td>
                                            <td style="border:1px solid #333;padding:6px 12px;">
                                                <?php
                                                if (isset($nguoi['da_duyet'])) {
                                                    if ($nguoi['da_duyet'] == 1) {
                                                        echo '<span style="color:#34A853;">Đã duyệt</span>';
                                                    } elseif ($nguoi['da_duyet'] == 0 && isset($nguoi['thoi_gian_duyet'])) {
                                                        echo '<span style="color:#EA4335;">Từ chối</span>';
                                                    } else {
                                                        // Nếu là cấp 1 và đã có người cấp 1 duyệt rồi thì để trống
                                                        if (isset($nguoi['cap_duyet']) && $nguoi['cap_duyet'] == 1 && $co_nguoi_cap1_da_duyet) {
                                                            // Để trống
                                                        } 
                                                        // Nếu là cấp 2 và đã có người cấp 2 duyệt rồi thì để trống
                                                        elseif (isset($nguoi['cap_duyet']) && $nguoi['cap_duyet'] == 2 && $co_nguoi_cap2_da_duyet) {
                                                            // Để trống
                                                        } 
                                                        else {
                                                            // Còn lại hiển thị Chờ duyệt
                                                            echo '<span style="color:#FBBC04;">Chờ duyệt</span>';
                                                        }
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td style="border:1px solid #333;padding:6px 12px;"><?= isset($nguoi['ly_do']) ? $nguoi['ly_do'] : '' ?></td>
                                            <td style="border:1px solid #333;padding:6px 12px;"><?= isset($nguoi['thoi_gian_duyet']) && $nguoi['thoi_gian_duyet'] ? date('d/m/Y H:i', strtotime($nguoi['thoi_gian_duyet'])) : '' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            <?php endif; ?>

                            <?php if (isset($data['link_duyet']) && $data['link_duyet']): ?>
                                <div style="margin-top:24px;">
                                    <a href="<?= $data['link_duyet'] ?>" style="display:inline-block;background:#34A853;color:#ffffff;padding:12px 24px;text-decoration:none;border-radius:4px;">
                                        Xem chi tiết và phê duyệt
                                    </a>
                                </div>
                            <?php endif; ?>

                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>