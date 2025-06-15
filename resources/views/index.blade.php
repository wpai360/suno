@extends('layouts.layout')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Orders</h1>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-lg shadow-md">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">ID</th>
                    <th class="py-2 px-4 border-b">Order Data</th>
                    <th class="py-2 px-4 border-b">QR Code</th>
                    </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td class="py-2 px-4 border-b">{{ $order->id }}</td>
                        <td class="py-2 px-4 border-b">{{ json_encode($order->toArray()) }}</td>
                        <td class="py-2 px-4 border-b">
                            <div id="qrcode-{{ $order->id }}"></div>
                            <script>
                                QRCode.toCanvas(document.getElementById('qrcode-{{ $order->id }}'), '{{ route('song.show', $order->id) }}', function (error) {
                                    if (error) console.error(error)
                                    console.log('success!');
                                })
                            </script>
                        </td>
                        </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection