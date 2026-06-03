# EduMatric — Documentação do Projecto

## Visão Geral

EduMatric é um sistema web de gestão de matrículas escolares desenvolvido em **Node.js** com arquitectura **MVC** (Model-View-Controller). Permite que alunos submetam candidaturas, a secretaria as aprove ou recuse, e o director acompanhe as estatísticas em tempo real.

---

## Índice

- [Estrutura do Projecto](#estrutura-do-projecto)
- [Tecnologias](#tecnologias)
- [Base de Dados](#base-de-dados)
- [Arquitectura MVC](#arquitectura-mvc)
- [Rotas](#rotas)
- [Contas de Acesso](#contas-de-acesso)
- [Como Executar](#como-executar)
- [Funcionalidades](#funcionalidades)

---

## Estrutura do Projecto

```
edumatric-front/
│
├── server.js               # Ponto de entrada da aplicação
├── package.json            # Dependências Node.js
├── schema.sql              # Esquema da base de dados PostgreSQL
├── seed.sql                # Dados de teste iniciais
│
├── src/
│   ├── config/
│   │   └── db.js           # Ligação à base de dados PostgreSQL (pg Pool)
│   │
│   ├── models/             # Camada de acesso a dados (Model)
│   │   ├── AlunoModel.js   # CRUD de alunos, aprovação, recusa, pesquisa
│   │   ├── SecretariaModel.js  # Autenticação e gestão da secretaria
│   │   └── DiretorModel.js     # Autenticação do director, toggle matrículas
│   │
│   ├── controllers/        # Lógica de negócio (Controller)
│   │   ├── HomeController.js        # Página inicial
│   │   ├── MatriculaController.js   # Submissão e consulta de matrículas
│   │   ├── AlunoController.js       # Login e painel do aluno
│   │   ├── SecretariaController.js  # Login e painel da secretaria
│   │   └── DiretorController.js     # Login e painel do director
│   │
│   ├── routes/
│   │   └── index.js        # Todas as rotas Express
│   │
│   ├── middleware/
│   │   └── auth.js         # Guards de sessão (requireAluno, requireSecretaria, requireDiretor)
│   │
│   └── views/              # Templates EJS (View)
│       ├── index.ejs              # Página inicial com particles
│       ├── fazer_matricula.ejs    # Formulário de matrícula
│       ├── status_matricula.ejs   # Consulta de estado
│       ├── login_aluno.ejs        # Login do aluno
│       ├── login_secretaria.ejs   # Login da secretaria
│       ├── login_diretor.ejs      # Login do director
│       ├── painel_aluno.ejs       # Área do aluno
│       ├── painel_secretaria.ejs  # Painel de gestão
│       └── painel_diretor.ejs     # Dashboard do director
│
├── public/
│   ├── css/
│   │   └── main.css        # Estilos globais partilhados (sem Tailwind)
│   ├── images/             # Imagens estáticas
│   └── uploads/            # Ficheiros enviados pelos alunos (fotos, certificados)
│
├── docs/                   # Esta documentação
└── legacy/                 # Ficheiros originais PHP/HTML (referência apenas)
```

---

## Tecnologias

| Tecnologia       | Versão   | Função                                |
|------------------|----------|---------------------------------------|
| Node.js          | 24+      | Servidor e runtime                    |
| Express          | 4.19     | Framework HTTP                        |
| EJS              | 3.1      | Motor de templates HTML               |
| PostgreSQL       | 14+      | Base de dados relacional              |
| pg               | 8.12     | Cliente PostgreSQL para Node.js       |
| bcryptjs         | 2.4      | Hash de senhas                        |
| express-session  | 1.18     | Gestão de sessões                     |
| multer           | 1.4      | Upload de ficheiros                   |
| Chart.js         | CDN      | Gráficos nos painéis                  |
| particles.js     | CDN      | Efeito de partículas na página inicial|
| CSS puro         | —        | Estilos (sem frameworks)              |

---

## Base de Dados

**Credenciais de ligação:**

```
Host:     localhost
Porto:    5432
Base:     edumatric
Utilizador: postgres
Senha:    1234
```

### Tabelas principais

#### `alunos`
Regista todos os candidatos e o estado da sua matrícula.

| Coluna               | Tipo          | Descrição                              |
|----------------------|---------------|----------------------------------------|
| id                   | SERIAL PK     | Identificador único                    |
| nome                 | VARCHAR(255)  | Nome completo do aluno                 |
| email                | VARCHAR(255)  | Email (único)                          |
| nif                  | VARCHAR(50)   | Número de Identificação Fiscal (único) |
| sexo                 | VARCHAR(20)   | Masculino / Feminino / Outro           |
| curso                | VARCHAR(100)  | Curso técnico escolhido                |
| classe               | VARCHAR(50)   | 10ª, 11ª, 12ª, 13ª                    |
| periodo              | VARCHAR(50)   | Manhã / Tarde / Noite                  |
| telefone             | VARCHAR(50)   | Número de contacto                     |
| escola_anterior      | VARCHAR(255)  | Escola de proveniência                 |
| endereco             | VARCHAR(255)  | Morada (opcional)                      |
| estado               | VARCHAR(20)   | Pendente / Aprovado / Recusado         |
| id_oficial           | VARCHAR(20)   | ID gerado na aprovação (ex: JOAB1001)  |
| foto                 | VARCHAR(255)  | Caminho do ficheiro de foto            |
| certificado          | VARCHAR(255)  | Caminho do certificado                 |
| mensagem_recusa      | VARCHAR(500)  | Motivo se recusado                     |
| data_cadastro        | TIMESTAMP     | Data de submissão                      |
| status_reconfirmacao | VARCHAR(50)   | Pendente / Aceita / Recusada           |

#### `secretaria`
Contas de acesso da secretaria.

| Coluna       | Tipo          | Descrição              |
|--------------|---------------|------------------------|
| id           | SERIAL PK     |                        |
| usuario      | VARCHAR(50)   | Nome de utilizador     |
| senha        | VARCHAR(255)  | Senha com bcrypt hash  |
| nome_completo| VARCHAR(255)  |                        |
| ativo        | BOOLEAN       | Conta activa?          |

#### `diretor`
Contas de acesso do director (estrutura idêntica à secretaria).

#### `configuracoes`
Parâmetros do sistema (ex: matrículas abertas/fechadas).

---

## Arquitectura MVC

```
Pedido HTTP
     │
     ▼
  Routes (src/routes/index.js)
     │
     ▼
  Middleware (auth.js)  ←── Verifica sessão
     │
     ▼
  Controller  ←── Recebe pedido, chama Model, passa dados à View
     │
     ├──► Model  ←── Executa queries PostgreSQL, retorna dados
     │
     └──► View (EJS)  ←── Renderiza HTML com dados do Controller
```

### Fluxo de uma matrícula

1. Aluno acede a `/fazer-matricula` → `MatriculaController.form()` → renderiza `fazer_matricula.ejs`
2. Aluno submete formulário → `MatriculaController.submit()` → `AlunoModel.criar()` → insere na BD
3. Secretaria acede ao painel → `SecretariaController.painel()` → `AlunoModel.listarTodos()` → lista alunos
4. Secretaria aprova → `SecretariaController.aprovar()` → `AlunoModel.aprovar()` → gera ID oficial, actualiza estado
5. Aluno consulta → `MatriculaController.status()` → `AlunoModel.buscarPorEmailENome()` → mostra resultado

---

## Rotas

### Públicas

| Método | Rota                  | Descrição                          |
|--------|-----------------------|------------------------------------|
| GET    | `/`                   | Página inicial                     |
| GET    | `/fazer-matricula`    | Formulário de matrícula            |
| POST   | `/fazer-matricula`    | Submeter matrícula                 |
| GET    | `/status-matricula`   | Formulário de consulta de estado   |
| POST   | `/status-matricula`   | Consultar estado por nome + email  |

### Aluno

| Método | Rota              | Descrição                    |
|--------|-------------------|------------------------------|
| GET    | `/login/aluno`    | Formulário de login          |
| POST   | `/login/aluno`    | Autenticar (ID + email)      |
| GET    | `/aluno/painel`   | Painel pessoal do aluno      |
| GET    | `/logout/aluno`   | Terminar sessão              |

### Secretaria

| Método | Rota                                 | Descrição                        |
|--------|--------------------------------------|----------------------------------|
| GET    | `/login/secretaria`                  | Formulário de login              |
| POST   | `/login/secretaria`                  | Autenticar                       |
| GET    | `/secretaria/painel`                 | Painel de gestão                 |
| POST   | `/secretaria/aprovar/:id`            | Aprovar matrícula                |
| POST   | `/secretaria/recusar/:id`            | Recusar com motivo               |
| POST   | `/secretaria/aceitar-reconf/:id`     | Aceitar reconfirmação            |
| POST   | `/secretaria/recusar-reconf/:id`     | Recusar reconfirmação            |
| POST   | `/secretaria/alterar-senha`          | Alterar senha da secretaria      |
| GET    | `/logout/secretaria`                 | Terminar sessão                  |

### Director

| Método | Rota                            | Descrição                       |
|--------|---------------------------------|---------------------------------|
| GET    | `/login/diretor`                | Formulário de login             |
| POST   | `/login/diretor`                | Autenticar                      |
| GET    | `/diretor/painel`               | Dashboard com estatísticas      |
| POST   | `/diretor/toggle-matriculas`    | Abrir / fechar matrículas       |
| GET    | `/logout/diretor`               | Terminar sessão                 |

---

## Contas de Acesso

Após executar `seed.sql`, as seguintes contas ficam disponíveis:

### Secretaria
- **Utilizador:** `secretaria`
- **Senha:** `senha123`
- **Acesso:** `/login/secretaria`

### Director
- **Utilizador:** `diretor`
- **Senha:** `senha123`
- **Acesso:** `/login/diretor`

### Alunos de teste

| Nome                    | ID Oficial  | Email                        | Estado   |
|-------------------------|-------------|------------------------------|----------|
| Joao Antonio Baptista   | JOAB1001    | joao.baptista@gmail.com      | Aprovado |
| Maria Fatima Nzinga     | MAFN1002    | maria.nzinga@gmail.com       | Aprovado |
| Manuel Augusto Teixeira | MAUT1007    | manuel.teixeira@gmail.com    | Aprovado |
| Pedro Augusto Lopes     | —           | pedro.lopes@gmail.com        | Pendente |
| Ana Lucia Domingos      | —           | ana.domingos@gmail.com       | Pendente |
| Sofia Isabel Tavares    | —           | sofia.tavares@gmail.com      | Pendente |
| Rosa Helena Chipilica   | —           | rosa.chipilica@gmail.com     | Pendente |
| Carlos Eduardo Mbambi   | —           | carlos.mbambi@gmail.com      | Recusado |

Login do aluno: ID Oficial + Email (apenas alunos aprovados têm ID).

---

## Como Executar

### Pré-requisitos
- Node.js 18+
- PostgreSQL 14+

### Passos

```bash
# 1. Instalar dependências
npm install

# 2. Criar a base de dados (se ainda não existir)
psql -U postgres -c "CREATE DATABASE edumatric;"

# 3. Criar as tabelas
psql -U postgres -d edumatric -f schema.sql

# 4. Inserir dados de teste
psql -U postgres -d edumatric -f seed.sql

# 5. Iniciar o servidor
npm start
# Servidor disponível em: http://localhost:3000
```

### Desenvolvimento (reinício automático)
```bash
npm run dev
```

---

## Funcionalidades

### Aluno
- Submeter candidatura de matrícula com foto e certificado
- Consultar o estado da matrícula (pendente / aprovado / recusado)
- Fazer login com ID oficial após aprovação
- Ver dados pessoais e académicos no painel

### Secretaria
- Dashboard com estatísticas em tempo real
- Listar todos os alunos com pesquisa por nome
- Aprovar matrícula (gera ID oficial automaticamente)
- Recusar matrícula com mensagem de motivo
- Gerir reconfirmações de matrícula
- Alterar senha de acesso

### Director
- Dashboard com gráficos de distribuição
- Visualizar taxas de aprovação e recusa
- Abrir e fechar inscrições do sistema

---

## Geração do ID Oficial

Quando uma matrícula é aprovada, o sistema gera automaticamente um ID único no formato:

```
[2 letras do primeiro nome] + [2 letras do último nome] + [4 dígitos aleatórios]

Exemplo: João Baptista → JOAB1234
```

O sistema verifica unicidade na base de dados antes de atribuir o ID.

---

## Upload de Ficheiros

Os ficheiros enviados (fotos e certificados) são guardados em `public/uploads/`. O nome do ficheiro é prefixado com o timestamp Unix para evitar colisões.

**Limites:** 10MB por ficheiro. Formatos aceites:
- Foto: `.jpg`, `.jpeg`, `.png`
- Certificado: `.pdf`, `.jpg`, `.jpeg`, `.png`
