<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db = "edumatric";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    die("Erro na conexão: ".$conn->connect_error);
}

// Função para gerar ID oficial único
function gerarIDOficial($conn) {
    do {
        $id = 'pv'.mt_rand(1000,9999); // pv + 4 dígitos
        $check = $conn->query("SELECT id_oficial FROM matriculas WHERE id_oficial='$id'");
    } while($check->num_rows > 0);
    return $id;
}

$dados = [];
$mensagem = "";

// Passo 1: Buscar aluno existente
if(isset($_POST['buscar'])){
    $id_anterior = $_POST['id_anterior'];
    $nome = $_POST['nome'];

    $stmt = $conn->prepare("SELECT * FROM alunos WHERE id_oficial=? AND nome=?");
    $stmt->bind_param("ss", $id_anterior, $nome);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if($resultado->num_rows == 1){
        $dados = $resultado->fetch_assoc();
    } else {
        $mensagem = "Aluno não encontrado. Verifique ID e nome.";
    }
}

// Passo 2: Enviar reconfirmação
if(isset($_POST['reconfirmar'])){
    $nome = $_POST['nome'];
    $sexo = $_POST['sexo'];
    $curso = $_POST['curso'];
    $classe = $_POST['classe'];
    $periodo = $_POST['periodo'];
    $nif = $_POST['nif'];
    $contacto = $_POST['contacto'];
    $gmail = $_POST['gmail'];

    $id_oficial = gerarIDOficial($conn);

    $stmt = $conn->prepare("INSERT INTO matriculas (nome, sexo, curso, classe, periodo, nif, contacto, gmail, status, id_oficial) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $status = 'Pendente';
    $stmt->bind_param("ssssssssss", $nome, $sexo, $curso, $classe, $periodo, $nif, $contacto, $gmail, $status, $id_oficial);
    $stmt->execute();

    $mensagem = "Reconfirmação enviada com sucesso! Seu novo ID oficial: $id_oficial. Aguarde aprovação da secretaria.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reconfirmação de Matrícula - EduMatric</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f4f8; color: #003366; padding: 20px; }
        h1 { text-align: center; margin-bottom: 20px; }
        form { max-width: 600px; margin: auto; background-color: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        label { display: block; margin-top: 15px; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; }
        button { margin-top: 20px; padding: 15px; width: 100%; background-color: #007bff; color: #fff; border: none; border-radius: 8px; font-size: 1em; cursor: pointer; transition: 0.3s; }
        button:hover { background-color: #0056b3; }
        .mensagem { text-align: center; margin-top: 15px; color: green; font-weight: bold; }
        .erro { text-align: center; margin-top: 15px; color: red; font-weight: bold; }
        .voltar { text-align: center; margin-top: 20px; }
        .voltar a { color: #007bff; text-decoration: none; font-weight: bold; }
        .voltar a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Reconfirmação de Matrícula</h1>

    <?php if(empty($dados)): ?>
        <form method="POST" action="">
            <label>ID Oficial Anterior</label>
            <input type="text" name="id_anterior" required>

            <label>Nome Completo</label>
            <input type="text" name="nome" required>

            <button type="submit" name="buscar">Buscar Dados</button>
        </form>
    <?php else: ?>
        <form method="POST" action="">
            <label>Nome Completo</label>
            <input type="text" name="nome" value="<?= $dados['nome'] ?>" required>

            <label>Sexo</label>
            <select name="sexo" required>
                <option value="Masculino" <?= $dados['sexo']=='Masculino'?'selected':'' ?>>Masculino</option>
                <option value="Feminino" <?= $dados['sexo']=='Feminino'?'selected':'' ?>>Feminino</option>
            </select>

            <label>Curso</label>
            <input type="text" name="curso" value="<?= $dados['curso'] ?>" required>

            <label>Classe</label>
            <select name="classe" required>
                <option value="11ª">11ª</option>
                <option value="12ª">12ª</option>
                <option value="13ª">13ª</option>
            </select>

            <label>Período</label>
            <input type="text" name="periodo" value="<?= $dados['periodo'] ?>" required>

            <label>NIF</label>
            <input type="text" name="nif" value="<?= $dados['nif'] ?>">

            <label>Contacto</label>
            <input type="text" name="contacto" value="<?= $dados['contacto'] ?>">

            <label>Gmail</label>
            <input type="email" name="gmail" value="<?= $dados['gmail'] ?>" required>

            <button type="submit" name="reconfirmar">Enviar Reconfirmação</button>
        </form>
    <?php endif; ?>

    <?php if($mensagem) echo "<p class='mensagem'>$mensagem</p>"; ?>

    <div class="voltar">
        <a href="index.php">← Voltar à Página Inicial</a>
    </div>
</body>
</html>
