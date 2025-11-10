( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, TextControl, RangeControl } = wp.components;
    const { createElement: el } = wp.element;

    registerBlockType( 'oportunidades/grid', {
        edit: function( props ) {
            const { attributes, setAttributes } = props;

            return el(
                'div',
                { className: props.className },
                [
                    el(
                        InspectorControls,
                        {},
                        el(
                            PanelBody,
                            { title: 'Configurações' },
                            [
                                el( TextControl, {
                                    label: 'Categoria',
                                    value: attributes.categoria,
                                    onChange: ( value ) => setAttributes( { categoria: value } ),
                                } ),
                                el( TextControl, {
                                    label: 'Distrito',
                                    value: attributes.distrito,
                                    onChange: ( value ) => setAttributes( { distrito: value } ),
                                } ),
                                el( RangeControl, {
                                    label: 'Limite',
                                    value: attributes.limite,
                                    min: 1,
                                    max: 50,
                                    onChange: ( value ) => setAttributes( { limite: value } ),
                                } ),
                            ]
                        )
                    ),
                    el( 'p', {}, 'Pré-visualização disponível no frontend.' ),
                ]
            );
        },
    } );
} )( window.wp );
