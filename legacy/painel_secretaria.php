<?php
session_start();
require "config.php";

protegerPagina("secretaria");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_GET['logout'])) {
    logoutSeguro();
}

$matriculas_abertas = verificarMatriculasAbertas($conn);
$flash_error = "";
$flash_success = "";

if (isset($_POST['toggle_matriculas'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $flash_error = "Requisição inválida.";
    } else {
        $novo_status = $matriculas_abertas ? 'não' : 'sim';
        $stmt = $conn->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'matriculas_abertas'");
        if (!$stmt) {
            $flash_error = "Erro no sistema.";
        } else {
            $stmt->bind_param("s", $novo_status);
            
            if ($stmt->execute()) {
                registrarLog($conn, 'configuracao_atualizada', 'configuracoes', null, "Matrículas: $novo_status");
                $matriculas_abertas = !$matriculas_abertas;
                $flash_success = "Status de matrículas atualizado para: <strong>" . ($matriculas_abertas ? "ABERTO" : "FECHADO") . "</strong>";
            } else {
                $flash_error = "Erro ao atualizar status.";
            }
            $stmt->close();
        }
    }
}

$totalResult = $conn->query("SELECT COUNT(*) as t FROM alunos");
$total = $totalResult ? (int)$totalResult->fetch_assoc()["t"] : 0;

$pendentesResult = $conn->query("SELECT COUNT(*) as t FROM alunos WHERE estado='Pendente'");
$pendentes = $pendentesResult ? (int)$pendentesResult->fetch_assoc()["t"] : 0;

$aprovadosResult = $conn->query("SELECT COUNT(*) as t FROM alunos WHERE estado='Aprovado'");
$aprovados = $aprovadosResult ? (int)$aprovadosResult->fetch_assoc()["t"] : 0;

$recusadosResult = $conn->query("SELECT COUNT(*) as t FROM alunos WHERE estado='Recusado'");
$recusados = $recusadosResult ? (int)$recusadosResult->fetch_assoc()["t"] : 0;

$reconfResult = $conn->query("SELECT COUNT(*) as t FROM alunos WHERE status_reconfirmacao='Pendente'");
$reconfirmacoes = $reconfResult ? (int)$reconfResult->fetch_assoc()["t"] : 0;

if(isset($_GET["aprovar"])){
    $id = intval($_GET["aprovar"]);
    
    $stmt = $conn->prepare("SELECT nome FROM alunos WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $aluno = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($aluno) {
            $idOficial = gerarIDOficial($conn, $aluno['nome']);
            
            $stmt = $conn->prepare("UPDATE alunos SET estado='Aprovado', id_oficial=?, data_aprovacao=NOW() WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("si", $idOficial, $id);
                
                if ($stmt->execute()) {
                    gerarRelatorio($conn, "Aprovação", date('Y-m'), $_SESSION['usuario']);
                    registrarLog($conn, 'matricula_aprovada', 'alunos', $id, "Aluno: {$aluno['nome']}");
                    $flash_success = "✅ Matrícula aprovada! ID: <strong>" . htmlspecialchars($idOficial) . "</strong>";
                }
                $stmt->close();
            }
        }
    }
}

if(isset($_POST["recusar"])){
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $flash_error = "Requisição inválida (CSRF).";
    } else {
        $id = intval($_POST["id"]);
        $mensagem = trim($_POST["mensagem"] ?? '');

        $stmt = $conn->prepare("SELECT nome FROM alunos WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $aluno = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($aluno && !empty($mensagem)) {
                $stmt = $conn->prepare("UPDATE alunos SET estado='Recusado', mensagem_recusa=? WHERE id=?");
                if ($stmt) {
                    $stmt->bind_param("si", $mensagem, $id);
                    
                    if ($stmt->execute()) {
                        gerarRelatorio($conn, "Recusa", date('Y-m'), $_SESSION['usuario']);
                        registrarLog($conn, 'matricula_recusada', 'alunos', $id, "Aluno: {$aluno['nome']}");
                        $flash_success = "❌ Matrícula recusada.";
                    }
                    $stmt->close();
                }
            } else {
                $flash_error = "Dados inválidos.";
            }
        }
    }
}

if(isset($_GET["aceitar_reconf"])){
    $id = intval($_GET["aceitar_reconf"]);
    $stmt = $conn->prepare("UPDATE alunos SET status_reconfirmacao='Aceita' WHERE id=?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
            registrarLog($conn, 'reconfirmacao_aceita', 'alunos', $id);
            $flash_success = "✅ Reconfirmação aceita!";
        }
        $stmt->close();
    }
}

if(isset($_POST["recusar_reconf"])){
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $flash_error = "Requisição inválida (CSRF).";
    } else {
        $id = intval($_POST["id_reconf"]);
        $mensagem = trim($_POST["mensagem_reconf"] ?? '');
        
        $stmt = $conn->prepare("UPDATE alunos SET status_reconfirmacao='Recusada' WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if($stmt->execute()){
                registrarLog($conn, 'reconfirmacao_recusada', 'alunos', $id, "Motivo: $mensagem");
                $flash_success = "❌ Reconfirmação recusada.";
            }
            $stmt->close();
        }
    }
}

if (isset($_POST['enviar_relatorio'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $flash_error = "Requisição inválida (CSRF).";
    } else {
        $tipo = $_POST['tipo'] ?? 'Geral';
        if (gerarRelatorio($conn, $tipo, date('Y-m'), $_SESSION['usuario'])) {
            $flash_success = "✅ Relatório '$tipo' gerado com sucesso.";
            registrarLog($conn, 'relatorio_gerado', 'relatorios', null, "Tipo: $tipo");
        } else {
            $flash_error = "Erro ao gerar relatório.";
        }
    }
}

$msgSenha = "";
if (isset($_POST['alterar_senha'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $flash_error = "Requisição inválida (CSRF).";
    } else {
        $usuarioSessao = $_SESSION['usuario'] ?? 'secretaria';
        $senha_atual = $_POST['senha_atual'] ?? '';
        $senha_nova  = $_POST['senha_nova'] ?? '';

        if ($senha_atual === '' || $senha_nova === '') {
            $msgSenha = '<div class="text-red-500 text-sm">❌ Preencha todos os campos.</div>';
        } elseif (strlen($senha_nova) < 6) {
            $msgSenha = '<div class="text-red-500 text-sm">❌ Senha mínimo 6 caracteres.</div>';
        } else {
            $stmt = $conn->prepare("SELECT senha FROM secretaria WHERE usuario=? LIMIT 1");
            if (!$stmt) {
                $msgSenha = '<div class="text-red-500 text-sm">❌ Erro no sistema.</div>';
            } else {
                $stmt->bind_param("s", $usuarioSessao);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stmt->close();

                if ($row && password_verify($senha_atual, $row['senha'])) {
                    $hash = password_hash($senha_nova, PASSWORD_ARGON2ID);
                    $stmt = $conn->prepare("UPDATE secretaria SET senha=? WHERE usuario=?");
                    if (!$stmt) {
                        $msgSenha = '<div class="text-red-500 text-sm">❌ Erro no sistema.</div>';
                    } else {
                        $stmt->bind_param("ss", $hash, $usuarioSessao);
                        
                        if ($stmt->execute()) {
                            registrarLog($conn, 'alteracao_senha', 'secretaria', null);
                            $msgSenha = '<div class="text-green-500 text-sm">✅ Senha alterada com sucesso.</div>';
                        } else {
                            $msgSenha = '<div class="text-red-500 text-sm">❌ Erro ao atualizar senha.</div>';
                        }
                        $stmt->close();
                    }
                } else {
                    $msgSenha = '<div class="text-red-500 text-sm">❌ Senha atual incorreta.</div>';
                }
            }
        }
    }
}

$busca = trim($_GET["buscar"] ?? "");
$filter = $_GET['filter'] ?? 'all';

if ($busca !== "") {
    $stmt = $conn->prepare("SELECT * FROM alunos WHERE nome LIKE ? ORDER BY id DESC");
    if ($stmt) {
        $like = "%".$busca."%";
        $stmt->bind_param("s", $like);
        $stmt->execute();
        $alunos = $stmt->get_result();
    } else {
        $alunos = $conn->query("SELECT * FROM alunos ORDER BY id DESC");
    }
} else {
    if ($filter === 'Pendente' || $filter === 'Aprovado' || $filter === 'Recusado') {
        $stmt = $conn->prepare("SELECT * FROM alunos WHERE estado=? ORDER BY id DESC");
        if ($stmt) {
            $stmt->bind_param("s", $filter);
            $stmt->execute();
            $alunos = $stmt->get_result();
        } else {
            $alunos = $conn->query("SELECT * FROM alunos ORDER BY id DESC");
        }
    } else {
        $alunos = $conn->query("SELECT * FROM alunos ORDER BY id DESC");
    }
}

$reconf_stmt = $conn->prepare("SELECT * FROM alunos WHERE status_reconfirmacao='Pendente' ORDER BY ultima_reconfirmacao DESC");
$reconfirmacoes_list = null;
if ($reconf_stmt) {
    $reconf_stmt->execute();
    $reconfirmacoes_list = $reconf_stmt->get_result();
}

$openSection = 'dashboard';
if ($busca !== "" || $filter !== 'all' || isset($_GET['show']) && $_GET['show'] === 'alunos') {
    $openSection = 'alunos';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Painel da Secretária | EduMatric</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-gray-100">

<!-- HEADER -->
<header class="bg-gradient-to-r from-gray-800 to-gray-900 border-b border-gray-700 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-white hover:text-blue-400 transition text-lg">←</a>
                <div>
                    <h1 class="text-xl font-bold text-white">Painel da Secretária</h1>
                    <p class="text-gray-400 text-xs">Gestão de Matrículas</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-400">Olá, <strong class="text-white"><?= esc($_SESSION['usuario']) ?></strong></span>
                <button onclick="toggleSenha()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-2 rounded text-sm font-semibold transition">
                    🔑
                </button>
                <a href="?logout=1" onclick="return confirm('Tem a certeza?')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded transition font-semibold">
                    Sair
                </a>
            </div>
        </div>
    </div>
</header>

<!-- CONTEÚDO -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- MODAL ALTERAR SENHA -->
    <div id="senhaModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 w-full max-w-sm">
            <h3 class="text-lg font-bold text-white mb-4">Alterar Senha</h3>
            <form method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="password" name="senha_atual" placeholder="Senha atual" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                <input type="password" name="senha_nova" placeholder="Nova senha" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                <div class="flex gap-2">
                    <button type="submit" name="alterar_senha" class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded font-semibold transition">Atualizar</button>
                    <button type="button" onclick="toggleSenha()" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded font-semibold transition">Cancelar</button>
                </div>
            </form>
            <?= $msgSenha ?? "" ?>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    <?php if (!empty($flash_error)): ?>
        <div class="mb-6 p-4 bg-red-900/50 border border-red-700 text-red-100 rounded-lg">
            <?= $flash_error ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($flash_success)): ?>
        <div class="mb-6 p-4 bg-green-900/50 border border-green-700 text-green-100 rounded-lg">
            <?= $flash_success ?>
        </div>
    <?php endif; ?>

    <!-- CARDS DE ESTATÍSTICAS (Clicáveis) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        
        <div onclick="showStatDetails('total', <?= $total ?>, 'Todos os alunos cadastrados')" class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg p-4 text-white shadow-lg cursor-pointer hover:shadow-xl transition transform hover:scale-105">
            <p class="text-blue-100 text-xs font-semibold">TOTAL</p>
            <h2 class="text-3xl font-black mt-2"><?= $total ?></h2>
            <p class="text-blue-200 text-xs mt-2">Clique para detalhes</p>
        </div>

        <div onclick="showStatDetails('pendentes', <?= $pendentes ?>, 'Aguardando aprovação')" class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-lg p-4 text-white shadow-lg cursor-pointer hover:shadow-xl transition transform hover:scale-105">
            <p class="text-yellow-100 text-xs font-semibold">PENDENTES</p>
            <h2 class="text-3xl font-black mt-2"><?= $pendentes ?></h2>
            <p class="text-yellow-200 text-xs mt-2">Clique para detalhes</p>
        </div>

        <div onclick="showStatDetails('aprovados', <?= $aprovados ?>, 'Matrículas confirmadas')" class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg p-4 text-white shadow-lg cursor-pointer hover:shadow-xl transition transform hover:scale-105">
            <p class="text-green-100 text-xs font-semibold">APROVADOS</p>
            <h2 class="text-3xl font-black mt-2"><?= $aprovados ?></h2>
            <p class="text-green-200 text-xs mt-2">Clique para detalhes</p>
        </div>

        <div onclick="showStatDetails('recusados', <?= $recusados ?>, 'Matrículas rejeitadas')" class="bg-gradient-to-br from-red-500 to-red-700 rounded-lg p-4 text-white shadow-lg cursor-pointer hover:shadow-xl transition transform hover:scale-105">
            <p class="text-red-100 text-xs font-semibold">RECUSADOS</p>
            <h2 class="text-3xl font-black mt-2"><?= $recusados ?></h2>
            <p class="text-red-200 text-xs mt-2">Clique para detalhes</p>
        </div>

        <div onclick="showStatDetails('reconf', <?= $reconfirmacoes ?>, 'Reconfirmações pendentes')" class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg p-4 text-white shadow-lg cursor-pointer hover:shadow-xl transition transform hover:scale-105">
            <p class="text-purple-100 text-xs font-semibold">RECONF.</p>
            <h2 class="text-3xl font-black mt-2"><?= $reconfirmacoes ?></h2>
            <p class="text-purple-200 text-xs mt-2">Clique para detalhes</p>
        </div>

    </div>

    <!-- MODAL DE DETALHES DO CARD -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-8 w-full max-w-2xl max-h-[80vh] overflow-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 id="detailsTitle" class="text-2xl font-bold text-white"></h3>
                <button onclick="closeDetails()" class="text-gray-400 hover:text-white text-2xl">✕</button>
            </div>
            <div id="detailsContent" class="text-gray-300 space-y-4">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
        </div>
    </div>

    <!-- STATUS DE MATRÍCULAS -->
    <div class="bg-gradient-to-r <?= $matriculas_abertas ? 'from-green-600 to-emerald-700' : 'from-red-600 to-red-700' ?> rounded-lg p-6 shadow-lg mb-8 text-white">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-black mb-1">Status das Matrículas</h3>
                <p class="text-sm opacity-90">Clique para alterar o status de inscrição</p>
            </div>
            <div class="text-right">
                <p class="text-4xl font-black mb-2"><?= $matriculas_abertas ? '🟢 ABERTO' : '🔴 FECHADO' ?></p>
            </div>
        </div>
        
        <form method="POST" class="mt-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit" name="toggle_matriculas" class="bg-white text-gray-900 font-black px-6 py-2 rounded hover:bg-gray-100 transition">
                <?= $matriculas_abertas ? '🔒 Fechar Matrículas' : '🔓 Abrir Matrículas' ?>
            </button>
        </form>
    </div>

    <!-- GRÁFICO DE ESTATÍSTICAS -->
    <div class="bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700 mb-8">
        <h3 class="text-lg font-bold text-white mb-4">Distribuição de Matrículas</h3>
        <div style="height: 300px;">
            <canvas id="grafico"></canvas>
        </div>
    </div>

    <!-- NAVIGATION BUTTONS -->
    <div class="flex gap-2 mb-6 flex-wrap">
        <button onclick="showSection('dashboard')" class="bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold transition">📊 Dashboard</button>
        <button onclick="showSection('alunos')" class="bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold transition">👥 Matrículas</button>
        <button onclick="showSection('reconfirmacoes')" class="bg-purple-700 hover:bg-purple-600 text-white px-4 py-2 rounded font-semibold transition">🔄 Reconfirmações</button>
        <button onclick="showSection('relatorios')" class="bg-green-700 hover:bg-green-600 text-white px-4 py-2 rounded font-semibold transition">📄 Relatórios</button>
    </div>

    <!-- SEÇÃO DASHBOARD -->
    <section id="dashboard" class="section bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700 mb-8">
        <h2 class="text-lg font-bold text-white mb-4">📊 Resumo Geral</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gray-700 rounded p-4">
                <p class="text-gray-300 mb-2">Taxa de Aprovação</p>
                <p class="text-3xl font-black text-blue-400"><?= $total > 0 ? round(($aprovados / $total) * 100, 1) : 0 ?>%</p>
            </div>
            <div class="bg-gray-700 rounded p-4">
                <p class="text-gray-300 mb-2">Total de Alunos</p>
                <p class="text-3xl font-black text-green-400"><?= $total ?></p>
            </div>
        </div>
    </section>

    <!-- SEÇÃO MATRÍCULAS -->
    <section id="alunos" class="section hidden bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <h2 class="text-lg font-bold text-white">Lista de Alunos</h2>
            <form method="GET" class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                <input type="text" name="buscar" placeholder="Pesquisar por nome..." value="<?= htmlspecialchars($busca) ?>" class="border border-gray-600 rounded px-3 py-2 bg-gray-700 text-white placeholder-gray-400 flex-1" />
                <button type="submit" class="bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold">Pesquisar</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs sm:text-sm">
                <thead class="bg-gray-700 border-b border-gray-600">
                    <tr>
                        <th class="text-left py-3 px-3 text-gray-300 font-semibold">Nome</th>
                        <th class="text-left py-3 px-3 text-gray-300 font-semibold">Email</th>
                        <th class="text-left py-3 px-3 text-gray-300 font-semibold">Curso</th>
                        <th class="text-center py-3 px-3 text-gray-300 font-semibold">Estado</th>
                        <th class="text-left py-3 px-3 text-gray-300 font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php if ($alunos): ?>
                        <?php while($a = $alunos->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-700/50 transition">
                            <td class="py-3 px-3 text-gray-300"><?= esc($a["nome"]) ?></td>
                            <td class="py-3 px-3 text-gray-400 text-xs"><?= esc($a["email"]) ?></td>
                            <td class="py-3 px-3 text-gray-300"><?= esc($a["curso"]) ?></td>
                            <td class="py-3 px-3 text-center">
                                <?php if($a["estado"] === "Pendente"): ?>
                                    <span class="bg-yellow-900/50 text-yellow-300 px-2 py-1 rounded text-xs font-bold">⏳</span>
                                <?php elseif($a["estado"] === "Aprovado"): ?>
                                    <span class="bg-green-900/50 text-green-300 px-2 py-1 rounded text-xs font-bold">✅</span>
                                <?php else: ?>
                                    <span class="bg-red-900/50 text-red-300 px-2 py-1 rounded text-xs font-bold">❌</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-3">
                                <div class="flex flex-wrap gap-1">
                                    <?php if($a["estado"] === "Pendente"): ?>
                                        <a href="?aprovar=<?= intval($a["id"]) ?>" class="bg-green-700 hover:bg-green-600 text-white px-2 py-1 rounded text-xs font-semibold transition">✓</a>
                                        <button onclick="openRecusarModal(<?= intval($a['id']) ?>, <?= json_encode(esc($a['nome'])) ?>)" class="bg-red-700 hover:bg-red-600 text-white px-2 py-1 rounded text-xs font-semibold transition">✗</button>
                                    <?php endif; ?>
                                    <button onclick='openDetailsModal(<?= json_encode($a, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' class="bg-gray-600 hover:bg-gray-500 text-white px-2 py-1 rounded text-xs font-semibold transition">👁</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- SEÇÃO RECONFIRMAÇÕES -->
    <section id="reconfirmacoes" class="section hidden bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700 mb-8">
        <h3 class="text-lg font-bold text-white mb-4">Reconfirmações Pendentes</h3>
        <div class="space-y-3">
            <?php if($reconfirmacoes_list && $reconfirmacoes_list->num_rows > 0): ?>
                <?php while($r = $reconfirmacoes_list->fetch_assoc()): ?>
                <div class="bg-gray-700 rounded-lg p-4 border border-purple-600/50">
                    <div class="flex justify-between items-start flex-wrap gap-4">
                        <div>
                            <p class="font-bold text-white"><?= esc($r['nome']) ?></p>
                            <p class="text-sm text-gray-300"><?= esc($r['email']) ?></p>
                            <p class="text-xs text-gray-400">ID: <?= esc($r['id_oficial']) ?></p>
                        </div>
                        <div class="flex gap-2">
                            <a href="?aceitar_reconf=<?= intval($r['id']) ?>" class="bg-green-700 hover:bg-green-600 text-white px-3 py-1 rounded text-xs font-semibold">Aceitar</a>
                            <button onclick="openRecusarReconfModal(<?= intval($r['id']) ?>)" class="bg-red-700 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold">Recusar</button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-400 text-center py-4">Nenhuma reconfirmação pendente</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- SEÇÃO RELATÓRIOS -->
    <section id="relatorios" class="section hidden bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700 mb-8">
        <h3 class="text-lg font-bold text-white mb-4">Gerar Relatórios</h3>
        <form method="POST" class="space-y-3 max-w-xs">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit" name="enviar_relatorio" onclick="document.getElementById('tipo_rel').value='Diário'" class="w-full bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold transition">📅 Diário</button>
            <button type="submit" name="enviar_relatorio" onclick="document.getElementById('tipo_rel').value='Mensal'" class="w-full bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold transition">📊 Mensal</button>
            <button type="submit" name="enviar_relatorio" onclick="document.getElementById('tipo_rel').value='Anual'" class="w-full bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold transition">📈 Anual</button>
            <input type="hidden" name="tipo" id="tipo_rel" value="Diário">
        </form>
    </section>

</main>

<!-- MODAL RECUSAR MATRÍCULA -->
<div id="modalRecusar" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-6">
    <div class="bg-gray-800 rounded-lg w-full max-w-lg p-6 border border-gray-700">
        <h3 class="text-lg font-bold text-white mb-4">Recusar Matrícula</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="id" id="recusarId" value="">
            <div>
                <label class="block text-sm text-gray-300 mb-2">Aluno</label>
                <input id="recusarNome" type="text" readonly class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-gray-400">
            </div>
            <div>
                <label for="mensagem" class="block text-sm text-gray-300 mb-2">Motivo</label>
                <textarea id="mensagem" name="mensagem" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500" rows="4"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeRecusarModal()" class="px-4 py-2 rounded border border-gray-600 text-gray-300 hover:bg-gray-700 transition">Cancelar</button>
                <button type="submit" name="recusar" class="px-4 py-2 rounded bg-red-700 hover:bg-red-600 text-white font-semibold transition">Recusar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL RECUSAR RECONFIRMAÇÃO -->
<div id="modalRecusarReconf" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-6">
    <div class="bg-gray-800 rounded-lg w-full max-w-lg p-6 border border-gray-700">
        <h3 class="text-lg font-bold text-white mb-4">Recusar Reconfirmação</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="id_reconf" id="recusarReconfId" value="">
            <div>
                <label for="mensagem_reconf" class="block text-sm text-gray-300 mb-2">Motivo</label>
                <textarea id="mensagem_reconf" name="mensagem_reconf" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500" rows="3"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeRecusarReconfModal()" class="px-4 py-2 rounded border border-gray-600 text-gray-300 hover:bg-gray-700 transition">Cancelar</button>
                <button type="submit" name="recusar_reconf" class="px-4 py-2 rounded bg-red-700 hover:bg-red-600 text-white font-semibold transition">Recusar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETALHES -->
<div id="modalDetalhes" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-6">
    <div class="bg-gray-800 rounded-lg w-full max-w-2xl p-6 overflow-auto max-h-[80vh] border border-gray-700">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-white">Detalhes do Aluno</h3>
            <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-white text-xl">✕</button>
        </div>
        <div id="detalhesConteudo" class="space-y-3 text-sm"></div>
    </div>
</div>

<script>
    function toggleSenha(){
        document.getElementById('senhaModal').classList.toggle('hidden');
    }

    function showSection(id){
        document.querySelectorAll('.section').forEach(sec=>{
            sec.classList.add('hidden');
        });
        const el = document.getElementById(id);
        if(el) el.classList.remove('hidden');
    }

    function showStatDetails(type, count, description) {
        const modal = document.getElementById('detailsModal');
        const title = document.getElementById('detailsTitle');
        const content = document.getElementById('detailsContent');

        const details = {
            total: {
                title: '📊 Total de Alunos',
                description: description,
                color: 'blue',
                stats: [
                    { label: 'Total Geral', value: count, color: 'blue' },
                    { label: 'Taxa de Ocupação', value: '<?= $total > 0 ? round(($aprovados / $total) * 100, 1) : 0 ?>%', color: 'green' }
                ]
            },
            pendentes: {
                title: '⏳ Matrículas Pendentes',
                description: description,
                color: 'yellow',
                stats: [
                    { label: 'Pendentes', value: count, color: 'yellow' },
                    { label: 'Percentual', value: '<?= $total > 0 ? round(($pendentes / $total) * 100, 1) : 0 ?>%', color: 'yellow' }
                ]
            },
            aprovados: {
                title: '✅ Matrículas Aprovadas',
                description: description,
                color: 'green',
                stats: [
                    { label: 'Aprovados', value: count, color: 'green' },
                    { label: 'Percentual', value: '<?= $total > 0 ? round(($aprovados / $total) * 100, 1) : 0 ?>%', color: 'green' }
                ]
            },
            recusados: {
                title: '❌ Matrículas Recusadas',
                description: description,
                color: 'red',
                stats: [
                    { label: 'Recusados', value: count, color: 'red' },
                    { label: 'Percentual', value: '<?= $total > 0 ? round(($recusados / $total) * 100, 1) : 0 ?>%', color: 'red' }
                ]
            },
            reconf: {
                title: '🔄 Reconfirmações Pendentes',
                description: description,
                color: 'purple',
                stats: [
                    { label: 'Reconfirmações', value: count, color: 'purple' }
                ]
            }
        };

        const detail = details[type];
        title.textContent = detail.title;
        
        content.innerHTML = `
            <p class="text-gray-300 mb-6">${detail.description}</p>
            <div class="grid grid-cols-2 gap-4 mb-6">
                ${detail.stats.map(stat => `
                    <div class="bg-gray-700 rounded p-4 text-center">
                        <p class="text-gray-400 text-sm">${stat.label}</p>
                        <p class="text-3xl font-black text-${stat.color}-400 mt-2">${stat.value}</p>
                    </div>
                `).join('')}
            </div>
            <div class="bg-gray-700 rounded p-4">
                <p class="text-gray-400 text-sm mb-2">Informação Adicional</p>
                <p class="text-gray-300">Esta métrica foi calculada em tempo real baseada nos dados do sistema.</p>
            </div>
        `;

        modal.classList.remove('hidden');
    }

    function closeDetails() {
        document.getElementById('detailsModal').classList.add('hidden');
    }

    function openRecusarModal(id, nome){
        document.getElementById('recusarId').value = id;
        document.getElementById('recusarNome').value = nome;
        document.getElementById('mensagem').value = '';
        document.getElementById('modalRecusar').classList.remove('hidden');
        document.getElementById('modalRecusar').classList.add('flex');
    }

    function closeRecusarModal(){
        document.getElementById('modalRecusar').classList.add('hidden');
        document.getElementById('modalRecusar').classList.remove('flex');
    }

    function openRecusarReconfModal(id){
        document.getElementById('recusarReconfId').value = id;
        document.getElementById('mensagem_reconf').value = '';
        document.getElementById('modalRecusarReconf').classList.remove('hidden');
        document.getElementById('modalRecusarReconf').classList.add('flex');
    }

    function closeRecusarReconfModal(){
        document.getElementById('modalRecusarReconf').classList.add('hidden');
        document.getElementById('modalRecusarReconf').classList.remove('flex');
    }

    function openDetailsModal(obj){
        const content = document.getElementById('detalhesConteudo');
        content.innerHTML = '';
        for (const key in obj) {
            if(obj.hasOwnProperty(key)){
                const value = obj[key] === null ? '—' : obj[key];
                const row = document.createElement('div');
                row.className = 'flex justify-between border-b border-gray-700 pb-2';
                const k = document.createElement('div');
                k.className = 'text-gray-400 font-semibold';
                k.textContent = key.toUpperCase();
                const v = document.createElement('div');
                v.className = 'text-gray-200 text-right';
                v.textContent = value;
                row.appendChild(k);
                row.appendChild(v);
                content.appendChild(row);
            }
        }
        document.getElementById('modalDetalhes').classList.remove('hidden');
        document.getElementById('modalDetalhes').classList.add('flex');
    }

    function closeDetailsModal(){
        document.getElementById('modalDetalhes').classList.add('hidden');
        document.getElementById('modalDetalhes').classList.remove('flex');
    }

    function updateURLFilter(filter){
        const url = new URL(window.location);
        url.searchParams.set('filter', filter);
        url.searchParams.set('show', 'alunos');
        url.searchParams.delete('buscar');
        window.history.pushState({}, '', url);
    }

    // Chart
    const ctx = document.getElementById('grafico').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Pendentes','Aprovados','Recusados'],
            datasets: [{
                label: 'Contagem',
                backgroundColor: ['#f59e0b','#10b981','#ef4444'],
                data: [<?= $pendentes ?>, <?= $aprovados ?>, <?= $recusados ?>]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#d1d5db' } } },
            scales: { 
                y: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } },
                x: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' } }
            }
        }
    });

    // Fechar modals ao clicar no overlay
    document.getElementById('modalRecusar').addEventListener('click', function(e){
        if(e.target === this) closeRecusarModal();
    });

    document.getElementById('modalRecusarReconf').addEventListener('click', function(e){
        if(e.target === this) closeRecusarReconfModal();
    });

    document.getElementById('modalDetalhes').addEventListener('click', function(e){
        if(e.target === this) closeDetailsModal();
    });

    document.getElementById('detailsModal').addEventListener('click', function(e){
        if(e.target === this) closeDetails();
    });

    showSection('<?= $openSection ?>');
</script>

</body>
</html>