DROP DATABASE IF EXISTS cel;
CREATE DATABASE cel;
USE cel;

CREATE TABLE chefia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL
);

CREATE TABLE divisao (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    chefia_id INT NOT NULL,
    FOREIGN KEY (chefia_id) REFERENCES chefia(id) ON DELETE CASCADE
);

CREATE TABLE perfis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao VARCHAR(255)
);

CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    idt_Mil VARCHAR(20) NOT NULL UNIQUE,
    pg VARCHAR(50) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    divisao_id INT,
    chefia_id INT,
    perfil_id INT NOT NULL DEFAULT 2,
    FOREIGN KEY (divisao_id) REFERENCES divisao(id) ON DELETE SET NULL,
    FOREIGN KEY (chefia_id) REFERENCES chefia(id) ON DELETE SET NULL,
    FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE RESTRICT
);

CREATE TABLE assuntos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assunto TEXT NOT NULL,
    critico ENUM('sim', 'nao') NOT NULL,
    prazo DATE NOT NULL,
    estado VARCHAR(50) DEFAULT 'pendente',
    dataAtualizacao DATE,
    criadoPor INT NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (criadoPor) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE historico (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assunto_id INT NOT NULL,
    data DATE NOT NULL,
    usuario INT,
    acao TEXT NOT NULL,
    FOREIGN KEY (assunto_id) REFERENCES assuntos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario) REFERENCES usuarios(id) ON DELETE SET NULL
);
CREATE TABLE acoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assunto_id INT NOT NULL,
    acao TEXT NOT NULL,
    providencia TEXT,
    estado VARCHAR(50) DEFAULT 'pendente',
    responsavel INT,
    dataAtualizacao DATE,
    FOREIGN KEY (assunto_id) REFERENCES assuntos(id) ON DELETE CASCADE,
    FOREIGN KEY (responsavel) REFERENCES usuarios(id) ON DELETE SET NULL
);
INSERT INTO perfis (nome, descricao) VALUES
('Administrador', 'Acesso total ao sistema, pode gerenciar usuários, assuntos, divisões e chefias'),
('Visualizador', 'Pode visualizar apenas os assuntos e o resumo'),
('Criador', 'Pode criar novos assuntos e visualizar o sistema');
INSERT INTO chefia (nome) VALUES
('Chefia de Material'),
('Chefia de Pessoal'),
('Chefia de Tecnologia');
INSERT INTO divisao (nome, chefia_id) VALUES
('Divisão de TI', 1),
('Divisão de Logística', 1),
('Divisão de Cadastro', 2),
('Divisão de RH', 2),
('Divisão de Redes', 3),
('Divisão de Sistemas', 3);
INSERT INTO usuarios (idt_Mil, pg, nome, senha, chefia_id, divisao_id, perfil_id) VALUES
('123456789', '1º Ten', 'João Silva', '$2y$10$kfx04LSwt1uM8AdYlB8BgOP1NV2oxvAMh1fabiY91oQPNYj/O94qS', 1, 1, 1),
('987654321', '3º Sgt', 'Maria Souza', '$2y$10$JzW3D8z5M6GyEAFn7VBNquQZ.Nkns5cUKiT6EDFMZIE1aR8zNFxfq', 2, 3, 2),
('192837465', 'Cap', 'Carlos Lima', '$2y$10$JzW3D8z5M6GyEAFn7VBNquQZ.Nkns5cUKiT6EDFMZIE1aR8zNFxfq', 3, 5, 3);
INSERT INTO assuntos (assunto, critico, prazo, estado, dataAtualizacao, criadoPor, ativo) VALUES
('Aquisição de equipamentos de informática para modernização do parque tecnológico', 'sim', '2025-01-20', 'pendente', '2025-01-15', 1, 1),
('Manutenção preventiva das viaturas administrativas do comando', 'nao', '2025-02-10', 'pendente', '2025-01-14', 1, 1);
INSERT INTO historico (assunto_id, data, usuario, acao) VALUES
(1, '2025-01-15', 1, 'Criou o assunto "Aquisição de equipamentos de informática para modernização do parque tecnológico"'),
(1, '2025-01-15', 1, 'Adicionou ação "Solicitar três orçamentos de diferentes fornecedores"'),
(2, '2025-01-14', 1, 'Criou o assunto "Manutenção preventiva das viaturas administrativas do comando"');
INSERT INTO acoes (assunto_id, acao, providencia, estado, responsavel, dataAtualizacao) VALUES
(1, 'Solicitar três orçamentos de diferentes fornecedores', 'Enviados emails para 5 fornecedores solicitando orçamentos detalhados', 'concluido', 1, '2025-01-15'),
(1, 'Analisar propostas técnicas e financeiras recebidas', '', 'pendente', 1, '2025-01-15'),
(2, 'Agendar revisão de todas as viaturas com a oficina credenciada', '', 'pendente', 1, '2025-01-14');


