# 🎉 Shortcode Funcionando - Instruções Finais

## ✅ CONFIRMADO: Tudo está funcionando!

Baseado no teste executado em `https://dev.arquirenova.pt/teste-shortcode.php`:

- ✅ **3 registros** importados com sucesso
- ✅ **Shortcode** `[oportunidades]` está funcionando
- ✅ **Template** renderiza corretamente
- ⚠️ **Filtros por categoria** tem problema de encoding (veja solução abaixo)

---

## 📋 Como Usar o Shortcode

### 1. Shortcode Básico (RECOMENDADO)

Insira em qualquer página ou post do WordPress:

```
[oportunidades]
```

Isso mostra **todas as oportunidades** (funciona 100% ✅)

### 2. Shortcode com Limite

```
[oportunidades limite="10"]
```

Mostra apenas as primeiras 10 oportunidades.

### 3. Onde Inserir o Shortcode

O plugin criou automaticamente uma página chamada **"Oportunidades"** durante a ativação.

**Para encontrar:**
1. Vá em **WordPress Admin → Páginas → Todas as Páginas**
2. Procure por "Oportunidades"
3. Edite a página
4. Verifique se contém `[oportunidades]`
5. Publique

**OU crie uma nova página:**
1. Páginas → Adicionar Nova
2. Título: "Oportunidades Disponíveis"
3. Conteúdo: `[oportunidades]`
4. Publicar

---

## ⚠️ Problema com Filtros por Categoria

### Situação Atual

```
[oportunidades categoria="Reabilitação"]
```

**NÃO funciona** porque os dados estão armazenados com encoding Unicode:
```json
["Reabilita\u00e7\u00e3o"]
```

### ✅ Solução: Executar Script de Correção

**Passo 1:** Baixe os arquivos atualizados
```bash
cd wp-content/plugins/oportunidades
git pull origin claude/create-api-key-011CUzhec8iVWdj3iFpoHQ8q
```

**Passo 2:** Copie o script para a raiz do WordPress
```bash
cp fix-json-encoding.php ../../../fix-json-encoding.php
```

**Passo 3:** Execute via navegador
```
https://dev.arquirenova.pt/fix-json-encoding.php
```

**Passo 4:** Clique em "Confirmar e Executar"

**Passo 5:** DELETE o arquivo
```bash
rm fix-json-encoding.php
```

**Passo 6:** Teste o filtro
Agora o shortcode com filtros deve funcionar:
```
[oportunidades categoria="Reabilitação"]
```

---

## 📊 Dados Atuais na Base de Dados

Você tem **3 oportunidades** prontas para exibição:

| Título | Entidade | Valor | Prazo |
|--------|----------|-------|-------|
| Construção de Cobertura em LSF | Empresa XYZ Lda | €85.000 | 15/05/2025 |
| Reabilitação de Fachada - Edifício Central | Município de Lisboa | €250.000 | 30/06/2025 |
| Isolamento Térmico de Edifício Residencial | Condomínio Residencial ABC | €120.000 | 20/07/2025 |

---

## 🔄 Importar Mais Dados

### Via Google Sheets

1. Configure a planilha conforme `GOOGLE_SHEETS_TEMPLATE.md`
2. Vá em **Admin → Oportunidades**
3. Preencha:
   - **ID da Planilha:** (seu ID)
   - **Intervalo de Dados:** `Oportunidades!A1:J1000` (NÃO use A1:J1!)
   - **API Key:** (sua chave)
4. Clique em **"Validar Configuração"**
5. Clique em **"Sincronizar agora"**

### Via Upload Manual

1. Vá em **Admin → Oportunidades**
2. Role até **"Importação Manual"**
3. Use `exemplo-importacao.json` como modelo
4. Faça upload do arquivo JSON
5. Clique em **"Importar agora"**

---

## 🎨 Personalizar Visual

O shortcode gera uma tabela HTML com classe CSS `.oportunidades-table`.

Para personalizar, adicione CSS no seu tema:

```css
.oportunidades-table {
    width: 100%;
    border-collapse: collapse;
}

.oportunidades-table th {
    background: #0073aa;
    color: white;
    padding: 12px;
    text-align: left;
}

.oportunidades-table td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.oportunidades-table tr:hover {
    background: #f5f5f5;
}
```

Arquivo CSS: `wp-content/plugins/oportunidades/public/css/public.css`

---

## 🧪 Arquivos de Teste (DELETE após usar)

Estes arquivos devem ser copiados para a **raiz do WordPress** apenas para teste:

| Arquivo | Propósito | Após usar |
|---------|-----------|-----------|
| `diagnostico.php` | Diagnóstico completo do plugin | ❌ DELETE |
| `teste-shortcode.php` | Testar se shortcode funciona | ❌ DELETE |
| `fix-json-encoding.php` | Corrigir filtros por categoria | ❌ DELETE |

**IMPORTANTE:** Nunca deixe estes arquivos no servidor em produção!

---

## ✅ Checklist Final

- [x] Plugin ativado
- [x] Tabela criada
- [x] 3 registros importados
- [x] Shortcode `[oportunidades]` funcionando
- [x] Template renderizando corretamente
- [ ] Executar `fix-json-encoding.php` (para filtros funcionarem)
- [ ] Inserir shortcode em página pública
- [ ] Configurar Google Sheets (se quiser sincronização automática)
- [ ] Deletar arquivos de teste da raiz do WordPress

---

## 📞 Suporte

Se tiver algum problema:

1. Execute `teste-shortcode.php` e envie os resultados
2. Verifique `wp-content/debug.log` (se WP_DEBUG estiver ativo)
3. Consulte `TROUBLESHOOTING.md`

---

## 🚀 Próximos Passos

1. **Corrigir encoding** (executar `fix-json-encoding.php`)
2. **Configurar Google Sheets** para importação automática
3. **Inserir shortcode** na página desejada
4. **Personalizar CSS** conforme design do site
5. **Deletar arquivos de teste**

---

**Última atualização:** 2025-11-10
**Status:** ✅ Funcionando (filtros pendentes de correção)
