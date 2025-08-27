<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HotelSync Pro')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 280px;
            background: white;
            border-right: 1px solid #e5e7eb;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
            z-index: 1030;
            transition: transform 0.3s ease;
        }
        
        .sidebar .nav-link {
            color: #6b7280;
            border-radius: 8px;
            margin: 4px 0;
            padding: 12px 16px;
            transition: all 0.2s ease;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .sidebar .nav-link:hover {
            background-color: #f3f4f6;
            color: #374151;
        }
        
        .sidebar .nav-link.active {
            background-color: #3b82f6;
            color: white;
        }
        
        .sidebar .nav-link.active i {
            color: white;
        }
        
        .sidebar .nav-link i {
            color: #9ca3af;
            width: 20px;
            text-align: center;
            margin-right: 12px;
            font-size: 16px;
        }
        
        .sidebar .nav-link.active i {
            color: white;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background-color: #f8fafc;
            transition: margin-left 0.3s ease;
        }
        
        /* Navbar */
        .navbar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .navbar-brand img {
            height: 28px;
            width: auto;
        }
        
        /* User Avatar */
        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        
        /* Card Styles */
        .border-left-primary {
            border-left: 4px solid #3b82f6 !important;
        }
        
        .border-left-success {
            border-left: 4px solid #10b981 !important;
        }
        
        .border-left-info {
            border-left: 4px solid #06b6d4 !important;
        }
        
        .border-left-warning {
            border-left: 4px solid #f59e0b !important;
        }
        
        .border-left-danger {
            border-left: 4px solid #ef4444 !important;
        }
        
        .text-gray-800 {
            color: #1f2937 !important;
        }
        
        .text-gray-300 {
            color: #d1d5db !important;
        }
        
        .text-xs {
            font-size: 0.75rem !important;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.sidebar-open {
                margin-left: 280px;
            }
        }
        
        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1025;
            display: none;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        /* Toast Container */
        .toast-container {
            z-index: 1055;
        }
        
        /* Custom Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }
        
        /* Card Hover Effects */
        .card {
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Table Styles */
        .table-sm th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #6b7280;
        }
        
        /* List Group Styles */
        .list-group-item-action:hover {
            background-color: #f8fafc;
        }
        
        /* Badge Styles */
        .badge {
            font-weight: 500;
        }
    </style>
    
    @yield('styles')
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="d-flex flex-column h-100">
            <!-- Logo -->
            <div class="p-3 border-bottom">
                <div class="d-flex align-items-center">
                    <img src="/img/logo-frontdesk.svg" alt="FrontDesk" class="navbar-brand mb-0">
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="flex-grow-1 p-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fas fa-th-large"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('properties.*') ? 'active' : '' }}" href="{{ route('properties.index') }}">
                            <i class="fas fa-home"></i>
                            Propriedades
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}" href="{{ route('bookings.index') }}">
                            <i class="fas fa-bed"></i>
                            Reservas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('messages.*') ? 'active' : '' }}" href="{{ route('messages.index') }}">
                            <i class="fas fa-comment"></i>
                            Mensagens
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}" href="{{ route('calendar.index') }}">
                            <i class="fas fa-calendar"></i>
                            Calendário
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                            <i class="fas fa-chart-bar"></i>
                            Relatórios
                        </a>
                    </li>
                    
                    @if(auth()->user() && auth()->user()->role === 'admin')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-users-cog"></i>
                                Administração
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
            
            <!-- User Info -->
            <div class="p-3 border-top">
                <div class="d-flex align-items-center">
                    <div class="user-avatar me-3">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">{{ auth()->user()->name ?? 'Usuário' }}</div>
                        <small class="text-muted">{{ auth()->user()->email ?? 'usuario@exemplo.com' }}</small>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <!-- Sidebar Toggle -->
                <button class="btn btn-link text-muted d-lg-none" type="button" onclick="toggleSidebar()">
                    <i class="fas fa-bars fs-5"></i>
                </button>
                
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        @yield('breadcrumb')
                    </ol>
                </nav>
                
                <!-- Right Side -->
                <div class="navbar-nav ms-auto">
                    <!-- User Dropdown -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar me-2">
                                {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                            </div>
                            <span class="d-none d-md-block">{{ auth()->user()->name ?? 'Usuário' }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('profile.index') }}"><i class="fas fa-user me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configurações</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()"><i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="p-4">
    @yield('content')
        </main>
    </div>

    <!-- SweetAlert2 para notificações -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery (necessário para alguns componentes do Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
        // Inicializar dropdowns do Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar todos os dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
            
            // Garantir que o dropdown do usuário funcione
            const userDropdown = document.querySelector('.nav-item.dropdown .dropdown-toggle');
            if (userDropdown) {
                userDropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dropdown = new bootstrap.Dropdown(this);
                    dropdown.toggle();
                });
            }
        });
        
        // Sidebar toggle for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('sidebar-open');
            overlay.classList.toggle('show');
        }
        
        // Close sidebar when clicking overlay
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            toggleSidebar();
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('[onclick="toggleSidebar()"]');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                    document.getElementById('mainContent').classList.remove('sidebar-open');
                    document.getElementById('sidebarOverlay').classList.remove('show');
                }
            }
        });
        
        // Função de logout
        function logout() {
            // Criar um formulário para fazer logout
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("logout") }}';
            
            // Adicionar token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken.getAttribute('content');
                form.appendChild(csrfInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html> 