<?php
/**
 * Script de Diagnóstico - Plugin Oportunidades
 *
 * Como usar:
 * 1. Coloque este arquivo na raiz do WordPress
 * 2. Acesse: http://seu-site.com/diagnostico.php
 * 3. Delete o arquivo após o diagnóstico
 */

// Tentar encontrar e carregar WordPress
$wp_load_paths = [
    __DIR__ . '/wp-load.php',                          // Raiz do WordPress
    __DIR__ . '/../wp-load.php',                       // Um nível acima
    __DIR__ . '/../../wp-load.php',                    // Dois níveis acima
    __DIR__ . '/../../../wp-load.php',                 // Três níveis acima (se no plugin)
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
    die('Erro: Não foi possível encontrar o wp-load.php. Certifique-se de colocar este arquivo na raiz do WordPress.');
}

// Verificar permissões
if (!current_user_can('manage_options')) {
    die('Acesso negado. Faça login como administrador primeiro, depois acesse este arquivo.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico - Plugin Oportunidades</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0073aa; color: white; }
        pre { background: #f9f9f9; padding: 10px; border-left: 3px solid #0073aa; overflow-x: auto; }
        .badge { padding: 5px 10px; border-radius: 3px; color: white; display: inline-block; }
        .badge-success { background: green; }
        .badge-error { background: red; }
        .badge-warning { background: orange; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico do Plugin Oportunidades</h1>

<?php
global $wpdb;

// Definir constantes se não existirem
if (!defined('OPORTUNIDADES_TABLE_NAME')) {
    define('OPORTUNIDADES_TABLE_NAME', $wpdb->prefix . 'oportunidades');
}
if (!defined('OPORTUNIDADES_OPTION_NAME')) {
    define('OPORTUNIDADES_OPTION_NAME', 'oportunidades_settings');
}

$table_name = OPORTUNIDADES_TABLE_NAME;
$settings = get_option(OPORTUNIDADES_OPTION_NAME, []);

// ============================================
// 1. INFORMAÇÕES DO SISTEMA
// ============================================
echo '<div class="section">';
echo '<h2>1. Informações do Sistema</h2>';
echo '<table>';
echo '<tr><th>Item</th><th>Valor</th></tr>';
echo '<tr><td>WordPress Version</td><td>' . get_bloginfo('version') . '</td></tr>';
echo '<tr><td>PHP Version</td><td>' . PHP_VERSION . '</td></tr>';
echo '<tr><td>MySQL Version</td><td>' . $wpdb->db_version() . '</td></tr>';
echo '<tr><td>DB Prefix</td><td>' . $wpdb->prefix . '</td></tr>';
echo '<tr><td>Table Name</td><td>' . $table_name . '</td></tr>';
echo '</table>';
echo '</div>';

// ============================================
// 2. VERIFICAR TABELA
// ============================================
echo '<div class="section">';
echo '<h2>2. Verificação da Tabela</h2>';

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

if ($table_exists) {
    echo '<p class="success">✅ Tabela existe: ' . $table_name . '</p>';

    // Contar registros
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo '<p><strong>Total de registros:</strong> ' . $count . '</p>';

    if ($count > 0) {
        echo '<p class="success">✅ Existem ' . $count . ' registros na base de dados</p>';

        // Mostrar últimos 5 registros
        $records = $wpdb->get_results("SELECT id, external_id, title, awarding_entity, value_normalized, created_at FROM $table_name ORDER BY id DESC LIMIT 5", ARRAY_A);

        if ($records) {
            echo '<h3>Últimos 5 registros:</h3>';
            echo '<table>';
            echo '<tr><th>ID</th><th>External ID</th><th>Título</th><th>Entidade</th><th>Valor</th><th>Criado em</th></tr>';
            foreach ($records as $record) {
                echo '<tr>';
                echo '<td>' . esc_html($record['id']) . '</td>';
                echo '<td>' . esc_html($record['external_id']) . '</td>';
                echo '<td>' . esc_html(substr($record['title'], 0, 50)) . '...</td>';
                echo '<td>' . esc_html($record['awarding_entity']) . '</td>';
                echo '<td>' . esc_html($record['value_normalized']) . '</td>';
                echo '<td>' . esc_html($record['created_at']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } else {
        echo '<p class="error">❌ A tabela está vazia! Nenhum registo encontrado.</p>';
    }

    // Estrutura da tabela
    echo '<h3>Estrutura da Tabela:</h3>';
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name", ARRAY_A);
    echo '<table>';
    echo '<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th></tr>';
    foreach ($columns as $column) {
        echo '<tr>';
        echo '<td>' . esc_html($column['Field']) . '</td>';
        echo '<td>' . esc_html($column['Type']) . '</td>';
        echo '<td>' . esc_html($column['Null']) . '</td>';
        echo '<td>' . esc_html($column['Key']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

} else {
    echo '<p class="error">❌ Tabela NÃO existe: ' . $table_name . '</p>';
    echo '<p>Execute: Plugins → Desativar "Oportunidades" → Ativar "Oportunidades"</p>';
}

echo '</div>';

// ============================================
// 3. CONFIGURAÇÕES DO PLUGIN
// ============================================
echo '<div class="section">';
echo '<h2>3. Configurações do Plugin</h2>';

if (empty($settings)) {
    echo '<p class="error">❌ Nenhuma configuração encontrada!</p>';
    echo '<p>Acesse: Admin → Oportunidades para configurar</p>';
} else {
    echo '<table>';
    echo '<tr><th>Configuração</th><th>Valor</th><th>Status</th></tr>';

    // API Token
    $token = $settings['api_token'] ?? '';
    echo '<tr>';
    echo '<td>API Token</td>';
    echo '<td>' . (empty($token) ? '-' : substr($token, 0, 10) . '...') . '</td>';
    echo '<td>' . (empty($token) ? '<span class="badge badge-error">Não configurado</span>' : '<span class="badge badge-success">OK</span>') . '</td>';
    echo '</tr>';

    // Google Sheets
    $sheets_id = $settings['sheets_spreadsheet_id'] ?? '';
    echo '<tr>';
    echo '<td>Google Sheets ID</td>';
    echo '<td>' . esc_html($sheets_id ?: '-') . '</td>';
    echo '<td>' . (empty($sheets_id) ? '<span class="badge badge-error">Não configurado</span>' : '<span class="badge badge-success">OK</span>') . '</td>';
    echo '</tr>';

    $sheets_range = $settings['sheets_range'] ?? '';
    echo '<tr>';
    echo '<td>Google Sheets Range</td>';
    echo '<td>' . esc_html($sheets_range ?: '-') . '</td>';
    echo '<td>' . (empty($sheets_range) ? '<span class="badge badge-warning">Não configurado</span>' : '<span class="badge badge-success">OK</span>') . '</td>';
    echo '</tr>';

    $sheets_key = $settings['sheets_api_key'] ?? '';
    echo '<tr>';
    echo '<td>Google API Key</td>';
    echo '<td>' . (empty($sheets_key) ? '-' : substr($sheets_key, 0, 10) . '...') . '</td>';
    echo '<td>' . (empty($sheets_key) ? '<span class="badge badge-error">Não configurado</span>' : '<span class="badge badge-success">OK</span>') . '</td>';
    echo '</tr>';

    // Outros
    echo '<tr><td>Sync Interval</td><td>' . ($settings['sync_interval'] ?? '-') . ' min</td><td>-</td></tr>';
    echo '<tr><td>Notification Emails</td><td>' . esc_html($settings['notification_emails'] ?? '-') . '</td><td>-</td></tr>';

    echo '</table>';

    echo '<h3>Configuração Completa:</h3>';
    echo '<pre>' . print_r($settings, true) . '</pre>';
}

echo '</div>';

// ============================================
// 4. HISTÓRICO DE IMPORTAÇÕES
// ============================================
echo '<div class="section">';
echo '<h2>4. Histórico de Importações</h2>';

$last_ingestion = get_option('oportunidades_last_ingestion');
$last_sheets_fetch = get_option('oportunidades_last_sheets_fetch');
$last_errors = get_transient('oportunidades_last_errors');
$last_summary = get_transient('oportunidades_last_summary');

echo '<table>';
echo '<tr><th>Item</th><th>Valor</th></tr>';
echo '<tr><td>Última Ingestão</td><td>' . ($last_ingestion ?: '<span class="error">Nunca</span>') . '</td></tr>';
echo '<tr><td>Última Sincronização Google Sheets</td><td>' . ($last_sheets_fetch ?: '<span class="error">Nunca</span>') . '</td></tr>';
echo '</table>';

if ($last_errors) {
    echo '<h3 class="error">❌ Últimos Erros:</h3>';
    echo '<pre>' . print_r($last_errors, true) . '</pre>';
}

if ($last_summary) {
    echo '<h3 class="success">✅ Último Resumo:</h3>';
    echo '<pre>' . print_r($last_summary, true) . '</pre>';
}

echo '</div>';

// ============================================
// 5. TESTE DE CONEXÃO GOOGLE SHEETS
// ============================================
echo '<div class="section">';
echo '<h2>5. Teste de Conexão Google Sheets</h2>';

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

    echo '<p><strong>URL de Teste:</strong><br><code>' . esc_html($api_url) . '</code></p>';

    $response = wp_remote_get($api_url, ['timeout' => 15]);

    if (is_wp_error($response)) {
        echo '<p class="error">❌ Erro na conexão: ' . $response->get_error_message() . '</p>';
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        echo '<p><strong>HTTP Status:</strong> ' . $status_code . '</p>';

        if ($status_code === 200) {
            echo '<p class="success">✅ Conexão bem-sucedida!</p>';

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['values'])) {
                $row_count = count($data['values']);
                echo '<p><strong>Linhas retornadas:</strong> ' . $row_count . '</p>';

                if ($row_count > 0) {
                    echo '<h3>Primeira linha (header):</h3>';
                    echo '<pre>' . print_r($data['values'][0], true) . '</pre>';

                    if ($row_count > 1) {
                        echo '<h3>Segunda linha (exemplo de dados):</h3>';
                        echo '<pre>' . print_r($data['values'][1], true) . '</pre>';
                    } else {
                        echo '<p class="error">❌ Apenas o cabeçalho foi retornado! Aumente o range (ex: A1:J1000)</p>';
                    }
                } else {
                    echo '<p class="error">❌ Nenhuma linha retornada do Google Sheets!</p>';
                }
            } else {
                echo '<p class="error">❌ Resposta não contém "values"</p>';
                echo '<pre>' . esc_html($body) . '</pre>';
            }
        } elseif ($status_code === 403) {
            echo '<p class="error">❌ Acesso negado (403)</p>';
            echo '<p>Verifique:<br>1. API Key está correta<br>2. Google Sheets API está habilitada<br>3. Planilha tem permissão de visualização pública</p>';
        } elseif ($status_code === 404) {
            echo '<p class="error">❌ Planilha não encontrada (404)</p>';
            echo '<p>Verifique o ID da planilha: ' . $spreadsheet_id . '</p>';
        } else {
            echo '<p class="error">❌ Erro HTTP ' . $status_code . '</p>';
            echo '<pre>' . esc_html(wp_remote_retrieve_body($response)) . '</pre>';
        }
    }
} else {
    echo '<p class="warning">⚠️ Google Sheets não configurado</p>';
}

echo '</div>';

// ============================================
// 6. VERIFICAR CRON JOBS
// ============================================
echo '<div class="section">';
echo '<h2>6. Cron Jobs Agendados</h2>';

$cron_jobs = [
    'oportunidades_sheets_sync' => 'Sincronização Google Sheets',
    'oportunidades_process_local_file' => 'Processar Ficheiro Local',
    'oportunidades_send_digest' => 'Enviar Resumo Diário'
];

echo '<table>';
echo '<tr><th>Job</th><th>Próxima Execução</th><th>Status</th></tr>';

foreach ($cron_jobs as $hook => $label) {
    $next = wp_next_scheduled($hook);
    echo '<tr>';
    echo '<td>' . $label . '</td>';
    echo '<td>' . ($next ? date('Y-m-d H:i:s', $next) : '-') . '</td>';
    echo '<td>' . ($next ? '<span class="badge badge-success">Agendado</span>' : '<span class="badge badge-error">Não agendado</span>') . '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</div>';

// ============================================
// 7. AÇÕES RECOMENDADAS
// ============================================
echo '<div class="section">';
echo '<h2>7. Ações Recomendadas</h2>';

$issues = [];

if (!$table_exists) {
    $issues[] = '❌ Reativar o plugin para criar a tabela';
}

if (empty($settings['sheets_spreadsheet_id']) || empty($settings['sheets_api_key'])) {
    $issues[] = '❌ Configurar Google Sheets (ID e API Key)';
}

if ($table_exists && $wpdb->get_var("SELECT COUNT(*) FROM $table_name") == 0) {
    $issues[] = '⚠️ Importar dados manualmente ou via sincronização';
}

if (!empty($settings['sheets_range']) && strpos($settings['sheets_range'], '!') !== false) {
    list($sheet, $range) = explode('!', $settings['sheets_range']);
    if (preg_match('/^A1:[A-Z]+1$/', $range)) {
        $issues[] = '❌ O range "' . $settings['sheets_range'] . '" busca apenas 1 linha! Altere para: ' . $sheet . '!A1:J1000';
    }
}

if (empty($issues)) {
    echo '<p class="success">✅ Tudo parece estar configurado corretamente!</p>';
} else {
    echo '<ol>';
    foreach ($issues as $issue) {
        echo '<li>' . $issue . '</li>';
    }
    echo '</ol>';
}

echo '</div>';

?>

<div class="section">
    <p><strong>⚠️ IMPORTANTE:</strong> Delete este arquivo após o diagnóstico por questões de segurança!</p>
    <pre>rm diagnostico.php</pre>
</div>

</body>
</html>
