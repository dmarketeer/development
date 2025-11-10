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

        // GitHub configuration validation
        const validateButton = document.getElementById( 'validate-github-config' );
        if ( validateButton ) {
            validateButton.addEventListener( 'click', function() {
                const resultDiv = document.getElementById( 'github-validation-result' );
                const repoUrl = document.querySelector( 'input[name="oportunidades_settings[github_repo_url]"]' ).value;
                const branch = document.querySelector( 'input[name="oportunidades_settings[github_branch]"]' ).value;
                const filePath = document.querySelector( 'input[name="oportunidades_settings[github_file_path]"]' ).value;

                if ( ! repoUrl ) {
                    resultDiv.innerHTML = '<div class="notice notice-error inline"><p>Por favor, preencha a URL do repositório.</p></div>';
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
                        action: 'oportunidades_validate_github',
                        nonce: data.validateNonce || '',
                        repo_url: repoUrl,
                        branch: branch,
                        file_path: filePath
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
