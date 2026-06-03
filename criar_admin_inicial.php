<?php
require "config.php";

$mensagem = "";
$tipo_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo = $_POST['tipo'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $nome = $_POST['nome'] ?? '';

    if (empty($tipo) || empty($usuario) || empty($senha) || empty($nome)) {
        $mensagem = "Preencha todos os campos!";
        $tipo_msg = "erro";
    } elseif (strlen($senha) < 6) {
        $mensagem = "A senha deve ter no mínimo 6 caracteres!";
        $tipo_msg = "erro";
    } else {
        
        $hash = password_hash($senha, PASSWORD_ARGON2ID);
        $email = $usuario . "@edumatric.local";

        if ($tipo == 'diretor') {
            $stmt = $conn->prepare("INSERT INTO diretor (usuario, senha, nome_completo, email, ativo) VALUES (?, ?, ?, ?, 1)");
        } else {
            $stmt = $conn->prepare("INSERT INTO secretaria (usuario, senha, nome_completo, email, ativo) VALUES (?, ?, ?, ?, 1)");
        }

        if ($stmt) {
            $stmt->bind_param("ssss", $usuario, $hash, $nome, $email);
            
            if ($stmt->execute()) {
                $mensagem = "✅ $tipo criado com sucesso! Usuário: <strong>$usuario</strong>";
                $tipo_msg = "sucesso";
                registrarLog($conn, 'admin_criado', $tipo, $stmt->insert_id, "Admin: $usuario");
            } else {
                $mensagem = "❌ Erro: " . $stmt->error;
                $tipo_msg = "erro";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Criar Admin | EduMatric</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">

<div class="max-w-lg mx-auto py-12 px-4">

<div class="bg-gray-800 border border-gray-700 rounded-lg p-8">

<h1 class="text-3xl font-black mb-2 text-center">Criar Administrador</h1>
<p class="text-gray-400 text-center mb-6">Ferramenta de configuração inicial</p>

<?php if ($mensagem): ?>
    <div class="mb-6 p-4 rounded-lg <?= $tipo_msg === 'sucesso' ? 'bg-green-900/50 border border-green-700 text-green-100' : 'bg-red-900/50 border border-red-700 text-red-100' ?>">
        <?= $mensagem ?>
    </div>
<?php endif; ?>

<form method="POST" class="space-y-4">

<div>
    <label class="block text-sm font-semibold mb-2">Tipo</label>
    <select name="tipo" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
        <option value="">Selecione...</option>
        <option value="diretor">Diretor</option>
        <option value="secretaria">Secretária</option>
    </select>
</div>

<div>
    <label class="block text-sm font-semibold mb-2">Usuário</label>
    <input type="text" name="usuario" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white placeholder-gray-400" placeholder="Ex: diretor">
    <p class="text-xs text-gray-400 mt-1">Padrão: diretor ou secretaria</p>
</div>

<div>
    <label class="block text-sm font-semibold mb-2">Senha</label>
    <input type="password" name="senha" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white placeholder-gray-400" placeholder="Mínimo 6 caracteres">
</div>

<div>
    <label class="block text-sm font-semibold mb-2">Nome Completo</label>
    <input type="text" name="nome" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white placeholder-gray-400" placeholder="Ex: João Silva">
</div>

<button type="submit" class="w-full bg-blue-700 hover:bg-blue-600 text-white font-bold py-2 rounded transition">
    ➕ Criar Admin
</button>

</form>

<p class="text-gray-400 text-sm text-center mt-6">
    ⚠️ Use esta página apenas para criar os primeiros administradores.<br>
    Depois, delete ou proteja este arquivo.
</p>

</div>

</div>

</body>
</html>