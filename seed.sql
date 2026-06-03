-- EduMatric — Dados de Teste
-- Executar: psql -U postgres -d edumatric -f seed.sql

-- Limpar dados anteriores (opcional)
-- TRUNCATE alunos, secretaria, diretor RESTART IDENTITY CASCADE;

-- ============================================================
-- Contas de acesso
-- Senhas geradas com bcrypt (valor original: "senha123")
-- ============================================================

-- Senha para ambas as contas: senha123
INSERT INTO secretaria (usuario, senha, nome_completo, email) VALUES
('secretaria', '$2a$10$k6QOhL1wFFZzmQmfkPSXQuh5vkkURo7CGdBomG9K2QhSDnhvxyOR2', 'Ana Beatriz Silva', 'secretaria@edumatric.ao')
ON CONFLICT (usuario) DO NOTHING;

INSERT INTO diretor (usuario, senha, nome_completo, email) VALUES
('diretor', '$2a$10$k6QOhL1wFFZzmQmfkPSXQuh5vkkURo7CGdBomG9K2QhSDnhvxyOR2', 'Carlos Manuel Ferreira', 'diretor@edumatric.ao')
ON CONFLICT (usuario) DO NOTHING;

-- ============================================================
-- Alunos de teste
-- ============================================================

INSERT INTO alunos (nome, email, nif, sexo, curso, classe, periodo, telefone, escola_anterior, endereco, estado, id_oficial) VALUES
('Joao Antonio Baptista',  'joao.baptista@gmail.com',   '005LA001', 'Masculino', 'Informática de Gestão',   '10ª', 'Manhã',  '+244 923 456 789', 'Escola Primaria Ngola Kiluanje', 'Rangel, Luanda',    'Aprovado',  'JOAB1001'),
('Maria Fatima Nzinga',    'maria.nzinga@gmail.com',    '005LA002', 'Feminino',  'Gestão Empresarial',      '11ª', 'Tarde',  '+244 912 345 678', 'Instituto Medio Comercial',     'Viana, Luanda',     'Aprovado',  'MAFN1002'),
('Pedro Augusto Lopes',    'pedro.lopes@gmail.com',     '005LA003', 'Masculino', 'Telecomunicações',        '12ª', 'Noite',  '+244 934 567 890', 'Escola Secundaria da Ingombota','Ingombota, Luanda',  'Pendente',  NULL),
('Ana Lucia Domingos',     'ana.domingos@gmail.com',    '005LA004', 'Feminino',  'Eletricidade Industrial', '13ª', 'Manhã',  '+244 945 678 901', 'Instituto Tecnico de Luanda',   'Sambizanga, Luanda','Pendente',  NULL),
('Carlos Eduardo Mbambi',  'carlos.mbambi@gmail.com',   '005LA005', 'Masculino', 'Informática de Gestão',   '10ª', 'Tarde',  '+244 956 789 012', 'Escola Primaria do Cazenga',    'Cazenga, Luanda',   'Recusado',  NULL),
('Sofia Isabel Tavares',   'sofia.tavares@gmail.com',   '005LA006', 'Feminino',  'Gestão Empresarial',      '12ª', 'Manhã',  '+244 967 890 123', 'Colegio Adventista',            'Maianga, Luanda',   'Pendente',  NULL),
('Manuel Augusto Teixeira','manuel.teixeira@gmail.com', '005LA007', 'Masculino', 'Telecomunicações',        '11ª', 'Noite',  '+244 978 901 234', 'Escola Secundaria de Cacuaco',  'Cacuaco, Luanda',   'Aprovado',  'MAUT1007'),
('Rosa Helena Chipilica',  'rosa.chipilica@gmail.com',  '005LA008', 'Feminino',  'Eletricidade Industrial', '10ª', 'Tarde',  '+244 989 012 345', 'Instituto Politecnico de Luanda','Kilamba, Luanda',  'Pendente',  NULL)
ON CONFLICT (email) DO NOTHING;

-- Mensagem de recusa para o aluno recusado
UPDATE alunos SET mensagem_recusa = 'Documentacao incompleta. O certificado de habilitacoes nao foi submetido correctamente. Por favor, submeta nova candidatura com todos os documentos.' WHERE email = 'carlos.mbambi@gmail.com';
