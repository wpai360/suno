@extends('layouts.layout')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Orders</h1>

    {{-- Print Button (visible on screen, hidden when printing) --}}
    <div class="mb-4 print:hidden">
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Print QR Codes
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg shadow-md">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">ID</th>
                    <th class="py-2 px-4 border-b">QR Code</th>
                    </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr class="print-only-row">
                        <td class="py-2 px-4 border-b print-only-id">{{ $order->id }}</td>
                        <td class="py-2 px-4 border-b">
                            {{-- Payment Slip Design --}}
                            <div class="flex flex-col items-center text-center p-4 border rounded-lg shadow-sm bg-white">
                                {{-- Tiger Logo Placeholder --}}
                                <img src="/images/logo.png" alt="Tiger Logo" class="w-20 h-auto mb-4">

                                <h2 class="text-lg font-bold mb-2">LA TUA CANZONE PERSONALIZZATA È PRONTA!</h2>

                                <div class="flex items-center justify-center mb-4">
                                    {{-- Down Arrows and Text --}}
                                    <div class="flex flex-col items-center mx-2">
                                        <span class="text-2xl">↓</span>
                                    </div>
                                    <span class="text-xl font-semibold">SCANSIONA IL QR PER ASCOLTARLA</span>
                                    <div class="flex flex-col items-center mx-2">
                                        <span class="text-2xl">↓</span>
                                    </div>
                                </div>

                                {{-- QR Code and Name Circle --}}
                                <div class="relative mb-4">
                                    <img src="data:image/svg+xml;base64,{{ $order->qrcode }}" alt="QR Code for Order {{ $order->id }}" class="w-40 h-40">
                                    {{-- Name Circle --}}
                                    <!-- <div class="absolute bottom-0 right-0 transform translate-x-1/4 translate-y-1/4 bg-white rounded-full p-2 border-2 border-black text-center text-sm font-bold leading-tight w-20 h-20 flex items-center justify-center">
                                        FATTA PER<br>{{ strtoupper($order->customer_name) }}</div> -->
                                </div>

                                <p class="text-sm mb-2">OGNI PIATTO HA LA SUA COLONNA SONORA.</p>
                                <p class="text-sm font-bold mb-4">LA TUA È GIÀ VIRALE? 😎</p>

                                <p class="text-xs">#TIGERGONGVIBES CONDIVIDILA CON GLI AMICI!</p>
                            </div>
                        </td>
                        </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

{{-- Add print-specific styles --}}
@push('styles')
<style>
    /* Hide elements in print view */
    @media print {
        body > *:not(.print-area) { /* Hide all direct children of body except the print area */
            display: none !important;
        }
        /* Ensure the main content area is visible */
        .print-area {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        /* Optionally hide specific columns in the table */
        .print-only-id {
            display: table-cell !important; /* Ensure ID is visible in print */
        }
        .print-only-row td:not(.print-only-id, .print-only-qr) { /* Hide all TDs except ID and QR code */
             display: none !important;
        }
        /* Add a class to the QR Code TD for specific targeting if needed */
        .print-only-qr {
             display: table-cell !important;
        }

        /* Specific overrides to ensure the table structure prints correctly */
        table, thead, tbody, th, td, tr {
            display: block !important;
            width: auto !important; /* Allow table elements to size naturally */
        }

         tr {
            margin-bottom: 10px; /* Add some space between order rows */
            border-bottom: 1px solid #ccc; /* Add a separator */
            page-break-inside: avoid; /* Avoid breaking a row across pages */
            page-break-after: always !important; /* Force a page break after each row */
        }

        td {
            border: none !important;
            padding: 0 4px !important; /* Adjust padding for print */
        }

        /* Adjust QR code image size if needed for print */
        .print-only-qr img {
            width: 100px !important; /* Example: set a fixed size for thermal print */
            height: auto !important;
        }

        /* Hide the payment slip container, show only the QR code image inside */
         .print-only-qr > div {
             display: block !important; /* Ensure the container for QR code is a block */
             border: none !important; /* Remove borders */
             box-shadow: none !important; /* Remove shadows */
             background-color: transparent !important; /* Remove background */
             padding: 0 !important; /* Remove padding */
             margin: 0 !important;
         }

         .print-only-qr .relative, /* Hide the relative container within the slip */
         .print-only-qr .rounded-full, /* Hide the name circle */
         .print-only-qr h2, /* Hide headings */
         .print-only-qr p, /* Hide paragraphs */
         .print-only-qr div:not(.relative) { /* Hide other divs within the slip not including the relative container holding the QR */
             display: none !important;
         }
         
         /* Explicitly ensure the QR code image is displayed */
         .print-only-qr img {
             display: block !important;
         }
    }

</style>
@endpush