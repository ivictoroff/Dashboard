<?php
session_start(); // Inicia a sessão PHP
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['message' => 'Usuário não autenticado. Favor fazer login novamente.']);
    header('Location: index.php');
    exit();
}
$criadoPorId = $_SESSION['usuario_id'];
$usuarioNome = $_SESSION['nome'];
$usuarioDivisao = $_SESSION['divisao'] ?? ''; // ou busque o nome da divisão pelo id
$perfil = $_SESSION['perfil'] ?? '';
if (isset($_SESSION['divisao_id'])) {
    require_once 'db.php';
    $stmt = $conn->prepare("SELECT nome FROM divisao WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['divisao_id']);
    $stmt->execute();
    $stmt->bind_result($usuarioDivisao);
    $stmt->fetch();
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - COLOG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" href="colog.png" type="image/x-icon">
    <style>
        .tab-active {
            border-bottom: 2px solid #3b82f6;
            color: #3b82f6;
        }
        .filter-active {
            background-color: #3b82f6;
            color: white;
        }
        .chefia-filter-active {
            background-color: #059669;
            color: white;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            max-height: 85vh;
            overflow-y: auto;
        }
        .modal-large {
            max-width: 900px;
            height: 95vh;
            width: 95%;
        }
        
        /* Responsividade para modais */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 2% auto;
                max-height: 95vh;
                padding: 10px;
                border-radius: 4px;
            }
            
            .modal-large {
                width: 98%;
                margin: 1% auto;
                height: 98vh;
                padding: 10px;
            }
        }
        .modal-actions {
            max-height: 400px;
            overflow-y: auto;
        }
        .action-row {
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 0;
        }
        .action-row:last-child {
            border-bottom: none;
        }
        
        /* Estilos para o gráfico */
        #assuntosChart {
            max-width: 100%;
            height: auto;
            min-height: 250px;
        }
        
        /* Container do gráfico de barras */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
                max-width: 100%;
            }
        }
        
        /* Responsividade para mobile */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                height: 100vh;
                z-index: 50;
                transition: left 0.3s ease;
                width: 280px;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            }
            
            .sidebar.open {
                left: 0;
            }
            
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 40;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .mobile-header {
                padding-left: 8px;
                padding-right: 8px;
            }
            
            /* Estilos específicos para tabelas em mobile */
            table {
                font-size: 0.7rem;
                min-width: 100%;
            }
            
            table th,
            table td {
                padding: 0.25rem 0.125rem !important;
                white-space: nowrap;
                min-width: 50px;
                font-size: 0.6rem;
            }
            
            /* Botões de ação em mobile */
            .btn-mobile {
                padding: 0.25rem 0.5rem;
                font-size: 0.625rem;
                border-radius: 4px;
            }
            
            /* Melhorar overflow das tabelas */
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                max-width: 100vw;
            }
            
            /* Estilos para campos de pesquisa retráteis */
            .search-arrow-transition {
                transition: transform 0.2s ease-in-out;
            }
            
            /* Estilos para paginação - usando principalmente Tailwind */
            .pagination-container {
                /* Container principal com fundo azul claro */
            }
            
            .pagination-btn {
                /* Botões de paginação customizados */
                transition: all 0.2s ease-in-out;
            }
            
            .pagination-btn:hover:not(:disabled) {
                transform: translateY(-1px);
            }
            
            .pagination-btn.active {
                box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
            }
            
            .pagination-btn:disabled {
                cursor: not-allowed;
                opacity: 0.5;
            }
        }
    
        @media (max-width: 640px) {
            .grid-responsive {
                grid-template-columns: 1fr;
            }
            
            .flex-responsive {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }
            
            .text-responsive {
                font-size: 0.9rem;
            }
            
            .text-responsive-sm {
                font-size: 0.8rem;
            }
            
            /* Estilos específicos para o resumo em mobile */
            .resumo-mobile {
                padding: 0.75rem 0.5rem;
            }
            
            .resumo-mobile .grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .resumo-mobile h3 {
                font-size: 1rem;
            }
            
            .resumo-mobile h4 {
                font-size: 0.85rem;
            }
            
            /* Melhorar botões em mobile */
            .filter-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 0.25rem;
            }
            
            .filter-buttons button {
                flex: 1;
                min-width: auto;
                font-size: 0.7rem;
                padding: 0.375rem 0.5rem;
            }
            
            /* Header responsivo */
            .mobile-header h2 {
                font-size: 1rem;
                line-height: 1.2;
            }
            
            .mobile-header p {
                font-size: 0.75rem;
            }
            
            /* Inputs mais compactos */
            input[type="date"],
            input[type="text"] {
                font-size: 0.8rem;
                padding: 0.25rem 0.5rem;
            }
        }
        
        /* Melhorias adicionais para muito pequenas telas */
        @media (max-width: 480px) {
            .sidebar {
                width: 100vw;
            }
            
            .mobile-header {
                padding: 0.5rem;
                padding-left: 8px;
            }
            
            .main-content {
                padding: 0;
            }
            
            .flex-1.p-4.md\\:p-6.overflow-y-auto {
                padding: 0.5rem !important;
            }
            
            table th,
            table td {
                padding: 0.125rem !important;
                font-size: 0.55rem;
            }
            
            .chart-container {
                height: 200px;
            }
            
            /* Botões muito pequenos para telas tiny */
            .filter-buttons button {
                font-size: 0.65rem;
                padding: 0.25rem 0.375rem;
            }
            
            /* Cards do resumo mais compactos */
            .bg-blue-50.p-6.rounded-lg,
            .bg-yellow-50.p-6.rounded-lg {
                padding: 0.75rem !important;
            }
            
            .bg-white.p-4.rounded-lg.shadow-sm {
                padding: 0.5rem !important;
            }
        }
        
        /* Utilitários responsivos adicionais */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .flex-wrap-mobile {
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .flex-wrap-mobile {
                flex-direction: column;
            }
            
            .w-full-mobile {
                width: 100% !important;
            }
        }
        
        /* Scroll suave para mobile */
        * {
            -webkit-overflow-scrolling: touch;
        }
        
        /* Melhorar o toque em mobile */
        button, a, input, select, textarea {
            touch-action: manipulation;
        }

        /* Classes para esconder colunas em mobile */
        @media (max-width: 768px) {
            .hide-mobile {
                display: none !important;
            }
        }

        @media (max-width: 640px) {
            .hide-mobile-sm {
                display: none !important;
            }
        }
        
        /* Controle de tamanho da coluna assunto */
        .assunto-column {
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        @media (max-width: 768px) {
            .assunto-column {
                max-width: 120px;
                min-width: 100px;
                white-space: nowrap;
            }
        }
        
        /* Estilo para o botão de menu mobile */
        #menuBtn {
            transition: all 0.2s ease;
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Garantir que o botão só apareça no mobile */
        @media (min-width: 768px) {
            #menuBtn {
                display: none !important;
            }
        }
        
        #menuBtn:hover {
            transform: scale(1.02);
            background-color: rgb(219 234 254);
            border-color: rgb(147 197 253);
        }
        
        #menuBtn:active {
            transform: scale(0.98);
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding-top: 0;
            }
            
            .mobile-header {
                padding-left: 8px !important;
                padding-right: 8px !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Overlay para mobile -->
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>
    
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar w-64 bg-blue-600 text-white flex flex-col">
            <div class="p-4 md:p-6">
                <h1 class="text-lg md:text-xl font-bold">Painel Administrativo<br>COLOG</h1>
            </div>
            <nav class="flex-1 px-4">
                <ul class="space-y-2">
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg bg-blue-700" onclick="switchTab('resumo')">
                            <span class="mr-3">📊</span>
                            <span>Resumo</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg hover:bg-blue-700" onclick="switchTab('todos')">
                            <span class="mr-3">📋</span>
                            <span>Todos os assuntos</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg hover:bg-blue-700" onclick="switchTab('usuarios')">
                            <span class="mr-3">👥</span>
                            <span>Usuários</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg hover:bg-blue-700" onclick="switchTab('om')">
                            <span class="mr-3">🏢</span>
                            <span>OM</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b p-2 md:p-4 flex justify-between items-center mobile-header relative">
                <!-- Menu Button for Mobile -->
                <button id="menuBtn" class="md:hidden p-2 rounded-lg hover:bg-blue-50 border border-blue-200 bg-blue-100 shadow-sm mr-3 z-10" onclick="toggleSidebar()">
                    <span class="text-blue-700 text-lg font-bold">☰</span>
                </button>
                
                <div class="flex-1 min-w-0">
                    <h2 class="text-lg md:text-2xl font-bold text-gray-800 text-responsive truncate" id="currentTabTitle">Resumo dos Assuntos</h2>
                    <p class="text-gray-600 text-xs md:text-base text-responsive-sm" id="totalCount">0 registros encontrados</p>
                </div>
                <div class="flex items-center space-x-1 md:space-x-4 ml-2">
                    <!-- User Info -->
                    <div class="hidden lg:flex items-center space-x-2 px-2 py-1 md:px-3 md:py-1 bg-gray-100 rounded-lg">
                        <span class="text-gray-700 font-medium text-xs md:text-sm" id="userInfo">Usuário - Divisão</span>
                    </div>
                    <div class="relative">
                        <button id="configBtn" class="p-1 md:p-2 rounded-lg hover:bg-gray-100">
                            <span class="text-gray-600 text-sm md:text-base">⚙️</span>
                        </button>
                        <div id="configMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-20">
                            <button onclick="openContaModal()" class="w-full text-left px-4 py-2 hover:bg-gray-100 rounded-lg block text-sm">
                                Conta
                            </button>
                            <a href="logout.php" id="logoffBtn" class="w-full text-left px-4 py-2 hover:bg-gray-100 rounded-lg block text-sm">
                                Sair
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 p-2 md:p-6 overflow-y-auto"
                 style="padding-left: 0.5rem; padding-right: 0.5rem;">
                <div class="max-w-full"
                     style="padding-left: 0.25rem; padding-right: 0.25rem;">
                <!-- Resumo Tab -->
                <div id="resumoTab" class="tab-content">
                    <div class="bg-white rounded-lg shadow p-3 md:p-6 mb-4 md:mb-6 resumo-mobile">
                        <h3 class="text-base md:text-lg font-semibold mb-4 md:mb-6">Resumo dos Assuntos</h3>
                        
                        <!-- Totais Gerais -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4 mb-6 md:mb-8" style="display:none;">
                            <div class="bg-blue-50 p-3 md:p-4 rounded-lg border-l-4 border-blue-500">
                                <h4 class="text-xs md:text-sm text-gray-600 mb-1">Total de Assuntos</h4>
                                <p class="text-lg md:text-2xl font-bold text-blue-600" id="totalAssuntos">0</p>
                            </div>
                            <div class="bg-red-50 p-3 md:p-4 rounded-lg border-l-4 border-red-500">
                                <h4 class="text-xs md:text-sm text-gray-600 mb-1">Total de Críticos</h4>
                                <p class="text-lg md:text-2xl font-bold text-red-600" id="totalCriticos">0</p>
                            </div>
                            <div class="bg-green-50 p-3 md:p-4 rounded-lg border-l-4 border-green-500">
                                <h4 class="text-xs md:text-sm text-gray-600 mb-1">Total de Ordinários</h4>
                                <p class="text-lg md:text-2xl font-bold text-green-600" id="totalOrdinarios">0</p>
                            </div>
                        </div>

                        <!-- Assuntos Pendentes e Concluídos -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                            <!-- Assuntos Pendentes -->
                            <div class="bg-yellow-50 p-3 md:p-6 rounded-lg border-l-4 border-yellow-500">
                                <h4 class="text-sm md:text-lg font-semibold text-yellow-800 mb-3 md:mb-4 flex items-center">
                                    <span class="mr-2">⏳</span>
                                    Assuntos Pendentes
                                </h4>
                                <div class="space-y-3 md:space-y-4">
                                    <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs md:text-sm font-medium text-gray-700">Críticos</span>
                                            <span class="text-lg md:text-xl font-bold text-red-600" id="pendentesCriticos">0</span>
                                        </div>
                                        <div class="mt-2 bg-red-200 rounded-full h-2">
                                            <div class="bg-red-500 h-2 rounded-full transition-all duration-300" id="pendentesCriticosBar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs md:text-sm font-medium text-gray-700">Ordinários</span>
                                            <span class="text-lg md:text-xl font-bold text-green-600" id="pendentesOrdinarios">0</span>
                                        </div>
                                        <div class="mt-2 bg-green-200 rounded-full h-2">
                                            <div class="bg-green-500 h-2 rounded-full transition-all duration-300" id="pendentesOrdinariosBar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div class="border-t pt-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm md:text-lg font-semibold text-yellow-800">Total Pendentes</span>
                                            <span class="text-xl md:text-2xl font-bold text-yellow-800" id="totalPendentes">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assuntos Concluídos -->
                            <div class="bg-blue-50 p-3 md:p-6 rounded-lg border-l-4 border-blue-500">
                                <h4 class="text-sm md:text-lg font-semibold text-blue-800 mb-3 md:mb-4 flex items-center">
                                    <span class="mr-2">✅</span>
                                    Assuntos Concluídos
                                </h4>
                                <div class="space-y-3 md:space-y-4">
                                    <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs md:text-sm font-medium text-gray-700">Críticos</span>
                                            <span class="text-lg md:text-xl font-bold text-red-600" id="concluidosCriticos">0</span>
                                        </div>
                                        <div class="mt-2 bg-red-200 rounded-full h-2">
                                            <div class="bg-red-500 h-2 rounded-full transition-all duration-300" id="concluidosCriticosBar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs md:text-sm font-medium text-gray-700">Ordinários</span>
                                            <span class="text-lg md:text-xl font-bold text-green-600" id="concluidosOrdinarios">0</span>
                                        </div>
                                        <div class="mt-2 bg-green-200 rounded-full h-2">
                                            <div class="bg-green-500 h-2 rounded-full transition-all duration-300" id="concluidosOrdinariosBar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div class="border-t pt-3">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm md:text-lg font-semibold text-green-800">Total Concluídos</span>
                                            <span class="text-xl md:text-2xl font-bold text-green-800" id="totalConcluidos">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gráfico dos Assuntos -->
                    <div class="bg-white rounded-lg shadow p-3 md:p-6">
                        <h3 class="text-base md:text-lg font-semibold mb-3 md:mb-4">Distribuição dos Assuntos</h3>
                        <!-- Gráfico de Barras -->
                        <div class="flex justify-center">
                            <div class="chart-container">
                                <canvas id="assuntosChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Todos os assuntos Tab -->
                <div id="todosTab" class="tab-content hidden">
                    <!-- Filtro por chefia -->
                    <div id="chefiaFilterSection" class="bg-white rounded-lg shadow p-2 md:p-6 mb-4 md:mb-6" style="display: none;">
                        <div class="flex flex-col gap-3 md:gap-4">
                            <!-- Primeira Linha - Filtros de chefia (apenas para Auditor COLOG - perfil 3) -->
                            <div id="chefiaFilterContainer" class="flex flex-wrap gap-1 md:gap-2" style="display: none;">
                                <span class="text-xs md:text-sm text-gray-600 font-medium flex items-center mr-1 md:mr-2 w-full sm:w-auto mb-1 sm:mb-0">Filtrar por OM/Chefia:</span>
                                <button id="chefiaFilterAll" class="px-2 py-1 md:px-4 md:py-2 text-xs md:text-sm rounded-lg border chefia-filter-active flex-1 sm:flex-none" onclick="filterByChefiaBtn('')">
                                    Todas
                                </button>
                                <div id="chefiaBtnContainer" class="flex flex-wrap gap-1 md:gap-2 flex-1">
                                    <!-- Botões de chefia serão inseridos aqui dinamicamente -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtros -->
                    <div class="bg-white rounded-lg shadow p-4 md:p-6 mb-6">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="flex flex-wrap gap-2">
                                <button class="px-3 py-2 text-xs md:px-4 md:py-2 md:text-sm rounded-lg border filter-active" onclick="filterAssuntos('pendentes')">
                                    Assuntos Pendentes
                                </button>
                                <button class="px-2 py-1 md:px-4 md:py-2 text-xs md:text-sm rounded-lg border hover:bg-gray-50 flex-1 sm:flex-none" onclick="filterAssuntos('todos')">
                                    Todos os Assuntos
                                </button>
                                <button class="px-2 py-1 md:px-4 md:py-2 text-xs md:text-sm rounded-lg border hover:bg-gray-50 flex-1 sm:flex-none" onclick="filterAssuntos('criticos')">
                                   Assuntos Críticos
                                </button>
                                <button class="px-2 py-1 md:px-4 md:py-2 text-xs md:text-sm rounded-lg border hover:bg-gray-50 flex-1 sm:flex-none" onclick="filterAssuntos('concluidos')">
                                    Assuntos Concluídos
                                </button>
                            </div>
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full sm:w-auto">
                                    <div class="flex items-center gap-2 w-full sm:w-auto">
                                        <label class="text-xs md:text-sm text-gray-600 whitespace-nowrap">De:</label>
                                        <input type="date" id="dataInicio" class="px-2 py-1 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm flex-1 sm:w-auto">
                                    </div>
                                    <div class="flex items-center gap-2 w-full sm:w-auto">
                                        <label class="text-xs md:text-sm text-gray-600 whitespace-nowrap">Até:</label>
                                        <input type="date" id="dataFim" class="px-2 py-1 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm flex-1 sm:w-auto">
                                    </div>
                                </div>
                                <button id="addAssuntoBtn" class="px-3 py-2 md:px-4 md:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs md:text-sm whitespace-nowrap w-full sm:w-auto">
                                    + Adicionar
                                </button>
                            </div>
                        </div>
                        
                        <!-- Divisor com botão de pesquisa avançada -->
                        <div class="border-t border-gray-200 mt-4 pt-4">
                            <div class="flex items-center justify-between cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition-colors" 
                                 onclick="toggleSearchFields()" id="searchHeader">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <span class="text-sm md:text-base font-medium text-gray-900">Pesquisa Avançada</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs md:text-sm text-gray-500" id="searchStatus">Clique para expandir</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5 text-gray-400 search-arrow-transition" 
                                         id="searchArrow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Conteúdo dos campos de pesquisa (inicialmente oculto) -->
                            <div id="searchFields" class="hidden mt-4 pt-4 border-t border-gray-100">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
                                    <div>
                                        <label for="searchAssunto" class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Pesquisar por assunto:</label>
                                        <div class="relative">
                                            <input type="text" id="searchAssunto" placeholder="Digite para pesquisar assunto..." 
                                                   class="w-full px-2 py-1 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm pr-8">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5 absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="searchAcoes" class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Pesquisar por ações a realizar:</label>
                                        <div class="relative">
                                            <input type="text" id="searchAcoes" placeholder="Digite para pesquisar ações..."
                                                   class="w-full px-2 py-1 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm pr-8">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5 absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="searchProvidencias" class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Pesquisar por providências adotadas:</label>
                                        <div class="relative">
                                            <input type="text" id="searchProvidencias" placeholder="Digite para pesquisar providências..."
                                                   class="w-full px-2 py-1 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm pr-8">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 md:h-5 md:w-5 absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-center">
                                    <button id="clearSearchBtn" class="px-4 py-2 md:px-6 md:py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-xs md:text-sm whitespace-nowrap">
                                        Limpar todos os filtros de pesquisa
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="overflow-x-auto table-container">
                            <table class="w-full table-auto min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-2 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-mobile">
                                            Chefia
                                        </th>
                                        <th class="px-2 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-mobile">
                                            Militar Responsável
                                        </th>
                                        <th class="px-2 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider assunto-column">
                                            Assunto
                                        </th>
                                        <th class="px-1 py-2 md:py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hide-mobile">
                                            É crítico?
                                        </th>
                                        <th class="px-2 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Prazo
                                        </th>
                                        <th class="px-2 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-mobile">
                                            Ações a realizar
                                        </th>
                                        <th class="px-2 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-mobile">
                                            Providências adotadas
                                        </th>
                                        <th class="px-2 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-mobile">
                                            Estado
                                        </th>
                                        <th class="px-1 py-2 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-mobile">
                                            Data Atualização
                                        </th>
                                        <th class="px-2 py-2 md:px-6 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Ações
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="assuntosTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Dados serão inseridos aqui via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        <div id="assuntosPagination" class="px-4 py-3 border-t border-gray-200">
                        </div>
                    </div>
                </div>

                <!-- Usuários Tab -->
                <div id="usuariosTab" class="tab-content hidden">
                    <!-- Filters -->
                    <div class="bg-white rounded-lg shadow p-4 md:p-6 mb-6">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2 md:gap-4 flex-1">
                                <input type="text" id="filterIdtMilitar" placeholder="ID Militar" class="px-2 py-2 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm">
                                <input type="text" id="filterpg" placeholder="P/Grad" class="px-2 py-2 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm">
                                <input type="text" id="filterNomeGuerra" placeholder="Nome de Guerra" class="px-2 py-2 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm">
                                <input type="text" id="filterChefia" placeholder="Chefia" class="px-2 py-2 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm">
                                <input type="text" id="filterDivisao" placeholder="Divisão" class="px-2 py-2 md:px-3 md:py-2 border rounded-lg text-xs md:text-sm">
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" id="mostrarInativos" class="mr-2">
                                    Mostrar usuários inativos
                                </label>
                                <button id="addUsuarioBtn" class="px-3 py-2 md:px-4 md:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs md:text-sm whitespace-nowrap">
                                    + Adicionar usuário
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="overflow-x-auto">
                            <table class="w-full table-auto">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-2 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-mobile-sm">
                                            Identidade Militar
                                        </th>
                                        <th class="px-2 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            P/Grad
                                        </th>
                                        <th class="px-2 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nome de Guerra
                                        </th>
                                        <th class="px-2 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-mobile">
                                            OM/Chefia
                                        </th>
                                        <th class="px-2 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hide-mobile">
                                            Divisão
                                        </th>
                                        <th class="px-2 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Perfil
                                        </th>
                                        <th class="px-2 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Ações
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="usuariosTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Dados serão inseridos aqui via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        <div id="usuariosPagination" class="px-4 py-3 border-t border-gray-200">
                        </div>
                    </div>
                </div>

                <!-- OM Tab -->
                <div id="omTab" class="tab-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                        <!-- Chefias -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-4 md:p-6 border-b">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                    <h3 class="text-lg font-semibold">OM/Chefias</h3>
                                    <button id="addChefiaBtn" class="px-3 py-2 md:px-4 md:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs md:text-sm w-full sm:w-auto">
                                        + Adicionar chefia
                                    </button>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full table-auto">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                OM/Chefia
                                            </th>
                                            <th class="px-3 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Ações
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="chefiasTableBody" class="bg-white divide-y divide-gray-200">
                                        <!-- Dados serão inseridos aqui via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="chefiasPagination" class="px-4 py-3 border-t border-gray-200">
                            </div>
                        </div>

                        <!-- Divisões -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-4 md:p-6 border-b">
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                    <h3 class="text-lg font-semibold">Divisões/Seção</h3>
                                    <button id="addDivisaoBtn" class="px-3 py-2 md:px-4 md:py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs md:text-sm w-full sm:w-auto">
                                        + Adicionar divisão
                                    </button>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full table-auto">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Divisão/Seção
                                            </th>
                                            <th class="px-3 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                OM/Chefia
                                            </th>
                                            <th class="px-3 py-3 md:px-4 md:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Ações
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="divisoesTableBody" class="bg-white divide-y divide-gray-200">
                                        <!-- Dados serão inseridos aqui via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="divisoesPagination" class="px-4 py-3 border-t border-gray-200">
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="bg-black text-white py-1 md:py-3 px-2 md:px-6 text-center text-xs">
                <div class="hidden lg:block md:text-sm">
                    Exército Brasileiro • Comando Logístico • Chefia de Material • SMU, Bloco C, Térreo. CEP: 70630-901 • Brasília DF • Divisão de Planejamento, Integração e Controle • Ramal 4374 / 5451
                </div>
                <div class="lg:hidden">
                    Exército Brasileiro • COLOG<br>
                    Divisão de Planejamento, Integração e Controle
                </div>
            </footer>
        </div>
    </div>

    <!-- Modal Adicionar Assunto -->
<div id="addAssuntoModal" class="modal">
    <div class="modal-content modal-large">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Adicionar Novo Assunto</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        
        <form id="addAssuntoForm" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Assunto</label>
                <input type="text" id="addAssunto" name="assunto" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">É crítico?</label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="critico" value="sim" required class="mr-2">
                        Sim
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="critico" value="nao" required class="mr-2">
                        Não
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Prazo</label>
                <input type="date" name="prazo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                <select name="estado" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Selecione o estado</option>
                    <option value="pendente">Pendente</option>
                    <option value="concluido">Concluído</option>
                </select>
            </div>

            <div>
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold">Ações a Realizar</h4>
                    <button type="button" onclick="adicionarNovaAcaoAdd()" class="px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                        + Nova Ação
                    </button>
                </div>
                <div id="acoesContainerAdd" class="space-y-4 max-h-60 overflow-y-auto">
                    <!-- Ações serão adicionadas dinamicamente -->
                </div>
            </div>

            <div class="flex justify-end gap-4 pt-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Adicionar Assunto
                </button>
            </div>
        </form>
    </div>
</div>

    <!-- Modal Adicionar/Editar Usuário -->
    <div id="usuarioModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 id="usuarioModalTitle" class="text-xl font-bold">Adicionar Novo Usuário</h3>
                <button onclick="closeUsuarioModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            
            <form id="usuarioForm" class="space-y-6">
                <input type="hidden" id="usuarioId" name="id">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Identidade Militar</label>
                    <input type="number" name="idt_Mil" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">P/Grad</label>
                    <select name="pg" id="pg" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                      <!--OPÇÕES-->
                      <option value="">Selecione seu posto/gradução</option>
                      <option value="Gen Ex">GENERAL DE EXÉRCITO</option> 
                      <option value="Gen Div">GENERAL DE DIVISÃO</option> 
                      <option value="Gen Bda">GENERAL DE BRIGADA</option> 
                      <option value="Cel">CORONEL</option>  
                      <option value="TC">TENENTE-CORONEL</option>
                      <option value="Maj">MAJOR</option>
                      <option value="Cap">CAPITÃO</option>
                      <option value="1°Ten">1°TENENTE</option>
                      <option value="2°Ten">2°TENENTE</option>
                      <option value="Asp">ASPIRANTE</option>
                      <option value="ST">SUB TENENTE</option>
                      <option value="1°Sgt">1°SARGENTO</option>
                      <option value="2°Sgt">2°SARGENTO</option>
                      <option value="3°Sgt">3°SARGENTO</option>
                      <option value="Cb">CABO</option>
                      <option value="Sd">SOLDADO</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Perfil do Usuário</label>
                    <select name="perfil_id" id="usuarioPerfilSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="1">Suporte Técnico</option>
                        <option value="2">Auditor OM/Chefia</option>
                        <option value="3">Auditor COLOG</option>
                        <option value="4">Editor</option>
                        <option value="5">Cadastro de Usuário</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome de Guerra</label>
                    <input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Senha Padrão</label>
                    <div class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                        A senha padrão será igual à Identidade Militar
                    </div>
                    <p class="text-xs text-gray-500 mt-1">O usuário será obrigado a alterar a senha no primeiro login</p>
                    <!-- Campo oculto para manter compatibilidade com o JavaScript -->
                    <input type="hidden" name="senha" value="default">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chefia</label>
                    <select name="chefia" id="usuarioChefiaSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione uma chefia</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Divisão</label>
                    <select name="divisao" id="usuarioDivisaoSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione uma divisão</option>
                    </select>
                </div>

                <div class="flex justify-between pt-4">
                    <button type="button" id="toggleUsuarioStatusBtn" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 hidden">
                        Desativar Usuário
                    </button>
                    <div class="flex gap-4">
                        <button type="button" onclick="closeUsuarioModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" id="saveUsuarioBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Adicionar usuário
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Alteração de Senha Obrigatória -->
    <div id="primeiroLoginModal" class="modal" style="z-index: 1001;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-red-600">Alteração de Senha Obrigatória</h3>
                <!-- Remover botão X para forçar alteração -->
            </div>
            
            <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <strong>Atenção:</strong> Este é seu primeiro acesso ao sistema. Por motivos de segurança, 
                    você deve alterar sua senha antes de continuar.
                </p>
            </div>
            
            <form id="primeiroLoginForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Senha Atual</label>
                    <input type="password" name="senha_atual" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Digite sua senha atual">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nova Senha</label>
                    <input type="password" name="nova_senha" required minlength="6"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Digite sua nova senha (mínimo 6 caracteres)">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Nova Senha</label>
                    <input type="password" name="confirmar_senha" required minlength="6"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Confirme sua nova senha">
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Alterar Senha
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Minha Conta -->
    <div id="contaModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold">Minha Conta</h3>
                <button onclick="closeContaModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            
            <form id="contaForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Identidade Militar</label>
                    <input type="number" id="contaIdtMil" disabled name="idt_Mil" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">P/Grad</label>
                    <select name="pg" id="contaPg" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                      <option value="">Selecione seu posto/graduação</option>
                      <option value="Gen Ex">GENERAL DE EXÉRCITO</option> 
                      <option value="Gen Div">GENERAL DE DIVISÃO</option> 
                      <option value="Gen Bda">GENERAL DE BRIGADA</option> 
                      <option value="Cel">CORONEL</option>  
                      <option value="TC">TENENTE-CORONEL</option>
                      <option value="Maj">MAJOR</option>
                      <option value="Cap">CAPITÃO</option>
                      <option value="1°Ten">1°TENENTE</option>
                      <option value="2°Ten">2°TENENTE</option>
                      <option value="Asp">ASPIRANTE</option>
                      <option value="ST">SUB TENENTE</option>
                      <option value="1°Sgt">1°SARGENTO</option>
                      <option value="2°Sgt">2°SARGENTO</option>
                      <option value="3°Sgt">3°SARGENTO</option>
                      <option value="Cb">CABO</option>
                      <option value="Sd">SOLDADO</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome de Guerra</label>
                    <input type="text" id="contaNome" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Perfil</label>
                    <input type="text" id="contaPerfil" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chefia</label>
                    <input type="text" id="contaChefia" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Divisão</label>
                    <input type="text" id="contaDivisao" disabled class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nova Senha</label>
                    <input type="password" id="contaSenha" name="senha" placeholder="Deixe em branco para manter a senha atual" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="closeContaModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Adicionar/Editar Chefia -->
    <div id="chefiaModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="flex justify-between items-center mb-6">
                <h3 id="chefiaModalTitle" class="text-xl font-bold">Adicionar Nova OM/Chefia</h3>
                <button onclick="closeChefiaModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            
            <form id="chefiaForm" class="space-y-6">
                <input type="hidden" id="chefiaId" name="id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">OM/Chefia</label>
                    <input type="text" name="chefia" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex justify-between pt-4">
                    <button type="button" id="deleteChefiaBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 hidden">
                        Excluir Chefia
                    </button>
                    <div class="flex gap-4">
                        <button type="button" onclick="closeChefiaModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" id="saveChefiaBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Adicionar chefia
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Adicionar/Editar Divisão -->
    <div id="divisaoModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="flex justify-between items-center mb-6">
                <h3 id="divisaoModalTitle" class="text-xl font-bold">Adicionar Nova Divisão</h3>
                <button onclick="closeDivisaoModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            
            <form id="divisaoForm" class="space-y-6">
                <input type="hidden" id="divisaoId" name="id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Divisão</label>
                    <input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Chefia</label>
                    <select name="chefia_id" id="divisaoChefiaSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Selecione uma chefia</option>
                    </select>
                </div>

                <div class="flex justify-between pt-4">
                    <button type="button" id="deleteDivisaoBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 hidden">
                        Excluir Divisão
                    </button>
                    <div class="flex gap-4">
                        <button type="button" onclick="closeDivisaoModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" id="saveDivisaoBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Adicionar divisão
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<!-- Modal Detalhar Assunto -->
<div id="detalharAssuntoModal" class="modal">
    <div class="modal-content modal-large">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold" id="detalharAssuntoTitle">Detalhes do Assunto</h3>
            <button onclick="closeDetalharAssuntoModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        
        <div id="detalharAssuntoContent" class="space-y-6">
            <!-- Conteúdo será preenchido dinamicamente -->
        </div>
        
        <!-- Seção de Notas de Auditoria (visível para todos, mas apenas Auditores e Editores podem adicionar) -->
        <div id="notasAuditoriaSection" class="mt-6 border-t pt-6" style="display: none;">
            <h4 class="text-lg font-semibold mb-4">Notas de Auditoria</h4>
            
            <!-- Lista de notas existentes -->
            <div id="notasExistentes" class="mb-4">
                <!-- Notas serão carregadas dinamicamente -->
            </div>
            
            <!-- Formulário para adicionar nova nota -->
            <div id="adicionarNotaForm" class="bg-gray-50 p-4 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">Adicionar Nova Nota</label>
                <textarea id="novaNota" placeholder="Digite sua nota de auditoria aqui..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
                <div class="mt-3 flex justify-end">
                    <button onclick="adicionarNotaAuditoria()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Adicionar Nota
                    </button>
                </div>
            </div>
        </div>
        
        <div class="flex justify-between pt-4 border-t">
            <button onclick="mostrarHistorico()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                Histórico de atualizações
            </button>
            <button onclick="closeDetalharAssuntoModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- Modal Editar Assunto -->
<div id="editarAssuntoModal" class="modal">
    <div class="modal-content modal-large">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Editar Assunto</h3>
            <button onclick="closeEditarAssuntoModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        
        <form id="editarAssuntoForm" class="space-y-6">
            <input type="hidden" id="editAssuntoId" name="id">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assunto</label>
                    <input type="text" name="assunto" id="editAssunto" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Prazo</label>
                    <input type="date" name="prazo" id="editPrazo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">É crítico?</label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="critico" value="sim" id="editCriticoSim" required class="mr-2">
                            Sim
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="critico" value="nao" id="editCriticoNao" required class="mr-2">
                            Não
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado do Assunto</label>
                    <select name="estado" id="editEstado" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="pendente">Pendente</option>
                        <option value="concluido">Concluído</option>
                    </select>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold">Ações a Realizar</h4>
                    <button type="button" onclick="adicionarNovaAcao()" class="px-3 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">
                        + Nova Ação
                    </button>
                </div>
                <div id="acoesContainer" class="space-y-4 max-h-200 overflow-y-auto">
                    <!-- Ações serão adicionadas dinamicamente -->
                </div>
            </div>

            <div class="flex justify-end gap-4 pt-4">
                <button type="button" onclick="closeEditarAssuntoModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Histórico -->
<div id="historicoModal" class="modal">
    <div class="modal-content modal-large">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Histórico de Atualizações</h3>
            <button onclick="closeHistoricoModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data/Hora
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Usuário
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ação Realizada
                        </th>
                    </tr>
                </thead>
                <tbody id="historicoTableBody" class="bg-white divide-y divide-gray-200">
                    <!-- Conteúdo será preenchido dinamicamente -->
                </tbody>
            </table>
        </div>
        
        <div class="flex justify-end pt-4">
            <button onclick="closeHistoricoModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- Modal Confirmação de Exclusão -->
<div id="confirmarExclusaoModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-red-600">Confirmar Exclusão</h3>
            <button onclick="closeConfirmarExclusaoModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        
        <div class="mb-6">
            <p class="text-gray-700 mb-4">Tem certeza que deseja excluir este assunto?</p>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-2"><strong>Assunto:</strong></p>
                <p class="text-sm" id="assuntoParaExcluir"></p>
            </div>
            <div class="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400">
                <p class="text-sm text-yellow-800">
                    <strong>Atenção:</strong> Esta ação não pode ser desfeita. O assunto será removido permanentemente da visualização, mas permanecerá no banco de dados para fins de auditoria.
                </p>
            </div>
        </div>
        
        <div class="flex justify-end gap-4">
            <button onclick="closeConfirmarExclusaoModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancelar
            </button>
            <button onclick="excluirAssunto()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                Sim, Excluir
            </button>
        </div>
    </div>
</div>

    <script>
        // Variáveis globais
        let currentFilter = 'pendentes';
        let currentUser = {
            id: <?php echo isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 'null'; ?>,
            nome: "<?php echo addslashes($usuarioNome); ?>",
            pg: "<?php echo addslashes($_SESSION['pg'] ?? ''); echo " -"; ?>",
            divisao: "<?php echo addslashes($usuarioDivisao); ?>",
            perfil: <?php echo isset($_SESSION['perfil_id']) ? (int)$_SESSION['perfil_id'] : (isset($perfil) ? (int)$perfil : 2); ?>, // 1=Suporte Técnico, 2=Auditor OM/Chefia, 3=Auditor COLOG, 4=Editor, 5=Cadastro de Usuário
            chefia_id: <?php echo isset($_SESSION['chefia_id']) ? (int)$_SESSION['chefia_id'] : 'null'; ?>,
            primeiro_login: <?php echo isset($_SESSION['primeiro_login']) && $_SESSION['primeiro_login'] ? 'true' : 'false'; ?>
        };

        // Variáveis de paginação
        let currentPage = {
            assuntos: 1,
            usuarios: 1,
            chefias: 1,
            divisoes: 1
        };
        let itemsPerPage = 10;
        let totalItems = {
            assuntos: 0,
            usuarios: 0,
            chefias: 0,
            divisoes: 0
        };
        let filteredData = {
            assuntos: [],
            usuarios: [],
            chefias: [],
            divisoes: []
        };

        // Funções de paginação
        function createPagination(tableType, containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const totalPages = Math.ceil(totalItems[tableType] / itemsPerPage);
            const current = currentPage[tableType];
            
            // Informações da paginação (lado esquerdo)
            const start = (current - 1) * itemsPerPage + 1;
            const end = Math.min(current * itemsPerPage, totalItems[tableType]);
            
            let paginationHTML = `
                <div class="flex flex-col md:flex-row justify-between items-center rounded-lg gap-3">
                    <!-- Contador de itens (lado esquerdo) -->
                    <div class="text-sm text-gray-700 font-medium">
                        ${start}-${end} of ${totalItems[tableType]} items
                    </div>
                    
                    <!-- Botões de navegação (centro) -->
                    <div class="flex items-center gap-1">
                        <!-- Botão anterior -->
                        <button onclick="changePage('${tableType}', ${current - 1})" 
                                ${current <= 1 ? 'disabled' : ''} 
                                class="pagination-btn w-8 h-8 flex items-center justify-center text-sm font-medium text-blue-600 bg-white border border-blue-300 rounded hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 disabled:border-gray-300">
                            &laquo;
                        </button>`;
            
            // Números das páginas
            const startPage = Math.max(1, current - 2);
            const endPage = Math.min(totalPages, current + 2);
            
            if (startPage > 1) {
                paginationHTML += `
                    <button onclick="changePage('${tableType}', 1)" 
                            class="pagination-btn w-8 h-8 flex items-center justify-center text-sm font-medium text-blue-600 bg-white border border-blue-300 rounded hover:bg-blue-50">
                        1
                    </button>`;
                if (startPage > 2) {
                    paginationHTML += `<span class="mx-1 text-gray-500">...</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === current;
                paginationHTML += `
                    <button onclick="changePage('${tableType}', ${i})" 
                            class="pagination-btn w-8 h-8 flex items-center justify-center text-sm font-medium rounded border ${isActive ? 'bg-blue-600 text-white border-blue-600 active' : 'text-blue-600 bg-white border-blue-300 hover:bg-blue-50'}">
                        ${i}
                    </button>`;
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHTML += `<span class="mx-1 text-gray-500">...</span>`;
                }
                paginationHTML += `
                    <button onclick="changePage('${tableType}', ${totalPages})" 
                            class="pagination-btn w-8 h-8 flex items-center justify-center text-sm font-medium text-blue-600 bg-white border border-blue-300 rounded hover:bg-blue-50">
                        ${totalPages}
                    </button>`;
            }
            
            // Botão próximo
            paginationHTML += `
                        <button onclick="changePage('${tableType}', ${current + 1})" 
                                ${current >= totalPages ? 'disabled' : ''} 
                                class="pagination-btn w-8 h-8 flex items-center justify-center text-sm font-medium text-blue-600 bg-white border border-blue-300 rounded hover:bg-blue-50 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 disabled:border-gray-300">
                            &raquo;
                        </button>
                    </div>
                    
                    <!-- Seletor de itens por página (lado direito) -->
                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <select onchange="changeItemsPerPage('${tableType}', this.value)" 
                                id="${tableType}ItemsSelect"
                                class="bg-white border border-blue-300 text-blue-600 text-sm rounded px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="10" ${itemsPerPage === 10 ? 'selected' : ''}>10</option>
                            <option value="25" ${itemsPerPage === 25 ? 'selected' : ''}>25</option>
                            <option value="50" ${itemsPerPage === 50 ? 'selected' : ''}>50</option>
                            <option value="100" ${itemsPerPage === 100 ? 'selected' : ''}>100</option>
                        </select>
                        <span class="font-medium">itens por página</span>
                    </div>
                </div>`;
            
            container.innerHTML = paginationHTML;
        }

        function changePage(tableType, page) {
            const totalPages = Math.ceil(totalItems[tableType] / itemsPerPage);
            if (page < 1 || page > totalPages) return;
            
            currentPage[tableType] = page;
            
            // Renderizar tabela com dados paginados
            switch(tableType) {
                case 'assuntos':
                    renderTablePaginated(filteredData.assuntos, 'assuntosTableBody', 'assuntos');
                    createPagination('assuntos', 'assuntosPagination');
                    break;
                case 'usuarios':
                    renderUsuariosTablePaginated(filteredData.usuarios);
                    createPagination('usuarios', 'usuariosPagination');
                    break;
                case 'chefias':
                    renderChefiasTablePaginated(filteredData.chefias);
                    createPagination('chefias', 'chefiasPagination');
                    break;
                case 'divisoes':
                    renderDivisoesTablePaginated(filteredData.divisoes);
                    createPagination('divisoes', 'divisoesPagination');
                    break;
            }
        }

        function changeItemsPerPage(tableType, newItemsPerPage) {
            itemsPerPage = parseInt(newItemsPerPage);
            
            // Resetar todas as páginas para 1
            currentPage.assuntos = 1;
            currentPage.usuarios = 1;
            currentPage.chefias = 1;
            currentPage.divisoes = 1;
            
            // Sincronizar todos os seletores
            const selectors = ['assuntosItemsSelect', 'usuariosItemsSelect', 'chefiasItemsSelect', 'divisoesItemsSelect'];
            selectors.forEach(selectorId => {
                const selector = document.getElementById(selectorId);
                if (selector) {
                    selector.value = newItemsPerPage;
                }
            });
            
            // Atualizar todas as tabelas
            if (filteredData.assuntos.length > 0) {
                renderTablePaginated(filteredData.assuntos, 'assuntosTableBody', 'assuntos');
                createPagination('assuntos', 'assuntosPagination');
            }
            if (filteredData.usuarios.length > 0) {
                renderUsuariosTablePaginated(filteredData.usuarios);
                createPagination('usuarios', 'usuariosPagination');
            }
            if (filteredData.chefias.length > 0) {
                renderChefiasTablePaginated(filteredData.chefias);
                createPagination('chefias', 'chefiasPagination');
            }
            if (filteredData.divisoes.length > 0) {
                renderDivisoesTablePaginated(filteredData.divisoes);
                createPagination('divisoes', 'divisoesPagination');
            }
        }

        function getPaginatedData(data, tableType) {
            const start = (currentPage[tableType] - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            return data.slice(start, end);
        }

        // Exibir/ocultar abas do sidebar conforme perfil
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar se é o primeiro login e mostrar modal obrigatório
            if (currentUser.primeiro_login) {
                document.getElementById('primeiroLoginModal').style.display = 'block';
                // Bloquear interação com o resto da página
                document.body.style.overflow = 'hidden';
            }
            
            // 1=Suporte Técnico, 2=Auditor OM/Chefia, 3=Auditor COLOG, 4=Editor, 5=Cadastro de Usuário
            if (currentUser.perfil === 1) {
                // Suporte Técnico: vê tudo, incluindo filtro de chefia
                const chefiaFilterSection = document.getElementById('chefiaFilterSection');
                if (chefiaFilterSection) {
                    chefiaFilterSection.style.display = 'block';
                }
                const chefiaFilterContainer = document.getElementById('chefiaFilterContainer');
                if (chefiaFilterContainer) {
                    chefiaFilterContainer.style.display = 'flex';
                    setupChefiaFilter();
                }
            } else if (currentUser.perfil === 2) {
                // Auditor OM/Chefia: não pode ver "Usuários" e "OM", pode criar e ver assuntos da sua chefia
                document.querySelector("a[onclick=\"switchTab('usuarios')\"]").style.display = 'none';
                document.querySelector("a[onclick=\"switchTab('om')\"]").style.display = 'none';
            } else if (currentUser.perfil === 3) {
                // Auditor COLOG: não pode ver "Usuários" e "OM", pode criar e ver assuntos, tem filtro de chefia
                document.querySelector("a[onclick=\"switchTab('usuarios')\"]").style.display = 'none';
                document.querySelector("a[onclick=\"switchTab('om')\"]").style.display = 'none';
                // Mostrar seção completa do filtro de chefia para Auditor COLOG
                const chefiaFilterSection = document.getElementById('chefiaFilterSection');
                if (chefiaFilterSection) {
                    chefiaFilterSection.style.display = 'block';
                }
                const chefiaFilterContainer = document.getElementById('chefiaFilterContainer');
                if (chefiaFilterContainer) {
                    chefiaFilterContainer.style.display = 'flex';
                    setupChefiaFilter();
                }
            } else if (currentUser.perfil === 4) {
                // Editor: não pode ver "Usuários" e "OM", vê apenas assuntos da sua divisão
                document.querySelector("a[onclick=\"switchTab('usuarios')\"]").style.display = 'none';
                document.querySelector("a[onclick=\"switchTab('om')\"]").style.display = 'none';
            } else if (currentUser.perfil === 5) {
                // Cadastro de Usuário: só pode ver "Usuários" e "OM", não vê resumo nem assuntos
                document.querySelector("a[onclick=\"switchTab('resumo')\"]").style.display = 'none';
                document.querySelector("a[onclick=\"switchTab('todos')\"]").style.display = 'none';
                
                // Ocultar botão de adicionar chefia para perfil 5
                const addChefiaBtn = document.getElementById('addChefiaBtn');
                if (addChefiaBtn) addChefiaBtn.style.display = 'none';
                
                // Redirecionar automaticamente para a aba de usuários ao carregar
                setTimeout(() => switchTab('usuarios'), 100);
            } // Suporte Técnico vê tudo
        });
        
        let assuntos = [];
        async function fetchAssuntos() {
            try {
                const res = await fetch('api/get_assuntos.php');
                assuntos = await res.json();
                
                // Ordenar assuntos por prazo (do menor para o maior)
                assuntos.sort((a, b) => {
                    const dateA = new Date(a.prazo);
                    const dateB = new Date(b.prazo);
                    return dateA - dateB;
                });
                
                applyFilters();
                updateResumo();
            } catch (err) {
                // Silently handle error
            }
        }
        
        let usuarios = [];
        async function fetchUsuarios() {
            try {
                const incluirInativos = document.getElementById('mostrarInativos')?.checked || false;
                const url = `api/get_usuarios.php${incluirInativos ? '?incluir_inativos=1' : ''}`;
                const res = await fetch(url);
                usuarios = await res.json();
                renderUsuariosTable(usuarios);
            } catch (err) {
                console.error('Erro ao carregar usuários:', err);
                // Silently handle error
            }
        }
        
        let chefias = [];
        async function fetchChefias() {
            try {
                const res = await fetch('api/get_chefias.php');
                chefias = await res.json();
                renderChefiasTable(chefias);
            } catch (err) {
                console.error('Erro ao carregar chefias:', err);
                // Silently handle error
            }
        }

        let divisoes = [];
        async function fetchDivisoes() {
            try {
                const res = await fetch('api/get_divisoes.php');
                divisoes = await res.json();
                renderDivisoesTable(divisoes);
            } catch (err) {
                console.error('Erro ao carregar divisões:', err);
                // Silently handle error
            }
        }
        
        let editingUsuario = null;
        let editingChefia = null;
        let editingDivisao = null;

        // Função para controlar o sidebar mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
            } else {
                sidebar.classList.add('open');
                overlay.classList.add('show');
            }
        }

        // Fechar sidebar quando clicar em um link (mobile)
        function setupSidebarLinks() {
            const sidebarLinks = document.querySelectorAll('#sidebar nav a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        toggleSidebar();
                    }
                });
            });
        }

        // Configurar eventos de redimensionamento
        function setupResponsiveEvents() {
            window.addEventListener('resize', () => {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                
                // Fechar sidebar automaticamente quando a tela aumentar
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('show');
                }
                
                // Ajustar tamanhos de elementos responsivos
                adjustResponsiveElements();
            });
            
            // Configurar eventos de toque para melhor experiência mobile
            setupTouchEvents();
        }
        
        // Ajustar elementos responsivos baseado no tamanho da tela
        function adjustResponsiveElements() {
            const isMobile = window.innerWidth <= 768;
            const isSmallMobile = window.innerWidth <= 480;
            
            // Ajustar padding do content area
            const contentArea = document.querySelector('.flex-1.overflow-y-auto');
            if (contentArea) {
                if (isSmallMobile) {
                    contentArea.style.padding = '0.25rem';
                } else if (isMobile) {
                    contentArea.style.padding = '0.5rem';
                } else {
                    contentArea.style.padding = '';
                }
            }
            
            // Ajustar tabelas para mobile
            const tables = document.querySelectorAll('table');
            tables.forEach(table => {
                if (isMobile) {
                    table.style.fontSize = isSmallMobile ? '0.55rem' : '0.7rem';
                } else {
                    table.style.fontSize = '';
                }
            });
            
            // Reajustar conteúdo da tabela de assuntos quando redimensionar
            if (typeof assuntos !== 'undefined' && assuntos.length > 0) {
                applyFilters();
            }
        }
        
        // Configurar eventos de toque para mobile
        function setupTouchEvents() {
            // Adicionar suporte a swipe para fechar sidebar
            let startX = 0;
            let currentX = 0;
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar) {
                sidebar.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                }, { passive: true });
                
                sidebar.addEventListener('touchmove', (e) => {
                    currentX = e.touches[0].clientX;
                }, { passive: true });
                
                sidebar.addEventListener('touchend', () => {
                    const diffX = startX - currentX;
                    
                    // Se swipe para esquerda com mais de 50px, fechar sidebar
                    if (diffX > 50 && sidebar.classList.contains('open')) {
                        toggleSidebar();
                    }
                }, { passive: true });
            }
        }

        // Inicializar informações do usuário
        function updateUserInfo() {
            const displayName = currentUser.pg ? `${currentUser.pg} ${currentUser.nome}` : currentUser.nome;
            document.getElementById('userInfo').textContent = `${displayName} - ${currentUser.divisao}`;
        }

        // Obter título baseado no filtro atual
        function getTitleForCurrentFilter() {
            const filterTitles = {
                'todos': 'Todos os assuntos',
                'criticos': 'Assuntos críticos',
                'pendentes': 'Assuntos pendentes',
                'concluidos': 'Assuntos concluídos'
            };
            return filterTitles[currentFilter] || 'Todos os assuntos';
        }

        // Controle de tabs
        function switchTab(tabName) {
            // Update sidebar active states
            document.querySelectorAll('nav a').forEach(link => {
                link.classList.remove('bg-blue-700');
                link.classList.add('hover:bg-blue-700');
            });
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Show selected tab
            const selectedTab = document.getElementById(tabName + 'Tab');
            if (selectedTab) {
                selectedTab.classList.remove('hidden');
            }
            
            // Update header title
            const titles = {
                'resumo': 'Resumo',
                'todos': getTitleForCurrentFilter(),
                'usuarios': 'Usuários',
                'om': 'Organizações Militares'
            };
            document.getElementById('currentTabTitle').textContent = titles[tabName];
            
            // Update sidebar active state
            const activeLink = document.querySelector(`nav a[onclick="switchTab('${tabName}')"]`);
            if (activeLink) {
                activeLink.classList.add('bg-blue-700');
                activeLink.classList.remove('hover:bg-blue-700');
            }
            
            // Execute tab-specific logic
            switch(tabName) {
                case 'resumo':
                    updateResumo();
                    break;
                case 'todos':
                    applyFilters();
                    // Reconfigurar listeners para pesquisa caso não estejam funcionando
                    setTimeout(() => {
                        const searchAssuntoEl = document.getElementById('searchAssunto');
                        const searchAcoesEl = document.getElementById('searchAcoes');
                        const searchProvidenciasEl = document.getElementById('searchProvidencias');
                        const clearSearchBtnEl = document.getElementById('clearSearchBtn');
                        
                        if (searchAssuntoEl && !searchAssuntoEl.hasAttribute('data-listener-added')) {
                            searchAssuntoEl.addEventListener('input', function() {
                                applyFilters();
                                updateSearchStatus();
                            });
                            searchAssuntoEl.setAttribute('data-listener-added', 'true');
                        }
                        
                        if (searchAcoesEl && !searchAcoesEl.hasAttribute('data-listener-added')) {
                            searchAcoesEl.addEventListener('input', function() {
                                applyFilters();
                                updateSearchStatus();
                            });
                            searchAcoesEl.setAttribute('data-listener-added', 'true');
                        }
                        
                        if (searchProvidenciasEl && !searchProvidenciasEl.hasAttribute('data-listener-added')) {
                            searchProvidenciasEl.addEventListener('input', function() {
                                applyFilters();
                                updateSearchStatus();
                            });
                            searchProvidenciasEl.setAttribute('data-listener-added', 'true');
                        }
                        
                        if (clearSearchBtnEl && !clearSearchBtnEl.hasAttribute('data-listener-added')) {
                            clearSearchBtnEl.addEventListener('click', function() {
                                if (searchAssuntoEl) searchAssuntoEl.value = '';
                                if (searchAcoesEl) searchAcoesEl.value = '';
                                if (searchProvidenciasEl) searchProvidenciasEl.value = '';
                                applyFilters();
                                updateSearchStatus();
                            });
                            clearSearchBtnEl.setAttribute('data-listener-added', 'true');
                        }
                    }, 50);
                    break;
                case 'usuarios':
                    // Garantir que todos os dados necessários estejam carregados
                    Promise.all([
                        usuarios.length === 0 ? fetchUsuarios() : Promise.resolve(),
                        chefias.length === 0 ? fetchChefias() : Promise.resolve(),
                        divisoes.length === 0 ? fetchDivisoes() : Promise.resolve()
                    ]).then(() => {
                        renderUsuariosTable(usuarios);
                        // Aplicar contagem correta baseada no perfil
                        let usuariosParaContar = usuarios;
                        if (currentUser.perfil === 5) {
                            usuariosParaContar = usuarios.filter(usuario => usuario.chefia_id === currentUser.chefia_id);
                        }
                        updateTotalCount(usuariosParaContar.length, 'usuários');
                        setupUsuarioFilters();
                    });
                    break;
                case 'om':
                    // Garantir que chefias e divisões estejam carregadas
                    Promise.all([
                        chefias.length === 0 ? fetchChefias() : Promise.resolve(),
                        divisoes.length === 0 ? fetchDivisoes() : Promise.resolve()
                    ]).then(() => {
                        renderChefiasTable(chefias);
                        renderDivisoesTable(divisoes);
                        // Aplicar contagem correta baseada no perfil
                        let chefiasParaContar = chefias;
                        let divisoesParaContar = divisoes;
                        if (currentUser.perfil === 5) {
                            divisoesParaContar = divisoes.filter(divisao => divisao.chefia_id === currentUser.chefia_id);
                            // Para chefias, só conta a própria chefia
                            chefiasParaContar = chefias.filter(chefia => chefia.id === currentUser.chefia_id);
                        }
                        updateTotalCount(chefiasParaContar.length + divisoesParaContar.length, 'registros');
                    });
                    break;
            }
        }

        // Atualizar resumo
        function updateResumo() {
            const totalAssuntos = assuntos.length;
            const totalCriticos = assuntos.filter(a => a.critico === 'sim').length;
            const totalOrdinarios = assuntos.filter(a => a.critico === 'nao').length;
            const totalPendentes = assuntos.filter(a => a.estado === 'pendente').length;
            const totalConcluidos = assuntos.filter(a => a.estado === 'concluido').length;
            
            // Calculando os campos detalhados
            const pendentesCriticos = assuntos.filter(a => a.estado === 'pendente' && a.critico === 'sim').length;
            const pendentesOrdinarios = assuntos.filter(a => a.estado === 'pendente' && a.critico === 'nao').length;
            const concluidosCriticos = assuntos.filter(a => a.estado === 'concluido' && a.critico === 'sim').length;
            const concluidosOrdinarios = assuntos.filter(a => a.estado === 'concluido' && a.critico === 'nao').length;
            
            // Atualizar cards do resumo geral
            document.getElementById('totalAssuntos').textContent = totalAssuntos;
            document.getElementById('totalCriticos').textContent = totalCriticos;
            document.getElementById('totalOrdinarios').textContent = totalOrdinarios;
            document.getElementById('totalPendentes').textContent = totalPendentes;
            document.getElementById('totalConcluidos').textContent = totalConcluidos;
            
            // Atualizar campos detalhados - Pendentes
            document.getElementById('pendentesCriticos').textContent = pendentesCriticos;
            document.getElementById('pendentesOrdinarios').textContent = pendentesOrdinarios;
            
            // Atualizar campos detalhados - Concluídos
            document.getElementById('concluidosCriticos').textContent = concluidosCriticos;
            document.getElementById('concluidosOrdinarios').textContent = concluidosOrdinarios;
            
            // Atualizar barras de progresso
            const maxPendentes = Math.max(pendentesCriticos, pendentesOrdinarios);
            const maxConcluidos = Math.max(concluidosCriticos, concluidosOrdinarios);
            
            if (maxPendentes > 0) {
                document.getElementById('pendentesCriticosBar').style.width = `${(pendentesCriticos / maxPendentes) * 100}%`;
                document.getElementById('pendentesOrdinariosBar').style.width = `${(pendentesOrdinarios / maxPendentes) * 100}%`;
            } else {
                document.getElementById('pendentesCriticosBar').style.width = '0%';
                document.getElementById('pendentesOrdinariosBar').style.width = '0%';
            }
            
            if (maxConcluidos > 0) {
                document.getElementById('concluidosCriticosBar').style.width = `${(concluidosCriticos / maxConcluidos) * 100}%`;
                document.getElementById('concluidosOrdinariosBar').style.width = `${(concluidosOrdinarios / maxConcluidos) * 100}%`;
            } else {
                document.getElementById('concluidosCriticosBar').style.width = '0%';
                document.getElementById('concluidosOrdinariosBar').style.width = '0%';
            }
            
            // Atualizar dados do gráfico
            updateChart(pendentesCriticos, pendentesOrdinarios, concluidosCriticos, concluidosOrdinarios, totalAssuntos);
            
            updateTotalCount(totalAssuntos, 'assuntos');
        }

        // Variável global para o gráfico
        let assuntosChart = null;

        // Função para criar/atualizar o gráfico
        function updateChart(pendentesCriticos, pendentesOrdinarios, concluidosCriticos, concluidosOrdinarios, total) {
            const ctx = document.getElementById('assuntosChart').getContext('2d');
            
            // Calcular percentuais corretamente
            const percentPendentesCriticos = total > 0 ? ((pendentesCriticos / total) * 100).toFixed(1) : 0;
            const percentPendentesOrdinarios = total > 0 ? ((pendentesOrdinarios / total) * 100).toFixed(1) : 0;
            const percentConcluidosCriticos = total > 0 ? ((concluidosCriticos / total) * 100).toFixed(1) : 0;
            const percentConcluidosOrdinarios = total > 0 ? ((concluidosOrdinarios / total) * 100).toFixed(1) : 0;
            
            // Destruir gráfico existente se houver
            if (assuntosChart) {
                assuntosChart.destroy();
            }
            
            // Verificar se há dados
            const hasData = total > 0;
            
            // Sempre mostrar todas as categorias no gráfico
            const chartData = [pendentesCriticos, pendentesOrdinarios, concluidosCriticos, concluidosOrdinarios];
            const chartLabels = ['Pendentes Críticos', 'Pendentes Ordinários', 'Concluídos Críticos', 'Concluídos Ordinários'];
            const chartColors = ['#FEE2E2', '#FEF3C7', '#dce4fcff', '#DBEAFE'];
            const chartBorderColors = ['#DC2626', '#D97706', '#1630a3ff', '#2563EB'];
            
            if (hasData) {
                
                // Criar gráfico com dados reais
                assuntosChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Quantidade de Assuntos',
                            data: chartData,
                            backgroundColor: chartColors,
                            borderColor: chartBorderColors,
                            borderWidth: 2,
                            borderRadius: 4,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        if (chartLabels[0] === 'Sem dados') return 'Nenhum dado disponível';
                                        const label = context.label || '';
                                        const value = context.parsed.y;
                                        // Usar o total real para o cálculo das porcentagens no tooltip
                                        const percent = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${value} (${percent}%)`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#E5E7EB',
                                    drawBorder: false
                                },
                                ticks: {
                                    stepSize: 1,
                                    color: '#6B7280',
                                    font: {
                                        size: 12
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Quantidade de Assuntos',
                                    color: '#374151',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#6B7280',
                                    maxRotation: 45,
                                    minRotation: 0,
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutCubic'
                        }
                    }
                });
            } else {
                // Criar gráfico vazio mostrando todas as categorias com valor 0
                assuntosChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Quantidade de Assuntos',
                            data: [0, 0, 0, 0],
                            backgroundColor: chartColors,
                            borderColor: chartBorderColors,
                            borderWidth: 2,
                            borderRadius: 4,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.label}: 0 (0%)`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#E5E7EB',
                                    drawBorder: false
                                },
                                ticks: {
                                    stepSize: 1,
                                    color: '#6B7280',
                                    font: {
                                        size: 12
                                    }
                                },
                                title: {
                                    display: true,
                                    text: 'Quantidade de Assuntos',
                                    color: '#374151',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#6B7280',
                                    maxRotation: 45,
                                    minRotation: 0,
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutCubic'
                        }
                    }
                });
            }
        }

        // Função para retornar o label do perfil
        function perfilLabel(perfilId) {
            switch(parseInt(perfilId)) {
                case 1: return 'Suporte Técnico';
                case 2: return 'Auditor OM/Chefia';
                case 3: return 'Auditor COLOG';
                case 4: return 'Editor';
                case 5: return 'Cadastro de Usuário';
                default: return 'Não definido';
            }
        }

        // Setup filtros de usuários
        function setupUsuarioFilters() {
            ['filterIdtMilitar', 'filterpg', 'filterNomeGuerra', 'filterChefia', 'filterDivisao'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.removeEventListener('input', filterUsuarios);
                    element.addEventListener('input', filterUsuarios);
                }
            });
            
            // Event listener para checkbox de mostrar inativos
            const mostrarInativosCheckbox = document.getElementById('mostrarInativos');
            if (mostrarInativosCheckbox) {
                mostrarInativosCheckbox.removeEventListener('change', fetchUsuarios);
                mostrarInativosCheckbox.addEventListener('change', fetchUsuarios);
            }
        }

        // Função para controlar a expansão/retração dos campos de pesquisa
        function toggleSearchFields() {
            const searchFields = document.getElementById('searchFields');
            const searchArrow = document.getElementById('searchArrow');
            const searchStatus = document.getElementById('searchStatus');
            
            if (searchFields.classList.contains('hidden')) {
                // Expandir
                searchFields.classList.remove('hidden');
                searchArrow.style.transform = 'rotate(180deg)';
                searchStatus.textContent = 'Clique para retrair';
                
                // Adicionar animação suave
                searchFields.style.maxHeight = '0';
                searchFields.style.overflow = 'hidden';
                searchFields.style.transition = 'max-height 0.3s ease-out';
                
                // Forçar reflow e então animar
                setTimeout(() => {
                    searchFields.style.maxHeight = searchFields.scrollHeight + 'px';
                }, 10);
                
                // Remover max-height após a animação
                setTimeout(() => {
                    searchFields.style.maxHeight = 'none';
                    searchFields.style.overflow = 'visible';
                }, 300);
                
            } else {
                // Retrair
                searchFields.style.maxHeight = searchFields.scrollHeight + 'px';
                searchFields.style.overflow = 'hidden';
                searchFields.style.transition = 'max-height 0.3s ease-in';
                
                // Forçar reflow e então animar
                setTimeout(() => {
                    searchFields.style.maxHeight = '0';
                }, 10);
                
                // Ocultar completamente após a animação
                setTimeout(() => {
                    searchFields.classList.add('hidden');
                    searchFields.style.maxHeight = '';
                    searchFields.style.overflow = '';
                    searchFields.style.transition = '';
                }, 300);
                
                searchArrow.style.transform = 'rotate(0deg)';
                updateSearchStatus();
            }
        }
        
        // Função para atualizar o status da pesquisa
        function updateSearchStatus() {
            const searchAssunto = document.getElementById('searchAssunto');
            const searchAcoes = document.getElementById('searchAcoes');
            const searchProvidencias = document.getElementById('searchProvidencias');
            const searchStatus = document.getElementById('searchStatus');
            const searchFields = document.getElementById('searchFields');
            
            if (searchFields && searchFields.classList.contains('hidden')) {
                let activeFilters = 0;
                if (searchAssunto && searchAssunto.value.trim()) activeFilters++;
                if (searchAcoes && searchAcoes.value.trim()) activeFilters++;
                if (searchProvidencias && searchProvidencias.value.trim()) activeFilters++;
                
                if (activeFilters > 0) {
                    searchStatus.textContent = `${activeFilters} filtro(s) ativo(s) - Clique para expandir`;
                    searchStatus.classList.add('text-blue-600', 'font-medium');
                    searchStatus.classList.remove('text-gray-500');
                } else {
                    searchStatus.textContent = 'Clique para expandir';
                    searchStatus.classList.remove('text-blue-600', 'font-medium');
                    searchStatus.classList.add('text-gray-500');
                }
            }
        }

        // Filtros de assuntos
        function filterAssuntos(filter) {
            currentFilter = filter;
            
            // Update filter buttons
            document.querySelectorAll('button[onclick^="filterAssuntos"]').forEach(btn => {
                btn.classList.remove('filter-active');
                btn.classList.add('hover:bg-gray-50');
            });
            
            event.target.classList.add('filter-active');
            event.target.classList.remove('hover:bg-gray-50');
            
            // Update page title
            document.getElementById('currentTabTitle').textContent = getTitleForCurrentFilter();
            
            applyFilters();
        }

        function applyFilters() {
            const dataInicio = document.getElementById('dataInicio').value;
            const dataFim = document.getElementById('dataFim').value;
            
            // Verificar se os elementos de pesquisa existem antes de tentar acessá-los
            const searchAssuntoEl = document.getElementById('searchAssunto');
            const searchAcoesEl = document.getElementById('searchAcoes');
            const searchProvidenciasEl = document.getElementById('searchProvidencias');
            
            const searchAssunto = searchAssuntoEl ? searchAssuntoEl.value.toLowerCase() : '';
            const searchAcoes = searchAcoesEl ? searchAcoesEl.value.toLowerCase() : '';
            const searchProvidencias = searchProvidenciasEl ? searchProvidenciasEl.value.toLowerCase() : '';
            
            let filteredAssuntos = assuntos;
            
            // Filter by type
            if (currentFilter === 'criticos') {
                filteredAssuntos = filteredAssuntos.filter(a => a.critico === 'sim');
            } else if (currentFilter === 'pendentes') {
                filteredAssuntos = filteredAssuntos.filter(a => a.estado === 'pendente');
            } else if (currentFilter === 'concluidos') {
                filteredAssuntos = filteredAssuntos.filter(a => a.estado === 'concluido');
            }
            
            // Filter by date range (prazo)
            if (dataInicio) {
                filteredAssuntos = filteredAssuntos.filter(a => a.prazo >= dataInicio);
            }
            if (dataFim) {
                filteredAssuntos = filteredAssuntos.filter(a => a.prazo <= dataFim);
            }
            
            // Filter by chefia (para Suporte Técnico e Auditor COLOG)
            if (currentChefiaFilter && (currentUser.perfil === 1 || currentUser.perfil === 3)) {
                filteredAssuntos = filteredAssuntos.filter(a => a.chefia === currentChefiaFilter);
            }
            
            // Filtrar por texto no assunto
            if (searchAssunto) {
                filteredAssuntos = filteredAssuntos.filter(a => 
                    a.assunto && a.assunto.toLowerCase().includes(searchAssunto)
                );
            }
            
            // Filtrar por texto nas ações a realizar
            if (searchAcoes) {
                filteredAssuntos = filteredAssuntos.filter(a => {
                    // Verificar se alguma ação contém o texto pesquisado
                    return a.acoes && a.acoes.some(acao => 
                        acao.acao && acao.acao.toLowerCase().includes(searchAcoes)
                    );
                });
            }
            
            // Filtrar por texto nas providências adotadas
            if (searchProvidencias) {
                filteredAssuntos = filteredAssuntos.filter(a => {
                    // Verificar se alguma providência contém o texto pesquisado
                    return a.acoes && a.acoes.some(acao => 
                        acao.providencia && acao.providencia.toLowerCase().includes(searchProvidencias)
                    );
                });
            }
            
            // Atualizar dados filtrados e renderizar com paginação
            filteredData.assuntos = filteredAssuntos;
            totalItems.assuntos = filteredAssuntos.length;
            currentPage.assuntos = 1; // Reset para primeira página
            
            renderTablePaginated(filteredAssuntos, 'assuntosTableBody', 'assuntos');
            createPagination('assuntos', 'assuntosPagination');
            updateTotalCount(filteredAssuntos.length, 'assuntos');
        }

        // Função para configurar os botões de filtro de chefia
        function setupChefiaFilter() {
            // Aguardar que as chefias sejam carregadas
            setTimeout(() => {
                const chefiaBtnContainer = document.getElementById('chefiaBtnContainer');
                if (chefiaBtnContainer && chefias.length > 0) {
                    // Limpar botões existentes
                    chefiaBtnContainer.innerHTML = '';
                    
                    // Adicionar botões para cada chefia
                    chefias.forEach(chefia => {
                        const button = document.createElement('button');
                        button.className = 'px-3 py-2 text-xs md:px-4 md:py-2 md:text-sm rounded-lg border hover:bg-gray-50';
                        button.textContent = chefia.nome;
                        button.onclick = () => filterByChefiaBtn(chefia.nome);
                        chefiaBtnContainer.appendChild(button);
                    });
                }
            }, 500); // Aguardar 500ms para as chefias serem carregadas
        }

        // Variável global para armazenar o filtro de chefia atual
        let currentChefiaFilter = '';

        // Função para filtrar por chefia usando botões
        function filterByChefiaBtn(chefiaNome) {
            currentChefiaFilter = chefiaNome;
            
            // Atualizar classes dos botões de chefia
            document.querySelectorAll('#chefiaFilterContainer button').forEach(btn => {
                btn.classList.remove('chefia-filter-active');
                btn.classList.add('hover:bg-gray-50');
            });
            
            // Marcar o botão ativo
            if (chefiaNome === '') {
                // Botão "Todas as chefias"
                document.getElementById('chefiaFilterAll').classList.add('chefia-filter-active');
                document.getElementById('chefiaFilterAll').classList.remove('hover:bg-gray-50');
            } else {
                // Encontrar e marcar o botão da chefia específica
                const buttons = document.querySelectorAll('#chefiaBtnContainer button');
                buttons.forEach(btn => {
                    if (btn.textContent === chefiaNome) {
                        btn.classList.add('chefia-filter-active');
                        btn.classList.remove('hover:bg-gray-50');
                    }
                });
            }
            
            // Aplicar filtros
            applyFilters();
        }

        // Função para resetar filtros de chefia
        function resetChefiaFilters() {
            currentChefiaFilter = '';
            // Resetar para "Todas as chefias"
            if (document.getElementById('chefiaFilterAll')) {
                filterByChefiaBtn('');
            }
        }

        function renderTable(data) {
            // Armazenar dados filtrados e atualizar paginação
            filteredData.assuntos = data;
            totalItems.assuntos = data.length;
            currentPage.assuntos = 1; // Reset para primeira página
            
            renderTablePaginated(data, 'assuntosTableBody', 'assuntos');
            createPagination('assuntos', 'assuntosPagination');
        }

        function renderTablePaginated(data, tbodyId, tableType = 'assuntos') {
            const tbody = document.getElementById(tbodyId);
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 md:hidden">Nenhum assunto encontrado</td><td colspan="10" class="px-6 py-4 text-center text-gray-500 hidden md:table-cell">Nenhum assunto encontrado</td></tr>';
                return;
            }
            
            // Obter dados da página atual
            const paginatedData = getPaginatedData(data, tableType);
            
            paginatedData.forEach(assunto => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                
                const acoesResumo = assunto.acoes.map(a => a.acao).join('; ').substring(0, 100) + (assunto.acoes.map(a => a.acao).join('; ').length > 100 ? '...' : '');
                const providenciasResumo = assunto.acoes.map(a => a.providencia).filter(p => p).join('; ').substring(0, 100) + (assunto.acoes.map(a => a.providencia).filter(p => p).join('; ').length > 100 ? '...' : '');
                
                // Formatação do militar responsável: P/Grad Nome
                const militarResponsavel = assunto.criadoPorPg && assunto.criadoPorNome 
                    ? `${assunto.criadoPorPg} ${assunto.criadoPorNome}` 
                    : assunto.criadoPor || '-';
                
                // Limitar tamanho do assunto apenas para mobile
                const assuntoExibido = window.innerWidth <= 768 
                    ? (assunto.assunto.length > 50 ? assunto.assunto.substring(0, 50) + '...' : assunto.assunto)
                    : assunto.assunto;
                
                row.innerHTML = `
                    <td class="px-6 py-4 text-sm text-gray-900 break-words max-w-xs hide-mobile">${assunto.chefia}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 break-words max-w-xs hide-mobile">${militarResponsavel}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 break-words assunto-column" title="${assunto.assunto}">${assuntoExibido}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm hide-mobile">
                        <span class="px-2 py-1 text-xs font-medium rounded-full ${assunto.critico === 'sim' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">
                            ${assunto.critico === 'sim' ? 'Sim' : 'Não'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatDate(assunto.prazo)}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 break-words max-w-md hide-mobile">${acoesResumo || '-'}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 break-words max-w-md hide-mobile">${providenciasResumo || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm hide-mobile">
                        <span class="px-2 py-1 text-xs font-medium rounded-full ${assunto.estado === 'concluido' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                            ${assunto.estado === 'concluido' ? 'Concluído' : 'Pendente'}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 hide-mobile">${formatDate(assunto.dataAtualizacao)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex gap-1 flex-col sm:flex-row sm:gap-2">
                            <button onclick="detalharAssunto(${assunto.id})" class="px-2 py-1 sm:px-3 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                Detalhar
                            </button>
                            <button onclick="editarAssunto(${assunto.id})" class="px-2 py-1 sm:px-3 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                Editar
                            </button>
                            <button onclick="confirmarExclusaoAssunto(${assunto.id}, '${assunto.assunto.substring(0, 50).replace(/'/g, "\\'")}...')" class="px-2 py-1 sm:px-3 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                                Excluir
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Atualizar contador de itens
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            
            // Para evitar problemas de fuso horário, criar a data diretamente dos componentes
            const dateParts = dateString.split('-');
            if (dateParts.length === 3) {
                const year = parseInt(dateParts[0]);
                const month = parseInt(dateParts[1]) - 1; // Mês é 0-indexado no JavaScript
                const day = parseInt(dateParts[2]);
                const date = new Date(year, month, day);
                return date.toLocaleDateString('pt-BR');
            }
            
            // Fallback para formato não esperado
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

        function updateTotalCount(count, tipo = 'registros') {
            document.getElementById('totalCount').textContent = `${count} ${tipo} encontrado${count !== 1 ? 's' : ''}`;
        }

        // Funções de usuários
        function renderUsuariosTable(data) {
            // Filtrar dados baseado no perfil do usuário
            let userFilteredData = data;
            if (currentUser.perfil === 5) { // Cadastro de Usuário
                // Só pode ver usuários da sua chefia
                userFilteredData = data.filter(usuario => usuario.chefia_id === currentUser.chefia_id);
            }
            
            // Armazenar dados filtrados e atualizar paginação
            filteredData.usuarios = userFilteredData;
            totalItems.usuarios = userFilteredData.length;
            currentPage.usuarios = 1; // Reset para primeira página
            
            renderUsuariosTablePaginated(userFilteredData);
            createPagination('usuarios', 'usuariosPagination');
        }

        function renderUsuariosTablePaginated(data) {
            const tbody = document.getElementById('usuariosTableBody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Nenhum usuário encontrado</td></tr>';
                return;
            }
            
            // Obter dados da página atual
            const paginatedData = getPaginatedData(data, 'usuarios');
            
            const perfilLabel = (perfilId) => {
                if (perfilId == 1) return 'Suporte Técnico';
                if (perfilId == 2) return 'Auditor OM/Chefia';
                if (perfilId == 3) return 'Auditor COLOG';
                if (perfilId == 4) return 'Editor';
                if (perfilId == 5) return 'Cadastro de Usuário';
                return '-';
            };
            
            paginatedData.forEach(usuario => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                
                // Verificar se o usuário pode editar este registro
                let canEdit = true;
                if (currentUser.perfil === 5) {
                    // Cadastro de Usuário só pode editar usuários da sua chefia
                    canEdit = usuario.chefia_id === currentUser.chefia_id;
                    
                    // E não pode editar usuários com perfil de Suporte Técnico (perfil 1)
                    if (usuario.perfil_id == 1) {
                        canEdit = false;
                    }
                }
                
                const editButton = canEdit ? 
                    `<button onclick="editUsuario(${usuario.id})" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">Editar</button>` :
                    `<span class="px-3 py-1 bg-gray-300 text-gray-500 text-xs rounded">Sem permissão</span>`;
                
                // Adicionar classe visual para usuários inativos
                const rowClass = usuario.ativo == 0 ? 'opacity-50 bg-gray-50' : '';
                row.className = rowClass;
                
                // Indicador de status do usuário
                const statusBadge = usuario.ativo == 1 ? 
                    '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Ativo</span>' :
                    '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Inativo</span>';
                
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 hide-mobile-sm">${usuario.idt_Mil}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${usuario.pg}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        ${usuario.nome}
                        ${usuario.ativo == 0 ? '<span class="text-red-500 text-xs ml-2">(Inativo)</span>' : ''}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 hide-mobile">${usuario.chefia}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 hide-mobile">${usuario.divisao}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${perfilLabel(usuario.perfil_id)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        ${editButton}
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            // Atualizar contador de itens
        }

        function filterUsuarios() {
            const filters = {
                idt_Mil: document.getElementById('filterIdtMilitar').value.toLowerCase(),
                pg: document.getElementById('filterpg').value.toLowerCase(),
                nome: document.getElementById('filterNomeGuerra').value.toLowerCase(),
                chefia: document.getElementById('filterChefia').value.toLowerCase(),
                divisao: document.getElementById('filterDivisao').value.toLowerCase()
            };

            const filtered = usuarios.filter(usuario => {
                return Object.keys(filters).every(key => {
                    if (!filters[key]) return true;
                    return usuario[key].toLowerCase().includes(filters[key]);
                });
            });
            
            // Aplicar filtro adicional baseado no perfil, mas não na renderização inicial
            // O filtro por perfil já é aplicado na renderUsuariosTable
            renderUsuariosTable(filtered);
            updateTotalCount(filtered.length, 'usuários');
        }

        function updateUsuarioSelects() {
            // Atualizar select de chefias
            const chefiaSelect = document.getElementById('usuarioChefiaSelect');
            chefiaSelect.innerHTML = '<option value="">Selecione uma chefia</option>';
            
            // Verificar se as chefias foram carregadas
            if (!chefias || chefias.length === 0) {
                chefiaSelect.innerHTML = '<option value="">Aguarde... carregando chefias</option>';
                return;
            }
            
            // Para perfil 5 (Cadastro de Usuário), só mostrar sua própria chefia
            let chefiasParaExibir = chefias;
            if (currentUser.perfil === 5 && currentUser.chefia_id !== null && currentUser.chefia_id !== undefined) {
                chefiasParaExibir = chefias.filter(chefia => chefia.id == currentUser.chefia_id); // Usar == em vez de === por causa dos tipos
                
                // Se o filtro não encontrou nenhuma chefia, mostrar todas como fallback
                if (chefiasParaExibir.length === 0) {
                    console.warn('Filtro por chefia_id não encontrou resultados. Mostrando todas as chefias como fallback.');
                    chefiasParaExibir = chefias;
                }
            } else if (currentUser.perfil === 5) {
                console.warn('Perfil 5 mas chefia_id é null/undefined. Mostrando todas as chefias.');
            }
            
            if (chefiasParaExibir.length === 0) {
                if (currentUser.perfil === 5) {
                    chefiaSelect.innerHTML = '<option value="">Sua chefia não foi encontrada. Contate o administrador.</option>';
                } else {
                    chefiaSelect.innerHTML = '<option value="">Nenhuma chefia cadastrada no sistema</option>';
                }
                return;
            }
            
            chefiasParaExibir.forEach(chefia => {
                const option = document.createElement('option');
                option.value = chefia.id;
                option.textContent = chefia.nome;
                chefiaSelect.appendChild(option);
            });
            
            // Remover listeners anteriores e adicionar novo evento de mudança para filtrar divisões
            chefiaSelect.removeEventListener('change', handleChefiaChange);
            chefiaSelect.addEventListener('change', handleChefiaChange);
            
            // Atualizar select de divisões (inicialmente vazio)
            updateDivisoesByChefia('');
        }

        function handleChefiaChange(event) {
            updateDivisoesByChefia(event.target.value);
        }

        function updateDivisoesByChefia(chefiaId) {
            const divisaoSelect = document.getElementById('usuarioDivisaoSelect');
            divisaoSelect.innerHTML = '<option value="">Selecione uma divisão</option>';
            
            // Se nenhuma chefia foi selecionada, mostrar mensagem orientativa
            if (!chefiaId) {
                divisaoSelect.innerHTML = '<option value="">Primeiro selecione uma chefia</option>';
                return;
            }
            
            // Verificar se as divisões foram carregadas
            if (!divisoes || divisoes.length === 0) {
                divisaoSelect.innerHTML = '<option value="">Aguarde... carregando divisões</option>';
                return;
            }
            
            // Filtrar divisões pela chefia selecionada
            const divisoesFiltradas = divisoes.filter(divisao => divisao.chefia_id == chefiaId);
            
            if (divisoesFiltradas.length === 0) {
                divisaoSelect.innerHTML = '<option value="">Nenhuma divisão disponível para esta chefia</option>';
                return;
            }
            
            divisoesFiltradas.forEach(divisao => {
                const option = document.createElement('option');
                option.value = divisao.id;
                option.textContent = divisao.nome;
                divisaoSelect.appendChild(option);
            });
        }

        async function openUsuarioModal(isEdit = false) {
            // Garantir que chefias e divisões estejam carregadas
            if (chefias.length === 0) {
                await fetchChefias();
            }
            if (divisoes.length === 0) {
                await fetchDivisoes();
            }
            
            updateUsuarioSelects();
            document.getElementById('usuarioModal').style.display = 'block';
            
            const senhaField = document.querySelector('#usuarioForm input[name="senha"]');
            
            if (isEdit) {
                document.getElementById('usuarioModalTitle').textContent = 'Editar Usuário';
                document.getElementById('saveUsuarioBtn').textContent = 'Salvar usuário';
                document.getElementById('toggleUsuarioStatusBtn').classList.remove('hidden');
                
                // Atualizar texto do botão baseado no status atual do usuário
                const btnToggle = document.getElementById('toggleUsuarioStatusBtn');
                if (editingUsuario && editingUsuario.ativo == 1) {
                    btnToggle.textContent = 'Desativar Usuário';
                    btnToggle.className = 'px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700';
                } else if (editingUsuario && editingUsuario.ativo == 0) {
                    btnToggle.textContent = 'Ativar Usuário';
                    btnToggle.className = 'px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700';
                } else {
                    // Caso padrão se não houver informação de status
                    btnToggle.textContent = 'Desativar Usuário';
                    btnToggle.className = 'px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700';
                }
                
                // Na edição, mostrar campo de senha tradicional
                const senhaContainer = senhaField.closest('div');
                senhaContainer.innerHTML = `
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nova Senha (opcional)</label>
                    <input type="password" name="senha" placeholder="Deixe em branco para manter a senha atual" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                `;
            } else {
                document.getElementById('usuarioModalTitle').textContent = 'Adicionar Novo Usuário';
                document.getElementById('saveUsuarioBtn').textContent = 'Adicionar usuário';
                document.getElementById('toggleUsuarioStatusBtn').classList.add('hidden');
                editingUsuario = null;
                
                // Na criação, mostrar mensagem sobre senha padrão
                const senhaContainer = senhaField.closest('div');
                senhaContainer.innerHTML = `
                    <label class="block text-sm font-medium text-gray-700 mb-2">Senha Padrão</label>
                    <div class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                        A senha padrão será igual à Identidade Militar
                    </div>
                    <p class="text-xs text-gray-500 mt-1">O usuário será obrigado a alterar a senha no primeiro login</p>
                    <input type="hidden" name="senha" value="default">
                `;
            }
            
            // Para perfil 5 (Cadastro de Usuário), pré-selecionar e desabilitar a chefia
            if (currentUser.perfil === 5) {
                const chefiaSelect = document.getElementById('usuarioChefiaSelect');
                
                // Aguardar um pequeno delay para garantir que o select foi populado
                setTimeout(() => {
                    chefiaSelect.value = currentUser.chefia_id;
                    // Em vez de disabled, fazer readonly visualmente mas funcional
                    chefiaSelect.style.backgroundColor = '#f3f4f6';
                    chefiaSelect.style.cursor = 'not-allowed';
                    // Prevenir mudanças mas permitir que o valor seja enviado
                    chefiaSelect.addEventListener('mousedown', (e) => e.preventDefault());
                    chefiaSelect.addEventListener('keydown', (e) => e.preventDefault());
                    
                    // Disparar evento change para atualizar divisões
                    chefiaSelect.dispatchEvent(new Event('change'));
                }, 100);
                
                // Restringir opções de perfil apenas quando criando novo usuário
                if (!isEdit) {
                    const perfilSelect = document.getElementById('usuarioPerfilSelect');
                    perfilSelect.innerHTML = `
                        <option value="">Selecione o perfil</option>
                        <option value="2">Auditor OM/Chefia</option>
                        <option value="4">Editor</option>
                    `;
                } else {
                    // Na edição, usuário perfil 5 tem restrições específicas
                    const perfilSelect = document.getElementById('usuarioPerfilSelect');
                    const usuarioAtual = editingUsuario;
                    
                    // Verificar se está editando seu próprio perfil ou perfil de Suporte Técnico
                    const isEditandoProprioUsuario = usuarioAtual && usuarioAtual.perfil_id == 5;
                    const isEditandoSuporteTecnico = usuarioAtual && usuarioAtual.perfil_id == 1;
                    
                    if (isEditandoProprioUsuario) {
                        // Não pode alterar seu próprio perfil - desabilitar select
                        perfilSelect.innerHTML = `<option value="5">Cadastro de Usuário (não editável)</option>`;
                        perfilSelect.disabled = true;
                        perfilSelect.style.backgroundColor = '#f3f4f6';
                        perfilSelect.style.cursor = 'not-allowed';
                    } else if (isEditandoSuporteTecnico) {
                        // Não pode alterar perfil de Suporte Técnico - desabilitar select
                        perfilSelect.innerHTML = `<option value="1">Suporte Técnico (não editável)</option>`;
                        perfilSelect.disabled = true;
                        perfilSelect.style.backgroundColor = '#f3f4f6';
                        perfilSelect.style.cursor = 'not-allowed';
                    } else {
                        // Para outros usuários, pode editar normalmente (mas só perfis 2 e 4)
                        let optionsHTML = `<option value="">Selecione o perfil</option>`;
                        
                        // Sempre incluir as opções que podem criar
                        optionsHTML += `<option value="2">Auditor OM/Chefia</option>`;
                        optionsHTML += `<option value="4">Editor</option>`;
                        
                        // Se o usuário atual tem perfil 3 (Auditor COLOG), incluir para manter a consistência
                        if (usuarioAtual && usuarioAtual.perfil_id == 3) {
                            optionsHTML += `<option value="3">Auditor COLOG (atual)</option>`;
                        }
                        
                        perfilSelect.innerHTML = optionsHTML;
                        perfilSelect.disabled = false;
                        perfilSelect.style.backgroundColor = '';
                        perfilSelect.style.cursor = '';
                    }
                }
            } else {
                // Reabilitar o select para outros perfis e restaurar todas as opções
                const chefiaSelect = document.getElementById('usuarioChefiaSelect');
                chefiaSelect.disabled = false;
                chefiaSelect.style.backgroundColor = '';
                chefiaSelect.style.cursor = '';
                // Remover listeners de prevenção se existirem
                chefiaSelect.removeEventListener('mousedown', (e) => e.preventDefault());
                chefiaSelect.removeEventListener('keydown', (e) => e.preventDefault());
                
                const perfilSelect = document.getElementById('usuarioPerfilSelect');
                perfilSelect.innerHTML = `
                    <option value="">Selecione o perfil</option>
                    <option value="1">Suporte Técnico</option>
                    <option value="2">Auditor OM/Chefia</option>
                    <option value="3">Auditor COLOG</option>
                    <option value="4">Editor</option>
                    <option value="5">Cadastro de Usuário</option>
                `;
            }
        }

        function closeUsuarioModal() {
            document.getElementById('usuarioModal').style.display = 'none';
            document.getElementById('usuarioForm').reset();
            editingUsuario = null;
            
            // Resetar o campo de senha para o estado padrão de criação
            const senhaField = document.querySelector('#usuarioForm input[name="senha"]');
            const senhaContainer = senhaField.closest('div');
            senhaContainer.innerHTML = `
                <label class="block text-sm font-medium text-gray-700 mb-2">Senha Padrão</label>
                <div class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                    A senha padrão será igual à Identidade Militar
                </div>
                <p class="text-xs text-gray-500 mt-1">O usuário será obrigado a alterar a senha no primeiro login</p>
                <input type="hidden" name="senha" value="default">
            `;
            
            // Resetar estilo do select de chefia
            const chefiaSelect = document.getElementById('usuarioChefiaSelect');
            chefiaSelect.disabled = false;
            chefiaSelect.style.backgroundColor = '';
            chefiaSelect.style.cursor = '';
            
            // Resetar estilo do select de perfil
            const perfilSelect = document.getElementById('usuarioPerfilSelect');
            perfilSelect.disabled = false;
            perfilSelect.style.backgroundColor = '';
            perfilSelect.style.cursor = '';
            
            // Resetar o select de divisões para o estado inicial
            updateDivisoesByChefia('');
        }

        async function editUsuario(usuarioId) {
            const usuario = usuarios.find(u => u.id == usuarioId);
            
            if (!usuario) {
                return;
            }
            
            // Verificar permissões para perfil 5 (Cadastro de Usuário)
            if (currentUser.perfil === 5) {
                // Não pode editar usuários fora da sua chefia
                if (usuario.chefia_id !== currentUser.chefia_id) {
                    alert('Você só pode editar usuários da sua chefia.');
                    return;
                }
                
                // Não pode editar usuários com perfil de Suporte Técnico (perfil 1)
                if (usuario.perfil_id == 1) {
                    alert('Você não tem permissão para editar usuários com perfil de Suporte Técnico.');
                    return;
                }
            }

            editingUsuario = usuario;
            
            // Abrir modal primeiro para que os selects sejam populados corretamente
            await openUsuarioModal(true);
            
            // Agora preencher os campos
            document.getElementById('usuarioId').value = usuario.id;
            document.querySelector('#usuarioForm input[name="idt_Mil"]').value = usuario.idt_Mil;
            document.querySelector('#usuarioForm select[name="pg"]').value = usuario.pg;
            document.querySelector('#usuarioForm input[name="nome"]').value = usuario.nome;
            // Não preencher senha - o campo será configurado corretamente pelo openUsuarioModal
            
            // Aguardar um pouco mais para garantir que o select de perfil foi populado corretamente
            setTimeout(() => {
                document.getElementById('usuarioPerfilSelect').value = usuario.perfil_id || '';
                
                const chefiaSelect = document.querySelector('#usuarioForm select[name="chefia"]');
                chefiaSelect.value = usuario.chefia_id;
                chefiaSelect.dispatchEvent(new Event('change'));
                
                setTimeout(() => {
                    document.querySelector('#usuarioForm select[name="divisao"]').value = usuario.divisao_id;
                }, 50);
            }, 150);
        }

        // Funções do modal Minha Conta
        function openContaModal() {
            // Fechar o menu de configurações
            document.getElementById('configMenu').classList.add('hidden');
            
            // Mostrar o modal primeiro
            document.getElementById('contaModal').style.display = 'block';
            
            // Preencher os campos com os dados do usuário logado
            carregarDadosUsuarioLogado();
        }

        function closeContaModal() {
            document.getElementById('contaModal').style.display = 'none';
            document.getElementById('contaForm').reset();
        }

        async function carregarDadosUsuarioLogado() {
            try {
                // Requisição para buscar dados do usuário logado
                const response = await fetch('api/get_usuario_logado.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const userData = await response.json();
                
                if (userData.success) {
                    document.getElementById('contaIdtMil').value = userData.user.idt_Mil || '';
                    document.getElementById('contaPg').value = userData.user.pg || '';
                    document.getElementById('contaNome').value = userData.user.nome || '';
                    document.getElementById('contaPerfil').value = userData.user.perfil_nome || 'Não definido';
                    document.getElementById('contaChefia').value = userData.user.chefia_nome || 'Não definido';
                    document.getElementById('contaDivisao').value = userData.user.divisao_nome || 'Não definido';
                } else {
                    alert('Erro ao carregar dados do usuário: ' + userData.message);
                }
            } catch (error) {
                alert('Erro ao conectar com o servidor. Verifique sua conexão e tente novamente.');
            }
        }

        // Event listener para o formulário de primeiro login
        document.getElementById('primeiroLoginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const novaSenha = formData.get('nova_senha');
            const confirmarSenha = formData.get('confirmar_senha');
            
            // Validar se as senhas coincidem
            if (novaSenha !== confirmarSenha) {
                alert('As senhas não coincidem. Tente novamente.');
                return;
            }
            
            // Validar tamanho mínimo da senha
            if (novaSenha.length < 6) {
                alert('A nova senha deve ter pelo menos 6 caracteres.');
                return;
            }
            
            try {
                const response = await fetch('api/alterar_senha_primeiro_login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Senha alterada com sucesso!');
                    // Fechar modal e reabilitar página
                    document.getElementById('primeiroLoginModal').style.display = 'none';
                    document.body.style.overflow = 'auto';
                    // Atualizar flag de primeiro login
                    currentUser.primeiro_login = false;
                } else {
                    alert('Erro ao alterar senha: ' + result.message);
                }
            } catch (error) {
                alert('Erro ao conectar com o servidor. Tente novamente.');
            }
        });

        // Event listener para o formulário da conta
        document.getElementById('contaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('api/update_minha_conta.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {                   
                    // Atualizar informações na interface
                    const novoNome = formData.get('nome');
                    const novoPg = formData.get('pg');
                    if (novoNome) {
                        currentUser.nome = novoNome;
                    }
                    if (novoPg) {
                        currentUser.pg = novoPg;
                    }
                    updateUserInfo();
                    
                    closeContaModal();
                } else {
                    alert('Erro ao atualizar dados: ' + result.message);
                }
            } catch (error) {
                alert('Erro ao atualizar dados. Tente novamente.');
            }
        });

        // Funções de chefias
        function renderChefiasTable(data) {
            // Filtrar dados baseado no perfil do usuário
            let chefiaFilteredData = data;
            if (currentUser.perfil === 5) { // Cadastro de Usuário
                // Só pode ver sua própria chefia
                chefiaFilteredData = data.filter(chefia => chefia.id === currentUser.chefia_id);
            }
            
            // Armazenar dados filtrados e atualizar paginação
            filteredData.chefias = chefiaFilteredData;
            totalItems.chefias = chefiaFilteredData.length;
            currentPage.chefias = 1; // Reset para primeira página
            
            renderChefiasTablePaginated(chefiaFilteredData);
            createPagination('chefias', 'chefiasPagination');
        }

        function renderChefiasTablePaginated(data) {
            const tbody = document.getElementById('chefiasTableBody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="2" class="px-6 py-4 text-center text-gray-500">Nenhuma chefia encontrada</td></tr>';
                return;
            }
            
            // Obter dados da página atual
            const paginatedData = getPaginatedData(data, 'chefias');
            
            paginatedData.forEach(chefia => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                
                // Verificar se o usuário pode editar este registro
                let canEdit = true;
                if (currentUser.perfil === 5) {
                    // Cadastro de Usuário não pode editar chefias
                    canEdit = false;
                }
                
                const editButton = canEdit ? 
                    `<button onclick="editChefia(${chefia.id})" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">Editar</button>` :
                    `<span class="px-3 py-1 bg-gray-300 text-gray-500 text-xs rounded">Sem permissão</span>`;
                
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${chefia.nome}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        ${editButton}
                    </td>
                `;
                
                tbody.appendChild(row);
            });
            
            // Atualizar contador de itens
        }

        function openChefiaModal(isEdit = false) {
            document.getElementById('chefiaModal').style.display = 'block';
            
            if (isEdit) {
                document.getElementById('chefiaModalTitle').textContent = 'Editar OM/Chefia';
                document.getElementById('saveChefiaBtn').textContent = 'Salvar OM/Chefia';
                document.getElementById('deleteChefiaBtn').classList.remove('hidden');
            } else {
                document.getElementById('chefiaModalTitle').textContent = 'Adicionar Nova OM/Chefia';
                document.getElementById('saveChefiaBtn').textContent = 'Adicionar OM/Chefia';
                document.getElementById('deleteChefiaBtn').classList.add('hidden');
                editingChefia = null;
            }
        }

        function closeChefiaModal() {
            document.getElementById('chefiaModal').style.display = 'none';
            document.getElementById('chefiaForm').reset();
            editingChefia = null;
        }

        function editChefia(chefiaId) {
            const chefia = chefias.find(c => c.id == chefiaId);
            if (!chefia) {
                return;
            }

            editingChefia = chefia;
            
            document.getElementById('chefiaId').value = chefia.id;
            document.querySelector('#chefiaForm input[name="chefia"]').value = chefia.nome;

            openChefiaModal(true);
        }

        // Funções de divisões
        function renderDivisoesTable(data) {
            // Filtrar dados baseado no perfil do usuário
            let divisaoFilteredData = data;
            if (currentUser.perfil === 5) { // Cadastro de Usuário
                // Só pode ver divisões da sua chefia
                divisaoFilteredData = data.filter(divisao => divisao.chefia_id === currentUser.chefia_id);
            }
            
            // Armazenar dados filtrados e atualizar paginação
            filteredData.divisoes = divisaoFilteredData;
            totalItems.divisoes = divisaoFilteredData.length;
            currentPage.divisoes = 1; // Reset para primeira página
            
            renderDivisoesTablePaginated(divisaoFilteredData);
            createPagination('divisoes', 'divisoesPagination');
        }

        function renderDivisoesTablePaginated(data) {
            const tbody = document.getElementById('divisoesTableBody');
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">Nenhuma divisão encontrada</td></tr>';
                return;
            }
            
            // Obter dados da página atual
            const paginatedData = getPaginatedData(data, 'divisoes');
            
            paginatedData.forEach(divisao => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                
                // Encontrar a chefia correspondente
                const chefia = chefias.find(c => c.id === divisao.chefia_id);
                const chefiaNome = chefia ? chefia.nome : '-';
                
                // Verificar se o usuário pode editar este registro
                let canEdit = true;
                if (currentUser.perfil === 5) {
                    // Cadastro de Usuário só pode editar divisões da sua chefia
                    canEdit = divisao.chefia_id === currentUser.chefia_id;
                }
                
                const editButton = canEdit ? 
                    `<button onclick="editDivisao(${divisao.id})" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700"> Editar </button>` :
                    `<span class="px-3 py-1 bg-gray-300 text-gray-500 text-xs rounded">Sem permissão</span>`;
                
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${divisao.nome}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${divisao.chefia_nome || '-'}</td> 
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        ${editButton}
                    </td>
                `;
                tbody.appendChild(row);
            });
            
            // Atualizar contador de itens
        }

        function updateChefiaSelect() {
            const select = document.getElementById('divisaoChefiaSelect');
            select.innerHTML = '<option value="">Selecione uma chefia</option>';
            
            // Verificar se as chefias foram carregadas
            if (!chefias || chefias.length === 0) {
                select.innerHTML = '<option value="">Aguarde... carregando chefias</option>';
                return;
            }
            
            // Para perfil 5 (Cadastro de Usuário), só mostrar sua própria chefia
            let chefiasParaExibir = chefias;
            if (currentUser.perfil === 5) {
                // Usar comparação flexível para lidar com tipos diferentes
                chefiasParaExibir = chefias.filter(chefia => chefia.id == currentUser.chefia_id);
            }
            
            chefiasParaExibir.forEach(chefia => {
                const option = document.createElement('option');
                option.value = chefia.id;
                option.textContent = chefia.nome || chefia.chefia;
                select.appendChild(option);
            });
        }

        async function openDivisaoModal(isEdit = false) {
            // Garantir que chefias estejam carregadas
            if (chefias.length === 0) {
                await fetchChefias();
            }
            
            updateChefiaSelect();
            
            if (chefias.length === 0) {
                alert('É necessário cadastrar pelo menos uma chefia antes de adicionar divisões.');
                return;
            }
            
            document.getElementById('divisaoModal').style.display = 'block';
            
            if (isEdit) {
                document.getElementById('divisaoModalTitle').textContent = 'Editar Divisão';
                document.getElementById('saveDivisaoBtn').textContent = 'Salvar divisão';
                document.getElementById('deleteDivisaoBtn').classList.remove('hidden');
            } else {
                document.getElementById('divisaoModalTitle').textContent = 'Adicionar Nova Divisão';
                document.getElementById('saveDivisaoBtn').textContent = 'Adicionar divisão';
                document.getElementById('deleteDivisaoBtn').classList.add('hidden');
                editingDivisao = null;
            }
            
            // Para perfil 5 (Cadastro de Usuário), pré-selecionar a chefia e torná-la visualmente não editável
            if (currentUser.perfil === 5) {
                // Aguardar um pequeno delay para garantir que o select foi populado
                setTimeout(() => {
                    const chefiaSelect = document.getElementById('divisaoChefiaSelect');
                    chefiaSelect.value = currentUser.chefia_id;
                    
                    // Em vez de disabled, fazer readonly visualmente mas funcional
                    chefiaSelect.style.backgroundColor = '#f3f4f6';
                    chefiaSelect.style.cursor = 'not-allowed';
                    // Prevenir mudanças mas permitir que o valor seja enviado
                    chefiaSelect.addEventListener('mousedown', (e) => e.preventDefault());
                    chefiaSelect.addEventListener('keydown', (e) => e.preventDefault());
                }, 100);
            } else {
                // Reabilitar o select para outros perfis
                const chefiaSelect = document.getElementById('divisaoChefiaSelect');
                chefiaSelect.disabled = false;
                chefiaSelect.style.backgroundColor = '';
                chefiaSelect.style.cursor = '';
                // Remover listeners de prevenção se existirem
                chefiaSelect.removeEventListener('mousedown', (e) => e.preventDefault());
                chefiaSelect.removeEventListener('keydown', (e) => e.preventDefault());
            }
        }

        function closeDivisaoModal() {
            document.getElementById('divisaoModal').style.display = 'none';
            document.getElementById('divisaoForm').reset();
            editingDivisao = null;
            
            // Resetar estilo do select de chefia
            const chefiaSelect = document.getElementById('divisaoChefiaSelect');
            chefiaSelect.disabled = false;
            chefiaSelect.style.backgroundColor = '';
            chefiaSelect.style.cursor = '';
            // Remover listeners de prevenção se existirem
            const preventHandler = (e) => e.preventDefault();
            chefiaSelect.removeEventListener('mousedown', preventHandler);
            chefiaSelect.removeEventListener('keydown', preventHandler);
        }

        async function editDivisao(divisaoId) {
            const divisao = divisoes.find(d => d.id == divisaoId);
            if (!divisao) {
                return;
            }
            
            // Verificar permissões para perfil 5 (Cadastro de Usuário)
            if (currentUser.perfil === 5 && divisao.chefia_id !== currentUser.chefia_id) {
                alert('Você só pode editar divisões da sua chefia.');
                return;
            }

            editingDivisao = divisao;
            
            document.getElementById('divisaoId').value = divisao.id;
            document.querySelector('#divisaoForm input[name="nome"]').value = divisao.nome;
            
            setTimeout(() => {
                document.getElementById('divisaoChefiaSelect').value = divisao.chefia_id;
            }, 100);

            await openDivisaoModal(true);
        }

        // Modal functions
        function openModal() {
            document.getElementById('acoesContainerAdd').innerHTML = '';
            contadorAcoesAdd = 0;
            document.getElementById('addAssuntoModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('addAssuntoModal').style.display = 'none';
            document.getElementById('acoesContainerAdd').innerHTML = '';
            contadorAcoesAdd = 0;
        }

        // Event listeners
        document.getElementById('addAssuntoBtn').addEventListener('click', openModal);
        
        document.getElementById('addUsuarioBtn').addEventListener('click', async () => await openUsuarioModal(false));
        document.getElementById('addChefiaBtn').addEventListener('click', () => openChefiaModal(false));
        document.getElementById('addDivisaoBtn').addEventListener('click', async () => await openDivisaoModal(false));

        document.getElementById('configBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('configMenu').classList.toggle('hidden');
        });

        document.addEventListener('click', function() {
            document.getElementById('configMenu').classList.add('hidden');
        });

        document.getElementById('logoffBtn').addEventListener('click', function() {
            if (confirm('Deseja realmente sair do sistema?')) {
                window.location.href = 'logout.php';
            }
        });

        document.getElementById('dataInicio').addEventListener('change', applyFilters);
        document.getElementById('dataFim').addEventListener('change', applyFilters);

        document.getElementById('usuarioForm').addEventListener('submit', function(e) {
            e.preventDefault();
            (async () => {
                const formData = new FormData(this);
                
                const usuarioData = {
                    id: formData.get('id'),
                    idt_Mil: formData.get('idt_Mil'),
                    pg: formData.get('pg'),
                    nome: formData.get('nome'),
                    chefia_id: formData.get('chefia'), // Enviar como chefia_id
                    divisao_id: formData.get('divisao'), // Enviar como divisao_id
                    perfil_id: formData.get('perfil_id')
                };
                
                // Para perfil 5, se os campos estiverem vazios devido ao disabled, usar os valores corretos
                if (currentUser.perfil === 5) {
                    if (!usuarioData.chefia_id) {
                        usuarioData.chefia_id = currentUser.chefia_id;
                    }
                }
                
                // Verificação adicional dos campos obrigatórios
                if (!usuarioData.chefia_id) {
                    alert('Por favor, selecione uma chefia.');
                    return;
                }
                if (!usuarioData.divisao_id) {
                    alert('Por favor, selecione uma divisão.');
                    return;
                }
                if (!usuarioData.perfil_id) {
                    alert('Por favor, selecione um perfil.');
                    return;
                }
                
                // Só inclui senha se não estiver vazia ou se for um novo usuário
                const senha = formData.get('senha');
                if (!editingUsuario || (senha && senha.trim() !== '')) {
                    usuarioData.senha = senha;
                }
                
                try {
                    const url = editingUsuario ? 'api/edit_usuario.php' : 'api/add_usuario.php';
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(usuarioData)
                    });
                    if (response.ok) {
                        await fetchUsuarios();
                        closeUsuarioModal();
                    } else {
                        const error = await response.json();
                        alert('Erro ao adicionar usuário: ' + (error.error || 'Erro desconhecido.'));
                    }
                } catch (err) {
                    alert('Erro ao conectar com o servidor.');
                }
            })();
        });

        document.getElementById('toggleUsuarioStatusBtn').addEventListener('click', async function() {
            if (editingUsuario) {
                const acao = editingUsuario.ativo == 1 ? 'desativar' : 'ativar';
                const confirmMessage = `Tem certeza que deseja ${acao} o usuário "${editingUsuario.nome}"?`;
                
                if (confirm(confirmMessage)) {
                    try {
                        const response = await fetch('api/toggle_usuario_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ id: editingUsuario.id })
                        });
                        
                        const result = await response.json();
                        
                        if (response.ok) {
                            alert(result.message);
                            await fetchUsuarios(); // Recarrega a lista do servidor
                            closeUsuarioModal();
                        } else {
                            alert('Erro ao alterar status do usuário: ' + (result.error || 'Erro desconhecido'));
                        }
                    } catch (err) {
                        alert('Erro ao conectar com o servidor');
                    }
                }
            }
        });

        document.getElementById('chefiaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const chefiaData = {
                nome: formData.get('chefia')
            };
            
            // Se estiver editando, inclui o ID
            if (editingChefia) {
                chefiaData.id = editingChefia.id;
            }
            
            try {
                const response = await fetch(`api/${editingChefia ? 'edit_chefia.php' : 'add_chefia.php'}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(chefiaData)
                });

                const data = await response.json();
                
                if (response.ok) {
                    await fetchChefias(); // Recarrega as chefias do servidor
                    closeChefiaModal();
                } else {
                    alert('Erro ao salvar chefia: ' + (data.error || 'Erro desconhecido'));
                }
            } catch (err) {
                alert('Erro ao conectar com o servidor');
            }
        });

        document.getElementById('deleteChefiaBtn').addEventListener('click', async function() {
            if (editingChefia && confirm('Tem certeza que deseja excluir esta chefia?')) {
                try {
                    const response = await fetch('api/delete_chefia.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ id: editingChefia.id })
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok) {
                        await fetchChefias(); // Recarrega a lista do servidor
                        closeChefiaModal();
                    } else {
                        alert('Erro ao excluir chefia: ' + (result.error || 'Erro desconhecido'));
                    }
                } catch (err) {
                    console.error('Erro:', err);
                    alert('Erro ao conectar com o servidor');
                }
            }
        });

        document.getElementById('divisaoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const divisaoData = {
                nome: formData.get('nome'),
                chefia_id: formData.get('chefia_id')
            };
            
            // Para perfil 5, se o campo chefia_id estiver vazio devido ao disabled, usar o valor correto
            if (currentUser.perfil === 5 && !divisaoData.chefia_id) {
                divisaoData.chefia_id = currentUser.chefia_id;
            }
            
            // Verificação adicional do campo obrigatório
            if (!divisaoData.chefia_id) {
                alert('Por favor, selecione uma chefia.');
                return;
            }
            
            // Se estiver editando, inclui o ID
            if (editingDivisao) {
                divisaoData.id = editingDivisao.id;
            }
            
            try {
                const url = editingDivisao ? 'api/edit_divisao.php' : 'api/add_divisao.php';
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(divisaoData)
                });

                const data = await response.json();
                
                if (response.ok) {
                    await fetchDivisoes(); // Recarrega as divisões do servidor
                    closeDivisaoModal();
                    alert(editingDivisao ? 'Divisão atualizada com sucesso!' : 'Divisão adicionada com sucesso!');
                } else {
                    alert('Erro ao salvar divisão: ' + (data.error || 'Erro desconhecido'));
                }
            } catch (err) {
                console.error('Erro na requisição:', err);
                alert('Erro ao conectar com o servidor');
            }
        });

        document.getElementById('deleteDivisaoBtn').addEventListener('click', async function() {
            if (editingDivisao && confirm('Tem certeza que deseja excluir esta divisão?')) {
                try {
                    const response = await fetch('api/delete_divisao.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ id: editingDivisao.id })
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok) {
                        await fetchDivisoes(); // Recarrega a lista do servidor
                        closeDivisaoModal();
                        alert('Divisão excluída com sucesso!');
                    } else {
                        alert('Erro ao excluir divisão: ' + (result.error || 'Erro desconhecido'));
                    }
                } catch (err) {
                    console.error('Erro:', err);
                    alert('Erro ao conectar com o servidor');
                }
            }
        });

        // Click outside modals to close
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        };

    document.getElementById('addAssuntoForm').addEventListener('submit', async function(event) {
        event.preventDefault(); // Impede o envio padrão do formulário

        const formData = new FormData(this); // Coleta todos os dados do formulário
        const assuntoData = {};
        formData.forEach((value, key) => {
            assuntoData[key] = value;
        });

        // Coleta dados das ações dinâmicas (se existirem)
        assuntoData.acoes = [];
        document.querySelectorAll('#acoesContainerAdd > div').forEach((acaoDiv, idx) => {
            const acao = acaoDiv.querySelector('textarea[name^="acoes["][name$="[acao]"]')?.value || '';
            const providencia = acaoDiv.querySelector('textarea[name^="acoes["][name$="[providencia]"]')?.value || '';
            const estado = acaoDiv.querySelector('select[name^="acoes["][name$="[estado]"]')?.value || '';
            if (acao && estado) {
                assuntoData.acoes.push({ acao, providencia, estado });
            }
        });
        try {
            const response = await fetch('api/add_assunto.php', { // Endpoint para salvar o assunto
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(assuntoData)
            });
            if (response.ok) {
                const result = await response.json();
                closeModal(); // Fecha o modal após o sucesso
                fetchAssuntos(); // Atualiza a lista de assuntos
            } else {
                const error = await response.text();
                alert('Erro ao adicionar assunto: ' + error);
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao conectar com o servidor.');
        }
        document.getElementById('addAssuntoForm').reset();
    });
    

// Variáveis globais para edição
let assuntoAtual = null;
let contadorAcoes = 0;

// Função para detalhar assunto
function detalharAssunto(assuntoId) {
    assuntoAtual = assuntos.find(a => a.id === assuntoId);
    if (!assuntoAtual) return;

    document.getElementById('detalharAssuntoTitle').textContent = `Detalhes: ${assuntoAtual.assunto}`;
    
    const content = document.getElementById('detalharAssuntoContent');
    content.innerHTML = `
        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div><strong>Chefia:</strong> ${assuntoAtual.chefia}</div>
                <div><strong>Divisão:</strong> ${assuntoAtual.divisao}</div>
                <div><strong>Prazo:</strong> ${formatDate(assuntoAtual.prazo)}</div>
                <div><strong>Estado:</strong> <span class="px-2 py-1 text-xs rounded-full ${assuntoAtual.estado === 'concluido' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">${assuntoAtual.estado === 'concluido' ? 'Concluído' : 'Pendente'}</span></div>
                <div><strong>Crítico:</strong> <span class="px-2 py-1 text-xs rounded-full ${assuntoAtual.critico === 'sim' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">${assuntoAtual.critico === 'sim' ? 'Sim' : 'Não'}</span></div>
            </div>
        </div>
        
        <div>
            <h4 class="text-lg font-semibold mb-4">Ações e Providências</h4>
            <div class="space-y-4">
                ${assuntoAtual.acoes.map(acao => {
                    // Usar os dados do usuário responsável que já vêm da API
                    const nomeUsuario = acao.responsavelPg && acao.responsavelNome 
                        ? `${acao.responsavelPg} ${acao.responsavelNome}` 
                        : (acao.responsavel || 'Responsável não definido');
                    
                    return `
                        <div class="action-row">
                            <div class="grid grid-cols-1 gap-3">
                                <div>
                                    <strong>Ação:</strong>
                                    <p class="text-sm text-gray-700 break-words">${acao.acao}</p>
                                </div>
                                <div>
                                    <strong>Providência:</strong>
                                    <p class="text-sm text-gray-700 break-words">${acao.providencia || 'Nenhuma providência registrada'}</p>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <div>
                                        <strong>Estado:</strong> 
                                        <span class="px-2 py-1 text-xs rounded-full ${acao.estado === 'concluido' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                            ${acao.estado === 'concluido' ? 'Concluído' : 'Pendente'}
                                        </span>
                                    </div>
                                    <div class="text-gray-600">
                                        <strong>Responsável:</strong> ${nomeUsuario} | 
                                        <strong>Atualizado em:</strong> ${formatDate(acao.dataAtualizacao)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
    
    // Sempre mostrar seção de notas de auditoria para todos os perfis
    document.getElementById('notasAuditoriaSection').style.display = 'block';
    carregarNotasAuditoria(assuntoId);
    
    // Controlar visibilidade do formulário de adicionar nota (apenas Auditores)
    const adicionarNotaForm = document.getElementById('adicionarNotaForm');
    if (currentUser.perfil === 2 || currentUser.perfil === 3) { // Auditor OM/Chefia ou Auditor COLOG
        adicionarNotaForm.style.display = 'block';
    } else {
        adicionarNotaForm.style.display = 'none';
    }
    
    document.getElementById('detalharAssuntoModal').style.display = 'block';
}

// Função para editar assunto
function editarAssunto(assuntoId) {
    assuntoAtual = assuntos.find(a => a.id === assuntoId);
    if (!assuntoAtual) return;

    // Garantir que o array de ações existe
    if (!assuntoAtual.acoes) {
        assuntoAtual.acoes = [];
    }

    // Preencher campos básicos
    document.getElementById('editAssuntoId').value = assuntoAtual.id;
    document.getElementById('editAssunto').value = assuntoAtual.assunto;
    document.getElementById('editPrazo').value = assuntoAtual.prazo;
    document.getElementById('editEstado').value = assuntoAtual.estado;
    
    if (assuntoAtual.critico === 'sim') {
        document.getElementById('editCriticoSim').checked = true;
    } else {
        document.getElementById('editCriticoNao').checked = true;
    }

    // Carregar ações
    carregarAcoesEdicao();
    
    document.getElementById('editarAssuntoModal').style.display = 'block';
}

// Função para carregar ações na edição
function carregarAcoesEdicao() {
    const container = document.getElementById('acoesContainer');
    container.innerHTML = '';
    contadorAcoes = 0; // Reset contador

    // Garantir que existe um array de ações
    if (!assuntoAtual.acoes || !Array.isArray(assuntoAtual.acoes)) {
        assuntoAtual.acoes = [];
    }

    assuntoAtual.acoes.forEach((acao, index) => {
        adicionarAcaoEdicao(acao, index, true);
        contadorAcoes++; // Incrementa contador para cada ação carregada
    });
}

// Função para adicionar ação na edição
function adicionarAcaoEdicao(acao = null, index = null, isReadOnly = true) {
    const container = document.getElementById('acoesContainer');
    const acaoId = index !== null ? index : contadorAcoes++;
    
    const acaoDiv = document.createElement('div');
    acaoDiv.className = 'border border-gray-200 rounded-lg p-4';
    acaoDiv.id = `acao_${acaoId}`;
    
    if (isReadOnly && acao) {
        // Modo visualização (read-only)
        acaoDiv.innerHTML = `
            <div class="flex justify-between items-center mb-3">
                <h5 class="font-medium">Ação ${typeof acaoId === 'string' ? acaoId.replace('nova_', 'Nova ') : (acaoId + 1)}</h5>
                <div class="flex gap-2">
                    <button type="button" onclick="editarAcaoExistente('${acaoId}')" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                        Editar ação
                    </button>
                    <button type="button" onclick="removerAcao('${acaoId}')" class="text-red-600 hover:text-red-800 text-sm">
                        Remover
                    </button>
                </div>
            </div>
            <div class="space-y-3 bg-gray-50 p-3 rounded">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ação a realizar</label>
                    <p class="text-sm text-gray-800">${acao.acao}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Providência adotada</label>
                    <p class="text-sm text-gray-800">${acao.providencia || 'Nenhuma providência registrada'}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado da ação</label>
                    <span class="px-2 py-1 text-xs rounded-full ${acao.estado === 'concluido' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                        ${acao.estado === 'concluido' ? 'Concluído' : 'Pendente'}
                    </span>
                </div>
            </div>
        `;
    } else {
        // Modo edição
        acaoDiv.innerHTML = `
            <div class="flex justify-between items-center mb-3">
                <h5 class="font-medium">Ação ${typeof acaoId === 'string' ? acaoId.replace('nova_', 'Nova ') : (acaoId + 1)}</h5>
                <div class="flex gap-2">
                    <button type="button" onclick="salvarAcao('${acaoId}')" class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                        Salvar ação
                    </button>
                    <button type="button" onclick="cancelarEdicaoAcao('${acaoId}')" class="text-red-600 hover:text-red-800 text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ação a realizar</label>
                    <textarea name="acao_${acaoId}" rows="2" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">${acao ? acao.acao : ''}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Providência adotada</label>
                    <textarea name="providencia_${acaoId}" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">${acao ? acao.providencia : ''}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado da ação</label>
                    <select name="estado_acao_${acaoId}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="pendente" ${acao && acao.estado === 'pendente' ? 'selected' : ''}>Pendente</option>
                        <option value="concluido" ${acao && acao.estado === 'concluido' ? 'selected' : ''}>Concluído</option>
                    </select>
                </div>
            </div>
        `;
    }
    
    container.appendChild(acaoDiv);
}

// Função para adicionar nova ação
function adicionarNovaAcao() {
    // Usar o comprimento real do array (sem elementos null) para o contador
    const acoesAtivas = assuntoAtual.acoes.filter(acao => acao !== null);
    const novoContador = acoesAtivas.length;
    
    // Para novas ações, use IDs únicos que não colidam com existentes
    const acaoId = 'nova_' + novoContador;
    adicionarAcaoEdicao(null, acaoId, false);
}

// Função para remover ação
function removerAcao(acaoId) {
    if (!confirm('Tem certeza que deseja remover esta ação?')) {
        return;
    }
    
    // Converter acaoId para número se for uma string numérica  
    const acaoIdNumerico = typeof acaoId === 'string' && !acaoId.startsWith('nova_') ? parseInt(acaoId) : acaoId;
    
    // Remove a div da interface
    const acaoDiv = document.getElementById(`acao_${acaoId}`);
    if (acaoDiv) {
        acaoDiv.remove();
    }
    
    // Remove do array local se for uma ação existente (numérica)
    if (typeof acaoIdNumerico === 'number' && !isNaN(acaoIdNumerico) && assuntoAtual.acoes[acaoIdNumerico]) {
        // Marcar como removida em vez de deletar do array para manter índices
        assuntoAtual.acoes[acaoIdNumerico] = null;
    }
    
    // Recriar a interface para reorganizar a numeração
    recriarInterfaceAcoes();
}

// Função para recriar a interface das ações após remoções
function recriarInterfaceAcoes() {
    const container = document.getElementById('acoesContainer');
    container.innerHTML = '';
    
    let novoIndice = 0;
    assuntoAtual.acoes.forEach((acao, index) => {
        if (acao !== null) { // Pular ações removidas
            adicionarAcaoEdicao(acao, novoIndice, true);
            // Atualizar a referência no array para o novo índice
            if (novoIndice !== index) {
                assuntoAtual.acoes[novoIndice] = acao;
                assuntoAtual.acoes[index] = null;
            }
            novoIndice++;
        }
    });
    
    // Limpar posições null do final do array
    assuntoAtual.acoes = assuntoAtual.acoes.filter(acao => acao !== null);
}

// Funções para fechar modais
function closeDetalharAssuntoModal() {
    document.getElementById('detalharAssuntoModal').style.display = 'none';
    assuntoAtual = null;
}

function closeEditarAssuntoModal() {
    document.getElementById('editarAssuntoModal').style.display = 'none';
    document.getElementById('editarAssuntoForm').reset();
    assuntoAtual = null;
}

function closeHistoricoModal() {
    document.getElementById('historicoModal').style.display = 'none';
}

// Variável para controlar qual assunto será excluído
let assuntoParaExcluir = null;

// Função para abrir o modal de confirmação de exclusão
function confirmarExclusaoAssunto(assuntoId, assuntoTexto) {
    assuntoParaExcluir = assuntoId;
    document.getElementById('assuntoParaExcluir').textContent = assuntoTexto;
    document.getElementById('confirmarExclusaoModal').style.display = 'block';
}

// Função para fechar o modal de confirmação de exclusão
function closeConfirmarExclusaoModal() {
    document.getElementById('confirmarExclusaoModal').style.display = 'none';
    assuntoParaExcluir = null;
}

// Função para excluir o assunto
async function excluirAssunto() {
    if (!assuntoParaExcluir) return;
    
    try {
        const response = await fetch('api/delete_assunto.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: assuntoParaExcluir
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Fechar modal
            closeConfirmarExclusaoModal();
            
            // Mostrar mensagem de sucesso
            alert('Assunto excluído com sucesso!');
            
            // Recarregar a lista de assuntos
            await fetchAssuntos();
            
        } else {
            throw new Error(data.error || 'Erro ao excluir assunto');
        }
        
    } catch (error) {
        console.error('Erro ao excluir assunto:', error);
        alert('Erro ao excluir assunto: ' + error.message);
    }
}

// Função para mostrar histórico
function mostrarHistorico() {
    if (!assuntoAtual) return;
    
    const tbody = document.getElementById('historicoTableBody');
    tbody.innerHTML = '';
    
    assuntoAtual.historico.forEach(item => {
        // Usar os dados do usuário que já vêm da API
        const nomeUsuario = item.usuarioPg && item.usuarioNome 
            ? `${item.usuarioPg} ${item.usuarioNome}` 
            : (item.usuario || 'Usuário não encontrado');
        
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${formatDateTime(item.data)}</td>
            <td class="px-6 py-4 text-sm text-gray-900 break-words max-w-xs">${nomeUsuario}</td>
            <td class="px-6 py-4 text-sm text-gray-900 break-words">${item.acao}</td>
        `;
        
        tbody.appendChild(row);
    });
    
    document.getElementById('historicoModal').style.display = 'block';
}

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateUserInfo();
            setDataInicio(); // Definir data atual no campo dataInicio
            fetchAssuntos();
            fetchUsuarios();
            fetchChefias();
            fetchDivisoes();
            switchTab('resumo');
            
            // Inicializar gráfico com dados vazios
            updateChart(0, 0, 0, 0);
            
            // Adicionar listeners para os campos de data
            document.getElementById('dataInicio').addEventListener('change', applyFilters);
            document.getElementById('dataFim').addEventListener('change', applyFilters);
            
            // Configurar listeners para pesquisa após um pequeno delay para garantir que os elementos existam
            setTimeout(() => {
                // Adicionar listeners para os campos de pesquisa com verificação
                const searchAssuntoEl = document.getElementById('searchAssunto');
                const searchAcoesEl = document.getElementById('searchAcoes');
                const searchProvidenciasEl = document.getElementById('searchProvidencias');
                const clearSearchBtnEl = document.getElementById('clearSearchBtn');
                
                if (searchAssuntoEl) {
                    searchAssuntoEl.addEventListener('input', function() {
                        applyFilters();
                        updateSearchStatus();
                    });
                }
                
                if (searchAcoesEl) {
                    searchAcoesEl.addEventListener('input', function() {
                        applyFilters();
                        updateSearchStatus();
                    });
                }
                
                if (searchProvidenciasEl) {
                    searchProvidenciasEl.addEventListener('input', function() {
                        applyFilters();
                        updateSearchStatus();
                    });
                }
                
                if (clearSearchBtnEl) {
                    clearSearchBtnEl.addEventListener('click', function() {
                        if (searchAssuntoEl) searchAssuntoEl.value = '';
                        if (searchAcoesEl) searchAcoesEl.value = '';
                        if (searchProvidenciasEl) searchProvidenciasEl.value = '';
                        applyFilters();
                        updateSearchStatus();
                    });
                }
            }, 100);
            
            // Inicializar funcionalidades responsivas
            setupSidebarLinks();
            setupResponsiveEvents();
        });

// Funções para o modal de adicionar
let contadorAcoesAdd = 0;

function adicionarNovaAcaoAdd() {
    adicionarAcaoAdd();
}

function adicionarAcaoAdd(acao = null, index = null) {
    const container = document.getElementById('acoesContainerAdd');
    const acaoId = index !== null ? index : contadorAcoesAdd++;

    const acaoDiv = document.createElement('div');
    acaoDiv.className = 'border border-gray-200 rounded-lg p-4';
    acaoDiv.id = `acaoAdd_${acaoId}`;

    acaoDiv.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <h5 class="font-medium">Ação ${acaoId + 1}</h5>
            <button type="button" onclick="removerAcaoAdd(${acaoId})" class="text-red-600 hover:text-red-800">
                Remover
            </button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ação a realizar</label>
                <textarea name="acoes[${acaoId}][acao]" rows="2" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">${acao ? acao.acao : ''}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Providência adotada</label>
                <textarea name="acoes[${acaoId}][providencia]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">${acao ? acao.providencia : ''}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado da ação</label>
                <select name="acoes[${acaoId}][estado]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="pendente" ${acao && acao.estado === 'pendente' ? 'selected' : ''}>Pendente</option>
                    <option value="concluido" ${acao && acao.estado === 'concluido' ? 'selected' : ''}>Concluído</option>
                </select>
            </div>
        </div>
    `;

    container.appendChild(acaoDiv);
}

function removerAcaoAdd(acaoId) {
    const acaoDiv = document.getElementById(`acaoAdd_${acaoId}`);
    if (acaoDiv) {
        acaoDiv.remove();
    }
}

// Funções para edição de ações existentes
function editarAcaoExistente(acaoId) {
    const acao = assuntoAtual.acoes[acaoId];
    if (!acao) return;
    
    const container = document.getElementById('acoesContainer');
    const acaoDiv = document.getElementById(`acao_${acaoId}`);
    acaoDiv.remove();
    
    adicionarAcaoEdicao(acao, acaoId, false);
}

function salvarAcao(acaoId) {
    const acaoTextarea = document.querySelector(`textarea[name="acao_${acaoId}"]`);
    const providenciaTextarea = document.querySelector(`textarea[name="providencia_${acaoId}"]`);
    const estadoSelect = document.querySelector(`select[name="estado_acao_${acaoId}"]`);
    
    if (!acaoTextarea || !acaoTextarea.value.trim()) {
        alert('A ação é obrigatória');
        return;
    }
    
    const acaoData = {
        acao: acaoTextarea.value,
        providencia: providenciaTextarea ? providenciaTextarea.value : '',
        estado: estadoSelect ? estadoSelect.value : 'pendente'
    };
    
    // Verifica se é uma ação existente ou nova
    const acaoIdNumerico = typeof acaoId === 'string' && !acaoId.startsWith('nova_') ? parseInt(acaoId) : acaoId;
    
    if (typeof acaoIdNumerico === 'number' && !isNaN(acaoIdNumerico) && assuntoAtual.acoes[acaoIdNumerico]) {
        // Ação existente - atualizar mantendo a posição original e preservando o responsável
        const acaoExistente = assuntoAtual.acoes[acaoIdNumerico];
        assuntoAtual.acoes[acaoIdNumerico] = { 
            ...acaoExistente, 
            ...acaoData,
            // Preservar campos importantes da ação original
            id: acaoExistente.id,
            responsavel: acaoExistente.responsavel,
            responsavelPg: acaoExistente.responsavelPg,
            responsavelNome: acaoExistente.responsavelNome
        };
        acaoId = acaoIdNumerico;
    } else if (typeof acaoId === 'string' && acaoId.startsWith('nova_')) {
        // Nova ação - adicionar ao array
        const novaAcao = {
            responsavel: currentUser.id || '1',
            dataAtualizacao: new Date().toISOString().split('T')[0],
            ...acaoData
        };
        
        assuntoAtual.acoes.push(novaAcao);
        const novoIndice = assuntoAtual.acoes.length - 1;
        
        const acaoDiv = document.getElementById(`acao_${acaoId}`);
        if (acaoDiv) {
            acaoDiv.id = `acao_${novoIndice}`;
        }
        acaoId = novoIndice;
    } else {
        console.error('ID de ação inválido:', acaoId);
        return;
    }
    
    // Recriar em modo read-only
    const acaoDiv = document.getElementById(`acao_${acaoId}`);
    if (acaoDiv) {
        acaoDiv.remove();
        const indiceFinal = typeof acaoId === 'string' ? assuntoAtual.acoes.length - 1 : acaoId;
        adicionarAcaoEdicao(assuntoAtual.acoes[indiceFinal], indiceFinal, true);
    } else {
        console.error('Div da ação não encontrada:', `acao_${acaoId}`);
    }
}

    function cancelarEdicaoAcao(acaoId) {
        const acaoDiv = document.getElementById(`acao_${acaoId}`);
        acaoDiv.remove();
        
        // Se é uma nova ação (ID string começando com "nova_"), apenas remove
        if (typeof acaoId === 'string' && acaoId.startsWith('nova_')) {
            return;
        }
        
        // Se é uma ação existente, recriar em modo read-only
        const acao = assuntoAtual.acoes[acaoId];
        if (acao) {
            adicionarAcaoEdicao(acao, acaoId, true);
        }
    }

    document.getElementById('editarAssuntoForm').addEventListener('submit', async function(event) {
        event.preventDefault();

        // Coleta dados do formulário
        const formData = new FormData(this);
        const assuntoData = {};
        formData.forEach((value, key) => {
            assuntoData[key] = value;
        });

        // Coleta as ações do array local (que já contém todas as ações atualizadas)
        assuntoData.acoes = [];
        
        console.log('Coletando ações do array local:', assuntoAtual.acoes);
        
        // Usar o array local que já foi atualizado pelas funções salvarAcao
        assuntoAtual.acoes.forEach((acao, index) => {
            // Pular ações que foram marcadas como removidas (null)
            if (acao === null) {
                console.log(`Ação ${index} foi removida, pulando...`);
                return;
            }
            
            if (acao && acao.acao && acao.acao.trim()) {
                const acaoObj = {
                    acao: acao.acao,
                    providencia: acao.providencia || '',
                    estado: acao.estado || 'pendente'
                };
                
                // Inclui ID e responsável se a ação já existe no banco
                if (acao.id && Number(acao.id) > 0) {
                    acaoObj.id = Number(acao.id);
                    // Para ações existentes, não enviar o campo responsavel - será preservado pela API
                }
                
                assuntoData.acoes.push(acaoObj);
                console.log(`Ação ${index} adicionada:`, acaoObj);
            }
        });

        // Adicione o ID do assunto
        assuntoData.id = document.getElementById('editAssuntoId').value;

        console.log('Dados finais sendo enviados:', assuntoData);

        try {
            const response = await fetch('api/edit_assunto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(assuntoData)
            });
            
            if (response.ok) {
                console.log('Assunto atualizado com sucesso');
                closeEditarAssuntoModal();
                fetchAssuntos();
            } else {
                const errorData = await response.json();
                console.error('Erro na resposta:', errorData);
                
                // Verificar se é erro de autenticação
                if (response.status === 401 || errorData.action === 'redirect_login') {
                    alert('Sua sessão expirou. Você será redirecionado para o login.');
                    window.location.href = 'index.php';
                    return;
                }
                
                alert('Erro ao atualizar assunto: ' + (errorData.error || errorData.message || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Ocorreu um erro ao conectar com o servidor.');
        }
    });

    // Funções para Notas de Auditoria
    async function carregarNotasAuditoria(assuntoId) {
        try {
            const response = await fetch(`api/get_notas_auditoria.php?assunto_id=${assuntoId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const notas = await response.json();
            exibirNotasAuditoria(notas);
            
        } catch (error) {
            console.error('Erro ao carregar notas de auditoria:', error);
            document.getElementById('notasExistentes').innerHTML = '<p class="text-red-600">Erro ao carregar notas de auditoria</p>';
        }
    }

    function exibirNotasAuditoria(notas) {
        const container = document.getElementById('notasExistentes');
        
        if (notas.length === 0) {
            container.innerHTML = '<p class="text-gray-500 italic">Nenhuma nota de auditoria registrada</p>';
            return;
        }
        
        const html = notas.map(nota => `
            <div class="bg-white border border-gray-200 rounded-lg p-4 mb-3">
                <div class="flex justify-between items-start mb-2">
                    <div class="text-sm text-gray-600">
                        <strong>${nota.autor}</strong> - ${nota.perfil}
                    </div>
                    <div class="text-xs text-gray-400">
                        ${formatDateTime(nota.data_criacao)}
                    </div>
                </div>
                <div class="text-sm text-gray-800">
                    ${nota.nota}
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
    }

    async function adicionarNotaAuditoria() {
        const nota = document.getElementById('novaNota').value.trim();
        
        if (!nota) {
            alert('Por favor, digite uma nota');
            return;
        }
        
        if (!assuntoAtual) {
            alert('Nenhum assunto selecionado');
            return;
        }
        
        try {
            const response = await fetch('api/add_nota_auditoria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    assunto_id: assuntoAtual.id,
                    nota: nota
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                // Limpar campo de texto
                document.getElementById('novaNota').value = '';
                
                // Recarregar notas
                carregarNotasAuditoria(assuntoAtual.id);
                
                alert('Nota de auditoria adicionada com sucesso!');
            } else {
                throw new Error(result.error || 'Erro desconhecido');
            }
            
        } catch (error) {
            console.error('Erro ao adicionar nota de auditoria:', error);
            alert('Erro ao adicionar nota de auditoria: ' + error.message);
        }
    }

    function formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('pt-BR');
    }

    // Inicialização principal da página
    window.addEventListener('load', function() {
        // Chamar as funções de inicialização que não foram chamadas no DOMContentLoaded
        if (currentUser.perfil === 1) {
            // Suporte Técnico pode ver tudo - não há restrições adicionais
            fetchAssuntos();
            fetchUsuarios();
            fetchChefias();
            fetchDivisoes();
        } else {
            // Para outros perfis, apenas carregar assuntos
            fetchAssuntos();
            if (currentUser.perfil === 1) {
                fetchUsuarios();
                fetchChefias();
                fetchDivisoes();
            } else {
                fetchChefias();
                fetchDivisoes();
            }
        }
        
        updateUserInfo();
        setDataInicio();
        setupSidebarLinks();
        setupResponsiveEvents();
        adjustResponsiveElements();
    });
</script>
</body>
</html>