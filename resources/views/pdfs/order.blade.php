<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order #{{ $order->id }} - Personalized Song</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #666;
            margin: 5px 0;
        }
        .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .customer-details, .order-details {
            flex: 1;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h2 {
            color: #2563eb;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }
        .value {
            flex: 1;
        }
        .items-list {
            list-style: none;
            padding: 0;
        }
        .items-list li {
            padding: 5px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            border: 2px dashed #2563eb;
            border-radius: 10px;
        }
        .qr-code {
            margin: 20px 0;
        }
        .qr-code img {
            border: 1px solid #ddd;
            padding: 10px;
        }
        .links-section {
            margin-top: 30px;
        }
        .link-item {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8fafc;
            border-left: 4px solid #2563eb;
        }
        .link-label {
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .link-url {
            color: #666;
            word-break: break-all;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        .status-completed {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-failed {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎵 Personalized Song Order</h1>
        <p>Your unique musical experience</p>
        <p>Generated on: {{ $orderDate }}</p>
    </div>

    <div class="order-info">
        <div class="customer-details">
            <div class="section">
                <h2>Customer Information</h2>
                <div class="info-row">
                    <span class="label">Name:</span>
                    <span class="value">{{ $order->customer_name }}</span>
                </div>
                <div class="info-row">
                    <span class="label">City:</span>
                    <span class="value">{{ $order->city }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Group Size:</span>
                    <span class="value">{{ $order->group_size }}</span>
                </div>
            </div>
        </div>

        <div class="order-details">
            <div class="section">
                <h2>Order Details</h2>
                <div class="info-row">
                    <span class="label">Order ID:</span>
                    <span class="value">#{{ $order->id }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Total:</span>
                    <span class="value">€{{ number_format($order->order_total, 2) }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Status:</span>
                    <span class="value">
                        <span class="status-badge status-{{ $order->status }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Order Items</h2>
        <ul class="items-list">
            @if(is_array($items) && count($items) > 0)
                @foreach($items as $item)
                    <li>• {{ $item }}</li>
                @endforeach
            @else
                <li>No items specified</li>
            @endif
        </ul>
    </div>

    @if($order->lyrics)
    <div class="section">
        <h2>Generated Lyrics</h2>
        <div style="background-color: #f8fafc; padding: 15px; border-radius: 5px; font-style: italic;">
            {{ $order->lyrics }}
        </div>
    </div>
    @endif

    <div class="qr-section">
        <h2>📱 Scan to Listen to Your Song</h2>
        <p>Use your phone's camera to scan this QR code and access your personalized song</p>
        <div class="qr-code">
            <img src="data:image/png;base64,{{ base64_encode($qrCode) }}" alt="QR Code">
        </div>
        <p><strong>Direct Link:</strong> {{ route('song.show', $order->id) }}</p>
    </div>

    <div class="links-section">
        <h2>Access Your Song</h2>
        
        @if($youtubeUrl)
        <div class="link-item">
            <div class="link-label">🎬 YouTube Video</div>
            <div class="link-url">{{ $youtubeUrl }}</div>
        </div>
        @endif

        @if($driveUrl)
        <div class="link-item">
            <div class="link-label">☁️ Google Drive</div>
            <div class="link-url">{{ $driveUrl }}</div>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>Thank you for choosing our AI-powered personalized song service!</p>
        <p>This is a unique musical experience created just for you based on your order.</p>
        <p>Generated on {{ $orderDate }} | Order #{{ $order->id }}</p>
    </div>
</body>
</html> 