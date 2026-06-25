<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f7fa;
            padding: 40px 20px;
        }
        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .email-header {
            background: linear-gradient(135deg, #69326e 0%, #823E87 100%);
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .email-body {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 30px;
        }
        .detail-card {
            background-color: #f8fafc;
            border-left: 4px solid #823E87;
            padding: 20px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 30px;
        }
        .detail-title {
            font-size: 16px;
            font-weight: 600;
            color: #823E87;
            margin-bottom: 15px;
            margin-top: 0;
        }
        .detail-item {
            margin-bottom: 10px;
            font-size: 15px;
            color: #334155;
            display: flex;
            align-items: flex-start;
        }
        .detail-label {
            font-weight: 600;
            width: 90px;
            flex-shrink: 0;
            color: #64748b;
        }
        .detail-value {
            font-weight: 500;
        }
        .action-button-container {
            text-align: center;
            margin: 35px 0;
        }
        .action-button {
            background-color: #823E87;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            display: inline-block;
            transition: background-color 0.3s;
            box-shadow: 0 4px 6px rgba(130, 62, 135, 0.2);
        }
        .action-button:hover {
            background-color: #69326e;
        }
        .footer-message {
            font-size: 15px;
            line-height: 1.6;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 25px;
            margin-top: 30px;
        }
        .email-footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
        }
        .rejection-box {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 25px;
            color: #b91c1c;
        }
        .rejection-title {
            font-weight: 600;
            margin-bottom: 5px;
            margin-top: 0;
        }
        .location-box {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 25px;
            color: #166534;
        }
        .location-title {
            font-weight: 600;
            margin-bottom: 5px;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="email-header">
                <h1>Bhimaswari Counseling</h1>
            </div>
            
            <div class="email-body">
                <div class="greeting">
                    Halo, {{ $name }}!
                </div>
                
                <div class="message">
                    {!! nl2br(e($introLines)) !!}
                </div>

                @if(!empty($rejectionReason))
                    <div class="rejection-box">
                        <p class="rejection-title">Alasan Penolakan:</p>
                        <p style="margin: 0;">{{ $rejectionReason }}</p>
                    </div>
                @endif

                @if(!empty($bookingDetails))
                    <div class="detail-card">
                        <p class="detail-title">Detail Booking</p>
                        @foreach($bookingDetails as $label => $value)
                            <div class="detail-item">
                                <div class="detail-label">{{ $label }}</div>
                                <div class="detail-value">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if(!empty($locationInfo))
                    <div class="location-box">
                        <p class="location-title">Informasi {{ $isOnline ? 'Meeting' : 'Lokasi' }}:</p>
                        <p style="margin: 0; white-space: pre-line;">{{ $locationInfo }}</p>
                    </div>
                @endif

                @if(!empty($actionText) && !empty($actionUrl))
                    <div class="action-button-container">
                        <a href="{{ $actionUrl }}" class="action-button">{{ $actionText }}</a>
                    </div>
                @endif

                @if(!empty($outroLines))
                    <div class="message">
                        {!! nl2br(e($outroLines)) !!}
                    </div>
                @endif

                <div class="footer-message">
                    Jika ada pertanyaan, silakan hubungi tim kami.<br><br>
                    Terima kasih,<br>
                    <strong>Tim Bhimaswari</strong>
                </div>
            </div>
            
            <div class="email-footer">
                &copy; {{ date('Y') }} Bhimaswari Counseling. Hak cipta dilindungi undang-undang.
            </div>
        </div>
    </div>
</body>
</html>
