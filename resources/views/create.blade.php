@extends('layout')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Create Order</h1>

    <form action="{{ route('orders.store') }}" method="POST" class="space-y-4">
        @csrf

        <div class="space-y-2">
            <label for="order_data" class="block font-medium">Order Data (JSON)</label>
            <textarea name="order_data" id="order_data" class="w-full border rounded-md p-2"></textarea>
        </div>

        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">Create Order</button>
    </form>
@endsection