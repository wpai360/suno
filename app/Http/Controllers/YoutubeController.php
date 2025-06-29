<?php

namespace App\Http\Controllers;

use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YoutubeController extends Controller
{
    protected $youtubeService;

    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    public function uploadVideo(Request $request)
    {
        try {
            $videoPath = public_path('output.mp4');
            
            if (!file_exists($videoPath)) {
                return response()->json(['error' => 'Video file not found'], 404);
            }

            $videoId = $this->youtubeService->uploadVideo(
                $videoPath,
                "My Video Title",
                "Video Description"
            );

            return response()->json([
                'success' => true,
                'video_id' => $videoId,
                'message' => 'Video uploaded successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('YouTube upload error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to upload video: ' . $e->getMessage()
            ], 500);
        }
    }
} 