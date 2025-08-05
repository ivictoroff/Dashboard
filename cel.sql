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
  `data` datetime NOT NULL,
  `usuario` int(11) DEFAULT NULL,
  `acao` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
(4, 'Editor', 'Pode criar novos assuntos e visualizar o sistema. Responsável pela criação e edição de conteúdo.'),
(5, 'Cadastro de Usuário', 'Pode visualizar e gerenciar sua própria chefia. Acesso restrito a funções de cadastro.');

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
