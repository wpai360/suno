<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::all();
        return view('index', compact('orders'));
    }

    public function create()
    {
        return view('create');
    }

    public function store(Request $request)
    {
        $order = Order::create(['order_data' => json_decode($request->input('order_data'), true)]);
        return redirect()->route('orders.index');
    }

    public function showSong(Order $order)
    {
        // Replace with your actual logic to get the song URL, youtube, and gdrive links
        $songUrl = '/path/to/song/' . $order->id . '.mp3';
        $youtubeUrl = 'https://youtube.com/yourvideo/' . $order->id;
        $gdriveUrl = 'https://drive.google.com/yourfile/' . $order->id;

        return view('song', compact('order', 'songUrl', 'youtubeUrl', 'gdriveUrl'));
    }
}