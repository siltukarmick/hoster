<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-green-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Tenant Dashboard</h1>
            <div>
                <span class="mr-4">Welcome, {{ Auth::guard('tenant')->user()->name }}</span>
                <form method="POST" action="{{ route('tenant.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-green-700 hover:bg-green-800 px-4 py-2 rounded">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-8 p-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-4">Tenant Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">Name:</p>
                    <p class="font-semibold">{{ Auth::guard('tenant')->user()->name }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Email:</p>
                    <p class="font-semibold">{{ Auth::guard('tenant')->user()->email }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Domain:</p>
                    <p class="font-semibold">{{ Auth::guard('tenant')->user()->domain ?? 'Not set' }}</p>
                </div>
                <div>
                    <p class="text-gray-600">Status:</p>
                    <p class="font-semibold">{{ ucfirst(Auth::guard('tenant')->user()->status) }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>