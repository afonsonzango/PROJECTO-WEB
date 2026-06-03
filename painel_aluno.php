<?php
require "config.php";
protegerPagina("aluno");

$id = $_SESSION["aluno_id"];

$stmt = $conn->prepare("SELECT * FROM alunos WHERE id = ?");
if (!$stmt) die("Erro: " . htmlspecialchars($conn->error));

$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$aluno = $res->fetch_assoc();
$stmt->close();

if (!$aluno) {
    logoutSeguro();
}

$stmt = $conn->prepare("SELECT COUNT(*) as t FROM alunos WHERE curso = ? AND estado = 'Aprovado'");
if (!$stmt) die("Erro: " . htmlspecialchars($conn->error));

$stmt->bind_param("s", $aluno['curso']);
$stmt->execute();
$totalTurma = $stmt->get_result()->fetch_assoc()["t"];
$stmt->close();

$msg_senha = "";
if(isset($_POST["alterar_senha"])){
    $nova_senha = trim($_POST["nova_senha"] ?? "");
    $conf_senha = trim($_POST["confirmar_senha"] ?? "");

    if (empty($nova_senha) || empty($conf_senha)) {
        $msg_senha = '<div class="text-red-600">Preencha todos os campos.</div>';
    } elseif ($nova_senha !== $conf_senha) {
        $msg_senha = '<div class="text-red-600">As senhas não conferem.</div>';
    } elseif (strlen($nova_senha) < 6) {
        $msg_senha = '<div class="text-red-600">A senha deve ter no mínimo 6 caracteres.</div>';
    } else {
        $hash = password_hash($nova_senha, HASH_ALGO);
        $stmt = $conn->prepare("UPDATE alunos SET senha = ? WHERE id = ?");
        if (!$stmt) {
            $msg_senha = '<div class="text-red-600">Erro no sistema.</div>';
        } else {
            $stmt->bind_param("si", $hash, $id);
            
            if ($stmt->execute()) {
                registrarLog($conn, 'alteracao_senha', 'alunos', $id);
                $msg_senha = '<div class="text-green-600">✅ Senha atualizada com sucesso.</div>';
            } else {
                $msg_senha = '<div class="text-red-600">Erro ao atualizar.</div>';
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['logout'])) {
    logoutSeguro();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel do Aluno | EduMatric</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-100">

<!-- HEADER -->
<div class="bg-blue-900 text-white p-4 md:p-6 flex justify-between items-center">
<div>
    <h1 class="text-xl md:text-2xl font-bold">Portal Académico do Aluno</h1>
    <p class="text-blue-200 text-sm">Bem-vindo, <?= esc($aluno['nome']) ?></p>
</div>
<a href="?logout=1" onclick="return confirm('Tem a certeza?')" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-white">Sair</a>
</div>

<div class="max-w-6xl mx-auto p-4 md:p-6">

<!-- PERFIL DO ALUNO -->
<div class="bg-white p-6 rounded-lg shadow mb-6">
<h2 class="text-2xl font-bold mb-4 text-blue-900">Seus Dados</h2>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">

<div class="bg-blue-50 p-4 rounded">
    <p class="text-gray-600 text-xs font-semibold">NOME</p>
    <p class="font-bold text-lg"><?= esc($aluno["nome"]) ?></p>
</div>

<div class="bg-blue-50 p-4 rounded">
    <p class="text-gray-600 text-xs font-semibold">ID OFICIAL</p>
    <p class="font-bold text-lg"><?= esc($aluno["id_oficial"]) ?></p>
</div>

<div class="bg-blue-50 p-4 rounded">
    <p class="text-gray-600 text-xs font-semibold">EMAIL</p>
    <p class="font-bold"><?= esc($aluno["email"]) ?></p>
</div>

<div class="bg-green-50 p-4 rounded">
    <p class="text-gray-600 text-xs font-semibold">CURSO</p>
    <p class="font-bold text-lg"><?= esc($aluno["curso"]) ?></p>
</div>

<div class="bg-green-50 p-4 rounded">
    <p class="text-gray-600 text-xs font-semibold">CLASSE</p>
    <p class="font-bold text-lg"><?= esc($aluno["classe"]) ?></p>
</div>

<div class="bg-green-50 p-4 rounded">
    <p class="text-gray-600 text-xs font-semibold">PERÍODO</p>
    <p class="font-bold text-lg"><?= esc($aluno["periodo"]) ?></p>
</div>

<div class="bg-purple-50 p-4 rounded">
    <p class="text-gray-600 text-xs font-semibold">ESTADO</p>
    <p class="font-bold text-lg text-green-600"><?= esc($aluno["estado"]) ?></p>
</div>

<div class="bg-purple-50 p-4 rounded">
    <p class="text-gray-600 text-xs font-semibold">NIF</p>
    <p class="font-bold"><?= esc($aluno["nif"] ?? "N/A") ?></p>
</div>

<div class="bg-purple-50 p-4 rounded">
    <p class="text-gray-600 text-xs font-semibold">TELEFONE</p>
    <p class="font-bold"><?= esc($aluno["telefone"] ?? "N/A") ?></p>
</div>

</div>
</div>

<div class="bg-white p-6 rounded-lg shadow">
<h3 class="font-bold mb-4 text-blue-900">Informações Gerais</h3>
<div class="space-y-3">
<div class="flex justify-between py-2 border-b">
<span class="text-gray-600">Alunos na sua turma:</span>
<span class="font-bold"><?= $totalTurma ?></span>
</div>
<div class="flex justify-between py-2 border-b">
<span class="text-gray-600">Data de cadastro:</span>
<span class="font-bold"><?= date('d/m/Y', strtotime($aluno['data_cadastro'])) ?></span>
</div>
<div class="flex justify-between py-2 border-b">
<span class="text-gray-600">Última reconfirmação:</span>
<span class="font-bold"><?= $aluno['ultima_reconfirmacao'] ? date('d/m/Y', strtotime($aluno['ultima_reconfirmacao'])) : 'Nunca' ?></span>
</div>
<div class="flex justify-between py-2">
<span class="text-gray-600">Endereço:</span>
<span class="font-bold text-right text-sm"><?= esc($aluno["endereco"] ?? "Não informado") ?></span>
</div>
</div>
</div>


<!-- RECONFIRMAÇÃO -->
<div class="bg-blue-50 p-6 rounded-lg shadow">
<h3 class="font-bold mb-2 text-blue-900">Reconfirmação de Matrícula</h3>
<p class="text-sm text-gray-700 mb-4">
    Atualize seus dados e mantenha seu cadastro sempre atualizado.
</p>
<a href="reconfirmacao_matricula.php" class="bg-blue-900 text-white px-6 py-2 rounded font-bold hover:bg-blue-800 inline-block">
    Fazer Reconfirmação →
</a>
</div>

</div>

<script>
new Chart(document.getElementById("grafAluno"), {
    type: 'doughnut',
    data: {
        labels: ['Alunos no Curso'],
        datasets: [{
            data: [<?= intval($totalTurma) ?>],
            backgroundColor: ['#3b82f6']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

</body>
</html>