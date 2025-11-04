<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Inpark_Public_Shortcode {
    public function __construct(){
        add_shortcode('inpark_reserva_form', array($this, 'render'));
    }

    public function render($atts){
        $nonce = wp_create_nonce('inpark_reserva_nonce');
        $catering = get_option('inpark_catering_items', array());
        if (!is_array($catering)) {
            $catering = array();
        }

        $conditional_fields = get_option('inpark_conditional_fields', array());
        if (!is_array($conditional_fields)) {
            $conditional_fields = array();
        }

        // Separar catering por categoria
        $alimentacao = array_filter($catering, function($item) {
            return ($item['categoria'] ?? 'alimentacao') === 'alimentacao';
        });
        
        $bebidas = array_filter($catering, function($item) {
            return ($item['categoria'] ?? 'alimentacao') === 'bebidas';
        });
        
        ob_start(); ?>
        <form id="inpark-reserva-form" class="inpark-form">
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>"/>
            
            <div class="row">
                <label><?php _e('Nome', 'inpark-reserva'); ?>*</label>
                <input type="text" name="nome" required>
            </div>
            <div class="row">
                <label><?php _e('Email', 'inpark-reserva'); ?>*</label>
                <input type="email" name="email" required>
            </div>
            <div class="row">
                <label><?php _e('Telefone', 'inpark-reserva'); ?>*</label>
                <input type="text" name="telefone" required>
            </div>
            <div class="row">
                <label><?php _e('Data', 'inpark-reserva'); ?>*</label>
                <input type="date" name="data" required>
            </div>
            <div class="row">
                <label><?php _e('Tipo de Aluguer', 'inpark-reserva'); ?>*</label>
                <select name="tipo" id="tipo-aluguer" required>
                    <option value="meio_dia"><?php _e('Meio dia (4h)', 'inpark-reserva'); ?></option>
                    <option value="dia_inteiro"><?php _e('Dia inteiro (8h)', 'inpark-reserva'); ?></option>
                </select>
            </div>
            <div class="row">
                <label><?php _e('Período', 'inpark-reserva'); ?>*</label>
                <select name="periodo" id="periodo" required></select>
                <small class="help"><?php _e('As opções dependem do dia da semana e do tipo de aluguer.', 'inpark-reserva'); ?></small>
            </div>

            <!-- HORAS EXTRA COM CHECKBOX -->
            <div class="row checkbox-toggle-row">
                <label class="checkbox-main">
                    <input type="checkbox" name="tem_horas_extra" id="tem_horas_extra">
                    <?php _e('Horas Extra', 'inpark-reserva'); ?>
                </label>
            </div>
            <div class="row conditional-field" data-show-when="tem_horas_extra" data-show-value="checked">
                <label><?php _e('Número de Horas Extra', 'inpark-reserva'); ?></label>
                <input type="number" name="horas_extra" id="horas_extra" value="0" min="0" step="1">
            </div>

            <?php if(!empty($conditional_fields)): ?>
                <div class="conditional-fields-container">
                    <?php foreach($conditional_fields as $field): 
                        $field_name = 'cf_' . sanitize_key($field['id']);
                        $show_class = '';
                        $data_attrs = '';
                        
                        if (!empty($field['show_when_field']) && !empty($field['show_when_value'])) {
                            $show_class = 'conditional-field';
                            $data_attrs = sprintf(
                                'data-show-when="%s" data-show-value="%s"',
                                esc_attr($field['show_when_field']),
                                esc_attr($field['show_when_value'])
                            );
                        }
                    ?>
                        <div class="row <?php echo $show_class; ?>" <?php echo $data_attrs; ?>>
                            <label>
                                <?php echo esc_html($field['label']); ?>
                                <?php if ($field['required']): ?>*<?php endif; ?>
                            </label>
                            
                            <?php if ($field['type'] === 'text'): ?>
                                <input type="text" name="<?php echo esc_attr($field_name); ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
                            
                            <?php elseif ($field['type'] === 'number'): ?>
                                <input type="number" name="<?php echo esc_attr($field_name); ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
                            
                            <?php elseif ($field['type'] === 'textarea'): ?>
                                <textarea name="<?php echo esc_attr($field_name); ?>" rows="4" <?php echo $field['required'] ? 'required' : ''; ?>></textarea>
                            
                            <?php elseif ($field['type'] === 'select' && !empty($field['options'])): ?>
                                <select name="<?php echo esc_attr($field_name); ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($field['options'] as $option): ?>
                                        <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            
                            <?php elseif ($field['type'] === 'radio' && !empty($field['options'])): ?>
                                <div class="radio-group">
                                    <?php foreach ($field['options'] as $option): ?>
                                        <label class="radio-option">
                                            <input type="radio" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($option); ?>" <?php echo $field['required'] ? 'required' : ''; ?>>
                                            <?php echo esc_html($option); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            
                            <?php elseif ($field['type'] === 'checkbox'): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="<?php echo esc_attr($field_name); ?>" value="1" <?php echo $field['required'] ? 'required' : ''; ?>>
                                    <?php echo esc_html($field['label']); ?>
                                </label>
                            
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- CATERING COM CHECKBOX PRINCIPAL -->
            <?php if(!empty($alimentacao) || !empty($bebidas)): ?>
            <div class="row checkbox-toggle-row">
                <label class="checkbox-main">
                    <input type="checkbox" name="tem_catering" id="tem_catering">
                    <?php _e('Catering', 'inpark-reserva'); ?>
                </label>
            </div>

            <div id="catering-container" class="catering-container">
                <!-- CATERING: ALIMENTAÇÃO -->
                <?php if(!empty($alimentacao)): ?>
                <fieldset class="catering">
                    <legend><?php _e('Alimentação', 'inpark-reserva'); ?></legend>
                    <?php foreach($alimentacao as $idx => $it): ?>
                        <label class="cat-line">
                            <input type="checkbox" name="catering[]" value="<?php echo esc_attr($it['id']); ?>" data-pvp="<?php echo esc_attr($it['pvp']); ?>">
                            <span><?php echo esc_html($it['nome']); ?> (<?php echo esc_html($it['unidade']); ?>) – <?php echo number_format_i18n($it['pvp'], 2); ?> €</span>
                            <input type="number" name="catering_qtd[]" class="qtd" value="1" min="1">
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <?php endif; ?>

                <!-- CATERING: BEBIDAS -->
                <?php if(!empty($bebidas)): ?>
                <fieldset class="catering">
                    <legend><?php _e('Bebidas', 'inpark-reserva'); ?></legend>
                    <?php foreach($bebidas as $idx => $it): ?>
                        <label class="cat-line">
                            <input type="checkbox" name="catering[]" value="<?php echo esc_attr($it['id']); ?>" data-pvp="<?php echo esc_attr($it['pvp']); ?>">
                            <span><?php echo esc_html($it['nome']); ?> (<?php echo esc_html($it['unidade']); ?>) – <?php echo number_format_i18n($it['pvp'], 2); ?> €</span>
                            <input type="number" name="catering_qtd[]" class="qtd" value="1" min="1">
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <p><?php _e('Sem itens de catering configurados.', 'inpark-reserva'); ?></p>
            <?php endif; ?>

            <!-- FATURAÇÃO COM CHECKBOX -->
            <div class="row checkbox-toggle-row">
                <label class="checkbox-main">
                    <input type="checkbox" name="pretende_fatura" id="pretende_fatura">
                    <?php _e('Pretende Fatura com NIF?', 'inpark-reserva'); ?>
                </label>
            </div>

            <div id="faturacao-container" class="faturacao-container">
                <fieldset class="faturacao-fields">
                    <legend><?php _e('Dados de Faturação', 'inpark-reserva'); ?></legend>
                    <div class="row">
                        <label><?php _e('Nome', 'inpark-reserva'); ?>*</label>
                        <input type="text" name="fatura_nome" id="fatura_nome">
                    </div>
                    <div class="row">
                        <label><?php _e('Morada', 'inpark-reserva'); ?>*</label>
                        <textarea name="fatura_morada" id="fatura_morada" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <label><?php _e('NIF', 'inpark-reserva'); ?>*</label>
                        <input type="text" name="fatura_nif" id="fatura_nif" maxlength="9">
                    </div>
                </fieldset>
            </div>

            <div class="row">
                <label><?php _e('Taxa de limpeza (obrigatória)', 'inpark-reserva'); ?></label>
                <input type="checkbox" checked disabled> <small><?php echo number_format_i18n( floatval( (get_option('inpark_reserva_settings')['taxa_limpeza'] ?? 40) ), 2 ); ?> €</small>
            </div>

            <div class="total">
                <strong><?php _e('Total', 'inpark-reserva'); ?>:</strong>
                <span id="total">0,00 €</span>
                <input type="hidden" name="total" id="total_input" value="0">
            </div>

            <div class="actions">
                <button type="submit" class="btn"><?php _e('Enviar Reserva', 'inpark-reserva'); ?></button>
                <div class="msg" id="form-msg" role="alert"></div>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }
}
new Inpark_Public_Shortcode();
