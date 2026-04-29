<div style="padding: 20px; font-family: Arial, sans-serif;">
    <div>
        <div style="font-family: 'Times New Roman'; font-size: 14px;">Kính gửi quý Thầy/Cô</div>
        <div><b
                style="font-family: 'Times New Roman'; color: #242F37; font-size: 14px;"><?= isset($data['tieu_de']) ? $data['tieu_de'] : '' ?></b>
        </div>
        <div style="font-family: 'Times New Roman'; color: #513667; font-size: 14px;"><i><b>
                    <?php
                    if (isset($data['trang_thai']) && $data['trang_thai'] == 'hoan_thanh') {
                        echo 'Đề xuất đã được hoàn thành và phê duyệt toàn bộ.';
                    } elseif (isset($data['trang_thai']) && $data['trang_thai'] == 'tu_choi') {
                        echo 'Đề xuất đã bị từ chối.';
                    } elseif (isset($data['trang_thai']) && $data['trang_thai'] == 'dang_xu_ly') {
                        echo 'Bạn có một đề xuất mới cần xem xét và phê duyệt.';
                    } else {
                        echo 'Thông tin về đề xuất của bạn.';
                    }
                    ?>
                </b></i></div>
        <?php if (isset($data['noi_dung']) && $data['noi_dung'] && !is_array($data['noi_dung'])): ?>
            <div style="font-family: 'Times New Roman'; font-size: 14px; margin-top: 8px;"><?= (string)$data['noi_dung'] ?></div>
        <?php endif; ?>
        <div style="margin-top: 15px;">Chi tiết tại: <a target="_blank"
                href="<?= isset($data['link_dang_nhap']) ? $data['link_dang_nhap'] : (isset($data['link_duyet']) ? $data['link_duyet'] : '') ?>"><i>myOffice.nctu.edu.vn</i></a>
        </div>
    </div>

    <div style="margin-top: 15px; color: #222222;">
        <div>---</div>
        <!-- <div><b style="color: #25359D;">Phòng Tổ chức - Hành chính</b></div> -->
        <div><b style="color: #EC1E24;">Trường Đại Học Nam Cần Thơ</b></div>
        <div><span style="color: #688A56;">ĐC: </span><u><span style="color: #28549A;">168, Nguyễn Văn Cừ nối dài,
                    Phường An Bình, TP. Cần Thơ</span></u></div>
        <div><span style="color: #688A56;">ĐT: 0292 3798 668</span></div>

        <!-- Dòng ngẫu nhiên giúp tránh bị Gmail ẩn -->
        <div style="font-size: 10px; color: #ccc; margin-top: 20px; display: none">Mã email: <?= uniqid() ?></div>
    </div>
</div>