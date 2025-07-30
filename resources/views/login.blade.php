<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - Inventario</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=1.0.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-boxes"></i>
            </div>
            <h2>Bienvenido</h2>
            <p>Accede a tu panel de inventario</p>
        </div>

        <!-- Mostrar errores -->
        @if ($errors->any())
            <div class="login-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="login-form">
            @csrf

            <div class="login-form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Nombre de Usuario
                </label>
                <input type="text" id="username" name="username" class="form-control"
                    placeholder="Ingresa tu usuario" value="{{ old('username') }}" required autocomplete="username">
            </div>

            <div class="login-form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Contraseña
                </label>
                <input type="password" id="password" name="password" class="form-control"
                    placeholder="Ingresa tu contraseña" required autocomplete="current-password">
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Recordar sesión</label>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Iniciar Sesión
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; 2025 Sistema de Inventario. Todos los derechos reservados.</p>
        </div>
    </div>

    <script>
        // Agregar efecto de loading al botón
        document.querySelector('.login-form').addEventListener('submit', function() {
            const btn = document.querySelector('.login-btn');
            btn.classList.add('loading');
            btn.disabled = true;
        });

        // Auto-focus en el primer campo
        document.getElementById('username').focus();
    </script>
</body>

</html>
