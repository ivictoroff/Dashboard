# Sistema de Assuntos Críticos do COLOG

## Descrição
Sistema web para gerenciamento de assuntos críticos do Comando Logístico do Exército Brasileiro.

## Funcionalidades
- Gerenciamento de assuntos críticos e não críticos
- Sistema de usuários com 3 perfis (Administrador, Visualizador, Criador)
- Gerenciamento de chefias e divisões
- Dashboard com resumos e gráficos
- Interface responsiva para desktop e mobile
- Histórico de alterações
- Sistema de ações e providências
- **NOVO: Exclusão suave de assuntos**

### Funcionalidade de Exclusão de Assuntos (Nova)

#### Permissões
- **Administradores**: Podem excluir qualquer assunto
- **Criadores**: Podem excluir qualquer assunto  
- **Visualizadores**: Não podem excluir assuntos (botão não aparece)

#### Como funciona
1. Na tabela de assuntos, clique no botão "Excluir" (vermelho)
2. Confirme a exclusão na tela de confirmação
3. O assunto será removido da visualização mas preservado no banco para auditoria

#### Características técnicas
- **Exclusão suave**: Usa campo `ativo` para controlar visibilidade
- **Histórico preservado**: Todos os dados permanecem no banco
- **Registro de auditoria**: Exclusão é registrada no histórico

#### Migração para sistemas existentes
```sql
USE cel;
ALTER TABLE assuntos ADD COLUMN IF NOT EXISTS ativo TINYINT(1) DEFAULT 1;
UPDATE assuntos SET ativo = 1 WHERE ativo IS NULL;
```

## Perfis de Usuário
- **Administrador**: Acesso total ao sistema
- **Visualizador**: Apenas visualização de assuntos e resumos
- **Criador**: Pode criar e editar assuntos, sem acesso ao gerenciamento de usuários

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
