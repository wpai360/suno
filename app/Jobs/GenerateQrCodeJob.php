<?php

namespace App\Jobs;

use App\Models\SongRequest;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GenerateQrCodeJob implements ShouldQueue
{
    protected $songRequest;

    public function __construct(SongRequest $songRequest)
    {
        $this->songRequest = $songRequest;
    }

    public function handle()
    {
        $qrCode = QrCode::size(300)->generate($this->songRequest->landing_page_link);
        $qrCodePath = storage_path('app/public/qrcode.png');
        file_put_contents($qrCodePath, $qrCode);

        $this->songRequest->update(['qr_code_link' => $qrCodePath]);
    }
}
