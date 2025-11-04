(function(){
    'use strict';
    
    console.log('🚀 InPark Reserva v1.0.7 carregado');
    
    let initialized = false;
    let checkCount = 0;
    const MAX_CHECKS = 50; // 50 tentativas = 5 segundos
    
    // Utility functions
    function euro(n){ 
        return (Number(n)||0).toFixed(2).replace('.', ',') + ' €'; 
    }
    
    function $(selector) {
        return document.querySelector(selector);
    }
    
    function $$(selector) {
        return document.querySelectorAll(selector);
    }

    // Define regras de períodos
    const PERIODOS = {
        meio_dia: {
            0: ['Noite (19h–23h)'],
            1: ['Manhã (10h–14h)','Tarde (15h–19h)','Noite (19h–23h)'],
            2: ['Manhã (10h–14h)','Tarde (15h–19h)','Noite (19h–23h)'],
            3: ['Manhã (10h–14h)','Tarde (15h–19h)','Noite (19h–23h)'],
            4: ['Manhã (10h–14h)','Tarde (15h–19h)','Noite (19h–23h)'],
            5: ['Tarde (15h–19h)','Noite (19h–23h)'],
            6: ['Noite (19h–23h)'],
        },
        dia_inteiro: {
            0: [],
            1: ['Manhã (10h–18h)','Tarde (14h–22h)'],
            2: ['Manhã (10h–18h)','Tarde (14h–22h)'],
            3: ['Manhã (10h–18h)','Tarde (14h–22h)'],
            4: ['Manhã (10h–18h)','Tarde (14h–22h)'],
            5: ['Manhã (10h–18h)','Tarde (14h–22h)'],
            6: []
        }
    };

    function refreshPeriodos(){
        const tipoEl = $('#tipo-aluguer');
        const dataEl = $('input[name="data"]');
        const periodoEl = $('#periodo');
        
        if(!tipoEl || !dataEl || !periodoEl) {
            console.log('⚠️ Elementos não encontrados para refreshPeriodos');
            return;
        }
        
        const tipo = tipoEl.value;
        const data = dataEl.value;
        
        periodoEl.innerHTML = '';
        
        if(!data) return;
        
        const d = new Date(data + 'T00:00:00');
        const dow = d.getUTCDay();
        const options = PERIODOS[tipo][dow] || [];
        
        if(options.length === 0){
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Indisponível para este dia';
            periodoEl.appendChild(opt);
        } else {
            options.forEach(function(p){
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                periodoEl.appendChild(opt);
            });
        }
        
        console.log('✓ Períodos atualizados:', options.length);
    }

    function calcular(){
        if(typeof InparkReserva === 'undefined') {
            console.log('⚠️ InparkReserva não definido');
            return;
        }
        
        const precos = InparkReserva.precos;
        const tipoEl = $('#tipo-aluguer');
        if(!tipoEl) return;
        
        const tipo = tipoEl.value;
        let total = 0;
        
        // Base price
        if(tipo === 'meio_dia'){ 
            total += precos.meio_dia;
        } else { 
            total += precos.dia_inteiro;
        }

        // Horas extra
        const horasExtraCheck = $('#tem_horas_extra');
        if(horasExtraCheck && horasExtraCheck.checked) {
            const horasExtraVal = $('#horas_extra');
            if(horasExtraVal) {
                const horas_extra = Number(horasExtraVal.value || 0);
                if (tipo === 'meio_dia') {
                    total += horas_extra * precos.hora_extra_meio;
                } else {
                    total += horas_extra * precos.hora_extra_inteiro;
                }
            }
        }

        // Catering
        const cateringCheck = $('#tem_catering');
        if(cateringCheck && cateringCheck.checked) {
            $$('fieldset.catering .cat-line').forEach(function(line){
                const chk = line.querySelector('input[type="checkbox"]');
                const qtdEl = line.querySelector('input.qtd');
                if(chk && chk.checked && qtdEl) {
                    const qtd = Number(qtdEl.value || 0);
                    const pvp = Number(chk.dataset.pvp || 0);
                    if(qtd > 0) {
                        total += pvp * qtd;
                    }
                }
            });
        }

        // Taxa limpeza
        total += precos.taxa_limpeza;

        const totalEl = $('#total');
        const totalInputEl = $('#total_input');
        if(totalEl) totalEl.textContent = euro(total);
        if(totalInputEl) totalInputEl.value = total.toFixed(2);
        
        console.log('✓ Total calculado:', total);
    }

    function slideDown(element, duration = 300) {
        element.style.display = 'block';
        element.style.overflow = 'hidden';
        element.style.height = '0';
        element.style.transition = `height ${duration}ms ease`;
        
        const height = element.scrollHeight;
        
        setTimeout(function() {
            element.style.height = height + 'px';
        }, 10);
        
        setTimeout(function() {
            element.style.height = '';
            element.style.overflow = '';
            element.style.transition = '';
        }, duration + 50);
    }
    
    function slideUp(element, duration = 300) {
        element.style.overflow = 'hidden';
        element.style.height = element.scrollHeight + 'px';
        element.style.transition = `height ${duration}ms ease`;
        
        setTimeout(function() {
            element.style.height = '0';
        }, 10);
        
        setTimeout(function() {
            element.style.display = 'none';
            element.style.height = '';
            element.style.overflow = '';
            element.style.transition = '';
        }, duration + 50);
    }

    function toggleHorasExtra(){
        console.log('🔄 toggleHorasExtra');
        const checkbox = $('#tem_horas_extra');
        const container = $('[data-show-when="tem_horas_extra"][data-show-value="checked"]');
        
        if(!checkbox || !container) {
            console.log('⚠️ Elementos horas extra não encontrados');
            return;
        }
        
        if(checkbox.checked) {
            console.log('✓ Mostrando horas extra');
            slideDown(container);
        } else {
            console.log('✓ Escondendo horas extra');
            slideUp(container);
            const horasInput = $('#horas_extra');
            if(horasInput) horasInput.value = '0';
        }
        calcular();
    }

    function toggleCatering(){
        console.log('🔄 toggleCatering');
        const checkbox = $('#tem_catering');
        const container = $('#catering-container');
        
        if(!checkbox || !container) {
            console.log('⚠️ Elementos catering não encontrados');
            return;
        }
        
        if(checkbox.checked) {
            console.log('✓ Mostrando catering');
            slideDown(container);
        } else {
            console.log('✓ Escondendo catering');
            slideUp(container);
            $$('#catering-container input[type="checkbox"]').forEach(function(chk){
                chk.checked = false;
            });
        }
        calcular();
    }

    function toggleFaturacao(){
        console.log('🔄 toggleFaturacao');
        const checkbox = $('#pretende_fatura');
        const container = $('#faturacao-container');
        
        if(!checkbox || !container) {
            console.log('⚠️ Elementos faturação não encontrados');
            return;
        }
        
        if(checkbox.checked) {
            console.log('✓ Mostrando faturação');
            slideDown(container);
            ['#fatura_nome', '#fatura_morada', '#fatura_nif'].forEach(function(sel){
                const el = $(sel);
                if(el) el.required = true;
            });
        } else {
            console.log('✓ Escondendo faturação');
            slideUp(container);
            ['#fatura_nome', '#fatura_morada', '#fatura_nif'].forEach(function(sel){
                const el = $(sel);
                if(el) {
                    el.required = false;
                    el.value = '';
                }
            });
        }
    }

    function checkConditionalFields(){
        $$('.conditional-field').forEach(function(field){
            const showWhen = field.dataset.showWhen;
            const showValue = field.dataset.showValue;
            
            if(showWhen === 'tem_horas_extra') return;
            
            if(!showWhen || !showValue) {
                field.style.display = 'block';
                return;
            }
            
            let currentValue = '';
            if(showWhen === 'tipo') {
                const el = $('#tipo-aluguer');
                currentValue = el ? el.value : '';
            } else if(showWhen === 'periodo') {
                const el = $('#periodo');
                currentValue = el ? el.value : '';
            }
            
            if(currentValue === showValue) {
                slideDown(field, 200);
                field.querySelectorAll('[required]').forEach(function(el){
                    el.disabled = false;
                });
            } else {
                slideUp(field, 200);
                field.querySelectorAll('[required]').forEach(function(el){
                    el.disabled = true;
                });
                field.querySelectorAll('input, select, textarea').forEach(function(el){
                    el.value = '';
                });
                field.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(function(el){
                    el.checked = false;
                });
            }
        });
    }

    function setupEventListeners() {
        console.log('🔗 Configurando event listeners');
        
        // Tipo e Data
        document.addEventListener('change', function(e){
            if(e.target.matches('#tipo-aluguer') || e.target.matches('input[name="data"]')) {
                console.log('📅 Tipo ou data mudou');
                refreshPeriodos();
                calcular();
                checkConditionalFields();
            }
        });
        
        // Período
        document.addEventListener('change', function(e){
            if(e.target.matches('#periodo')) {
                console.log('⏰ Período mudou');
                checkConditionalFields();
            }
        });
        
        // Checkboxes principais
        document.addEventListener('change', function(e){
            if(e.target.matches('#tem_horas_extra')) {
                console.log('☑️ Checkbox horas extra');
                toggleHorasExtra();
            } else if(e.target.matches('#tem_catering')) {
                console.log('☑️ Checkbox catering');
                toggleCatering();
            } else if(e.target.matches('#pretende_fatura')) {
                console.log('☑️ Checkbox faturação');
                toggleFaturacao();
            }
        });
        
        // Inputs de cálculo
        document.addEventListener('input', function(e){
            if(e.target.matches('#horas_extra') || e.target.closest('fieldset.catering')) {
                calcular();
            }
        });
        
        document.addEventListener('change', function(e){
            if(e.target.closest('fieldset.catering')) {
                calcular();
            }
        });
        
        // Submissão
        document.addEventListener('submit', function(e){
            if(e.target.matches('#inpark-reserva-form')) {
                e.preventDefault();
                handleSubmit(e.target);
            }
        });
        
        console.log('✓ Event listeners configurados');
    }

    function handleSubmit(form) {
        console.log('📤 Submetendo formulário');
        
        // Desabilitar campos hidden required
        $$('.conditional-field:not([style*="display: block"]) [required]').forEach(function(el){
            el.disabled = true;
        });
        
        const formData = new FormData(form);
        formData.append('action', 'inpark_reserva_submit');
        
        fetch(InparkReserva.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(function(response){ return response.json(); })
        .then(function(resp){
            const msgEl = $('#form-msg');
            if(resp && resp.success) {
                if(msgEl) {
                    msgEl.textContent = InparkReserva.i18n.ok;
                    msgEl.className = 'msg ok';
                }
                form.reset();
                
                // Reset containers
                const containers = [
                    $('[data-show-when="tem_horas_extra"][data-show-value="checked"]'),
                    $('#catering-container'),
                    $('#faturacao-container')
                ];
                containers.forEach(function(c){
                    if(c) c.style.display = 'none';
                });
                
                const horasInput = $('#horas_extra');
                if(horasInput) horasInput.value = '0';
                
                refreshPeriodos();
                calcular();
                checkConditionalFields();
            } else {
                if(msgEl) {
                    msgEl.textContent = resp && resp.data ? resp.data.message : InparkReserva.i18n.fail;
                    msgEl.className = 'msg err';
                }
            }
        })
        .catch(function(error){
            console.error('Erro:', error);
            const msgEl = $('#form-msg');
            if(msgEl) {
                msgEl.textContent = InparkReserva.i18n.fail;
                msgEl.className = 'msg err';
            }
        })
        .finally(function(){
            // Re-habilitar campos
            $$('[disabled]').forEach(function(el){
                el.disabled = false;
            });
        });
    }

    function initForm() {
        if(initialized) {
            console.log('⚠️ Já inicializado');
            return;
        }
        
        const form = $('#inpark-reserva-form');
        if(!form) {
            console.log('⚠️ Formulário não encontrado');
            return false;
        }
        
        console.log('🎯 Inicializando formulário...');
        
        // Garantir que containers estão ocultos
        const horasContainer = $('[data-show-when="tem_horas_extra"][data-show-value="checked"]');
        const cateringContainer = $('#catering-container');
        const faturacaoContainer = $('#faturacao-container');
        
        if(horasContainer) horasContainer.style.display = 'none';
        if(cateringContainer) cateringContainer.style.display = 'none';
        if(faturacaoContainer) faturacaoContainer.style.display = 'none';
        
        console.log('✓ Containers inicialmente ocultos');
        
        // Setup
        refreshPeriodos();
        calcular();
        checkConditionalFields();
        
        initialized = true;
        console.log('✅ Formulário inicializado com sucesso!');
        
        return true;
    }

    function tryInit() {
        if(initialized) return;
        
        checkCount++;
        console.log(`🔍 Tentativa ${checkCount}/${MAX_CHECKS}`);
        
        if(initForm()) {
            console.log('✅ Inicialização bem sucedida!');
            return;
        }
        
        if(checkCount < MAX_CHECKS) {
            setTimeout(tryInit, 100);
        } else {
            console.log('❌ Max tentativas atingido');
        }
    }

    // Event listeners devem ser configurados apenas uma vez
    if(document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function(){
            console.log('📄 DOMContentLoaded');
            setupEventListeners();
            tryInit();
        });
    } else {
        console.log('📄 DOM já pronto');
        setupEventListeners();
        tryInit();
    }
    
    // Window load backup
    window.addEventListener('load', function(){
        console.log('🪟 Window load');
        setTimeout(tryInit, 200);
    });
    
    // Elementor específico
    window.addEventListener('elementor/frontend/init', function(){
        console.log('⚡ Elementor init');
        setTimeout(tryInit, 500);
    });
    
})();
