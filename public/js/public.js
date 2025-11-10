( function() {
    document.addEventListener( 'click', function( event ) {
        const target = event.target;
        if ( target.matches( '.oportunidades-table a' ) ) {
            target.setAttribute( 'rel', 'noopener noreferrer' );
        }
    } );
} )();
