@extends('layouts.layout')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-6">Your Personalized Song</h1>

    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="px-6 py-4">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Song for Order {{ $order->id }}</h2>

            @if ($youtubeUrl)
                {{-- Responsive YouTube Embed --}}
                <div class="relative" style="padding-top: 56.25%;">
                    <iframe 
                        class="absolute top-0 left-0 w-full h-full rounded-md"
                        src="{{ str_replace('watch?v=', 'embed/', $youtubeUrl) }}"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen
                    ></iframe>
                </div>
            @else
                <p class="text-gray-600 text-center">YouTube video is not available yet for this order.</p>
            @endif
        </div>

        <div class="px-6 py-4 bg-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">What's next?</h3>
            {{-- CTA Buttons --}}
            <div class="flex flex-col space-y-3">
                <a href="#" class="inline-block bg-blue-500 hover:bg-blue-600 text-white text-center font-bold py-2 px-4 rounded-md transition duration-300 ease-in-out">
                    Review OR FEEDBACK
                </a>
                <a href="#" class="inline-block bg-green-500 hover:bg-green-600 text-white text-center font-bold py-2 px-4 rounded-md transition duration-300 ease-in-out">
                    Order Your Own Song
                </a>
                {{-- Add more CTAs as needed --}}
            </div>
        </div>

        {{-- Optional: Display lyrics or order details --}}
        {{--
        <div class="px-6 py-4">
             <h3 class="text-lg font-semibold text-gray-800 mb-3">Lyrics</h3>
             <p class="text-gray-700">{{ $order->lyrics ?? 'Lyrics not available.' }}</p>
        </div>
        --}}

    </div>

</div>

{{-- Analytics Script Placeholder --}}
<script>
    // Replace with your actual Google Analytics or Firebase tracking code
    // Example for Google Analytics 4 (GA4):
    /*
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'YOUR_GA4_MEASUREMENT_ID');
    */

    // You might also want to track events, e.g., when the video plays or a CTA button is clicked.
    // Example tracking a CTA button click (requires adding an ID or class to the button):
    /*
    document.getElementById('share-button').addEventListener('click', function() {
        gtag('event', 'share_button_click', {
            'event_category': 'engagement',
            'event_label': 'Share Song',
            'order_id': {{ $order->id }}
        });
    });
    */

</script>

@endsection