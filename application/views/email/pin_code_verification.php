<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực mã PIN</title>
    <style>
        /* Reset and basics */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333333;
            -webkit-font-smoothing: antialiased;
        }
        
        /* Wrappers */
        .wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 40px 0;
        }
        
        /* Logo Header */
        .logo-header {
            text-align: center;
            margin-bottom: 0;
            background-color: #34a853;
            padding: 20px 0;
            border-radius: 8px 8px 0 0;
        }
        
        .logo-text {
            color: #ffffff;
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
            font-family: Arial, sans-serif;
        }
        
        /* Main Card */
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
            padding: 40px;
        }
        
        /* Content */
        .greeting {
            font-size: 20px;
            color: #333;
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message {
            font-size: 15px;
            line-height: 1.6;
            color: #555555;
            margin-bottom: 20px;
        }
        
        /* Button */
        .btn-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .btn {
            background-color: #34a853;
            color: #ffffff !important;
            padding: 12px 30px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
            transition: background-color 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn:hover {
            background-color: #2d8e47;
        }
        
        .note {
            font-size: 13px;
            color: #888;
            margin-top: 10px;
            font-style: italic;
        }
        
        /* Divider */
        .divider {
            border-top: 1px solid #eeeeee;
            margin: 30px 0;
        }
        
        /* Footer */
        .footer-text {
            font-size: 13px;
            color: #777;
            line-height: 1.5;
        }
        .footer-link {
            color: #0056b3;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Main Container with internal header -->
        <div style="max-width: 600px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;">
            <!-- Header -->
            <div class="logo-header">
                <h1 class="logo-text">MyOffice</h1>
            </div>

            <div class="container" style="box-shadow: none; border-radius: 0 0 8px 8px; margin-top: 0;background:#eee">
                <h2 class="greeting">Chào <?php echo isset($data['ql_nguoi_dung_ho_ten']) ? htmlspecialchars($data['ql_nguoi_dung_ho_ten']) : 'bạn'; ?>,</h2>
            
            <p class="message">
                Cảm ơn bạn đã đăng ký mã PIN trên hệ thống DNC MyOffice! Trước khi bắt đầu, 
                chúng tôi cần xác nhận đây chính là bạn. Vui lòng nhấp vào nút bên dưới để xác 
                minh địa chỉ email của bạn:
            </p>

            <p class="message">
                Thời gian xác thực mã PIN là <b style="color: red; font-weight: bold;">5 phút</b>. Nếu bạn không xác thực mã PIN trong thời gian này, mã PIN sẽ hết hạn và cần phải gửi yêu cầu xác thực lại.
            </p>
            
            <div class="btn-container">
                <a href="<?php echo isset($link_xac_thuc) ? $link_xac_thuc : '#'; ?>" class="btn">Xác minh mã PIN</a>
            </div>
             
            <div class="divider"></div>
            
            <div class="footer-text">
                Cần trợ giúp? <a href="https://zalo.me/g/vqpvny987" class="footer-link">Tham gia nhóm Zalo hỗ trợ</a> hoặc liên lạc trực tiếp với chúng tôi.<br>
                <br>
                <strong>Thông tin liên hệ:</strong><br>
                Phòng I1-04, Khu I, Trường Đại học Nam Cần Thơ<br>
                Email: <a href="mailto:ttphanmem@nctu.edu.vn" class="footer-link">ttphanmem@nctu.edu.vn</a><br>
                Số điện thoại: <a href="tel:0292385136" class="footer-link">0292 38 51 136</a>
            </div>
        </div>
        </div> <!-- End Main Container -->
    </div>
</body>
</html>
