<?php
require "config.php";

$estado = trim($_GET['estado'] ?? '');
$valid_estados = ['Pendente', 'Aprovado', 'Recusado'];

if (!in_array($estado, $valid_estados)) {
    die('<p class="text-red-400">Estado inválido</p>');
}

$stmt = $conn->prepare("SELECT * FROM alunos WHERE estado = ? ORDER BY id DESC");
$stmt->bind_param("s", $estado);
$stmt->execute();
$alunos = $stmt->get_result();

if ($alunos->num_rows === 0) {
    echo '<p class="text-gray-400 text-center py-4">Nenhum aluno encontrado</p>';
    exit;
}
?>

<div class="overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="bg-gray-700 border-b border-gray-600">
            <tr>
                <th class="text-left py-3 px-3 text-gray-300 font-semibold">Nome</th>
                <th class="text-left py-3 px-3 text-gray-300 font-semibold">Email</th>
                <th class="text-left py-3 px-3 text-gray-300 font-semibold">Curso</th>
                <th class="text-left py-3 px-3 text-gray-300 font-semibold">Classe</th>
                <th class="text-left py-3 px-3 text-gray-300 font-semibold">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
            <?php while($a = $alunos->fetch_assoc()): ?>
            <tr class="hover:bg-gray-700/50 transition">
                <td class="py-3 px-3 text-gray-300"><?= esc($a["nome"]) ?></td>
                <td class="py-3 px-3 text-gray-400"><?= esc($a["email"]) ?></td>
                <td class="py-3 px-3 text-gray-300"><?= esc($a["curso"]) ?></td>
                <td class="py-3 px-3 text-gray-300"><?= esc($a["classe"]) ?></td>
                <td class="py-3 px-3">
                    <div class="flex flex-wrap gap-1">
                        <?php if($a["foto"]): ?>
                            <a href="uploads/<?= htmlspecialchars($a['foto']) ?>" target="_blank" class="bg-blue-700 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs font-semibold transition">📷</a>
                        <?php endif; ?>
                        
                        <?php if($a["certificado"]): ?>
                            <a href="uploads/<?= htmlspecialchars($a['certificado']) ?>" target="_blank" class="bg-blue-700 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs font-semibold transition">📄</a>
                        <?php endif; ?>
                        
                        <?php if($estado === "Pendente"): ?>
                            <a href="painel_secretaria.php?aprovar=<?= intval($a["id"]) ?>" class="bg-green-700 hover:bg-green-600 text-white px-2 py-1 rounded text-xs font-semibold transition" onclick="location.reload();">✓</a>
                            <button onclick="alert('Recusar: ' + <?= json_encode(esc($a['nome'])) ?>)" class="bg-red-700 hover:bg-red-600 text-white px-2 py-1 rounded text-xs font-semibold transition">✗</button>
                        <?php endif; ?>
                        
                        <button onclick='alert(JSON.stringify(<?= json_encode($a, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>))' class="bg-gray-600 hover:bg-gray-500 text-white px-2 py-1 rounded text-xs font-semibold transition">👁</button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>