<?php
/**
 * Debug de Importação - Descobrir por que registros não são salvos
 *
 * COMO USAR:
 * 1. Coloque na raiz do WordPress
 * 2. Acesse: http://seu-site.com/debug-importacao.php
 * 3. DELETE após usar
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

if (!defined('OPORTUNIDADES_TABLE_NAME')) {
    define('OPORTUNIDADES_TABLE_NAME', $wpdb->prefix . 'oportunidades');
}
if (!defined('OPORTUNIDADES_OPTION_NAME')) {
    define('OPORTUNIDADES_OPTION_NAME', 'oportunidades_settings');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug de Importação</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        h1 { color: #333; }
        h2 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f9f9f9; padding: 10px; border-left: 3px solid #0073aa; overflow-x: auto; font-size: 12px; }
        .debug-item { margin: 10px 0; padding: 10px; background: #f0f0f0; border-left: 3px solid #ccc; }
    </style>
</head>
<body>
    <h1>🔍 Debug de Importação</h1>

    <?php
    // Teste 1: Verificar última importação
    echo '<div class="section">';
    echo '<h2>1. Informações da Última Importação</h2>';

    $last_ingestion = get_option('oportunidades_last_ingestion');
    $last_summary = get_transient('oportunidades_last_summary');
    $last_errors = get_transient('oportunidades_last_errors');

    echo '<p><strong>Última ingestão:</strong> ' . ($last_ingestion ?: 'Nunca') . '</p>';

    if ($last_summary) {
        echo '<h3>Resumo da Última Importação:</h3>';
        echo '<pre>' . print_r($last_summary, true) . '</pre>';
    }

    if ($last_errors) {
        echo '<h3 class="error">Erros da Última Importação:</h3>';
        echo '<pre>' . print_r($last_errors, true) . '</pre>';
    }

    echo '</div>';

    // Teste 2: Simular importação de 1 registro
    echo '<div class="section">';
    echo '<h2>2. Teste de Importação (1 Registro de Exemplo)</h2>';

    $test_payload = [
        'schema_version' => '1.0',
        'oportunidades' => [
            [
                'titulo' => 'TESTE DEBUG - ' . date('Y-m-d H:i:s'),
                'resumo' => 'Registro de teste para debug',
                'entidade_adjudicante' => 'Entidade Teste',
                'valor_normalizado' => 100000,
                'prazo' => '2025-12-31',
                'url' => 'https://example.com/teste',
                'categorias' => ['Teste'],
                'filtros' => ['Debug'],
            ]
        ]
    ];

    try {
        if (class_exists('\Oportunidades\Includes\Database') && class_exists('\Oportunidades\Includes\Importer')) {
            $database = new \Oportunidades\Includes\Database();
            $importer = new \Oportunidades\Includes\Importer($database);

            echo '<p class="success">✅ Classes encontradas</p>';

            echo '<h3>Payload de Teste:</h3>';
            echo '<pre>' . print_r($test_payload, true) . '</pre>';

            // Ativar debug do WordPress
            $wpdb->show_errors();

            echo '<h3>Executando importação...</h3>';
            $result = $importer->import($test_payload, 'debug');

            echo '<h3 class="success">Resultado:</h3>';
            echo '<pre>' . print_r($result, true) . '</pre>';

            // Verificar se foi inserido
            $count = $wpdb->get_var("SELECT COUNT(*) FROM " . OPORTUNIDADES_TABLE_NAME . " WHERE title LIKE 'TESTE DEBUG%'");
            echo '<p><strong>Registros de teste no DB:</strong> ' . $count . '</p>';

            if ($result['inserted'] > 0) {
                echo '<p class="success">✅ Importação funcionou! O problema deve estar nos seus dados.</p>';
            } elseif ($result['updated'] > 0) {
                echo '<p class="warning">⚠️ Registro foi atualizado (já existia). Hash de deduplicação funcionando.</p>';
            } else {
                echo '<p class="error">❌ Nenhum registro inserido ou atualizado!</p>';
                if (!empty($result['errors'])) {
                    echo '<h3 class="error">Erros:</h3>';
                    foreach ($result['errors'] as $error) {
                        echo '<p class="error">• ' . esc_html($error) . '</p>';
                    }
                }
            }

            // Mostrar últimos erros do wpdb
            if ($wpdb->last_error) {
                echo '<h3 class="error">Erro do MySQL:</h3>';
                echo '<pre>' . $wpdb->last_error . '</pre>';
            }

        } else {
            echo '<p class="error">❌ Classes do plugin não encontradas</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Exceção: ' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }

    echo '</div>';

    // Teste 3: Verificar estrutura dos dados que falharam
    echo '<div class="section">';
    echo '<h2>3. Analisar Estrutura do Google Sheets</h2>';

    $settings = get_option(OPORTUNIDADES_OPTION_NAME, []);

    if (!empty($settings['sheets_spreadsheet_id']) && !empty($settings['sheets_api_key'])) {
        $spreadsheet_id = $settings['sheets_spreadsheet_id'];
        $range = $settings['sheets_range'] ?: 'Sheet1';
        $api_key = $settings['sheets_api_key'];

        $api_url = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s',
            $spreadsheet_id,
            urlencode($range),
            $api_key
        );

        echo '<p><strong>Buscando primeiras 5 linhas do Google Sheets...</strong></p>';

        $response = wp_remote_get($api_url, ['timeout' => 15]);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['values'])) {
                $rows = $data['values'];

                echo '<p class="success">✅ Retornou ' . count($rows) . ' linhas</p>';

                if (count($rows) > 0) {
                    echo '<h3>Cabeçalho (Linha 1):</h3>';
                    echo '<pre>' . print_r($rows[0], true) . '</pre>';

                    // Verificar se tem "titulo" ou "title"
                    $header = $rows[0];
                    $has_titulo = in_array('titulo', $header) || in_array('title', $header);

                    if (!$has_titulo) {
                        echo '<p class="error">❌ PROBLEMA ENCONTRADO: Falta coluna "titulo" ou "title" no cabeçalho!</p>';
                        echo '<p>O campo "titulo" é OBRIGATÓRIO. Sem ele, TODOS os registros falham.</p>';
                    } else {
                        echo '<p class="success">✅ Cabeçalho tem campo "titulo" ou "title"</p>';
                    }

                    if (count($rows) > 1) {
                        echo '<h3>Primeira Linha de Dados (Linha 2):</h3>';
                        echo '<pre>' . print_r($rows[1], true) . '</pre>';

                        // Combinar header com dados
                        if (count($rows[1]) >= count($header)) {
                            $combined = array_combine($header, array_slice($rows[1], 0, count($header)));
                            echo '<h3>Dados Combinados (como seria processado):</h3>';
                            echo '<pre>' . print_r($combined, true) . '</pre>';

                            // Verificar se titulo está vazio
                            $titulo_value = $combined['titulo'] ?? $combined['title'] ?? '';
                            if (empty($titulo_value)) {
                                echo '<p class="error">❌ PROBLEMA: Campo "titulo" está VAZIO na linha 2!</p>';
                                echo '<p>Todos os registros com título vazio são rejeitados.</p>';
                            } else {
                                echo '<p class="success">✅ Campo "titulo" tem valor: ' . esc_html($titulo_value) . '</p>';
                            }
                        } else {
                            echo '<p class="error">❌ PROBLEMA: Linha 2 tem menos colunas que o cabeçalho!</p>';
                            echo '<p>Cabeçalho: ' . count($header) . ' colunas</p>';
                            echo '<p>Linha 2: ' . count($rows[1]) . ' colunas</p>';
                        }
                    }
                }
            } else {
                echo '<p class="error">❌ Resposta não contém "values"</p>';
                echo '<pre>' . esc_html($body) . '</pre>';
            }
        } else {
            echo '<p class="error">❌ Erro ao buscar: ' . $response->get_error_message() . '</p>';
        }
    } else {
        echo '<p class="warning">⚠️ Google Sheets não configurado</p>';
    }

    echo '</div>';

    // Teste 4: Verificar registros atuais
    echo '<div class="section">';
    echo '<h2>4. Registros Atuais na Base de Dados</h2>';

    $total = $wpdb->get_var("SELECT COUNT(*) FROM " . OPORTUNIDADES_TABLE_NAME);
    echo '<p><strong>Total de registros:</strong> ' . $total . '</p>';

    if ($total > 0) {
        // Últimos 3 registros
        $recent = $wpdb->get_results(
            "SELECT id, title, origin, created_at FROM " . OPORTUNIDADES_TABLE_NAME . " ORDER BY id DESC LIMIT 3",
            ARRAY_A
        );

        echo '<h3>Últimos 3 Registros:</h3>';
        foreach ($recent as $row) {
            echo '<div class="debug-item">';
            echo '<strong>ID:</strong> ' . $row['id'] . '<br>';
            echo '<strong>Título:</strong> ' . esc_html($row['title']) . '<br>';
            echo '<strong>Origem:</strong> ' . $row['origin'] . '<br>';
            echo '<strong>Criado:</strong> ' . $row['created_at'];
            echo '</div>';
        }

        // Contar por origem
        $by_origin = $wpdb->get_results(
            "SELECT origin, COUNT(*) as count FROM " . OPORTUNIDADES_TABLE_NAME . " GROUP BY origin",
            ARRAY_A
        );

        echo '<h3>Registros por Origem:</h3>';
        foreach ($by_origin as $row) {
            echo '<p><strong>' . ($row['origin'] ?: '(null)') . ':</strong> ' . $row['count'] . ' registros</p>';
        }
    }

    echo '</div>';

    // Teste 5: Diagnóstico Rápido
    echo '<div class="section">';
    echo '<h2>5. Diagnóstico Rápido</h2>';

    $issues = [];

    // Verificar se range está correto
    if (!empty($settings['sheets_range'])) {
        $range_parts = explode('!', $settings['sheets_range']);
        if (count($range_parts) > 1) {
            $cells = $range_parts[1];
            if (preg_match('/^[A-Z]+1:[A-Z]+1$/', $cells)) {
                $issues[] = '❌ Range busca apenas 1 linha: <code>' . $settings['sheets_range'] . '</code>. Altere para algo como <code>Oportunidades!A1:J1000</code>';
            }
        }
    }

    if (empty($issues)) {
        echo '<p class="success">✅ Nenhum problema óbvio encontrado. Veja os detalhes acima para mais informações.</p>';
    } else {
        echo '<h3 class="error">Problemas Encontrados:</h3>';
        foreach ($issues as $issue) {
            echo '<p>' . $issue . '</p>';
        }
    }

    echo '</div>';
    ?>

    <div class="section">
        <h2>🔍 Próximos Passos</h2>
        <ol>
            <li>Analise os resultados acima</li>
            <li>Se o "Teste de Importação" funcionou, o problema está nos <strong>dados do Google Sheets</strong></li>
            <li>Verifique se o cabeçalho tem <code>titulo</code> ou <code>title</code></li>
            <li>Verifique se as linhas de dados não estão vazias</li>
            <li>Verifique se o range não é apenas <code>A1:J1</code></li>
        </ol>
        <p><strong>⚠️ DELETE este arquivo após usar:</strong></p>
        <pre>rm debug-importacao.php</pre>
    </div>

</body>
</html>
