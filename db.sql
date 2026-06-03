-- Criação do banco de dados completo
DROP DATABASE IF EXISTS edumatric;
CREATE DATABASE IF NOT EXISTS edumatric;
USE edumatric;

-- =============================
-- Tabela alunos (PRINCIPAL)
-- =============================
CREATE TABLE IF NOT EXISTS alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    nif VARCHAR(50) UNIQUE DEFAULT NULL,
    numero VARCHAR(50) UNIQUE DEFAULT NULL,
    senha VARCHAR(255) DEFAULT NULL,
    curso VARCHAR(100) NOT NULL,
    classe VARCHAR(50) NOT NULL,
    periodo VARCHAR(50) DEFAULT 'Não definido',
    telefone VARCHAR(50) DEFAULT NULL,
    endereco VARCHAR(255) DEFAULT NULL,
    estado ENUM('Pendente','Aprovado','Recusado') DEFAULT 'Pendente',
    id_oficial VARCHAR(20) UNIQUE DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    certificado VARCHAR(255) DEFAULT NULL,
    mensagem_recusa VARCHAR(500) DEFAULT NULL,
    sexo VARCHAR(20) DEFAULT NULL,
    escola_anterior VARCHAR(255) DEFAULT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_reconfirmacao TIMESTAMP NULL,
    data_aprovacao TIMESTAMP NULL,
    status_reconfirmacao VARCHAR(50) DEFAULT NULL,
    pagamento_status VARCHAR(50) DEFAULT 'Pendente',
    INDEX idx_email (email),
    INDEX idx_numero (numero),
    INDEX idx_id_oficial (id_oficial),
    INDEX idx_estado (estado),
    INDEX idx_curso (curso),
    INDEX idx_status_reconfirmacao (status_reconfirmacao)
);

-- =============================
-- Tabela secretaria (ADMINISTRAÇÃO)
-- =============================
CREATE TABLE IF NOT EXISTS secretaria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    INDEX idx_usuario (usuario),
    INDEX idx_ativo (ativo)
);

-- =============================
-- Tabela diretor (ADMINISTRAÇÃO)
-- =============================
CREATE TABLE IF NOT EXISTS diretor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    INDEX idx_usuario (usuario),
    INDEX idx_ativo (ativo)
);

-- =============================
-- Tabela configuracoes
-- =============================
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor VARCHAR(500) NOT NULL,
    tipo VARCHAR(20) DEFAULT 'texto',
    descricao TEXT,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
);

-- Inserts das configurações iniciais
INSERT INTO configuracoes (chave, valor, tipo, descricao) VALUES
('matriculas_abertas', 'sim', 'boolean', 'Status das matrículas (sim/não)'),
('periodo_abertura', '2026-01-01', 'data', 'Data de abertura das matrículas'),
('periodo_fechamento', '2026-08-31', 'data', 'Data de fechamento das matrículas'),
('limite_alunos_por_turma', '50', 'numero', 'Limite máximo de alunos por turma');

-- =============================
-- Tabela relatorios
-- =============================
CREATE TABLE IF NOT EXISTS relatorios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) DEFAULT 'Geral',
    periodo VARCHAR(100),
    total_alunos INT DEFAULT 0,
    total_pendentes INT DEFAULT 0,
    total_aprovados INT DEFAULT 0,
    total_recusados INT DEFAULT 0,
    criado_por VARCHAR(100),
    data_geracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_data (data_geracao),
    INDEX idx_tipo (tipo),
    INDEX idx_criado_por (criado_por)
);

-- =============================
-- Tabela historico_reconfirmacao
-- =============================
CREATE TABLE IF NOT EXISTS historico_reconfirmacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    id_oficial VARCHAR(20) NOT NULL,
    nome_anterior VARCHAR(255),
    curso_anterior VARCHAR(100),
    classe_anterior VARCHAR(50),
    observacao VARCHAR(500),
    data DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    INDEX idx_aluno_id (aluno_id),
    INDEX idx_id_oficial (id_oficial),
    INDEX idx_data (data)
);

-- =============================
-- Tabela uploads
-- =============================
CREATE TABLE IF NOT EXISTS uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    arquivo VARCHAR(500) NOT NULL,
    tamanho INT,
    mime_type VARCHAR(100),
    data_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    INDEX idx_aluno_id (aluno_id),
    INDEX idx_tipo (tipo),
    INDEX idx_data_upload (data_upload)
);

-- =============================
-- Tabela saber_mais
-- =============================
CREATE TABLE IF NOT EXISTS saber_mais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo LONGTEXT NOT NULL,
    autor VARCHAR(100),
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE,
    INDEX idx_ativo (ativo),
    INDEX idx_data_criacao (data_criacao)
);

-- =============================
-- Tabela logs (AUDITORIA)
-- =============================
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100),
    tipo_usuario VARCHAR(50),
    acao VARCHAR(100),
    tabela VARCHAR(50),
    registro_id INT,
    descricao TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario),
    INDEX idx_acao (acao),
    INDEX idx_data (data),
    INDEX idx_tipo_usuario (tipo_usuario)
);