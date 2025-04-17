<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/qrcode.min.js"></script>

</head>
<body class="bg-gray-100">

<div x-data="{ open: false }" class="flex h-screen">

    <aside class="bg-gray-800 text-white w-64 p-4" :class="{ '-ml-64': !open, 'transition-transform duration-300 ease-in-out': true }">
        <div class="mb-4">
            <button @click="open = !open" class="text-white focus:outline-none">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
        <nav>
            <a href="{{ route('orders.index') }}" class="block py-2 px-4 hover:bg-gray-700">Orders</a>
            <a href="{{ route('orders.create') }}" class="block py-2 px-4 hover:bg-gray-700">Create Order</a>
        </nav>
    </aside>

    <main class="flex-1 p-8">
        @yield('content')
    </main>

</div>

</body>
</html>