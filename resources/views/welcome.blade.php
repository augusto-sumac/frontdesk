image.png<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
            @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">{{ config('app.name', 'Laravel') }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
            @if (Route::has('login'))
                    @auth
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/dashboard') }}">Dashboard</a>
                            </li>
                    @else
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">Log in</a>
                            </li>
                        @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">Register</a>
                                </li>
                        @endif
                    @endauth
                    @endif
                </ul>
            </div>
        </div>
                </nav>

    <main class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h1 class="h3 mb-0">Bem-vindo ao Laravel com Bootstrap!</h1>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Este é um projeto Laravel configurado com SQLite e Bootstrap.</p>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">SQLite</h5>
                                        <p class="card-text">Banco de dados configurado e funcionando.</p>
                                        <span class="badge bg-success">Ativo</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Bootstrap</h5>
                                        <p class="card-text">Framework CSS instalado e configurado.</p>
                                        <span class="badge bg-primary">Ativo</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5>Próximos passos:</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Criar modelos e migrações
                        </li>
                                <li class="list-group-item">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Implementar autenticação
                        </li>
                                <li class="list-group-item">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Desenvolver funcionalidades específicas
                        </li>
                    </ul>
                        </div>

                        <div class="mt-4">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                Abrir Modal de Exemplo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Exemplo -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Modal de Exemplo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Este é um exemplo de modal do Bootstrap funcionando corretamente!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary">Salvar</button>
                </div>
            </div>
        </div>
        </div>

    <footer class="bg-light text-center text-muted py-4 mt-5">
        <div class="container">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. Todos os direitos reservados.</p>
        </div>
    </footer>
    </body>
</html>
