# InPark - Eventos Reserva PRO v1.0.6

## 🔧 Correção Crítica: Compatibilidade com Elementor

### ❗ O que foi corrigido na v1.0.6

**Problema:** As checkboxes clicavam mas os containers não abriam no Elementor:
- ☑️ Horas Extra - campo não aparecia
- ☑️ Catering - sub-caixas não apareciam
- ☑️ Pretende Fatura com NIF? - campos não apareciam

**Causa:** Conflito de timing com o Elementor e event listeners não delegados corretamente.

**Solução:** JavaScript completamente reescrito com:
- ✅ Eventos delegados no `document`
- ✅ Múltiplos pontos de inicialização
- ✅ MutationObserver para detectar carregamento dinâmico
- ✅ Compatibilidade específica com Elementor
- ✅ Console.log para debug
- ✅ Verificações de existência de elementos

---

## 🎯 Agora Funciona com:

- ✅ WordPress nativo
- ✅ **Elementor**
- ✅ Gutenberg
- ✅ Classic Editor
- ✅ Qualquer page builder

---

## 🚀 Novidades Técnicas da v1.0.6

### 1. Eventos Delegados no Document

**Antes (v1.0.5):**
```javascript
$('#tem_catering').on('change', function(){ ... });
```

**Agora (v1.0.6):**
```javascript
$(document).on('change', '#tem_catering', function(){ ... });
```

**Por quê:** Funciona mesmo que o elemento seja adicionado dinamicamente pelo Elementor.

---

### 2. Múltiplos Pontos de Inicialização

```javascript
// 1. Document Ready
$(document).ready(function(){ initInparkForm(); });

// 2. Window Load
$(window).on('load', function(){ initInparkForm(); });

// 3. Elementor Frontend Init
$(window).on('elementor/frontend/init', function(){ initInparkForm(); });

// 4. MutationObserver
const observer = new MutationObserver(function(mutations) {
    // Detecta quando formulário é adicionado
});
```

**Por quê:** Garante que o formulário é inicializado não importa quando o Elementor carrega o conteúdo.

---

### 3. Console.log para Debug

Agora o JavaScript tem logs para facilitar debug:

```
InPark Reserva JS carregado - v1.0.6
Document ready
Inicializando formulário InPark...
Formulário encontrado!
Containers inicialmente ocultos
Checkbox catering mudou
toggleCatering chamado true
Mostrando catering
```

**Como ver:** Abrir DevTools (F12) → Console

---

### 4. MutationObserver

Detecta quando o Elementor adiciona o formulário dinamicamente:

```javascript
const observer = new MutationObserver(function(mutations) {
    // Se formulário for adicionado
    if ($(node).find('#inpark-reserva-form').length) {
        initInparkForm();
    }
});
```

---

## 🐛 Resolução de Problemas

### Se as checkboxes ainda não funcionarem

1. **Limpar Cache**
   ```
   - Cache do WordPress
   - Cache do Elementor
   - Cache do browser (Ctrl+F5)
   ```

2. **Verificar Console**
   ```
   F12 → Console
   Deve ver: "InPark Reserva JS carregado - v1.0.6"
   ```

3. **Verificar jQuery**
   ```
   No console: typeof jQuery
   Deve ver: "function"
   ```

4. **Verificar se shortcode está correto**
   ```
   [inpark_reserva_form]
   ```

5. **Testar fora do Elementor**
   ```
   Adicionar shortcode numa página normal
   Se funcionar = problema específico do Elementor
   ```

---

## 📋 O que a v1.0.6 tem (tudo da v1.0.5 +)

### Funcionalidades Core
- ✅ Sistema de reservas completo
- ✅ Cálculo automático de preços
- ✅ Períodos dinâmicos por dia da semana
- ✅ Taxa de limpeza obrigatória

### Opções Opcionais (Checkboxes)
- ✅ Horas Extra
- ✅ Catering (Alimentação + Bebidas)
- ✅ Faturação com NIF

### Admin
- ✅ Configuração de preços
- ✅ Gestão de catering categorizado
- ✅ Campos condicionais personalizados
- ✅ Templates de email

### Compatibilidade
- ✅ **WordPress nativo**
- ✅ **Elementor** ← NOVO na v1.0.6!
- ✅ Gutenberg
- ✅ Classic Editor
- ✅ Mobile responsive

---

## 🔄 Atualizar da v1.0.5 para v1.0.6

### É OBRIGATÓRIO atualizar se usar Elementor!

```bash
1. WordPress Admin → Plugins
2. Desativar "InPark - Eventos Reserva PRO"
3. Remover plugin antigo
4. Upload: inpark-eventos-reserva-v1.0.6.zip
5. Ativar plugin
6. Limpar cache do Elementor:
   Elementor → Tools → Regenerate Files & Data
7. Limpar cache do browser (Ctrl+F5)
8. Testar checkboxes
9. ✅ Funciona!
```

---

## ⚡ Início Rápido

### 1. Instalar
```
WordPress → Plugins → Upload
Ficheiro: inpark-eventos-reserva-v1.0.6.zip
```

### 2. Configurar
```
WordPress Admin → Reservas → Configuração
- Definir preços
- Configurar emails
```

### 3. Adicionar à Página

**Elementor:**
```
1. Editar página com Elementor
2. Adicionar widget "Shortcode"
3. Inserir: [inpark_reserva_form]
4. Publicar
```

**Gutenberg:**
```
1. Adicionar bloco "Shortcode"
2. Inserir: [inpark_reserva_form]
3. Publicar
```

### 4. Testar
```
- Abrir página
- Clicar nas checkboxes
- Containers devem abrir ✓
```

---

## 🧪 Como Testar

### Teste 1: Horas Extra
```
1. Marcar checkbox "Horas Extra"
2. Campo numérico deve aparecer ✓
3. Desmarcar checkbox
4. Campo deve desaparecer ✓
```

### Teste 2: Catering
```
1. Marcar checkbox "Catering"
2. Sub-caixas (Alimentação + Bebidas) devem aparecer ✓
3. Selecionar alguns itens
4. Desmarcar checkbox
5. Tudo deve desaparecer e desmarcar ✓
```

### Teste 3: Faturação
```
1. Marcar checkbox "Pretende Fatura com NIF?"
2. 3 campos devem aparecer (Nome, Morada, NIF) ✓
3. Campos devem ficar obrigatórios ✓
4. Desmarcar checkbox
5. Campos devem desaparecer e limpar ✓
```

---

## 📊 Comparação de Versões

| Funcionalidade | v1.0.5 | v1.0.6 |
|----------------|:------:|:------:|
| Checkboxes opcionais | ✅ | ✅ |
| Faturação com NIF | ✅ | ✅ |
| Funciona no WordPress nativo | ✅ | ✅ |
| **Funciona no Elementor** | ❌ | ✅ |
| Eventos delegados | ❌ | ✅ |
| MutationObserver | ❌ | ✅ |
| Console.log debug | ❌ | ✅ |
| Múltiplas inicializações | ❌ | ✅ |

---

## 💡 Para Developers

### Estrutura do JavaScript

```javascript
// Eventos delegados (funciona com Elementor)
$(document).on('change', '#tem_catering', function(){
    toggleCatering();
});

// Múltiplas inicializações
$(document).ready(initInparkForm);           // Normal
$(window).on('load', initInparkForm);        // Garantia
$(window).on('elementor/frontend/init', ...); // Elementor
MutationObserver(...)                        // Dinâmico
```

### Debug no Console

```javascript
console.log('InPark Reserva JS carregado - v1.0.6');
console.log('toggleCatering chamado', $('#tem_catering').is(':checked'));
```

---

## 🎯 Casos de Uso

### Uso Normal (WordPress)
```
1. Criar página
2. Adicionar shortcode
3. Funciona ✓
```

### Uso com Elementor
```
1. Criar página no Elementor
2. Widget Shortcode
3. [inpark_reserva_form]
4. Funciona agora! ✓
```

### Uso com Gutenberg
```
1. Bloco Shortcode
2. [inpark_reserva_form]
3. Funciona ✓
```

---

## 📁 Estrutura de Ficheiros

```
inpark-eventos-reserva-v1.0.6/
│
├── inpark-eventos-reserva.php        (v1.0.6)
├── README.md                          (este ficheiro)
├── CHANGELOG.md
│
├── admin/
│   ├── class-inpark-admin.php
│   └── admin-style.css
│
├── includes/
│   └── installer.php
│
└── public/
    ├── class-inpark-shortcode.php
    ├── style.css
    └── js/
        └── inpark-reserva.js         (reescrito v1.0.6)
```

---

## ✅ Checklist Pós-Instalação

```
□ Plugin instalado e ativado
□ Preços configurados
□ Emails configurados
□ Shortcode adicionado à página
□ Cache limpo (WordPress + Elementor + Browser)
□ Testado checkbox Horas Extra
□ Testado checkbox Catering
□ Testado checkbox Faturação
□ Testado submissão de formulário
□ Emails recebidos
□ Tudo funciona! ✓
```

---

**Versão:** 1.0.6  
**Data:** 03 de Novembro de 2025  
**Autor:** Mário Karim  
**Status:** ✅ Compatível com Elementor

🎉 **Agora funciona perfeitamente no Elementor!**
