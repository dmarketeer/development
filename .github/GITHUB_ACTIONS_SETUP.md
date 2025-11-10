# Configuração GitHub Actions - Secrets e Variables

Este documento explica como configurar as secrets e variables necessárias para os workflows do GitHub Actions.

## 📋 Variables Necessárias

### `WP_DB_PREFIX`

**Tipo:** Repository Variable
**Valor:** `wpac_`
**Descrição:** Prefixo das tabelas da base de dados WordPress

---

## 🔧 Como Adicionar Variables no GitHub

### Opção 1: Via Interface Web

1. **Acesse o repositório no GitHub**
   - Vá para: `https://github.com/dmarketeer/development`

2. **Navegue até Settings**
   - Clique na aba **Settings** (ícone de engrenagem)

3. **Acesse Secrets and Variables**
   - No menu lateral esquerdo, expanda **Secrets and variables**
   - Clique em **Actions**

4. **Adicione a Variable**
   - Clique na aba **Variables**
   - Clique no botão **New repository variable**
   - Preencha:
     - **Name:** `WP_DB_PREFIX`
     - **Value:** `wpac_`
   - Clique em **Add variable**

### Opção 2: Via GitHub CLI

```bash
# Instalar GitHub CLI se ainda não tiver
# https://cli.github.com/

# Autenticar
gh auth login

# Adicionar a variable
gh variable set WP_DB_PREFIX --body "wpac_" --repo dmarketeer/development
```

---

## 🔐 Diferença entre Secrets e Variables

| Tipo | Quando Usar | Visibilidade nos Logs |
|------|-------------|---------------------|
| **Secrets** | Dados sensíveis (passwords, tokens, API keys) | Ocultos (aparecem como `***`) |
| **Variables** | Configurações não sensíveis (prefixos, URLs, nomes) | Visíveis nos logs |

---

## ✅ Verificar Configuração

Após adicionar as variables, elas estarão disponíveis nos workflows através de:

```yaml
env:
  WP_DB_PREFIX: ${{ vars.WP_DB_PREFIX }}
```

Para verificar se está funcionando:

1. Faça um commit e push em qualquer branch `claude/**`
2. Vá para a aba **Actions** do repositório
3. Verifique o workflow **Tests** executando
4. No step **Display environment info**, você verá o prefixo sendo usado

---

## 📚 Variables Atuais do Projeto

| Nome | Valor | Descrição |
|------|-------|-----------|
| `WP_DB_PREFIX` | `wpac_` | Prefixo das tabelas WordPress |

---

## 🔄 Adicionar Novas Variables no Futuro

Para adicionar mais variables conforme o projeto cresce:

```yaml
# Exemplo no workflow
env:
  WP_DB_PREFIX: ${{ vars.WP_DB_PREFIX || 'wp_' }}  # Fallback para 'wp_' se não existir
  WP_VERSION: ${{ vars.WP_VERSION || 'latest' }}
  PHP_VERSION: ${{ vars.PHP_VERSION || '8.1' }}
```

---

## 🚀 Uso no Código PHP

Se precisar acessar o prefixo em testes ou scripts:

```php
// Em testes PHPUnit
$prefix = getenv('WP_DB_PREFIX') ?: 'wp_';

// Em setup de testes
define('DB_PREFIX', getenv('WP_DB_PREFIX') ?: 'wp_');
```

---

## 📞 Suporte

Se tiver problemas ao configurar:

1. Verifique se você tem permissões de admin no repositório
2. Certifique-se de que está na aba **Variables** (não Secrets)
3. Confirme que o nome está correto: `WP_DB_PREFIX` (case-sensitive)
