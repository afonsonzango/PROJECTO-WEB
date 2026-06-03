<?php
require "config.php";
protegerPagina("diretor");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_GET['logout'])) {
    logoutSeguro();
}

$msgSenha = "";
if (isset($_POST['alterar_senha'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $msgSenha = '<div class="text-red-500 text-sm">❌ Requisição inválida (CSRF).</div>';
    } else {
        $usuarioSessao = $_SESSION['usuario'] ?? 'diretor';
        $senha_atual = $_POST['senha_atual'] ?? '';
        $senha_nova  = $_POST['senha_nova'] ?? '';

        if ($senha_atual === '' || $senha_nova === '') {
            $msgSenha = '<div class="text-red-500 text-sm">❌ Preencha todos os campos.</div>';
        } elseif (strlen($senha_nova) < 6) {
            $msgSenha = '<div class="text-red-500 text-sm">❌ Senha mínimo 6 caracteres.</div>';
        } else {
            $stmt = $conn->prepare("SELECT senha FROM diretor WHERE usuario=? LIMIT 1");
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
                    $stmt = $conn->prepare("UPDATE diretor SET senha=? WHERE usuario=?");
                    if (!$stmt) {
                        $msgSenha = '<div class="text-red-500 text-sm">❌ Erro no sistema.</div>';
                    } else {
                        $stmt->bind_param("ss", $hash, $usuarioSessao);
                        
                        if ($stmt->execute()) {
                            registrarLog($conn, 'alteracao_senha', 'diretor', null);
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

$totalResult = $conn->query("SELECT COUNT(*) as t FROM alunos");
$total = $totalResult ? (int)$totalResult->fetch_assoc()["t"] : 0;

$pendentesResult = $conn->query("SELECT COUNT(*) as t FROM alunos WHERE estado='Pendente'");
$pendentes = $pendentesResult ? (int)$pendentesResult->fetch_assoc()["t"] : 0;

$aprovadosResult = $conn->query("SELECT COUNT(*) as t FROM alunos WHERE estado='Aprovado'");
$aprovados = $aprovadosResult ? (int)$aprovadosResult->fetch_assoc()["t"] : 0;

$recusadosResult = $conn->query("SELECT COUNT(*) as t FROM alunos WHERE estado='Recusado'");
$recusados = $recusadosResult ? (int)$recusadosResult->fetch_assoc()["t"] : 0;

$relatorios = $conn->query("SELECT * FROM relatorios ORDER BY data_geracao DESC LIMIT 10");

$cursos_result = $conn->query("SELECT curso, COUNT(*) as total FROM alunos WHERE estado='Aprovado' GROUP BY curso");
$classes_result = $conn->query("SELECT classe, COUNT(*) as total FROM alunos WHERE estado='Aprovado' GROUP BY classe");
$periodos_result = $conn->query("SELECT periodo, COUNT(*) as total FROM alunos WHERE estado='Aprovado' GROUP BY periodo");

$cursos = [];
$cursos_count = [];
if ($cursos_result) {
    while ($row = $cursos_result->fetch_assoc()) {
        $cursos[] = $row['curso'];
        $cursos_count[] = $row['total'];
    }
}

$classes = [];
$classes_count = [];
if ($classes_result) {
    while ($row = $classes_result->fetch_assoc()) {
        $classes[] = $row['classe'];
        $classes_count[] = $row['total'];
    }
}

$periodos = [];
$periodos_count = [];
if ($periodos_result) {
    while ($row = $periodos_result->fetch_assoc()) {
        $periodos[] = $row['periodo'];
        $periodos_count[] = $row['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel do Diretor | EduMatric</title>
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
                    <h1 class="text-xl font-bold text-white">Painel do Diretor</h1>
                    <p class="text-gray-400 text-xs">Visão Estratégica</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-400">Olá, <strong class="text-white"><?= esc($_SESSION['usuario']) ?></strong></span>
                <button onclick="toggleSenha()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-2 rounded text-sm font-semibold transition">
                    🔑 Alterar Senha
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

    <!-- CARDS DE ESTATÍSTICAS (Clicáveis) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <div onclick="showStatDetails('total', <?= $total ?>, 'Todos os alunos cadastrados')" class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-lg p-6 text-white shadow-lg cursor-pointer hover:shadow-xl transition transform hover:scale-105">
            <p class="text-blue-100 text-sm font-semibold">TOTAL GERAL</p>
            <h2 class="text-4xl font-black mt-2"><?= $total ?></h2>
            <p class="text-blue-200 text-xs mt-3">Clique para detalhes</p>
        </div>

        <div onclick="showStatDetails('pendentes', <?= $pendentes ?>, 'Aguardando aprovação')" class="bg-gradient-to-br from-yellow-500 to-orange-600 rounded-lg p-6 text-white shadow-lg cursor-pointer hover:shadow-xl transition transform hover:scale-105">
            <p class="text-yellow-100 text-sm font-semibold">PENDENTES</p>
            <h2 class="text-4xl font-black mt-2"><?= $pendentes ?></h2>
            <p class="text-yellow-200 text-xs mt-3">Clique para detalhes</p>
        </div>

        <div onclick="showStatDetails('aprovados', <?= $aprovados ?>, 'Matrículas confirmadas')" class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg p-6 text-white shadow-lg cursor-pointer hover:shadow-xl transition transform hover:scale-105">
            <p class="text-green-100 text-sm font-semibold">APROVADOS</p>
            <h2 class="text-4xl font-black mt-2"><?= $aprovados ?></h2>
            <p class="text-green-200 text-xs mt-3">Clique para detalhes</p>
        </div>

        <div onclick="showStatDetails('recusados', <?= $recusados ?>, 'Matrículas rejeitadas')" class="bg-gradient-to-br from-red-500 to-red-700 rounded-lg p-6 text-white shadow-lg cursor-pointer hover:shadow-xl transition transform hover:scale-105">
            <p class="text-red-100 text-sm font-semibold">RECUSADOS</p>
            <h2 class="text-4xl font-black mt-2"><?= $recusados ?></h2>
            <p class="text-red-200 text-xs mt-3">Clique para detalhes</p>
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

    <!-- GRÁFICOS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <div class="bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700">
            <h3 class="text-lg font-bold text-white mb-4">Distribuição por Curso</h3>
            <div style="height: 300px;">
                <canvas id="chartCursos"></canvas>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700">
            <h3 class="text-lg font-bold text-white mb-4">Distribuição por Classe</h3>
            <div style="height: 300px;">
                <canvas id="chartClasses"></canvas>
            </div>
        </div>

    </div>

    <div class="bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700 mb-8">
        <h3 class="text-lg font-bold text-white mb-4">Distribuição por Período</h3>
        <div style="height: 300px;">
            <canvas id="chartPeriodos"></canvas>
        </div>
    </div>

    <!-- ESTATÍSTICAS GERAIS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <div class="bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700">
            <h3 class="text-lg font-bold text-white mb-4">Taxa de Aprovação</h3>
            <div class="text-center">
                <div class="text-5xl font-black text-blue-400">
                    <?= $total > 0 ? round(($aprovados / $total) * 100, 1) : 0 ?>%
                </div>
                <p class="text-gray-400 text-sm mt-3"><?= $aprovados ?> de <?= $total ?> alunos</p>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700">
            <h3 class="text-lg font-bold text-white mb-4">Análise Rápida</h3>
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Taxa Pendência:</span>
                    <span class="text-yellow-400 font-bold"><?= $total > 0 ? round(($pendentes / $total) * 100, 1) : 0 ?>%</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Taxa Rejeição:</span>
                    <span class="text-red-400 font-bold"><?= $total > 0 ? round(($recusados / $total) * 100, 1) : 0 ?>%</span>
                </div>
                <hr class="border-gray-700 my-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Média por Classe:</span>
                    <span class="text-green-400 font-bold"><?= $total > 0 ? round($total / 4) : 0 ?></span>
                </div>
            </div>
        </div>

    </div>

    <!-- RELATÓRIOS RECENTES -->
    <div class="bg-gray-800 rounded-lg p-6 shadow-lg border border-gray-700">
        <h3 class="text-lg font-bold text-white mb-4">Relatórios Recentes</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-700">
                    <tr>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Tipo</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Período</th>
                        <th class="text-center py-3 px-4 text-gray-300 font-semibold">Total</th>
                        <th class="text-center py-3 px-4 text-gray-300 font-semibold">Aprovados</th>
                        <th class="text-center py-3 px-4 text-gray-300 font-semibold">Criado Por</th>
                        <th class="text-left py-3 px-4 text-gray-300 font-semibold">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php if ($relatorios): ?>
                        <?php while($rel = $relatorios->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-700/50 transition">
                            <td class="py-3 px-4 text-gray-300"><?= esc($rel['tipo']) ?></td>
                            <td class="py-3 px-4 text-gray-300"><?= esc($rel['periodo']) ?></td>
                            <td class="py-3 px-4 text-center text-gray-300"><?= esc($rel['total_alunos']) ?></td>
                            <td class="py-3 px-4 text-center text-green-400 font-bold"><?= esc($rel['total_aprovados']) ?></td>
                            <td class="py-3 px-4 text-gray-300"><?= esc($rel['criado_por']) ?></td>
                            <td class="py-3 px-4 text-gray-400 text-xs"><?= date('d/m/Y H:i', strtotime($rel['data_geracao'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
    function toggleSenha(){
        document.getElementById('senhaModal').classList.toggle('hidden');
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

    // Gráfico de Cursos
    new Chart(document.getElementById('chartCursos'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($cursos) ?>,
            datasets: [{
                data: <?= json_encode($cursos_count) ?>,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: '#d1d5db' } } }
        }
    });

    // Gráfico de Classes
    new Chart(document.getElementById('chartClasses'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($classes) ?>,
            datasets: [{
                label: 'Alunos',
                data: <?= json_encode($classes_count) ?>,
                backgroundColor: '#3b82f6',
                borderColor: '#1e40af',
                borderWidth: 1
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

    // Gráfico de Períodos
    new Chart(document.getElementById('chartPeriodos'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($periodos) ?>,
            datasets: [{
                data: <?= json_encode($periodos_count) ?>,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: '#d1d5db' } } }
        }
    });

    // Fechar modal ao clicar no background
    document.getElementById('detailsModal').addEventListener('click', function(e){
        if(e.target === this) closeDetails();
    });
</script>

</body>
</html>