<?php
// secretaria_area.php
session_start();
if(!isset($_SESSION['sec_id'])){
    header("Location: login_secretaria.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "edumatric";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    die("Erro na conexão: ".$conn->connect_error);
}

// Aceitar ou recusar matrícula
if(isset($_POST['acao'])){
    $id_aluno = $_POST['id_aluno'];
    $status = $_POST['acao']; // Aceite ou Recusado

    $stmt = $conn->prepare("UPDATE matriculas SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id_aluno);
    $stmt->execute();
}

// Buscar matrículas pendentes
$pendentes = $conn->query("SELECT * FROM matriculas WHERE status='Pendente' ORDER BY id ASC");

// Gerar relatórios (simples)
$relatorio_total = $conn->query("SELECT COUNT(*) as total FROM matriculas")->fetch_assoc()['total'];
$relatorio_10 = $conn->query("SELECT COUNT(*) as total FROM matriculas WHERE classe='10ª'")->fetch_assoc()['total'];
$relatorio_11 = $conn->query("SELECT COUNT(*) as total FROM matriculas WHERE classe='11ª'")->fetch_assoc()['total'];
$relatorio_12 = $conn->query("SELECT COUNT(*) as total FROM matriculas WHERE classe='12ª'")->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área Secretaria - EduMatric</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f4f8; color: #003366; padding: 20px; }
        h1, h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #007bff; color: #fff; }
        button { padding: 8px 12px; margin: 2px; background-color: #28a745; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        button.recusar { background-color: #dc3545; }
        .logout { text-align: center; margin-top: 20px; }
        .logout a { color: #007bff; text-decoration: none; font-weight: bold; }
        .logout a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Área Administrativa - Secretaria</h1>

    <h2>Matrículas Pendentes</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Curso</th>
            <th>Classe</th>
            <th>Período</th>
            <th>Ações</th>
        </tr>
        <?php while($aluno = $pendentes->fetch_assoc()): ?>
        <tr>
            <td><?= $aluno['id'] ?></td>
            <td><?= $aluno['nome'] ?></td>
            <td><?= $aluno['curso'] ?></td>
            <td><?= $aluno['classe'] ?></td>
            <td><?= $aluno['periodo'] ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id_aluno" value="<?= $aluno['id'] ?>">
                    <button type="submit" name="acao" value="Aceite">Aceitar</button>
                    <button type="submit" name="acao" value="Recusado" class="recusar">Recusar</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <h2>Relatórios Gerais</h2>
    <p>Total de alunos: <?= $relatorio_total ?></p>
    <p>10ª Classe: <?= $relatorio_10 ?> | 11ª Classe: <?= $relatorio_11 ?> | 12ª Classe: <?= $relatorio_12 ?></p>

    <div class="logout">
        <a href="logout_secretaria.php">Sair</a>
    </div>
</body>
</html>
