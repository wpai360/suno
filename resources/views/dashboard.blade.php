<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>

        <!-- Google Token Status Section -->
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Google API Token Status</h3>
                    
                    <div id="token-status" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Loading token status...</span>
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
                        </div>
                    </div>

                    <div class="mt-4 space-x-2">
                        <button onclick="refreshToken()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Refresh Token
                        </button>
                        <button onclick="clearToken()" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            Clear Token
                        </button>
                        <a href="{{ route('google.auth') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-block">
                            Authenticate with Google
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load token status on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadTokenStatus();
        });

        function loadTokenStatus() {
            fetch('{{ route("google.token.status") }}')
                .then(response => response.json())
                .then(data => {
                    const statusDiv = document.getElementById('token-status');
                    
                    if (data.has_token) {
                        const statusClass = data.is_expired ? 'text-red-600' : 
                                          data.needs_refresh ? 'text-yellow-600' : 'text-green-600';
                        const statusText = data.is_expired ? 'Expired' : 
                                         data.needs_refresh ? 'Needs Refresh' : 'Valid';
                        
                        statusDiv.innerHTML = `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Status: <span class="font-semibold ${statusClass}">${statusText}</span></p>
                                    <p class="text-sm text-gray-600">Expires: <span class="font-semibold">${data.expires_at}</span></p>
                                    <p class="text-sm text-gray-600">Time until expiry: <span class="font-semibold">${Math.floor(data.time_until_expiry / 60)} minutes</span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Has refresh token: <span class="font-semibold">${data.has_refresh_token ? 'Yes' : 'No'}</span></p>
                                    <p class="text-sm text-gray-600">Needs refresh: <span class="font-semibold">${data.needs_refresh ? 'Yes' : 'No'}</span></p>
                                </div>
                            </div>
                        `;
                    } else {
                        statusDiv.innerHTML = `
                            <div class="text-center py-4">
                                <p class="text-gray-600">${data.message}</p>
                                <p class="text-sm text-gray-500 mt-2">Click "Authenticate with Google" to get started.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading token status:', error);
                    document.getElementById('token-status').innerHTML = `
                        <div class="text-center py-4">
                            <p class="text-red-600">Error loading token status</p>
                        </div>
                    `;
                });
        }

        function refreshToken() {
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Refreshing...';
            button.disabled = true;

            fetch('{{ route("google.token.refresh") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Token refreshed successfully!');
                    loadTokenStatus();
                } else {
                    alert('Error refreshing token: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error refreshing token:', error);
                alert('Error refreshing token');
            })
            .finally(() => {
                button.textContent = originalText;
                button.disabled = false;
            });
        }

        function clearToken() {
            if (!confirm('Are you sure you want to clear the Google token? This will require re-authentication.')) {
                return;
            }

            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Clearing...';
            button.disabled = true;

            fetch('{{ route("google.token.clear") }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Token cleared successfully!');
                    loadTokenStatus();
                } else {
                    alert('Error clearing token: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error clearing token:', error);
                alert('Error clearing token');
            })
            .finally(() => {
                button.textContent = originalText;
                button.disabled = false;
            });
        }
    </script>
</x-app-layout>
