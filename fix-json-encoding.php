<?php
/**
 * Script para corrigir encoding JSON nas categorias
 *
 * Reimporta os dados existentes com JSON_UNESCAPED_UNICODE
 * para que os filtros por categoria funcionem corretamente
 *
 * COMO USAR:
 * 1. Coloque na raiz do WordPress
 * 2. Acesse: http://seu-site.com/fix-json-encoding.php
 * 3. DELETE o arquivo após executar
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

if (!current_user_can('manage_options')) {
    die('Acesso negado. Faça login como administrador primeiro.');
}

global $wpdb;
$table_name = $wpdb->prefix . 'oportunidades';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Corrigir Encoding JSON</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        h1 { color: #333; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f9f9f9; padding: 10px; border-left: 3px solid #0073aa; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Corrigir Encoding JSON - Categorias e Filtros</h1>

    <?php
    if (!isset($_GET['confirm'])) {
        echo '<div class="section">';
        echo '<h2>⚠️ Confirmação Necessária</h2>';
        echo '<p>Este script vai reprocessar todos os registros da tabela <code>' . $table_name . '</code> para corrigir o encoding JSON.</p>';
        echo '<p><strong>O que será feito:</strong></p>';
        echo '<ul>';
        echo '<li>Ler todos os registros existentes</li>';
        echo '<li>Converter campos <code>categories</code>, <code>filters</code> e <code>custom_fields</code> de Unicode escapado para caracteres normais</li>';
        echo '<li>Exemplo: <code>["Reabilita\\u00e7\\u00e3o"]</code> → <code>["Reabilitação"]</code></li>';
        echo '<li>Atualizar os registros na base de dados</li>';
        echo '</ul>';
        echo '<p><strong>Isto é seguro?</strong> Sim, apenas muda a forma como os dados JSON estão armazenados.</p>';
        echo '<p><a href="?confirm=yes" style="display: inline-block; background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; font-weight: bold;">✅ Confirmar e Executar</a></p>';
        echo '</div>';
    } else {
        echo '<div class="section">';
        echo '<h2>📊 Processando...</h2>';

        // Buscar todos os registros
        $records = $wpdb->get_results("SELECT id, categories, filters, custom_fields FROM $table_name", ARRAY_A);

        if (empty($records)) {
            echo '<p class="error">❌ Nenhum registro encontrado na tabela.</p>';
        } else {
            $total = count($records);
            $updated = 0;
            $errors = 0;

            echo '<p class="info">Total de registros: ' . $total . '</p>';

            foreach ($records as $record) {
                try {
                    // Decodificar e recodificar com JSON_UNESCAPED_UNICODE
                    $categories = null;
                    $filters = null;
                    $custom_fields = null;

                    if (!empty($record['categories'])) {
                        $decoded = json_decode($record['categories'], true);
                        $categories = wp_json_encode($decoded, JSON_UNESCAPED_UNICODE);
                    }

                    if (!empty($record['filters'])) {
                        $decoded = json_decode($record['filters'], true);
                        $filters = wp_json_encode($decoded, JSON_UNESCAPED_UNICODE);
                    }

                    if (!empty($record['custom_fields'])) {
                        $decoded = json_decode($record['custom_fields'], true);
                        $custom_fields = wp_json_encode($decoded, JSON_UNESCAPED_UNICODE);
                    }

                    // Atualizar o registro
                    $result = $wpdb->update(
                        $table_name,
                        [
                            'categories' => $categories,
                            'filters' => $filters,
                            'custom_fields' => $custom_fields,
                        ],
                        ['id' => $record['id']],
                        ['%s', '%s', '%s'],
                        ['%d']
                    );

                    if ($result !== false) {
                        $updated++;
                        echo '<p class="success">✅ Registro ID ' . $record['id'] . ' atualizado</p>';
                    } else {
                        echo '<p class="error">⚠️ Registro ID ' . $record['id'] . ' não precisou de atualização</p>';
                    }
                } catch (Exception $e) {
                    $errors++;
                    echo '<p class="error">❌ Erro ao processar ID ' . $record['id'] . ': ' . $e->getMessage() . '</p>';
                }
            }

            echo '<hr>';
            echo '<h2>📈 Resumo</h2>';
            echo '<p><strong>Total de registros:</strong> ' . $total . '</p>';
            echo '<p class="success"><strong>Atualizados:</strong> ' . $updated . '</p>';
            if ($errors > 0) {
                echo '<p class="error"><strong>Erros:</strong> ' . $errors . '</p>';
            }

            // Testar filtro
            echo '<hr>';
            echo '<h2>🧪 Teste do Filtro</h2>';

            // Buscar com filtro de categoria
            $test_query = $wpdb->prepare(
                "SELECT id, title, categories FROM $table_name WHERE categories LIKE %s",
                '%"Reabilitação"%'
            );
            $test_results = $wpdb->get_results($test_query, ARRAY_A);

            if ($test_results) {
                echo '<p class="success">✅ Filtro por categoria "Reabilitação" agora funciona!</p>';
                echo '<p>Registros encontrados: ' . count($test_results) . '</p>';
                foreach ($test_results as $row) {
                    echo '<p>• ID ' . $row['id'] . ': ' . esc_html($row['title']) . '</p>';
                }
            } else {
                echo '<p class="error">❌ Ainda não funcionou. Pode ser necessário investigar mais.</p>';
            }
        }

        echo '</div>';

        echo '<div class="section">';
        echo '<h2>✅ Concluído!</h2>';
        echo '<p>Agora teste o shortcode com filtro:</p>';
        echo '<pre>[oportunidades categoria="Reabilitação"]</pre>';
        echo '<p><strong>⚠️ IMPORTANTE:</strong> DELETE este arquivo agora!</p>';
        echo '<pre>rm fix-json-encoding.php</pre>';
        echo '</div>';
    }
    ?>

</body>
</html>
