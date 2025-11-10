<?php
/**
 * Teste Rápido - Shortcode Oportunidades
 *
 * Coloque na raiz do WordPress e acesse: http://seu-site.com/teste-shortcode.php
 * DELETE após testar!
 */

// Tentar encontrar e carregar WordPress
$wp_load_paths = [
    __DIR__ . '/wp-load.php',
    __DIR__ . '/../wp-load.php',
    __DIR__ . '/../../wp-load.php',
    __DIR__ . '/../../../wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Erro: wp-load.php não encontrado');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste Shortcode Oportunidades</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        h1 { color: #333; }
        h2 { color: #0073aa; }
        pre { background: #f9f9f9; padding: 10px; border-left: 3px solid #0073aa; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #0073aa; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Teste do Shortcode Oportunidades</h1>

    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'oportunidades';

    // Teste 1: Verificar se a tabela existe
    echo '<div class="section">';
    echo '<h2>1. Verificar Tabela</h2>';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    if ($table_exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo '<p class="success">✅ Tabela existe: ' . $table_name . '</p>';
        echo '<p><strong>Total de registros:</strong> ' . $count . '</p>';
    } else {
        echo '<p class="error">❌ Tabela não existe!</p>';
    }
    echo '</div>';

    // Teste 2: Query direta dos dados
    echo '<div class="section">';
    echo '<h2>2. Query Direta (SQL)</h2>';
    $direct_records = $wpdb->get_results("SELECT id, title, awarding_entity, value_normalized, deadline_date, categories FROM $table_name LIMIT 5", ARRAY_A);

    if ($direct_records) {
        echo '<p class="success">✅ Query direta retornou ' . count($direct_records) . ' registros</p>';
        echo '<table>';
        echo '<tr><th>ID</th><th>Título</th><th>Entidade</th><th>Valor</th><th>Prazo</th><th>Categorias</th></tr>';
        foreach ($direct_records as $row) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . esc_html($row['title']) . '</td>';
            echo '<td>' . esc_html($row['awarding_entity']) . '</td>';
            echo '<td>' . number_format($row['value_normalized'], 2) . '</td>';
            echo '<td>' . $row['deadline_date'] . '</td>';
            echo '<td>' . esc_html($row['categories']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="error">❌ Query não retornou registros</p>';
    }
    echo '</div>';

    // Teste 3: Usar a classe Database do plugin
    echo '<div class="section">';
    echo '<h2>3. Classe Database do Plugin</h2>';

    if (class_exists('\Oportunidades\Includes\Database')) {
        $database = new \Oportunidades\Includes\Database();
        $records = $database->query_records(['per_page' => 5]);

        echo '<p class="success">✅ Classe Database encontrada</p>';
        echo '<p><strong>Registros retornados:</strong> ' . count($records) . '</p>';

        if (!empty($records)) {
            echo '<h3>Dados retornados pela classe:</h3>';
            echo '<pre>' . print_r($records, true) . '</pre>';
        } else {
            echo '<p class="error">❌ Classe retornou array vazio</p>';
        }
    } else {
        echo '<p class="error">❌ Classe \Oportunidades\Includes\Database não encontrada</p>';
    }
    echo '</div>';

    // Teste 4: Executar o shortcode
    echo '<div class="section">';
    echo '<h2>4. Executar Shortcode [oportunidades]</h2>';

    if (shortcode_exists('oportunidades')) {
        echo '<p class="success">✅ Shortcode "oportunidades" está registrado</p>';

        echo '<h3>Resultado do Shortcode:</h3>';
        echo '<div style="border: 2px solid #0073aa; padding: 15px; background: white;">';
        echo do_shortcode('[oportunidades]');
        echo '</div>';
    } else {
        echo '<p class="error">❌ Shortcode "oportunidades" não está registrado</p>';
    }
    echo '</div>';

    // Teste 5: Executar shortcode com filtros
    echo '<div class="section">';
    echo '<h2>5. Executar Shortcode com Filtros</h2>';

    if (shortcode_exists('oportunidades')) {
        echo '<h3>Com categoria "Reabilitação":</h3>';
        echo '<div style="border: 2px solid #0073aa; padding: 15px; background: white;">';
        echo do_shortcode('[oportunidades categoria="Reabilitação"]');
        echo '</div>';

        echo '<h3>Com limite de 2:</h3>';
        echo '<div style="border: 2px solid #0073aa; padding: 15px; background: white;">';
        echo do_shortcode('[oportunidades limite="2"]');
        echo '</div>';
    }
    echo '</div>';

    // Teste 6: Verificar estrutura da tabela
    echo '<div class="section">';
    echo '<h2>6. Estrutura da Tabela</h2>';
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name", ARRAY_A);
    echo '<table>';
    echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Default</th></tr>';
    foreach ($columns as $column) {
        echo '<tr>';
        echo '<td>' . esc_html($column['Field']) . '</td>';
        echo '<td>' . esc_html($column['Type']) . '</td>';
        echo '<td>' . esc_html($column['Null']) . '</td>';
        echo '<td>' . esc_html($column['Key']) . '</td>';
        echo '<td>' . esc_html($column['Default']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
    ?>

    <div class="section">
        <p><strong>⚠️ IMPORTANTE:</strong> Delete este arquivo após o teste!</p>
        <pre>rm teste-shortcode.php</pre>
    </div>

</body>
</html>
