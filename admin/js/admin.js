( function( wp, data ) {
    if ( ! data || ! data.tableData ) {
        return;
    }

    const { render, createElement } = wp.element;

    function Table( props ) {
        const rows = props.rows || [];

        return createElement(
            'table',
            { className: 'wp-list-table widefat striped oportunidades-admin-table' },
            [
                createElement(
                    'thead',
                    {},
                    createElement(
                        'tr',
                        {},
                        [ 'Título', 'Entidade', 'Prazo', 'Valor', 'Origem' ].map( ( heading ) =>
                            createElement( 'th', { key: heading }, heading )
                        )
                    )
                ),
                createElement(
                    'tbody',
                    {},
                    rows.map( ( row ) =>
                        createElement(
                            'tr',
                            { key: row.id },
                            [
                                createElement( 'td', {}, row.title ),
                                createElement( 'td', {}, row.awarding_entity || '—' ),
                                createElement( 'td', {}, row.deadline_date || '—' ),
                                createElement( 'td', {}, row.value_normalized || '—' ),
                                createElement( 'td', {}, row.origin || '—' ),
                            ]
                        )
                    )
                ),
            ]
        );
    }

    document.addEventListener( 'DOMContentLoaded', function() {
        const container = document.getElementById( 'oportunidades-admin-table' );
        if ( container ) {
            render( createElement( Table, { rows: data.tableData } ), container );
        }

        // Google Sheets configuration validation
        const validateButton = document.getElementById( 'validate-sheets-config' );
        if ( validateButton ) {
            validateButton.addEventListener( 'click', function() {
                const resultDiv = document.getElementById( 'sheets-validation-result' );
                const spreadsheetId = document.querySelector( 'input[name="oportunidades_settings[sheets_spreadsheet_id]"]' ).value;
                const range = document.querySelector( 'input[name="oportunidades_settings[sheets_range]"]' ).value;
                const apiKey = document.querySelector( 'input[name="oportunidades_settings[sheets_api_key]"]' ).value;

                if ( ! spreadsheetId ) {
                    resultDiv.innerHTML = '<div class="notice notice-error inline"><p>Por favor, preencha o ID da planilha.</p></div>';
                    return;
                }

                if ( ! apiKey ) {
                    resultDiv.innerHTML = '<div class="notice notice-error inline"><p>Por favor, preencha a API Key.</p></div>';
                    return;
                }

                validateButton.disabled = true;
                validateButton.textContent = 'A validar...';
                resultDiv.innerHTML = '';

                // Make AJAX request
                fetch( ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams( {
                        action: 'oportunidades_validate_sheets',
                        nonce: data.validateNonce || '',
                        spreadsheet_id: spreadsheetId,
                        range: range,
                        api_key: apiKey
                    } )
                } )
                .then( response => response.json() )
                .then( result => {
                    if ( result.success ) {
                        resultDiv.innerHTML = '<div class="notice notice-success inline"><p>' + result.data.message + '</p></div>';
                    } else {
                        resultDiv.innerHTML = '<div class="notice notice-error inline"><p>' + result.data.message + '</p></div>';
                    }
                } )
                .catch( error => {
                    resultDiv.innerHTML = '<div class="notice notice-error inline"><p>Erro ao validar: ' + error.message + '</p></div>';
                } )
                .finally( () => {
                    validateButton.disabled = false;
                    validateButton.textContent = 'Validar Configuração';
                } );
            } );
        }
    } );
} )( window.wp || {}, window.OportunidadesAdmin || {} );
