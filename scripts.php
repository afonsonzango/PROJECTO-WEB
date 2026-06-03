<?php
// aprovar_matricula.php
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

// Função para gerar ID oficial único
function gerarIDOficial($conn) {
    do {
        $id = 'pv'.mt_rand(1000,9999); // pv seguido de 4 dígitos aleatórios
        $check = $conn->query("SELECT id_oficial FROM matriculas WHERE id_oficial='$id'");
    } while($check->num_rows > 0);
    return $id;
}

// Verifica se recebeu matrícula para aprovar
if(isset($_POST['aprovar'])){
    $id_matricula = $_POST['id_matricula'];

    // Gera ID oficial
    $id_oficial = gerarIDOficial($conn);

    // Atualiza status da matrícula e adiciona ID oficial
    $stmt = $conn->prepare("UPDATE matriculas SET status='Aceite', id_oficial=? WHERE id=?");
    $stmt->bind_param("si", $id_oficial, $id_matricula);
    $stmt->execute();

    // Recupera dados da matrícula aprovada
    $mat = $conn->query("SELECT * FROM matriculas WHERE id=$id_matricula")->fetch_assoc();

    // Inserir aluno na tabela alunos (área restrita)
    $senha_padrao = password_hash('123456', PASSWORD_ARGON2ID); // senha padrão inicial
    $stmt2 = $conn->prepare("INSERT INTO alunos (nome, sexo, curso, classe, periodo, nif, contacto, gmail, senha, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $status = 'Aceite';
    $stmt2->bind_param("ssssssssss", $mat['nome'], $mat['sexo'], $mat['curso'], $mat['classe'], $mat['periodo'], $mat['nif'], $mat['contacto'], $mat['gmail'], $senha_padrao, $status);
    $stmt2->execute();

    echo "Matrícula aprovada com sucesso! ID oficial: $id_oficial";
}

$conn->close();
?>
