<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực mã OTP</title>
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
            background-color: #5548c7;
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
        
        .otp-box {
            text-align: center;
            background-color: #f8f9fa;
            border: 2px dashed #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .otp-code {
            font-family: 'Courier New', monospace;
            font-size: 32px;
            font-weight: bold;
            color: #5548c7;
            letter-spacing: 5px;
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
            <div class="logo-header" style="background-color: #34a853;">
                <h1 class="logo-text">MyOffice</h1>
            </div>

            <div class="container" style="box-shadow: none; border-radius: 0 0 8px 8px; margin-top: 0;">
                <h2 class="greeting">Xin chào <?php echo isset($ho_ten) ? htmlspecialchars($ho_ten) : 'bạn'; ?>,</h2>
            
                <p class="message">
                    Bạn vừa yêu cầu lấy mã OTP để xác thực bảo mật trên hệ thống <b>MyOffice</b>.
                </p>
                <p class="message">
                    Mã xác thực (OTP) của bạn là:
                </p>

                <div class="otp-box">
                    <div class="otp-code"><?php echo isset($otp_code) ? $otp_code : '******'; ?></div>
                    <div class="note">Mã này có hiệu lực trong vòng 5 phút.</div>
                </div>

                <p class="message">
                    Vui lòng nhập mã này vào màn hình xác thực để hoàn tất quá trình. Tuyệt đối không chia sẻ mã này cho bất kỳ ai.
                </p>
                
                <div class="divider"></div>
                
                <div class="footer-text">
                    Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này hoặc liên hệ quản trị viên ngay lập tức.<br>
                    <br>
                    Trân trọng,<br>
                    Đội ngũ MyOffice
                </div>
            </div>
        </div> 
    </div>
</body>
</html>
