<?php
// diretor_area.php
session_start();
if(!isset($_SESSION['diretor_id'])){
    header("Location: login_diretor.php");
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

// Relatórios enviados pela secretaria
$total_alunos = $conn->query("SELECT COUNT(*) as total FROM matriculas")->fetch_assoc()['total'];
$total_10 = $conn->query("SELECT COUNT(*) as total FROM matriculas WHERE classe='10ª'")->fetch_assoc()['total'];
$total_11 = $conn->query("SELECT COUNT(*) as total FROM matriculas WHERE classe='11ª'")->fetch_assoc()['total'];
$total_12 = $conn->query("SELECT COUNT(*) as total FROM matriculas WHERE classe='12ª'")->fetch_assoc()['total'];

$cursos = $conn->query("SELECT curso, COUNT(*) as total FROM matriculas GROUP BY curso");

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área Diretor - EduMatric</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f4f8; color: #003366; padding: 20px; }
        h1, h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        th { background-color: #007bff; color: #fff; }
        .logout { text-align: center; margin-top: 20px; }
        .logout a { color: #007bff; text-decoration: none; font-weight: bold; }
        .logout a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Área do Diretor</h1>

    <h2>Relatórios Gerais</h2>
    <p>Total de alunos: <?= $total_alunos ?></p>
    <p>10ª Classe: <?= $total_10 ?> | 11ª Classe: <?= $total_11 ?> | 12ª Classe: <?= $total_12 ?></p>

    <h2>Distribuição por Curso</h2>
    <table>
        <tr>
            <th>Curso</th>
            <th>Total de Alunos</th>
        </tr>
        <?php while($curso = $cursos->fetch_assoc()): ?>
        <tr>
            <td><?= $curso['curso'] ?></td>
            <td><?= $curso['total'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="logout">
        <a href="logout_diretor.php">Sair</a>
    </div>
</body>
</html>
