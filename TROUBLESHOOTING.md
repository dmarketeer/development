# 🔧 Troubleshooting - Plugin Oportunidades

Este guia ajuda a resolver problemas comuns com o plugin Oportunidades.

---

## 📋 Problema: "Não está a importar dados"

### Passo 1: Execute o Script de Diagnóstico

1. **Copie o arquivo `diagnostico.php` para a raiz do WordPress:**
   ```bash
   cp diagnostico.php /caminho/para/wordpress/diagnostico.php
   ```

2. **Acesse via navegador:**
   ```
   http://seu-site.com/diagnostico.php
   ```

3. **Analise os resultados** e identifique os problemas

4. **Delete o arquivo após uso:**
   ```bash
   rm /caminho/para/wordpress/diagnostico.php
   ```

---

### Passo 2: Verificar Configuração do Google Sheets

#### ⚠️ Problema Comum: Range Incorreto

Se o range estiver como `Oportunidades!A1:J1`, você está buscando **apenas 1 linha** (o cabeçalho).

**Solução:**
```
❌ Errado: Oportunidades!A1:J1
✅ Correto: Oportunidades!A1:J1000
✅ Correto: Oportunidades
✅ Correto: Oportunidades!A:J
```

#### 🔑 Verificar API Key do Google

1. Acesse: https://console.cloud.google.com/
2. Vá em **APIs & Services → Credentials**
3. Verifique se a API Key existe e está ativa
4. Confirme que **Google Sheets API** está habilitada
5. Teste a API Key no botão **"Validar Configuração"** no WordPress Admin

#### 🔒 Verificar Permissões da Planilha

A planilha Google Sheets deve ter permissão **"Anyone with the link can view"**:

1. Abra a planilha no Google Sheets
2. Clique em **"Share"** (Partilhar)
3. Clique em **"Change to anyone with the link"**
4. Defina para **"Viewer"** (Visualizador)
5. Copie o link e extraia o ID

---

### Passo 3: Verificar Estrutura da Planilha Google

#### Cabeçalho Esperado (Linha 1):

A primeira linha da planilha deve conter os nomes dos campos. O plugin aceita nomes em **Português** ou **Inglês**:

| Coluna | Nome PT (recomendado) | Nome EN (alternativo) | Obrigatório | Tipo |
|--------|----------------------|----------------------|-------------|------|
| A | titulo | title | ✅ Sim | Texto |
| B | resumo | summary, descricao | ❌ Não | Texto longo |
| C | identificador | id | ❌ Não | Texto |
| D | entidade_adjudicante | entity | ❌ Não | Texto |
| E | valor_normalizado | valor | ❌ Não | Número |
| F | prazo | deadline | ❌ Não | Data (YYYY-MM-DD) |
| G | url | link | ❌ Não | URL |
| H | categorias | categories | ❌ Não | JSON Array ou texto separado por vírgulas |
| I | filtros | filters | ❌ Não | JSON Array ou texto separado por vírgulas |
| J | campos_personalizados | custom_fields | ❌ Não | JSON Object |

#### Exemplo de Linha de Dados (Linha 2):

```
titulo                          | resumo                        | identificador | entidade_adjudicante | valor_normalizado | prazo      | url                          | categorias                      | filtros            | campos_personalizados
Reabilitação de Fachada         | Projeto de reabilitação...    | DR-2025-001   | Município de Lisboa  | 250000            | 2025-06-30 | https://base.gov.pt/...      | ["Reabilitação","Civil"]        | ["Fachadas","LSF"] | {"distrito":"Lisboa"}
```

**Nota:** As colunas `categorias`, `filtros` e `campos_personalizados` podem ser:
- **JSON válido:** `["Reabilitação","Civil"]` ou `{"distrito":"Lisboa"}`
- **Texto simples:** `Reabilitação, Civil` (será convertido automaticamente)
- **Vazio:** deixe a célula em branco

---

### Passo 4: Testar Importação Manual

1. **Baixe o arquivo de exemplo:**
   ```
   exemplo-importacao.json
   ```

2. **No WordPress Admin:**
   - Vá em **Oportunidades** no menu lateral
   - Role até **"Importação Manual"**
   - Selecione o arquivo `exemplo-importacao.json`
   - Clique em **"Importar agora"**

3. **Verifique o resultado:**
   - Se aparecer "Processados 3 registos. Inseridos: 3", a importação funcionou! ✅
   - Se houver erro, leia a mensagem de erro

---

### Passo 5: Verificar a Tabela do Banco de Dados

#### Usando WP-CLI:
```bash
wp db query "SELECT COUNT(*) as total FROM wpac_oportunidades;"
wp db query "SELECT id, title, awarding_entity FROM wpac_oportunidades LIMIT 5;"
```

#### Usando phpMyAdmin ou Adminer:
```sql
SELECT COUNT(*) as total FROM wpac_oportunidades;
SELECT * FROM wpac_oportunidades LIMIT 5;
```

Se o resultado for `0 rows`, significa que **nenhum dado foi importado**.

---

## 🔍 Problemas Comuns e Soluções

### Problema 1: "Tabela não existe"

**Erro:** `Table 'wordpress.wpac_oportunidades' doesn't exist`

**Solução:**
1. Vá em **Plugins** no WordPress Admin
2. **Desative** o plugin "Oportunidades"
3. **Ative** novamente o plugin
4. A tabela será criada automaticamente

---

### Problema 2: "Payload inválido: campo 'oportunidades' em falta"

**Causa:** O JSON não está no formato esperado

**Solução:** Certifique-se que o JSON tem esta estrutura:
```json
{
  "schema_version": "1.0",
  "oportunidades": [
    {
      "titulo": "...",
      ...
    }
  ]
}
```

---

### Problema 3: "Registo sem título"

**Causa:** Falta o campo `titulo` ou `title` nos dados

**Solução:**
- Se usar Google Sheets: Certifique-se que a coluna A tem o cabeçalho `titulo` ou `title`
- Se usar JSON: Adicione o campo `"titulo": "..."` em cada objeto

---

### Problema 4: "Erro 403 - Acesso Negado" (Google Sheets)

**Causas possíveis:**
1. API Key inválida ou expirada
2. Google Sheets API não está habilitada
3. Planilha não tem permissões públicas

**Soluções:**
1. Gere uma nova API Key no Google Cloud Console
2. Habilite "Google Sheets API" em APIs & Services → Library
3. Torne a planilha pública: Share → Anyone with the link → Viewer

---

### Problema 5: "Erro 404 - Planilha Não Encontrada"

**Causa:** ID da planilha está incorreto

**Solução:**
1. Abra a planilha no Google Sheets
2. Copie o ID da URL:
   ```
   https://docs.google.com/spreadsheets/d/[ESTE_É_O_ID]/edit
   ```
3. Cole no campo **"ID da Planilha Google"** no WordPress Admin

---

### Problema 6: Shortcode não mostra dados

**Causa:** Provavelmente os dados não foram importados OU os filtros estão bloqueando

**Soluções:**

1. **Verificar se há dados:**
   ```bash
   wp db query "SELECT COUNT(*) FROM wpac_oportunidades;"
   ```

2. **Testar shortcode sem filtros:**
   ```
   [oportunidades]
   ```

3. **Verificar se os filtros estão corretos:**
   ```
   [oportunidades categoria="Reabilitação"]
   ```
   O valor de `categoria` deve corresponder exatamente ao que está na coluna `categorias` dos dados

4. **Aumentar o limite:**
   ```
   [oportunidades limite="50"]
   ```

---

### Problema 7: "Apenas o cabeçalho foi retornado"

**Causa:** Range do Google Sheets está como `A1:J1` (só 1 linha)

**Solução:**
```
Altere para: Oportunidades!A1:J1000
```

---

## 🚀 Testes Rápidos

### Teste 1: Verificar instalação do plugin
```bash
wp plugin list | grep oportunidades
```
Deve mostrar: `oportunidades | active`

### Teste 2: Verificar tabela
```bash
wp db query "SHOW TABLES LIKE 'wpac_oportunidades';"
```
Deve retornar: `wpac_oportunidades`

### Teste 3: Contar registros
```bash
wp db query "SELECT COUNT(*) FROM wpac_oportunidades;"
```
Deve retornar um número > 0

### Teste 4: Ver últimos registros
```bash
wp db query "SELECT id, title FROM wpac_oportunidades ORDER BY id DESC LIMIT 3;"
```

---

## 📞 Checklist de Diagnóstico

Use este checklist para verificar tudo:

- [ ] Plugin está ativo
- [ ] Tabela `wpac_oportunidades` existe
- [ ] Google Sheets ID está configurado
- [ ] Google Sheets Range está correto (não apenas A1:J1)
- [ ] Google API Key está configurada
- [ ] Google Sheets API está habilitada no Google Cloud
- [ ] Planilha tem permissões públicas de visualização
- [ ] Primeira linha da planilha contém cabeçalhos
- [ ] Segunda linha da planilha contém dados (não está vazia)
- [ ] Campo `titulo` existe no cabeçalho
- [ ] Botão "Validar Configuração" retorna sucesso
- [ ] Sincronização manual foi executada
- [ ] Existem registros na tabela (SELECT COUNT(*))
- [ ] Shortcode `[oportunidades]` está inserido numa página

---

## 📧 Logs e Debug

Para ativar logs detalhados, adicione ao `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Os erros serão salvos em `wp-content/debug.log`.

---

## 🔗 Recursos Adicionais

- **Google Cloud Console:** https://console.cloud.google.com/
- **Google Sheets API Docs:** https://developers.google.com/sheets/api/guides/concepts
- **WordPress Database API:** https://developer.wordpress.org/reference/classes/wpdb/

---

Se após seguir todos os passos o problema persistir, execute o `diagnostico.php` e envie o resultado para análise.
