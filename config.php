<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================
// CONFIGURAÇÃO DO BANCO
// ============================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edumatric";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro de conexão: " . htmlspecialchars($conn->connect_error));
}

// Define charset para evitar problemas de encoding
$conn->set_charset("utf8mb4");

// ============================
// CONSTANTES GLOBAIS
// ============================
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('SESSION_TIMEOUT', 1800); // 30 minutos
define('HASH_ALGO', PASSWORD_ARGON2ID);

// ============================
// FUNÇÃO: GERAR ID OFICIAL
// ============================
function gerarIDOficial($conn, $nome = null) {
    
    if (!$nome || trim($nome) === "") {
        $iniciais = "ALU";
    } else {
        $iniciais = strtoupper(substr(preg_replace("/[^a-zA-Z]/", "", $nome), 0, 3));
        if(strlen($iniciais) < 3){
            $iniciais = str_pad($iniciais, 3, "X");
        }
    }

    $sigla = "V";

    do {
        $numero = rand(100, 999);
        $id = $iniciais . $sigla . $numero;

        $stmt = $conn->prepare("SELECT id FROM alunos WHERE id_oficial = ?");
        if (!$stmt) {
            throw new Exception("Erro na query: " . $conn->error);
        }
        
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->store_result();

    } while ($stmt->num_rows > 0);

    $stmt->close();
    return $id;
}

// ============================
// FUNÇÃO: PROTEGER PÁGINA
// ============================
function protegerPagina($tipoUsuario) {
    if (!isset($_SESSION['tipo']) || !isset($_SESSION['usuario'])) {
        header("Location: index.php");
        exit();
    }
    
    if ($_SESSION['tipo'] !== $tipoUsuario) {
        header("Location: index.php");
        exit();
    }

    // Verificar timeout de sessão
    if (isset($_SESSION['ultimo_acesso'])) {
        if (time() - $_SESSION['ultimo_acesso'] > SESSION_TIMEOUT) {
            logoutSeguro();
        }
    }
    
    $_SESSION['ultimo_acesso'] = time();
}

// ============================
// FUNÇÃO: LOGOUT SEGURO
// ============================
function logoutSeguro() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: index.php");
    exit();
}

// ============================
// FUNÇÃO: GERAR RELATÓRIO
// ============================
function gerarRelatorio($conn, $tipo = "Geral", $periodo = null, $criado_por = null) {
    
    $stmt = $conn->prepare(
        "INSERT INTO relatorios (tipo, periodo, total_alunos, total_pendentes, total_aprovados, total_recusados, criado_por) 
         VALUES (?, ?, 
            (SELECT COUNT(*) FROM alunos),
            (SELECT COUNT(*) FROM alunos WHERE estado='Pendente'),
            (SELECT COUNT(*) FROM alunos WHERE estado='Aprovado'),
            (SELECT COUNT(*) FROM alunos WHERE estado='Recusado'),
            ?)"
    );

    if (!$stmt) {
        return false;
    }

    $periodo = $periodo ?? date('Y-m');
    $stmt->bind_param("sss", $tipo, $periodo, $criado_por);
    
    $resultado = $stmt->execute();
    $stmt->close();
    
    return $resultado;
}

// ============================
// FUNÇÃO: REGISTRAR LOG
// ============================
function registrarLog($conn, $acao, $tabela = null, $registro_id = null, $descricao = null) {
    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $tipo_usuario = $_SESSION['tipo'] ?? 'anonimo';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $conn->prepare(
        "INSERT INTO logs (usuario, tipo_usuario, acao, tabela, registro_id, descricao, ip_address, user_agent) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if ($stmt) {
        $stmt->bind_param("ssssisss", $usuario, $tipo_usuario, $acao, $tabela, $registro_id, $descricao, $ip, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

// ============================
// FUNÇÃO: VERIFICAR MATRÍCULAS ABERTAS
// ============================
function verificarMatriculasAbertas($conn) {
    $stmt = $conn->prepare("SELECT valor FROM configuracoes WHERE chave = 'matriculas_abertas' LIMIT 1");
    
    if (!$stmt) {
        return false;
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();
    $stmt->close();

    return isset($row) && strtolower($row['valor']) === 'sim';
}

// ============================
// FUNÇÃO: OBTER CONFIGURAÇÃO
// ============================
function obterConfiguracao($conn, $chave, $padrao = null) {
    $stmt = $conn->prepare("SELECT valor FROM configuracoes WHERE chave = ? LIMIT 1");
    
    if (!$stmt) {
        return $padrao;
    }

    $stmt->bind_param("s", $chave);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $row = $resultado->fetch_assoc();
    $stmt->close();

    return isset($row) ? $row['valor'] : $padrao;
}

// ============================
// FUNÇÃO: ESCAPAR HTML
// ============================
function esc($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

// ============================
// HEADERS DE SEGURANÇA
// ============================
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
?>