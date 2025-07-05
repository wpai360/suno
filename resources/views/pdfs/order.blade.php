<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order #{{ $order->id }} - Song</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 10px;
            color: #000;
            font-size: 10px;
            line-height: 1.2;
            width: 80mm; /* Standard thermal printer width */
        }
        
        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .header p {
            margin: 2px 0;
            font-size: 9px;
        }
        
        .info-section {
            margin-bottom: 15px;
        }
        
        .info-row {
            margin-bottom: 3px;
        }
        
        .label {
            font-weight: bold;
            display: inline-block;
            width: 60px;
        }
        
        .value {
            display: inline-block;
        }
        
        .qr-section {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #000;
        }
        
        .qr-code {
            margin: 10px 0;
        }
        
        .qr-code img {
            width: 120px;
            height: 120px;
        }
        
        .qr-instructions {
            font-size: 8px;
            margin: 5px 0;
        }
        
        .song-link {
            font-size: 8px;
            word-break: break-all;
            margin: 5px 0;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .highlight {
            background-color: #f0f0f0;
            padding: 5px;
            margin: 5px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎵 PERSONALIZED SONG</h1>
        <p>AI-Generated Musical Experience</p>
        <p>{{ $orderDate }}</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="label">Order:</span>
            <span class="value">#{{ $order->id }}</span>
        </div>
        <div class="info-row">
            <span class="label">Name:</span>
            <span class="value">{{ $order->customer_name }}</span>
        </div>
        <div class="info-row">
            <span class="label">City:</span>
            <span class="value">{{ $order->city }}</span>
        </div>
        <div class="info-row">
            <span class="label">Group:</span>
            <span class="value">{{ $order->group_size }}</span>
        </div>
        <div class="info-row">
            <span class="label">Total:</span>
            <span class="value">€{{ number_format($order->order_total, 2) }}</span>
        </div>
    </div>

    <div class="divider"></div>

    @if(is_array($items) && count($items) > 0)
    <div class="info-section">
        <div class="highlight">ORDER ITEMS:</div>
        @foreach($items as $item)
            <div class="info-row">• {{ $item }}</div>
        @endforeach
    </div>
    @endif

    <div class="divider"></div>

    <div class="qr-section">
        <div class="qr-instructions">📱 SCAN TO LISTEN</div>
        <div class="qr-code">
            <img src="data:image/png;base64,{{ base64_encode($qrCode) }}" alt="QR Code">
        </div>
        <div class="qr-instructions">Your personalized song is ready!</div>
        <div class="song-link">{{ $songUrl ?? 'https://api.targetgong.com/song/' . $order->id }}</div>
    </div>

    <div class="divider"></div>

    @if($order->lyrics)
    <div class="info-section">
        <div class="highlight">LYRICS PREVIEW:</div>
        <div style="font-size: 8px; font-style: italic; margin: 5px 0;">
            {{ Str::limit($order->lyrics, 200) }}...
        </div>
    </div>
    @endif

    <div class="footer">
        <div>Thank you for choosing</div>
        <div>our AI song service!</div>
        <div style="margin-top: 5px;">Generated: {{ $orderDate }}</div>
        <div>Order #{{ $order->id }}</div>
    </div>
</body>
</html> 