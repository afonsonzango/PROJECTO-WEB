-- EduMatric - PostgreSQL Schema

CREATE TABLE IF NOT EXISTS alunos (
    id SERIAL PRIMARY KEY,
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
    estado VARCHAR(20) DEFAULT 'Pendente' CHECK (estado IN ('Pendente','Aprovado','Recusado')),
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
    pagamento_status VARCHAR(50) DEFAULT 'Pendente'
);

CREATE TABLE IF NOT EXISTS secretaria (
    id SERIAL PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS diretor (
    id SERIAL PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS configuracoes (
    id SERIAL PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor VARCHAR(500) NOT NULL,
    tipo VARCHAR(20) DEFAULT 'texto',
    descricao TEXT,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO configuracoes (chave, valor, tipo, descricao) VALUES
('matriculas_abertas', 'sim', 'boolean', 'Status das matrículas (sim/não)')
ON CONFLICT (chave) DO NOTHING;
