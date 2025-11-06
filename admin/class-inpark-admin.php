<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Inpark_Admin {
    public function __construct(){
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }

    public function admin_scripts($hook) {
        if (strpos($hook, 'inpark_reserva') === false) return;
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('inpark-admin-css', INPARK_RESERVA_URL . 'admin/admin-style.css', array(), INPARK_RESERVA_VERSION);
    }

    public function menu(){
        add_menu_page(
            __('Reservas', 'inpark-reserva'),
            __('Reservas', 'inpark-reserva'),
            'manage_options',
            'inpark_reserva',
            array($this, 'render_settings'),
            'dashicons-calendar-alt',
            25
        );

        add_submenu_page('inpark_reserva',
            __('Configuração', 'inpark-reserva'),
            __('Configuração', 'inpark-reserva'),
            'manage_options',
            'inpark_reserva',
            array($this, 'render_settings')
        );

        add_submenu_page('inpark_reserva',
            __('Catering', 'inpark-reserva'),
            __('Catering', 'inpark-reserva'),
            'manage_options',
            'inpark_reserva_catering',
            array($this, 'render_catering')
        );

        add_submenu_page('inpark_reserva',
            __('Campos Condicionais', 'inpark-reserva'),
            __('Campos Condicionais', 'inpark-reserva'),
            'manage_options',
            'inpark_reserva_conditional',
            array($this, 'render_conditional_fields')
        );
    }

    public function register_settings(){
        register_setting('inpark_reserva_group', 'inpark_reserva_settings');
        register_setting('inpark_reserva_group', 'inpark_catering_items');
        register_setting('inpark_reserva_group', 'inpark_conditional_fields');
    }

    public function render_settings(){
        if (!current_user_can('manage_options')) return;
        $opts = get_option('inpark_reserva_settings', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Configuração de Preços e Emails', 'inpark-reserva'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('inpark_reserva_group'); ?>
                <table class="form-table" role="presentation">
                    <tr><th colspan="2"><h2><?php _e('Preços', 'inpark-reserva'); ?></h2></th></tr>
                    <tr>
                        <th><label for="preco_meio_dia">Meio dia (4h)</label></th>
                        <td><input name="inpark_reserva_settings[preco_meio_dia]" type="number" step="0.01" value="<?php echo esc_attr($opts['preco_meio_dia'] ?? 220); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="hora_extra_meio">Hora adicional (meio dia)</label></th>
                        <td><input name="inpark_reserva_settings[hora_extra_meio]" type="number" step="0.01" value="<?php echo esc_attr($opts['hora_extra_meio'] ?? 49.50); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="preco_dia_inteiro">Dia inteiro (8h)</label></th>
                        <td><input name="inpark_reserva_settings[preco_dia_inteiro]" type="number" step="0.01" value="<?php echo esc_attr($opts['preco_dia_inteiro'] ?? 280); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="hora_extra_inteiro">Hora adicional (dia inteiro)</label></th>
                        <td><input name="inpark_reserva_settings[hora_extra_inteiro]" type="number" step="0.01" value="<?php echo esc_attr($opts['hora_extra_inteiro'] ?? 63.00); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="taxa_limpeza">Taxa de limpeza (obrigatória)</label></th>
                        <td><input name="inpark_reserva_settings[taxa_limpeza]" type="number" step="0.01" value="<?php echo esc_attr($opts['taxa_limpeza'] ?? 40); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <hr/>
                <table class="form-table" role="presentation">
                    <tr><th colspan="2"><h2><?php _e('Emails', 'inpark-reserva'); ?></h2></th></tr>
                    <tr>
                        <th><label for="admin_email">Email do Admin</label></th>
                        <td><input name="inpark_reserva_settings[admin_email]" type="email" value="<?php echo esc_attr($opts['admin_email'] ?? get_option('admin_email')); ?>" class="regular-text"></td>
                    </tr>
                    <tr><th colspan="2"><h3><?php _e('Email para o Utilizador', 'inpark-reserva'); ?></h3></th></tr>
                    <tr>
                        <th><label for="user_subject">Assunto</label></th>
                        <td><input name="inpark_reserva_settings[user_subject]" type="text" value="<?php echo esc_attr($opts['user_subject'] ?? 'Confirmação de reserva #{reserva_id}'); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="user_body">Mensagem</label></th>
                        <td><textarea name="inpark_reserva_settings[user_body]" rows="8" class="large-text code"><?php echo esc_textarea($opts['user_body'] ?? 'Olá {nome},\n\nObrigado pela sua reserva...'); ?></textarea>
                        <p class="description"><?php _e('Placeholders: {nome}, {email}, {telefone}, {data}, {periodo}, {tipo}, {horas_extra}, {catering}, {faturacao}, {campos_extras}, {total}, {reserva_id}', 'inpark-reserva'); ?></p></td>
                    </tr>
                    <tr><th colspan="2"><h3><?php _e('Email para o Admin', 'inpark-reserva'); ?></h3></th></tr>
                    <tr>
                        <th><label for="admin_subject">Assunto</label></th>
                        <td><input name="inpark_reserva_settings[admin_subject]" type="text" value="<?php echo esc_attr($opts['admin_subject'] ?? 'Nova reserva #{reserva_id}'); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="admin_body">Mensagem</label></th>
                        <td><textarea name="inpark_reserva_settings[admin_body]" rows="8" class="large-text code"><?php echo esc_textarea($opts['admin_body'] ?? 'Nova reserva...'); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_catering(){
        if (!current_user_can('manage_options')) return;
        $items = get_option('inpark_catering_items', array());

        // Handle add/update
        if (isset($_POST['inpark_catering_nonce']) && wp_verify_nonce($_POST['inpark_catering_nonce'], 'inpark_catering_save')){
            $new = array();
            $existing_ids = array_column($items, 'id');
            $next_id = !empty($existing_ids) ? max($existing_ids) + 1 : 1;
            
            if (!empty($_POST['cat_nome'])){
                foreach ($_POST['cat_nome'] as $i => $nome){
                    $nome = sanitize_text_field($nome);
                    if ($nome === '') continue;

                    $item_id = isset($_POST['cat_id'][$i]) && intval($_POST['cat_id'][$i]) > 0 
                        ? intval($_POST['cat_id'][$i]) 
                        : $next_id++;

                    $new[] = array(
                        'id' => $item_id,
                        'nome' => $nome,
                        'categoria' => sanitize_text_field($_POST['cat_categoria'][$i] ?? 'alimentacao'),
                        'unidade' => sanitize_text_field($_POST['cat_unidade'][$i] ?? ''),
                        'custo' => floatval($_POST['cat_custo'][$i] ?? 0),
                        'pvp' => floatval($_POST['cat_pvp'][$i] ?? 0),
                    );
                }
            }
            update_option('inpark_catering_items', $new);
            $items = $new;
            echo '<div class="updated notice"><p>'.__('Catering atualizado.', 'inpark-reserva').'</p></div>';
        }

        $alimentacao = array_filter($items, function($item) {
            return ($item['categoria'] ?? 'alimentacao') === 'alimentacao';
        });
        
        $bebidas = array_filter($items, function($item) {
            return ($item['categoria'] ?? 'alimentacao') === 'bebidas';
        });

        ?>
        <div class="wrap">
            <h1><?php _e('Catering', 'inpark-reserva'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('inpark_catering_save', 'inpark_catering_nonce'); ?>
                
                <h2><?php _e('Alimentação', 'inpark-reserva'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width: 60px;"><?php _e('ID', 'inpark-reserva'); ?></th>
                            <th><?php _e('Nome', 'inpark-reserva'); ?></th>
                            <th style="width: 120px;"><?php _e('Unidade', 'inpark-reserva'); ?></th>
                            <th style="width: 120px;"><?php _e('Custo Fornecedor (€)', 'inpark-reserva'); ?></th>
                            <th style="width: 120px;"><?php _e('PVP (€)', 'inpark-reserva'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($alimentacao)) : foreach ($alimentacao as $i => $it): ?>
                        <tr>
                            <td><input type="number" name="cat_id[]" value="<?php echo esc_attr($it['id']); ?>" readonly style="width: 100%;"></td>
                            <td>
                                <input type="text" name="cat_nome[]" value="<?php echo esc_attr($it['nome']); ?>" style="width: 100%;">
                                <input type="hidden" name="cat_categoria[]" value="alimentacao">
                            </td>
                            <td><input type="text" name="cat_unidade[]" value="<?php echo esc_attr($it['unidade']); ?>" style="width: 100%;"></td>
                            <td><input type="number" step="0.01" name="cat_custo[]" value="<?php echo esc_attr($it['custo']); ?>" style="width: 100%;"></td>
                            <td><input type="number" step="0.01" name="cat_pvp[]" value="<?php echo esc_attr($it['pvp']); ?>" style="width: 100%;"></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        <tr style="background-color: #f0f0f1;">
                            <td><input type="number" name="cat_id[]" value="" placeholder="<?php _e('auto', 'inpark-reserva'); ?>" readonly style="width: 100%;"></td>
                            <td>
                                <input type="text" name="cat_nome[]" value="" placeholder="<?php _e('Novo item', 'inpark-reserva'); ?>" style="width: 100%;">
                                <input type="hidden" name="cat_categoria[]" value="alimentacao">
                            </td>
                            <td><input type="text" name="cat_unidade[]" value="" style="width: 100%;"></td>
                            <td><input type="number" step="0.01" name="cat_custo[]" value="" style="width: 100%;"></td>
                            <td><input type="number" step="0.01" name="cat_pvp[]" value="" style="width: 100%;"></td>
                        </tr>
                    </tbody>
                </table>

                <br>

                <h2><?php _e('Bebidas', 'inpark-reserva'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width: 60px;"><?php _e('ID', 'inpark-reserva'); ?></th>
                            <th><?php _e('Nome', 'inpark-reserva'); ?></th>
                            <th style="width: 120px;"><?php _e('Unidade', 'inpark-reserva'); ?></th>
                            <th style="width: 120px;"><?php _e('Custo Fornecedor (€)', 'inpark-reserva'); ?></th>
                            <th style="width: 120px;"><?php _e('PVP (€)', 'inpark-reserva'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bebidas)) : foreach ($bebidas as $i => $it): ?>
                        <tr>
                            <td><input type="number" name="cat_id[]" value="<?php echo esc_attr($it['id']); ?>" readonly style="width: 100%;"></td>
                            <td>
                                <input type="text" name="cat_nome[]" value="<?php echo esc_attr($it['nome']); ?>" style="width: 100%;">
                                <input type="hidden" name="cat_categoria[]" value="bebidas">
                            </td>
                            <td><input type="text" name="cat_unidade[]" value="<?php echo esc_attr($it['unidade']); ?>" style="width: 100%;"></td>
                            <td><input type="number" step="0.01" name="cat_custo[]" value="<?php echo esc_attr($it['custo']); ?>" style="width: 100%;"></td>
                            <td><input type="number" step="0.01" name="cat_pvp[]" value="<?php echo esc_attr($it['pvp']); ?>" style="width: 100%;"></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        <tr style="background-color: #f0f0f1;">
                            <td><input type="number" name="cat_id[]" value="" placeholder="<?php _e('auto', 'inpark-reserva'); ?>" readonly style="width: 100%;"></td>
                            <td>
                                <input type="text" name="cat_nome[]" value="" placeholder="<?php _e('Novo item', 'inpark-reserva'); ?>" style="width: 100%;">
                                <input type="hidden" name="cat_categoria[]" value="bebidas">
                            </td>
                            <td><input type="text" name="cat_unidade[]" value="" style="width: 100%;"></td>
                            <td><input type="number" step="0.01" name="cat_custo[]" value="" style="width: 100%;"></td>
                            <td><input type="number" step="0.01" name="cat_pvp[]" value="" style="width: 100%;"></td>
                        </tr>
                    </tbody>
                </table>

                <br>
                <?php submit_button(__('Guardar Catering', 'inpark-reserva')); ?>
            </form>
        </div>
        <?php
    }

    public function render_conditional_fields(){
        if (!current_user_can('manage_options')) return;
        $fields = get_option('inpark_conditional_fields', array());

        // Handle save
        if (isset($_POST['inpark_conditional_nonce']) && wp_verify_nonce($_POST['inpark_conditional_nonce'], 'inpark_conditional_save')){
            $new_fields = array();
            
            if (!empty($_POST['cf_label'])){
                foreach ($_POST['cf_label'] as $i => $label){
                    $label = sanitize_text_field($label);
                    if (empty($label)) continue;

                    $field_id = isset($_POST['cf_id'][$i]) && !empty($_POST['cf_id'][$i]) 
                        ? sanitize_key($_POST['cf_id'][$i])
                        : 'field_' . time() . '_' . $i;

                    $options = array();
                    if (!empty($_POST['cf_options'][$i])) {
                        $opts = explode("\n", $_POST['cf_options'][$i]);
                        foreach ($opts as $opt) {
                            $opt = trim($opt);
                            if (!empty($opt)) {
                                $options[] = sanitize_text_field($opt);
                            }
                        }
                    }

                    $new_fields[] = array(
                        'id' => $field_id,
                        'label' => $label,
                        'type' => sanitize_text_field($_POST['cf_type'][$i] ?? 'text'),
                        'required' => isset($_POST['cf_required'][$i]) ? true : false,
                        'show_when_field' => sanitize_text_field($_POST['cf_show_when_field'][$i] ?? ''),
                        'show_when_value' => sanitize_text_field($_POST['cf_show_when_value'][$i] ?? ''),
                        'options' => $options,
                    );
                }
            }
            
            update_option('inpark_conditional_fields', $new_fields);
            $fields = $new_fields;
            echo '<div class="updated notice"><p>'.__('Campos condicionais atualizados.', 'inpark-reserva').'</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Campos Condicionais', 'inpark-reserva'); ?></h1>
            <p><?php _e('Configure campos personalizados que aparecem conforme as escolhas do utilizador.', 'inpark-reserva'); ?></p>

            <form method="post" id="conditional-fields-form">
                <?php wp_nonce_field('inpark_conditional_save', 'inpark_conditional_nonce'); ?>
                
                <div class="inpark-fields-container">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th><?php _e('Label do Campo', 'inpark-reserva'); ?></th>
                                <th style="width: 120px;"><?php _e('Tipo', 'inpark-reserva'); ?></th>
                                <th style="width: 80px;"><?php _e('Obrigatório', 'inpark-reserva'); ?></th>
                                <th><?php _e('Mostrar quando', 'inpark-reserva'); ?></th>
                                <th><?php _e('Opções (1 por linha)', 'inpark-reserva'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="conditional-fields-tbody">
                            <?php if (!empty($fields)) : foreach ($fields as $idx => $field): ?>
                            <tr class="conditional-field-row">
                                <td><?php echo $idx + 1; ?></td>
                                <td>
                                    <input type="hidden" name="cf_id[]" value="<?php echo esc_attr($field['id']); ?>">
                                    <input type="text" name="cf_label[]" value="<?php echo esc_attr($field['label']); ?>" placeholder="Ex: Número de convidados" style="width: 100%;">
                                </td>
                                <td>
                                    <select name="cf_type[]" style="width: 100%;" class="cf-type-select">
                                        <option value="text" <?php selected($field['type'], 'text'); ?>>Texto</option>
                                        <option value="textarea" <?php selected($field['type'], 'textarea'); ?>>Textarea</option>
                                        <option value="number" <?php selected($field['type'], 'number'); ?>>Número</option>
                                        <option value="select" <?php selected($field['type'], 'select'); ?>>Select</option>
                                        <option value="radio" <?php selected($field['type'], 'radio'); ?>>Radio</option>
                                        <option value="checkbox" <?php selected($field['type'], 'checkbox'); ?>>Checkbox</option>
                                    </select>
                                </td>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="cf_required[<?php echo $idx; ?>]" <?php checked($field['required'], true); ?>>
                                </td>
                                <td>
                                    <select name="cf_show_when_field[]" style="width: 48%; display: inline-block;">
                                        <option value="">Sempre</option>
                                        <option value="tipo" <?php selected($field['show_when_field'], 'tipo'); ?>>Tipo de Aluguer</option>
                                        <option value="periodo" <?php selected($field['show_when_field'], 'periodo'); ?>>Período</option>
                                        <option value="tem_horas_extra" <?php selected($field['show_when_field'], 'tem_horas_extra'); ?>>Horas Extra</option>
                                    </select>
                                    <input type="text" name="cf_show_when_value[]" value="<?php echo esc_attr($field['show_when_value']); ?>" placeholder="Valor" style="width: 48%; display: inline-block;">
                                </td>
                                <td>
                                    <textarea name="cf_options[]" rows="3" style="width: 100%;" placeholder="Opção 1&#10;Opção 2&#10;Opção 3"><?php echo esc_textarea(implode("\n", $field['options'])); ?></textarea>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>

                    <p>
                        <button type="button" class="button" id="add-conditional-field">
                            <?php _e('+ Adicionar Campo', 'inpark-reserva'); ?>
                        </button>
                    </p>
                </div>

                <?php submit_button(__('Guardar Campos', 'inpark-reserva')); ?>
            </form>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h3><?php _e('Como usar:', 'inpark-reserva'); ?></h3>
                <ul>
                    <li><strong>Label:</strong> Texto que aparece no formulário</li>
                    <li><strong>Tipo:</strong> Tipo de campo (texto, select, checkbox, etc.)</li>
                    <li><strong>Obrigatório:</strong> Se marcado, o campo é obrigatório</li>
                    <li><strong>Mostrar quando:</strong> Define quando o campo aparece:
                        <ul>
                            <li><em>Sempre</em>: Campo sempre visível</li>
                            <li><em>Tipo de Aluguer = meio_dia</em>: Só aparece quando "Meio dia" selecionado</li>
                            <li><em>Tipo de Aluguer = dia_inteiro</em>: Só aparece quando "Dia inteiro" selecionado</li>
                            <li><em>Horas Extra = checked</em>: Só aparece quando checkbox "Horas Extra" marcada</li>
                        </ul>
                    </li>
                    <li><strong>Opções:</strong> Para Select/Radio, uma opção por linha</li>
                </ul>
                <p><strong>Nota:</strong> Os campos condicionais aparecem automaticamente no formulário e nos emails com o placeholder <code>{campos_extras}</code></p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($){
            let fieldCounter = <?php echo count($fields); ?>;
            
            $('#add-conditional-field').on('click', function(){
                fieldCounter++;
                const row = `
                    <tr class="conditional-field-row">
                        <td>${fieldCounter}</td>
                        <td>
                            <input type="hidden" name="cf_id[]" value="">
                            <input type="text" name="cf_label[]" value="" placeholder="Ex: Número de convidados" style="width: 100%;">
                        </td>
                        <td>
                            <select name="cf_type[]" style="width: 100%;" class="cf-type-select">
                                <option value="text">Texto</option>
                                <option value="textarea">Textarea</option>
                                <option value="number">Número</option>
                                <option value="select">Select</option>
                                <option value="radio">Radio</option>
                                <option value="checkbox">Checkbox</option>
                            </select>
                        </td>
                        <td style="text-align: center;">
                            <input type="checkbox" name="cf_required[${fieldCounter-1}]">
                        </td>
                        <td>
                            <select name="cf_show_when_field[]" style="width: 48%; display: inline-block;">
                                <option value="">Sempre</option>
                                <option value="tipo">Tipo de Aluguer</option>
                                <option value="periodo">Período</option>
                                <option value="tem_horas_extra">Horas Extra</option>
                            </select>
                            <input type="text" name="cf_show_when_value[]" value="" placeholder="Valor" style="width: 48%; display: inline-block;">
                        </td>
                        <td>
                            <textarea name="cf_options[]" rows="3" style="width: 100%;" placeholder="Opção 1&#10;Opção 2&#10;Opção 3"></textarea>
                        </td>
                    </tr>
                `;
                $('#conditional-fields-tbody').append(row);
            });
        });
        </script>
        <?php
    }
}
new Inpark_Admin();
