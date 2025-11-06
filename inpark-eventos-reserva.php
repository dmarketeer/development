<?php
/**
 * Plugin Name: InPark - Eventos Reserva PRO
 * Description: Plugin para aluguer de espaço com catering e cálculo automático. Shortcode: [inpark_reserva_form]
 * Version: 1.0.6
 * Author: Mário Karim
 * Text Domain: inpark-reserva
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define('INPARK_RESERVA_VERSION', '1.0.6');
define('INPARK_RESERVA_PATH', plugin_dir_path(__FILE__));
define('INPARK_RESERVA_URL', plugin_dir_url(__FILE__));

// Includes
require_once INPARK_RESERVA_PATH . 'includes/installer.php';
require_once INPARK_RESERVA_PATH . 'admin/class-inpark-admin.php';
require_once INPARK_RESERVA_PATH . 'public/class-inpark-shortcode.php';

/**
 * Register CPT Reservas
 */
function inpark_register_cpt_reserva() {
    $labels = array(
        'name' => __('Reservas', 'inpark-reserva'),
        'singular_name' => __('Reserva', 'inpark-reserva'),
    );
    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'inpark_reserva',
        'supports' => array('title'),
    );
    register_post_type('inpark_reserva', $args);
}
add_action('init', 'inpark_register_cpt_reserva');

/**
 * Enqueue public scripts
 */
function inpark_reserva_enqueue() {
    wp_enqueue_script('inpark-reserva-js', INPARK_RESERVA_URL . 'public/js/inpark-reserva.js', array('jquery'), INPARK_RESERVA_VERSION, true);
    $opts = get_option('inpark_reserva_settings', array());
    
    $localized = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'precos'  => array(
            'exclusivo' => floatval($opts['preco_exclusivo'] ?? 300),
            'parcial' => floatval($opts['preco_parcial'] ?? 300),
        ),
        'thank_you_url' => $opts['thank_you_url'] ?? '/muito-obrigado/',
        'i18n' => array(
            'required' => __('Campo obrigatório.', 'inpark-reserva'),
            'ok' => __('Reserva enviada com sucesso!', 'inpark-reserva'),
            'fail' => __('Falha ao enviar. Tente novamente.', 'inpark-reserva'),
        ),
    );
    wp_localize_script('inpark-reserva-js', 'InparkReserva', $localized);
    wp_enqueue_style('inpark-reserva-css', INPARK_RESERVA_URL . 'public/style.css', array(), INPARK_RESERVA_VERSION);
}
add_action('wp_enqueue_scripts', 'inpark_reserva_enqueue');

/**
 * Handle submission
 */
function inpark_reserva_submit() {
    check_ajax_referer('inpark_reserva_nonce', 'nonce');

    $nome  = sanitize_text_field($_POST['nome'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $telefone = sanitize_text_field($_POST['telefone'] ?? '');
    $data  = sanitize_text_field($_POST['data'] ?? '');
    $tipo  = sanitize_text_field($_POST['tipo'] ?? '');
    $periodo = sanitize_text_field($_POST['periodo'] ?? '');
    
    // Horas extra só se checkbox marcada
    $tem_horas_extra = isset($_POST['tem_horas_extra']) ? true : false;
    $horas_extra = $tem_horas_extra ? floatval($_POST['horas_extra'] ?? 0) : 0;
    
    $total = floatval($_POST['total'] ?? 0);

    // 🗓️ Formatar data no estilo português
    if (!empty($data)) {
        setlocale(LC_TIME, 'pt_PT.UTF-8', 'pt_PT', 'Portuguese_Portugal');
        $date_obj = date_create_from_format('Y-m-d', $data);
        if ($date_obj) {
            $formatted_date = strftime('%A, %d de %B de %Y', $date_obj->getTimestamp());
            $formatted_date = ucfirst($formatted_date);
            $data = $formatted_date;
        }
    }

    // Catering só se checkbox principal marcada
    $catering = array();
    $tem_catering = isset($_POST['tem_catering']) ? true : false;
    if ($tem_catering && !empty($_POST['catering'])) {
        foreach ($_POST['catering'] as $idx => $item) {
            $qtd = isset($_POST['catering_qtd'][$idx]) ? intval($_POST['catering_qtd'][$idx]) : 0;
            $catering[] = array(
                'id' => intval($item),
                'qtd' => $qtd,
            );
        }
    }

    // Faturação só se checkbox marcada
    $faturacao = array();
    $pretende_fatura = isset($_POST['pretende_fatura']) ? true : false;
    if ($pretende_fatura) {
        $faturacao = array(
            'nome' => sanitize_text_field($_POST['fatura_nome'] ?? ''),
            'morada' => sanitize_textarea_field($_POST['fatura_morada'] ?? ''),
            'nif' => sanitize_text_field($_POST['fatura_nif'] ?? ''),
        );
    }

    // Processar campos condicionais
    $conditional_data = array();
    $conditional_fields = get_option('inpark_conditional_fields', array());
    foreach ($conditional_fields as $field) {
        $field_name = 'cf_' . sanitize_key($field['id']);
        if (isset($_POST[$field_name])) {
            $value = '';
            if ($field['type'] === 'checkbox') {
                $value = isset($_POST[$field_name]) ? 'Sim' : 'Não';
            } elseif ($field['type'] === 'select' || $field['type'] === 'radio') {
                $value = sanitize_text_field($_POST[$field_name]);
            } else {
                $value = sanitize_textarea_field($_POST[$field_name]);
            }
            $conditional_data[$field['label']] = $value;
        }
    }

    if (empty($nome) || empty($email) || empty($telefone) || empty($data) || empty($tipo) || empty($periodo)) {
        wp_send_json_error(array('message' => __('Preencha todos os campos obrigatórios.', 'inpark-reserva')));
    }

    // Validar dados de faturação se checkbox marcada
    if ($pretende_fatura) {
        if (empty($faturacao['nome']) || empty($faturacao['morada']) || empty($faturacao['nif'])) {
            wp_send_json_error(array('message' => __('Preencha todos os dados de faturação.', 'inpark-reserva')));
        }
    }

    // Create reserva post
    $post_id = wp_insert_post(array(
        'post_type' => 'inpark_reserva',
        'post_title' => sprintf('%s – %s (%s)', $nome, $data, $periodo),
        'post_status' => 'publish',
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('message' => __('Erro ao gravar reserva.', 'inpark-reserva')));
    }

    update_post_meta($post_id, '_inpark_reserva_meta', array(
        'nome' => $nome,
        'email' => $email,
        'telefone' => $telefone,
        'data' => $data,
        'tipo' => $tipo,
        'periodo' => $periodo,
        'tem_horas_extra' => $tem_horas_extra,
        'horas_extra' => $horas_extra,
        'tem_catering' => $tem_catering,
        'total' => $total,
        'catering' => $catering,
        'pretende_fatura' => $pretende_fatura,
        'faturacao' => $faturacao,
        'conditional_fields' => $conditional_data,
    ));

    // Send emails
    $opts = get_option('inpark_reserva_settings', array());
    $admin_email = !empty($opts['admin_email']) ? $opts['admin_email'] : get_option('admin_email');

    $placeholders = array(
        '{nome}' => $nome,
        '{email}' => $email,
        '{telefone}' => $telefone,
        '{data}' => $data,
        '{tipo}' => $tipo,
        '{periodo}' => $periodo,
        '{horas_extra}' => $tem_horas_extra ? number_format_i18n($horas_extra, 2) : 'Não',
        '{total}' => number_format_i18n($total, 2) . ' €',
        '{catering}' => $tem_catering ? inpark_format_catering_for_email($catering) : 'Sem catering',
        '{faturacao}' => $pretende_fatura ? inpark_format_faturacao_for_email($faturacao) : 'Sem fatura',
        '{campos_extras}' => inpark_format_conditional_fields_for_email($conditional_data),
        '{reserva_id}' => $post_id,
    );

    $user_subject = $opts['user_subject'] ?? 'Confirmação de reserva #{reserva_id}';
    $user_body    = $opts['user_body'] ?? "Olá {nome},\n\nObrigado pela sua reserva em {data} ({periodo}).\nTipo: {tipo}\nHoras extra: {horas_extra}\nCatering:\n{catering}\n\nTotal: {total}\n\nAté breve.";
    $admin_subject = $opts['admin_subject'] ?? 'Nova reserva #{reserva_id}';
    $admin_body    = $opts['admin_body'] ?? "Nova reserva:\nNome: {nome}\nEmail: {email}\nTelefone: {telefone}\nData: {data}\nPeríodo: {periodo}\nTipo: {tipo}\nHoras extra: {horas_extra}\nCatering:\n{catering}\nTotal: {total}";

    foreach ($placeholders as $k => $v) {
        $user_subject = str_replace($k, $v, $user_subject);
        $user_body    = str_replace($k, $v, $user_body);
        $admin_subject = str_replace($k, $v, $admin_subject);
        $admin_body    = str_replace($k, $v, $admin_body);
    }

    wp_mail($email, $user_subject, $user_body);
    wp_mail($admin_email, $admin_subject, $admin_body);

    wp_send_json_success(array('message' => __('Reserva enviada com sucesso.', 'inpark-reserva')));
}
add_action('wp_ajax_inpark_reserva_submit', 'inpark_reserva_submit');
add_action('wp_ajax_nopriv_inpark_reserva_submit', 'inpark_reserva_submit');

function inpark_format_catering_for_email($items){
    if (empty($items)) {
        return '';
    }
    
    $catalog = get_option('inpark_catering_items', array());
    $lines = array();
    
    $alimentacao = array();
    $bebidas = array();
    
    foreach ($items as $entry){
        foreach ($catalog as $idx => $it){
            if (intval($it['id']) === intval($entry['id'])){
                $q = intval($entry['qtd']);
                $pvp = floatval($it['pvp']);
                $linha = sprintf('- %s x %d = %s €', $it['nome'], $q, number_format_i18n($pvp*$q, 2));
                
                $categoria = $it['categoria'] ?? 'alimentacao';
                if ($categoria === 'bebidas') {
                    $bebidas[] = $linha;
                } else {
                    $alimentacao[] = $linha;
                }
            }
        }
    }
    
    if (!empty($alimentacao)) {
        $lines[] = "\nAlimentação:";
        $lines = array_merge($lines, $alimentacao);
    }
    
    if (!empty($bebidas)) {
        $lines[] = "\nBebidas:";
        $lines = array_merge($lines, $bebidas);
    }
    
    return implode("\n", $lines);
}

function inpark_format_faturacao_for_email($faturacao) {
    if (empty($faturacao)) {
        return '';
    }
    
    $lines = array(
        "\nDados de Faturação:",
        sprintf('- Nome: %s', $faturacao['nome']),
        sprintf('- Morada: %s', $faturacao['morada']),
        sprintf('- NIF: %s', $faturacao['nif'])
    );
    
    return implode("\n", $lines);
}

function inpark_format_conditional_fields_for_email($data) {
    if (empty($data)) {
        return '';
    }
    
    $lines = array("\nInformações Adicionais:");
    foreach ($data as $label => $value) {
        $lines[] = sprintf('- %s: %s', $label, $value);
    }
    
    return implode("\n", $lines);
}
