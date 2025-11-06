(function($){
    'use strict';
    
    // Preços (vão ser carregados do PHP via wp_localize_script)
    const precos = InparkReserva.precos || {
        exclusivo: 300,
        parcial: 300
    };
    
    // Inicialização
    $(document).ready(function(){
        initBotoesAluguer();
        initComplementos();
        initFaturacao();
        initFormSubmit();
        initHorarioSelector();
        calcularTotal();
    });
    
    // Botões de Tipo de Aluguer
    function initBotoesAluguer(){
        $('.btn-aluguer').on('click', function(){
            const radio = $(this).find('input[type="radio"]');
            
            // Remove active de todos
            $('.btn-aluguer').removeClass('active');
            
            // Adiciona active no clicado
            $(this).addClass('active');
            radio.prop('checked', true);
            
            // Atualiza preço display
            atualizarPrecoBase();
            
            // Recalcula total
            calcularTotal();
        });
        
        // Ativar primeiro por padrão
        $('.btn-aluguer').first().addClass('active');
        $('.btn-aluguer').first().find('input[type="radio"]').prop('checked', true);
        atualizarPrecoBase();
    }
    
    function atualizarPrecoBase(){
        const tipoSelecionado = $('input[name="tipo"]:checked').val();
        let preco = 0;
        
        if (tipoSelecionado === 'exclusivo') {
            preco = precos.exclusivo || 300;
        } else if (tipoSelecionado === 'parcial') {
            preco = precos.parcial || 300;
        }
        
        $('#preco-base-display').text(formatarPreco(preco));
    }
    
    // Complementos - Visual feedback
    function initComplementos(){
        $('.complemento-item').on('click', function(e){
            // Se clicar no checkbox, não fazer nada (deixa comportamento padrão)
            if ($(e.target).is('input[type="checkbox"]')) {
                return;
            }
            
            // Toggle checkbox
            const checkbox = $(this).find('input[type="checkbox"]');
            checkbox.prop('checked', !checkbox.prop('checked'));
            checkbox.trigger('change');
        });
        
        // Visual quando checkbox muda
        $('.complemento-checkbox').on('change', function(){
            const item = $(this).closest('.complemento-item');
            
            if ($(this).is(':checked')) {
                item.addClass('checked');
            } else {
                item.removeClass('checked');
            }
            
            calcularTotal();
        });
    }
    
    // Toggle Faturação
    function initFaturacao(){
        $('#toggle-fatura').on('change', function(){
            if ($(this).is(':checked')) {
                $('#fatura-fields').removeClass('hidden').slideDown(300);
            } else {
                $('#fatura-fields').slideUp(300, function(){
                    $(this).addClass('hidden');
                });
                // Limpar campos
                $('#fatura_nome, #fatura_morada, #fatura_nif').val('');
            }
        });
    }
    
    // Calcular Total
    function calcularTotal(){
        let total = 0;
        
        // Preço base do tipo de aluguer
        const tipoSelecionado = $('input[name="tipo"]:checked').val();
        if (tipoSelecionado === 'exclusivo') {
            total += precos.exclusivo || 300;
        } else if (tipoSelecionado === 'parcial') {
            total += precos.parcial || 300;
        }
        
        // Complementos selecionados
        $('.complemento-checkbox:checked').each(function(){
            const preco = parseFloat($(this).data('preco')) || 0;
            total += preco;
        });
        
        // Atualizar display
        $('#total-value').text(formatarPreco(total));
        $('#total-hidden').val(total.toFixed(2));
    }
    
    function formatarPreco(valor){
        return valor.toFixed(2).replace('.', ',') + '€';
    }

    // Seleção de Horário
    function initHorarioSelector(){
        const $horarioInput = $('#horario');
        const $dataInput = $('#data');

        // Criar container para os botões de horário
        const $horariosContainer = $('<div class="horarios-container hidden"></div>');
        $horarioInput.closest('.form-row').append($horariosContainer);

        // Quando clicar no campo de horário
        $horarioInput.on('click', function(){
            const dataValue = $dataInput.val();

            if (!dataValue) {
                alert('Por favor, selecione primeiro uma data.');
                $dataInput.focus();
                return;
            }

            // Toggle do container
            if ($horariosContainer.hasClass('hidden')) {
                gerarHorarios();
                $horariosContainer.removeClass('hidden').slideDown(300);
            } else {
                $horariosContainer.slideUp(300, function(){
                    $(this).addClass('hidden');
                });
            }
        });

        // Quando mudar o tipo de aluguer, atualizar horários se já estiver aberto
        $('input[name="tipo"]').on('change', function(){
            if (!$horariosContainer.hasClass('hidden')) {
                gerarHorarios();
            }
            // Limpar horário selecionado ao trocar tipo
            $horarioInput.val('');
        });

        // Quando mudar a data, limpar horário
        $dataInput.on('change', function(){
            $horarioInput.val('');
            $horariosContainer.slideUp(300, function(){
                $(this).addClass('hidden');
            });
        });

        function gerarHorarios(){
            const tipoSelecionado = $('input[name="tipo"]:checked').val();
            const horarios = [];

            if (tipoSelecionado === 'exclusivo') {
                // Aluguer exclusivo: blocos de 4 horas
                horarios.push('10:00-14:00');
                horarios.push('14:00-18:00');
                horarios.push('18:00-22:00');
            } else if (tipoSelecionado === 'parcial') {
                // Aluguer parcial: blocos de 2 horas
                horarios.push('10:00-12:00');
                horarios.push('12:00-14:00');
                horarios.push('14:00-16:00');
                horarios.push('16:00-18:00');
                horarios.push('18:00-20:00');
                horarios.push('20:00-22:00');
            }

            // Limpar container
            $horariosContainer.empty();

            // Criar botões
            horarios.forEach(function(horario){
                const $btn = $('<button type="button" class="btn-horario">' + horario + '</button>');

                $btn.on('click', function(){
                    $horarioInput.val(horario);
                    $('.btn-horario').removeClass('selected');
                    $(this).addClass('selected');

                    // Fechar container após seleção
                    setTimeout(function(){
                        $horariosContainer.slideUp(300, function(){
                            $(this).addClass('hidden');
                        });
                    }, 200);
                });

                $horariosContainer.append($btn);
            });
        }

        // Fechar ao clicar fora
        $(document).on('click', function(e){
            if (!$(e.target).closest('.form-row').length || $(e.target).is('#data')) {
                if (!$horariosContainer.hasClass('hidden')) {
                    $horariosContainer.slideUp(300, function(){
                        $(this).addClass('hidden');
                    });
                }
            }
        });
    }

    // Submissão do Formulário
    function initFormSubmit(){
        $('#inpark-reserva-form').on('submit', function(e){
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('.btn-submit');
            const $msg = $('#form-msg');
            
            // Desabilitar botão
            $submitBtn.prop('disabled', true).text('Enviando...');
            
            // Preparar dados
            const data = $form.serializeArray();
            data.push({name: 'action', value: 'inpark_reserva_submit'});
            
            // Enviar via AJAX
            $.post(InparkReserva.ajaxurl, data)
                .done(function(resp){
                    if (resp && resp.success) {
                        // Sucesso
                        $msg.removeClass('err').addClass('ok')
                            .text(InparkReserva.i18n.ok || 'Reserva enviada com sucesso!')
                            .show();
                        
                        // Reset form
                        $form[0].reset();
                        $('.btn-aluguer').removeClass('active');
                        $('.btn-aluguer').first().addClass('active');
                        $('.complemento-item').removeClass('checked');
                        $('#fatura-fields').addClass('hidden');
                        
                        // Recalcular
                        calcularTotal();
                        atualizarPrecoBase();
                        
                        // Redirecionar para Thank You Page
                        if (InparkReserva.thank_you_url) {
                            console.log('InPark: Redirecionando para ' + InparkReserva.thank_you_url);
                            setTimeout(function(){
                                window.location.href = InparkReserva.thank_you_url;
                            }, 2000);
                        }
                    } else {
                        // Erro
                        const errorMsg = resp && resp.data && resp.data.message 
                            ? resp.data.message 
                            : (InparkReserva.i18n.fail || 'Erro ao enviar. Tente novamente.');
                        
                        $msg.removeClass('ok').addClass('err')
                            .text(errorMsg)
                            .show();
                    }
                })
                .fail(function(){
                    $msg.removeClass('ok').addClass('err')
                        .text(InparkReserva.i18n.fail || 'Erro ao enviar. Tente novamente.')
                        .show();
                })
                .always(function(){
                    // Reabilitar botão
                    $submitBtn.prop('disabled', false).text('Submeter');
                });
        });
    }
    
})(jQuery);
