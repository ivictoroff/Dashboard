# Sistema de Assuntos Críticos do COLOG

## Descrição
Sistema web para gerenciamento de assuntos críticos do Comando Logístico do Exército Brasileiro.

## Funcionalidades
- Gerenciamento de assuntos críticos e não críticos
- Sistema de usuários com 4 perfis (Suporte Técnico, Auditor OM/Chefia, Auditor COLOG, Editor)
- Gerenciamento de chefias e divisões
- Dashboard com resumos e gráficos
- Interface responsiva para desktop e mobile
- Histórico de alterações
- Sistema de ações e providências
- Sistema de notas de auditoria

## Perfis de Usuário
- **Suporte Técnico**: Acesso total ao sistema, pode gerenciar usuários, assuntos, divisões e chefias
- **Auditor OM/Chefia**: Pode visualizar assuntos da sua chefia e criar notas de auditoria
- **Auditor COLOG**: Pode visualizar todos os assuntos e criar notas de auditoria  
- **Editor**: Pode criar novos assuntos e visualizar o sistema

## Requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache com mod_rewrite habilitado
- Navegador web moderno

## Instalação
1. Extrair arquivos para o diretório web
2. Configurar banco de dados em `db.php`
3. Executar script SQL em `sql.sql`
4. Configurar permissões apropriadas
5. Acessar via navegador

## Configuração de Produção
- Ativar HTTPS
- Configurar backup automático do banco
- Verificar logs de erro regularmente
- Atualizar senhas padrão

## Estrutura de Arquivos
- `index.php` - Página de login
- `home.php` - Dashboard principal
- `api/` - Endpoints da API
- `sql.sql` - Script de criação do banco
- `.htaccess` - Configurações de segurança

## Suporte
Sistema desenvolvido para o COLOG - Exército Brasileiro
