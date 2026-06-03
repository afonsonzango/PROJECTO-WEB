<?php
// reconfirmar_matricula.php

$host = "localhost";
$user = "root";
$pass = "";
$db = "edumatric";

$conn = new mysqli($host, $user, $pass, $db);
if($conn->connect_error){
    die("Erro na conexão: ".$conn->connect_error);
}

// Inicializar variáveis
$aluno = null;
$mensagem = "";

// Buscar dados do aluno pelo ID e nome
if(isset($_POST['buscar'])){
    $id_anterior = $_POST['id_anterior'];
    $nome = $_POST['nome'];

    $stmt = $conn->prepare("SELECT * FROM matriculas WHERE id = ? AND nome = ?");
    $stmt->bind_param("is", $id_anterior, $nome);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if($resultado->num_rows > 0){
        $aluno = $resultado->fetch_assoc();
    } else {
        $mensagem = "Aluno não encontrado. Verifique ID e nome.";
    }
}

// Atualizar dados para reconfirmação
if(isset($_POST['atualizar'])){
    $id = $_POST['id'];
    $sexo = $_POST['sexo'];
    $curso = $_POST['curso'];
    $classe = $_POST['classe'];
    $periodo = $_POST['periodo'];
    $contacto = $_POST['contacto'];
    $gmail = $_POST['gmail'];

    $stmt = $conn->prepare("UPDATE matriculas SET sexo=?, curso=?, classe=?, periodo=?, contacto=?, gmail=?, status='Pendente' WHERE id=?");
    $stmt->bind_param("ssssssi", $sexo, $curso, $classe, $periodo, $contacto, $gmail, $id);

    if($stmt->execute()){
        echo "<script>alert('Reconfirmação enviada com sucesso! Aguarde análise da secretaria.'); window.location='status_matricula.php';</script>";
    } else {
        echo "<script>alert('Erro ao enviar reconfirmação.');</script>";
    }
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
        h1 { text-align: center; margin-bottom: 30px; }
        form { max-width: 600px; margin: auto; background-color: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        label { display: block; margin-top: 15px; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; }
        button { margin-top: 20px; padding: 15px; width: 100%; background-color: #007bff; color: #fff; border: none; border-radius: 8px; font-size: 1em; cursor: pointer; transition: 0.3s; }
        button:hover { background-color: #0056b3; }
        .mensagem { color: red; text-align: center; margin-bottom: 15px; }
        .voltar { text-align: center; margin-top: 15px; }
        .voltar a { color: #007bff; text-decoration: none; font-weight: bold; }
        .voltar a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Reconfirmação de Matrícula</h1>

    <!-- Formulário de busca -->
    <form method="POST" action="">
        <h3>Buscar Aluno Existente</h3>
        <?php if($mensagem != "") { echo "<p class='mensagem'>$mensagem</p>"; } ?>
        <label>ID Anterior</label>
        <input type="number" name="id_anterior" required>

        <label>Nome Completo</label>
        <input type="text" name="nome" required>

        <button type="submit" name="buscar">Buscar</button>
    </form>

    <!-- Formulário de atualização -->
    <?php if($aluno): ?>
    <form method="POST" action="">
        <input type="hidden" name="id" value="<?= $aluno['id'] ?>">
        <label>Sexo</label>
        <select name="sexo" required>
            <option value="<?= $aluno['sexo'] ?>"><?= $aluno['sexo'] ?></option>
            <option value="Masculino">Masculino</option>
            <option value="Feminino">Feminino</option>
        </select>

        <label>Curso</label>
        <select name="curso" required>
            <option value="<?= $aluno['curso'] ?>"><?= $aluno['curso'] ?></option>
            <option value="Informática">Informática</option>
            <option value="Técnico Administrativo">Técnico Administrativo</option>
            <option value="Enfermagem">Enfermagem</option>
        </select>

        <label>Classe</label>
        <select name="classe" required>
            <?php
            $classes = ['10ª','11ª','12ª'];
            foreach($classes as $c){
                $selected = ($aluno['classe']==$c)?"selected":"";
                echo "<option value='$c' $selected>$c</option>";
            }
            ?>
        </select>

        <label>Período</label>
        <select name="periodo" required>
            <option value="<?= $aluno['periodo'] ?>"><?= $aluno['periodo'] ?></option>
            <option value="Manhã">Manhã</option>
            <option value="Tarde">Tarde</option>
            <option value="Noite">Noite</option>
        </select>

        <label>Contacto</label>
        <input type="text" name="contacto" value="<?= $aluno['contacto'] ?>" required>

        <label>Email (Gmail)</label>
        <input type="email" name="gmail" value="<?= $aluno['gmail'] ?>" required>

        <button type="submit" name="atualizar">Enviar Reconfirmação</button>
    </form>
    <?php endif; ?>

    <div class="voltar">
        <a href="index.php">← Voltar à Página Inicial</a>
    </div>
</body>
</html>
