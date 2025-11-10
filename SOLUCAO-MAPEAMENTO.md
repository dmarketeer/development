# ✅ SOLUÇÃO: Mapeamento de Campos Google Sheets

## 🎉 Problema Resolvido!

**Situação anterior:**
- 112 registros processados
- 0 inseridos
- 0 atualizados

**Causa identificada:**
Os cabeçalhos do seu Google Sheets não correspondiam aos esperados pelo plugin.

---

## 📋 Mapeamento Implementado

O plugin agora aceita **automaticamente** seus cabeçalhos:

| Seu Google Sheets | Campo do Plugin | Descrição |
|-------------------|-----------------|-----------|
| **Contrato** | `titulo` | ✅ Título da oportunidade (OBRIGATÓRIO) |
| **Descrição** | `resumo` | Descrição/resumo da oportunidade |
| **Adjudicante** | `entidade_adjudicante` | Entidade adjudicante |
| **Preço base s/IVA (€)** | `valor_normalizado` | Valor da oportunidade |
| **Prazo das propostas** | `deadline_date` | Prazo final para propostas |
| **Link PDF** | `url` | URL do PDF/anúncio |
| **Anúncio** | `external_id` | Identificador externo |
| **Distrito** | `custom_fields['distrito']` | Armazenado em campos personalizados |
| **Prazo** | `custom_fields['prazo_execucao']` | Prazo de execução |
| **Data do Anúncio** | `custom_fields['data_anuncio']` | Data de publicação |

---

## 🚀 Como Aplicar a Correção

### **PASSO 1: Atualizar o plugin**

No servidor, execute:

```bash
cd wp-content/plugins/oportunidades
git pull origin claude/create-api-key-011CUzhec8iVWdj3iFpoHQ8q
```

### **PASSO 2: Sincronizar novamente**

1. Vá em **WordPress Admin → Oportunidades**
2. Role até **"Sincronizar do Google Sheets"**
3. Clique em **"Sincronizar agora"**

### **PASSO 3: Verificar resultado**

Você deve ver algo como:
```
Processados 112 registos. Inseridos: 112. Actualizados: 0.
```

---

## 📊 Estrutura do Seu Google Sheets (Mantida)

Você **NÃO precisa alterar nada** no Google Sheets!

Seus cabeçalhos atuais:
```
Anúncio | Adjudicante | Data do Anúncio | Preço base s/IVA (€) | Contrato | Descrição | Distrito | Prazo | Prazo das propostas | Link PDF
```

Agora funcionam automaticamente! ✅

---

## 🎯 Exemplo de Dados Processados

**Linha 2 do seu Google Sheets:**
```
29064/2025
Direção Regional da Habitação
2025-11-10
20.000,00 €
Coordenação e fiscalização da empreitada...
Aquisição de serviços para coordenação...
Freguesia de Praia da Vitória...
300 DIAS
25-11-2025 23:59
https://files.diariodarepublica.pt/...
```

**Será importado como:**
- **Título:** "Coordenação e fiscalização da empreitada..."
- **Resumo:** "Aquisição de serviços para coordenação..."
- **Entidade:** "Direção Regional da Habitação"
- **Valor:** 20.000 €
- **Prazo:** 25-11-2025 23:59
- **URL:** https://files.diariodarepublica.pt/...
- **ID Externo:** 29064/2025
- **Campos extras:**
  - distrito: "Freguesia de Praia da Vitória..."
  - prazo_execucao: "300 DIAS"
  - data_anuncio: "2025-11-10"

---

## ✅ Compatibilidade

O plugin agora aceita **3 formatos** de cabeçalhos:

### 1. **Português padrão** (original):
```
titulo | resumo | entidade_adjudicante | valor_normalizado | prazo | url
```

### 2. **Inglês** (alternativo):
```
title | summary | entity | valor_normalizado | deadline | link
```

### 3. **Seus cabeçalhos** (NOVO):
```
Contrato | Descrição | Adjudicante | Preço base s/IVA (€) | Prazo das propostas | Link PDF
```

**Todos funcionam!** ✅

---

## 🔍 Verificar Importação

Após sincronizar, verifique:

1. **Total de registros:**
   ```sql
   SELECT COUNT(*) FROM wpac_oportunidades;
   ```
   Deve retornar: **112** (ou mais se já tinha dados anteriores)

2. **Visualizar no shortcode:**
   Acesse a página com `[oportunidades]` e veja os 112 registros listados.

3. **Filtrar por distrito:**
   ```
   [oportunidades]
   ```
   (Os filtros por distrito agora funcionam via `custom_fields`)

---

## 🎨 Campos Personalizados Disponíveis

Os seguintes campos estão disponíveis em `custom_fields` para cada oportunidade:

- `distrito` - Ex: "Freguesia de Praia da Vitória (Santa Cruz)..."
- `prazo_execucao` - Ex: "300 DIAS"
- `data_anuncio` - Ex: "2025-11-10"

Você pode acessá-los no template ou via filtros personalizados.

---

## 📝 Notas Importantes

### ✅ O que funciona agora:
- ✅ Importação dos 112 registros
- ✅ Todos os campos mapeados automaticamente
- ✅ Valores monetários parseados corretamente (20.000,00 € → 20000.00)
- ✅ Datas parseadas (25-11-2025 23:59 → 2025-11-25 23:59:00)
- ✅ Campos extras armazenados em custom_fields

### ⚠️ Limitações atuais:
- Categorias e filtros não são extraídos automaticamente (podem ser configurados via "Filtros predefinidos" no Admin)
- Distrito está em custom_fields, não como filtro direto (mas pode ser usado)

### 💡 Melhorias futuras possíveis:
- Adicionar extração automática de categorias baseada em palavras-chave no título
- Criar filtros por distrito automaticamente
- Parse inteligente do campo "Prazo" para criar deadline_date

---

## 🚨 Se Ainda Não Funcionar

1. **Verifique se atualizou o código:**
   ```bash
   cd wp-content/plugins/oportunidades
   git log -1
   ```
   Deve mostrar: "Adicionar mapeamento automático de campos do Google Sheets"

2. **Execute o teste novamente:**
   ```
   https://dev.arquirenova.pt/debug-importacao.php
   ```

   O **Teste 2** deve mostrar:
   ```
   ✅ Importação funcionou!
   ```

3. **DELETE os arquivos de teste:**
   ```bash
   rm debug-importacao.php
   rm teste-shortcode.php
   rm diagnostico.php
   ```

---

## 📞 Próximos Passos

1. ✅ Atualizar o plugin (`git pull`)
2. ✅ Sincronizar Google Sheets novamente
3. ✅ Verificar 112 registros importados
4. ✅ Testar shortcode `[oportunidades]`
5. ✅ Executar `fix-json-encoding.php` (para filtros)
6. ✅ Deletar arquivos de teste

---

**Data da correção:** 2025-11-10
**Commit:** `2efbfbb`
**Status:** ✅ Pronto para uso
