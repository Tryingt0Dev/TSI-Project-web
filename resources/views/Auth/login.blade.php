<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Biblioteca – Iniciar Sesión</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Animación suave */
        .fade-in {
            animation: fadein .7s ease-out forwards;
            opacity: 0;
            transform: translateY(12px);
        }
        @keyframes fadein {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 via-white to-blue-100 flex items-center justify-center min-h-screen">

    <div class="fade-in bg-white/80 backdrop-blur-xl shadow-xl rounded-2xl p-10 w-96 border border-white/40">

        <!-- Ícono tipo libro -->
        <div class="flex justify-center mb-6">
            <svg width="65" height="65" viewBox="0 0 24 24" fill="none" class="text-blue-600">
                <path stroke="currentColor" stroke-width="1.5" 
                    d="M6 4h9a4 4 0 0 1 4 4v10a2 2 0 0 1-2 2h-9a4 4 0 0 1-4-4V6a2 2 0 0 1 2-2z"/>
                <path stroke="currentColor" stroke-width="1.5" 
                    d="M6 8h11"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold mb-6 text-center text-gray-700">Acceso Biblioteca</h1>

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-1">Email</label>
                <input 
                    type="email" 
                    name="email" 
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 
                           focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition"
                    required
                >
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-1">Contraseña</label>
                <input 
                    type="password" 
                    name="password" 
                    class="w-full px-4 py-2 rounded-lg border border-gray-300
                           focus:ring-2 focus:ring-blue-400 focus:border-blue-400 transition"
                    required
                >
            </div>

            <button 
                type="submit" 
                class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-700 
                       transition active:scale-95"
            >
                Entrar
            </button>
        </form>
    </div>
    @stack('scripts')
</body>
</html>
