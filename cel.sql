-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 30/07/2025 às 18:08
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `cel`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `acoes`
--

CREATE TABLE `acoes` (
  `id` int(11) NOT NULL,
  `assunto_id` int(11) NOT NULL,
  `acao` text NOT NULL,
  `providencia` text DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'pendente',
  `responsavel` int(11) DEFAULT NULL,
  `dataAtualizacao` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `acoes`
--

INSERT INTO `acoes` (`id`, `assunto_id`, `acao`, `providencia`, `estado`, `responsavel`, `dataAtualizacao`) VALUES
(1, 1, 'Solicitar três orçamentos de diferentes fornecedores', 'Enviados emails para 5 fornecedores solicitando orçamentos detalhados', 'concluido', 4, '2025-07-30'),
(2, 1, 'Analisar propostas técnicas e financeiras recebidas', '', 'pendente', 4, '2025-07-30'),
(3, 2, 'Agendar revisão de todas as viaturas com a oficina credenciada', '', 'pendente', 4, '2025-07-30'),
(4, 3, 'sdaf', 'fdsa', 'pendente', 4, '2025-07-30'),
(5, 4, 'fdsa', 'fdsa', 'pendente', 4, '2025-07-30'),
(6, 5, 'fdsa', 'fdsa', 'pendente', 4, '2025-07-30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `assuntos`
--

CREATE TABLE `assuntos` (
  `id` int(11) NOT NULL,
  `assunto` text NOT NULL,
  `critico` enum('sim','nao') NOT NULL,
  `prazo` date NOT NULL,
  `estado` varchar(50) DEFAULT 'pendente',
  `dataAtualizacao` date DEFAULT NULL,
  `criadoPor` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `assuntos`
--

INSERT INTO `assuntos` (`id`, `assunto`, `critico`, `prazo`, `estado`, `dataAtualizacao`, `criadoPor`, `ativo`) VALUES
(1, 'Aquisição de equipamentos de informática para modernização do parque tecnológico', 'sim', '2025-08-07', 'pendente', '2025-07-30', 1, 1),
(2, 'Manutenção preventiva das viaturas administrativas do comando', 'nao', '2025-08-07', 'pendente', '2025-07-30', 1, 1),
(3, 'sdgsfdg', 'sim', '2025-08-08', 'pendente', '2025-07-30', 4, 1),
(4, 'sdaf', 'sim', '2025-08-08', 'concluido', '2025-07-30', 4, 1),
(5, 'fdsf', 'sim', '0001-11-11', 'pendente', '2025-07-30', 4, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `chefia`
--

CREATE TABLE `chefia` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `chefia`
--

INSERT INTO `chefia` (`id`, `nome`) VALUES
(1, 'COEX'),
(2, 'DFPC'),
(3, 'C Mat'),
(4, 'C Sup'),
(5, 'C M Av EX'),
(6, 'C Op Log'),
(7, 'Ba Ap Log'),
(8, 'COLOG');

-- --------------------------------------------------------

--
-- Estrutura para tabela `divisao`
--

CREATE TABLE `divisao` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `chefia_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `divisao`
--

INSERT INTO `divisao` (`id`, `nome`, `chefia_id`) VALUES
(1, 'DPIC', 3),
(2, 'Divisão Santa Maria', 3),
(3, 'Classe V - Armamento', 3),
(4, 'Classe III e IX - Motomecanização', 3),
(5, 'Classe IX - Blindados', 3),
(6, 'Turma de apoio', 3),
(7, 'DPIC', 1),
(8, 'Ch Mat', 3),
(9, 'S Cmt Log', 8);

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico`
--

CREATE TABLE `historico` (
  `id` int(11) NOT NULL,
  `assunto_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `usuario` int(11) DEFAULT NULL,
  `acao` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `historico`
--

INSERT INTO `historico` (`id`, `assunto_id`, `data`, `usuario`, `acao`) VALUES
(1, 1, '2025-01-15', 1, 'Criou o assunto \"Aquisição de equipamentos de informática para modernização do parque tecnológico\"'),
(2, 1, '2025-01-15', 1, 'Adicionou ação \"Solicitar três orçamentos de diferentes fornecedores\"'),
(3, 2, '2025-01-14', 1, 'Criou o assunto \"Manutenção preventiva das viaturas administrativas do comando\"'),
(4, 1, '2025-07-30', 4, 'Alterou o prazo de \"20/01/2025\" para \"07/08/2025\"'),
(5, 2, '2025-07-30', 4, 'Alterou o prazo de \"10/02/2025\" para \"07/08/2025\"'),
(6, 3, '2025-07-30', 4, 'Criou o assunto \"sdgsfdg\"'),
(7, 3, '2025-07-30', 4, 'Adicionou ação \"sdaf\"'),
(8, 4, '2025-07-30', 4, 'Criou o assunto \"sdaf\"'),
(9, 4, '2025-07-30', 4, 'Adicionou ação \"fdsa\"'),
(10, 5, '2025-07-30', 4, 'Criou o assunto \"fdsf\"'),
(11, 5, '2025-07-30', 4, 'Adicionou ação \"fdsa\"'),
(12, 3, '2025-07-30', 4, 'Alterou o prazo de \"11/11/0011\" para \"08/08/2025\"'),
(13, 4, '2025-07-30', 4, 'Alterou o prazo de \"11/11/0001\" para \"08/08/2025\"'),
(14, 4, '2025-07-30', 4, 'Alterou o estado de \"Pendente\" para \"Concluído\"'),
(15, 1, '2025-07-30', 4, 'Excluiu o assunto: Aquisição de equipamentos de informática para modernização do parque tecnológico'),
(16, 2, '2025-07-30', 4, 'Excluiu o assunto: Manutenção preventiva das viaturas administrativas do comando');

-- --------------------------------------------------------

--
-- Estrutura para tabela `notas_auditoria`
--

CREATE TABLE `notas_auditoria` (
  `id` int(11) NOT NULL,
  `assunto_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nota` text NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notas_auditoria`
--

INSERT INTO `notas_auditoria` (`id`, `assunto_id`, `usuario_id`, `nota`, `data_criacao`) VALUES
(1, 1, 4, 'Verificado que os orçamentos foram solicitados conforme protocolo. Recomendo acompanhar os prazos de resposta dos fornecedores.', '2025-01-16 13:30:00'),
(2, 1, 5, 'Analisando as especificações técnicas solicitadas. Algumas podem estar superdimensionadas para a necessidade atual.', '2025-01-17 17:15:00'),
(3, 2, 4, 'Assunto está dentro do prazo estabelecido. Sugerido verificar disponibilidade orçamentária antes do agendamento.', '2025-01-15 19:45:00'),

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfis`
--

CREATE TABLE `perfis` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `perfis`
--

INSERT INTO `perfis` (`id`, `nome`, `descricao`) VALUES
(1, 'Suporte Técnico', 'Acesso total ao sistema, pode gerenciar usuários, assuntos, divisões e chefias. Responsável pelo suporte técnico do sistema.'),
(2, 'Auditor OM/Chefia', 'Pode visualizar assuntos da sua chefia e criar notas de auditoria. Acesso limitado às funções de auditoria por OM/Chefia.'),
(3, 'Auditor COLOG', 'Pode visualizar todos os assuntos e criar notas de auditoria. Acesso amplo para auditoria do COLOG.'),
(4, 'Editor', 'Pode criar novos assuntos e visualizar o sistema. Responsável pela criação e edição de conteúdo.');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `idt_Mil` varchar(20) NOT NULL,
  `pg` varchar(50) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `divisao_id` int(11) DEFAULT NULL,
  `chefia_id` int(11) DEFAULT NULL,
  `perfil_id` int(11) NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `idt_Mil`, `pg`, `nome`, `senha`, `divisao_id`, `chefia_id`, `perfil_id`) VALUES
(1, '123456789', 'Sd', 'V Araujo', '$2y$10$KzC.k3gAS4MUqyOCPdUcyeTTaxukMXahlEJP/6CBCp39qZiBQ4MC.', 1, 3, 1),
(2, '012345678', 'Gen Bda', 'Eron', '$2y$10$uK.UFspQsLa.cd9rgwLQueIH2zXrJesZjmfHgjzaBXuYpPAwm8elC', 8, 3, 2),
(3, '12345678', 'Maj', 'Afonso Neto', '$2y$10$TIHIRZZTNBV6K9Dl7Ou/r.Nu.myYErKH6DTKGuuNtxUZI5rXcIwdu', 1, 3, 4),
(4, '0123456789', 'Gen Div', 'Flavio Neiva', '$2y$10$xxyWWD4v7N5Rur2hZwKr7OMh6VvL.REDou4N0pOm9E6M7qMtwDa6i', 9, 8, 3),
(5, '444555666', 'TC', 'Pedro Costa', '$2y$10$JzW3D8z5M6GyEAFn7VBNquQZ.Nkns5cUKiT6EDFMZIE1aR8zNFxfq', 4, 2, 4);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `acoes`
--
ALTER TABLE `acoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assunto_id` (`assunto_id`),
  ADD KEY `responsavel` (`responsavel`);

--
-- Índices de tabela `assuntos`
--
ALTER TABLE `assuntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `criadoPor` (`criadoPor`);

--
-- Índices de tabela `chefia`
--
ALTER TABLE `chefia`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `divisao`
--
ALTER TABLE `divisao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chefia_id` (`chefia_id`);

--
-- Índices de tabela `historico`
--
ALTER TABLE `historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assunto_id` (`assunto_id`),
  ADD KEY `usuario` (`usuario`);

--
-- Índices de tabela `notas_auditoria`
--
ALTER TABLE `notas_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assunto_id` (`assunto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `perfis`
--
ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idt_Mil` (`idt_Mil`),
  ADD KEY `divisao_id` (`divisao_id`),
  ADD KEY `chefia_id` (`chefia_id`),
  ADD KEY `perfil_id` (`perfil_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `acoes`
--
ALTER TABLE `acoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `assuntos`
--
ALTER TABLE `assuntos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `chefia`
--
ALTER TABLE `chefia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `divisao`
--
ALTER TABLE `divisao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `historico`
--
ALTER TABLE `historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `notas_auditoria`
--
ALTER TABLE `notas_auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `perfis`
--
ALTER TABLE `perfis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `acoes`
--
ALTER TABLE `acoes`
  ADD CONSTRAINT `acoes_ibfk_1` FOREIGN KEY (`assunto_id`) REFERENCES `assuntos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acoes_ibfk_2` FOREIGN KEY (`responsavel`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `assuntos`
--
ALTER TABLE `assuntos`
  ADD CONSTRAINT `assuntos_ibfk_1` FOREIGN KEY (`criadoPor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `divisao`
--
ALTER TABLE `divisao`
  ADD CONSTRAINT `divisao_ibfk_1` FOREIGN KEY (`chefia_id`) REFERENCES `chefia` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `historico`
--
ALTER TABLE `historico`
  ADD CONSTRAINT `historico_ibfk_1` FOREIGN KEY (`assunto_id`) REFERENCES `assuntos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historico_ibfk_2` FOREIGN KEY (`usuario`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `notas_auditoria`
--
ALTER TABLE `notas_auditoria`
  ADD CONSTRAINT `notas_auditoria_ibfk_1` FOREIGN KEY (`assunto_id`) REFERENCES `assuntos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notas_auditoria_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`divisao_id`) REFERENCES `divisao` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`chefia_id`) REFERENCES `chefia` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
