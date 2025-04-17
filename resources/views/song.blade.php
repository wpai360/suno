@extends('layout')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Song for Order {{ $order->id }}</h1>

    <audio controls>
        <source src="{{ $songUrl }}" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <div class="mt-4">
        <a href="{{ $youtubeUrl }}" target="_blank" class="bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600">View on YouTube</a>
        <a href="{{ $gdriveUrl }}" target="_blank" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600">View on Google Drive</a>
    </div>
@endsection