<div style="padding: 20px; border: 1px solid #ddd; font-family: Arial, sans-serif;">

    <p style="margin-bottom: 25px; color: #222222;"><b>Trường Đại Học Nam Cần Thơ chuyển lương tháng <?= $mY ?>.</b></p>

    <table style="border: none;"> 
        <tbody>
            <tr>
                <td style="padding: 5.5px 8px;"> Mã số nhân viên: </td>
                <td style="padding: 5.5px 8px;"> <?= $ma_nhan_vien ?> </td> 
            </tr>
            <tr>
                <td style="padding: 5.5px 8px;"> Họ tên: </td>
                <td style="padding: 5.5px 8px;"> <?= $ho_va_ten ?> </td> 
            </tr>
            <tr>
                <td style="padding: 5.5px 8px;"> Đơn vị: </td>
                <td style="padding: 5.5px 8px;"> <?= $don_vi ?> </td> 
            </tr>
            <tr>
                <td style="padding: 5.5px 8px;"> Số tài khoản: </td>
                <td style="padding: 5.5px 8px;"> <?= $so_tai_khoan ?> </td> 
            </tr>
            <tr>
                <td style="padding: 5.5px 8px;"> Ngân hàng: </td>
                <td style="padding: 5.5px 8px;"> <?= $ngan_hang ?> </td> 
            </tr>
            <tr>
                <td style="padding: 5.5px 8px;"> Số tiền nhận: </td>
                <td style="padding: 5.5px 8px;"> <?= $so_tien_nhan ?> </td> 
            </tr>
            <tr>
                <td style="padding: 5.5px 8px;"> Ngày chuyển khoản: </td>
                <td style="padding: 5.5px 8px;"> <?= $ngay_chuyen_khoan ?> </td> 
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px; color: #222222;">
        <div>Trân trọng cảm ơn!</div>
        <div style="margin-bottom: 25px;">
            <i><small>Lưu ý: Email này được tạo từ hệ thống, vui lòng không phản hồi.</small></i>
        </div>

        <div><i><b>Trường Đại Học Nam Cần Thơ</b></i></div>
        <div><i>Địa chỉ: 168 Nguyễn Văn Cừ Nối Dài, An Bình, Ninh Kiều, Cần Thơ</i></div>
        <div><i>Điện thoại: (0292) 3 798 222 - 3 798 668</i></div>
        <div><i>Hotline: 0939 257 838</i></div>

        <!-- Dòng ngẫu nhiên giúp tránh bị Gmail ẩn -->
        <div style="font-size: 10px; color: #ccc; margin-top: 20px;">Mã email: <?= uniqid() ?></div>
    </div>

</div> 