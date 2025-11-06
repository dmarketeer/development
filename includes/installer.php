<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function inpark_reserva_activate(){
    // Default settings
    if (!get_option('inpark_reserva_settings')){
        add_option('inpark_reserva_settings', array(
            'preco_meio_dia' => 220,
            'hora_extra_meio' => 49.50,
            'preco_dia_inteiro' => 280,
            'hora_extra_inteiro' => 63.00,
            'taxa_limpeza' => 40,
            'admin_email' => get_option('admin_email'),
            'user_subject' => 'Confirmação de reserva #{reserva_id}',
            'user_body' => "Olá {nome},\n\nObrigado pela sua reserva em {data} ({periodo}).\nTipo: {tipo}\nHoras extra: {horas_extra}\nCatering:\n{catering}\n\nTotal: {total}\n\nAté breve.",
            'admin_subject' => 'Nova reserva #{reserva_id}',
            'admin_body' => "Nova reserva:\nNome: {nome}\nEmail: {email}\nTelefone: {telefone}\nData: {data}\nPeríodo: {periodo}\nTipo: {tipo}\nHoras extra: {horas_extra}\nCatering:\n{catering}\nTotal: {total}",
        ));
    }
    if (!get_option('inpark_catering_items')){
        add_option('inpark_catering_items', array());
    }
    if (!get_option('inpark_conditional_fields')){
        add_option('inpark_conditional_fields', array());
    }
}
register_activation_hook(INPARK_RESERVA_PATH . 'inpark-eventos-reserva.php', 'inpark_reserva_activate');
