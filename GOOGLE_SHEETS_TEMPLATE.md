# 📊 Template Google Sheets - Plugin Oportunidades

Este documento explica como configurar corretamente a planilha Google Sheets para integração com o plugin Oportunidades.

---

## 📋 Estrutura da Planilha

### Linha 1: Cabeçalhos (Obrigatório)

A primeira linha DEVE conter os nomes dos campos. Use os nomes em **Português** (recomendado) ou **Inglês**.

| Coluna | Nome PT | Nome EN | Obrigatório | Tipo | Exemplo |
|--------|---------|---------|-------------|------|---------|
| **A** | `titulo` | `title` | ✅ **SIM** | Texto | Reabilitação de Fachada |
| **B** | `resumo` | `summary` ou `descricao` | ❌ Não | Texto longo | Projeto de reabilitação completa da fachada... |
| **C** | `identificador` | `id` | ❌ Não | Texto | DR-2025-001 |
| **D** | `entidade_adjudicante` | `entity` | ❌ Não | Texto | Município de Lisboa |
| **E** | `valor_normalizado` ou `valor` | - | ❌ Não | Número | 250000.00 |
| **F** | `prazo` | `deadline` | ❌ Não | Data | 2025-06-30 |
| **G** | `url` | `link` | ❌ Não | URL | https://base.gov.pt/... |
| **H** | `categorias` | `categories` | ❌ Não | Array | Reabilitação, Civil |
| **I** | `filtros` | `filters` | ❌ Não | Array | Fachadas, LSF |
| **J+** | Campos personalizados | - | ❌ Não | Variável | distrito, concelho, etc |

---

## 🔧 Configuração Passo a Passo

### 1. Criar Nova Planilha no Google Sheets

1. Acesse: https://sheets.google.com
2. Clique em **"+ Blank"** (Nova planilha em branco)
3. Nomeie a planilha (ex: "Arquirenova Oportunidades")
4. Renomeie a aba para **"Oportunidades"**

---

### 2. Configurar Cabeçalhos (Linha 1)

Cole os seguintes cabeçalhos na primeira linha:

```
titulo | resumo | identificador | entidade_adjudicante | valor_normalizado | prazo | url | categorias | filtros
```

**OU** use os nomes em inglês:

```
title | summary | id | entity | valor_normalizado | deadline | url | categories | filters
```

**OU** utilize os cabeçalhos oficiais do Portal BASE (mapeados automaticamente):

```
Anúncio | Adjudicante | Data do Anúncio | Preço base s/IVA (€) | Contrato | Descrição | Distrito | Prazo | Prazo das propostas | Link PDF
```

> ✅ Estes cabeçalhos são importados directamente e cada campo é convertido para os atributos internos do plugin. O `Contrato` passa a ser o título, `Descrição` o resumo, `Prazo das propostas` o deadline, `Link PDF` a URL e todos os restantes campos ficam disponíveis em `custom_fields`.

**⚠️ IMPORTANTE:** O campo `titulo` (ou `title`) é **OBRIGATÓRIO**!

---

### 3. Adicionar Dados (Linha 2 em diante)

#### Exemplo de Linha Completa:

| A | B | C | D | E | F | G | H | I |
|---|---|---|---|---|---|---|---|---|
| Reabilitação de Fachada | Projeto de reabilitação... | DR-2025-001 | Município de Lisboa | 250000 | 2025-06-30 | https://base.gov.pt/opp001 | Reabilitação, Civil | Fachadas, LSF |

---

### 4. Formatos de Dados Aceitos

#### 📝 Texto Simples (Colunas A, B, C, D, G)
- Texto normal
- Pode conter acentos e caracteres especiais
- Exemplo: `Reabilitação de Fachada Histórica`

#### 💰 Valores Numéricos (Coluna E)
Aceita qualquer um destes formatos:
- `250000` (número simples)
- `250000.50` (com decimais)
- `250.000,00` (formato PT)
- `250,000.00` (formato EN)
- `€ 250.000` (com símbolo de moeda)

O plugin remove automaticamente símbolos e formata corretamente.

#### 📅 Datas (Coluna F)
Aceita vários formatos:
- `2025-06-30` (recomendado: YYYY-MM-DD)
- `30/06/2025` (DD/MM/YYYY)
- `06/30/2025` (MM/DD/YYYY)
- `2025-06-30 14:30:00` (com hora)

#### 🔗 URLs (Coluna G)
- URLs completas: `https://www.base.gov.pt/oportunidade`
- Devem começar com `http://` ou `https://`

#### 📋 Arrays - Categorias e Filtros (Colunas H, I)

**Opção 1: Texto separado por vírgulas** (mais fácil)
```
Reabilitação, Construção Civil, LSF
```

**Opção 2: JSON Array** (mais avançado)
```
["Reabilitação", "Construção Civil", "LSF"]
```

Ambos funcionam!

---

### 5. Campos Personalizados (Opcional)

Você pode adicionar colunas extras para campos personalizados como `distrito`, `concelho`, `tipo_contrato`, etc.

#### Exemplo com Campos Personalizados:

| A (titulo) | B (resumo) | ... | J (distrito) | K (concelho) | L (tipo_contrato) |
|------------|------------|-----|--------------|--------------|-------------------|
| Reabilitação... | Projeto... | ... | Lisboa | Lisboa | Empreitada |

#### Configurar Mapeamento no WordPress:

1. Vá em **Admin → Oportunidades**
2. Role até **"Mapeamento de campos adicionais"**
3. Adicione um JSON:

```json
{
  "distrito": "Distrito",
  "concelho": "Concelho",
  "tipo_contrato": "Tipo de Contrato"
}
```

Isso mapeia:
- Coluna `distrito` (Google Sheets) → Campo `Distrito` (WordPress)
- Coluna `concelho` (Google Sheets) → Campo `Concelho` (WordPress)
- etc.

---

## 🔐 Configurar Permissões da Planilha

### Opção 1: Permissão Pública (Recomendado para este plugin)

1. Clique no botão **"Share"** (Partilhar) no canto superior direito
2. Em **"Get link"**, clique em **"Change to anyone with the link"**
3. Certifique-se que está como **"Viewer"** (Visualizador)
4. Copie o link

### Opção 2: Service Account (Mais Seguro, Requer Configuração Avançada)

Para uso com Service Account, consulte a documentação do Google Sheets API.

---

## 🔑 Obter ID da Planilha

Da URL da planilha:
```
https://docs.google.com/spreadsheets/d/1A2B3C4D5E6F7G8H9I0J/edit#gid=0
                                       ^^^^^^^^^^^^^^^^^^^
                                       Este é o ID
```

Copie apenas o ID (ex: `1A2B3C4D5E6F7G8H9I0J`).

---

## ⚙️ Configurar no WordPress

### 1. Configuração Básica

Vá em **Admin → Oportunidades** e preencha:

| Campo | Valor |
|-------|-------|
| **ID da Planilha Google** | `1A2B3C4D5E6F7G8H9I0J` |
| **Intervalo de Dados (Range)** | `Oportunidades!A1:I1000` |
| **API Key do Google** | `AIzaSy...` (obtida no Google Cloud Console) |

### 2. Entender o Range

O formato do range é: `[Nome da Aba]![Células]`

**Exemplos:**

| Range | Descrição |
|-------|-----------|
| `Oportunidades` | Busca TODA a aba "Oportunidades" |
| `Oportunidades!A1:I1000` | Busca linhas 1 a 1000, colunas A a I |
| `Oportunidades!A:I` | Busca TODAS as linhas, colunas A a I |
| `Sheet1!A1:Z10` | Busca aba "Sheet1", 10 linhas, 26 colunas |

**⚠️ ERRO COMUM:**
```
❌ Oportunidades!A1:I1    → Busca só 1 linha (só o header!)
✅ Oportunidades!A1:I1000 → Busca até 1000 linhas
```

### 3. Validar Configuração

1. Preencha os 3 campos acima
2. Clique no botão **"Validar Configuração"**
3. Aguarde a mensagem:
   - ✅ **Sucesso:** "Configuração válida! A planilha foi encontrada..."
   - ❌ **Erro:** Leia a mensagem e corrija o problema

---

## 🔄 Sincronizar Dados

### Sincronização Manual

1. Vá em **Admin → Oportunidades**
2. Role até **"Sincronizar do Google Sheets"**
3. Clique em **"Sincronizar agora"**
4. Aguarde a mensagem de confirmação

### Sincronização Automática

O plugin sincroniza automaticamente a cada **1 hora** por padrão.

Para alterar o intervalo:
1. Vá em **Admin → Oportunidades**
2. Altere **"Intervalo de sincronização (minutos)"**
3. Valores recomendados:
   - `60` = 1 hora
   - `1440` = 1 dia
   - `15` = 15 minutos (mínimo)

---

## 📊 Template Pronto para Copiar

### Planilha de Exemplo

Copie e cole no Google Sheets:

```
titulo	resumo	identificador	entidade_adjudicante	valor_normalizado	prazo	url	categorias	filtros
Reabilitação de Fachada	Projeto de reabilitação completa da fachada principal	DR-2025-001	Município de Lisboa	250000	2025-06-30	https://base.gov.pt/001	Reabilitação, Civil	Fachadas, LSF
Construção de Cobertura	Nova cobertura em sistema LSF	DR-2025-002	Empresa XYZ Lda	85000	2025-05-15	https://base.gov.pt/002	Construção, LSF	LSF, Coberturas
Isolamento Térmico	Aplicação de ETICS em edifício residencial	DR-2025-003	Condomínio ABC	120000	2025-07-20	https://base.gov.pt/003	Reabilitação, Eficiência	ETICS, Isolamento
```

**Como usar:**
1. Selecione tudo acima
2. Copie (Ctrl+C)
3. Cole no Google Sheets (Ctrl+V)
4. O Google Sheets vai separar automaticamente pelas tabulações

---

## ✅ Checklist de Configuração

Antes de sincronizar, verifique:

- [ ] Planilha criada no Google Sheets
- [ ] Primeira linha contém cabeçalhos
- [ ] Campo `titulo` existe no cabeçalho (coluna A)
- [ ] Pelo menos 1 linha de dados (linha 2) está preenchida
- [ ] Campo `titulo` da linha 2 está preenchido (obrigatório)
- [ ] Permissão da planilha está como "Anyone with the link - Viewer"
- [ ] ID da planilha foi copiado corretamente
- [ ] Range está correto (ex: `Oportunidades!A1:I1000`)
- [ ] API Key do Google foi configurada
- [ ] Google Sheets API está habilitada no Google Cloud Console
- [ ] Botão "Validar Configuração" retornou sucesso
- [ ] Sincronização manual foi executada com sucesso

---

## 🔍 Testar se Funcionou

Após sincronizar:

1. **Verificar mensagem de sucesso:**
   ```
   "Processados X registos. Inseridos: Y. Actualizados: Z."
   ```

2. **Testar o shortcode:**
   - Crie uma nova página no WordPress
   - Adicione o shortcode: `[oportunidades]`
   - Publique e visualize
   - Você deve ver a lista de oportunidades

3. **Verificar no Admin:**
   - Role até o final da página **Admin → Oportunidades**
   - Deve aparecer uma tabela com os dados importados

---

## 📞 Problemas?

Se algo não funcionar, consulte o `TROUBLESHOOTING.md` ou execute o `diagnostico.php`.

---

## 📚 Recursos

- **Google Sheets:** https://sheets.google.com
- **Google Cloud Console:** https://console.cloud.google.com
- **Google Sheets API:** https://developers.google.com/sheets/api

---

**Última atualização:** 2025-01-10
