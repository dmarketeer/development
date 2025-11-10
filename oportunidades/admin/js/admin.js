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
    } );
} )( window.wp || {}, window.OportunidadesAdmin || {} );
