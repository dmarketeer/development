<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<html>
<head>
    <meta charset="utf-8" />
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1><?php esc_html_e( 'Oportunidades destacadas', 'oportunidades' ); ?></h1>
    <p><?php esc_html_e( 'Segue a lista diária de oportunidades filtradas a partir da base local.', 'oportunidades' ); ?></p>
    <table>
        <thead>
            <tr>
                <th><?php esc_html_e( 'Título', 'oportunidades' ); ?></th>
                <th><?php esc_html_e( 'Prazo', 'oportunidades' ); ?></th>
                <th><?php esc_html_e( 'Valor', 'oportunidades' ); ?></th>
                <th><?php esc_html_e( 'Link', 'oportunidades' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $records as $record ) : ?>
                <tr>
                    <td><?php echo esc_html( $record['title'] ); ?></td>
                    <td><?php echo esc_html( $record['deadline_date'] ? get_date_from_gmt( $record['deadline_date'], 'd/m/Y' ) : '—' ); ?></td>
                    <td><?php echo esc_html( null !== $record['value_normalized'] ? number_format_i18n( $record['value_normalized'], 2 ) : '—' ); ?></td>
                    <td><a href="<?php echo esc_url( $record['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver detalhe', 'oportunidades' ); ?></a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
