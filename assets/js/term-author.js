jQuery( document ).ready( function( $ ) {
    'use strict';

    $( '.editinline' ).on( 'click', function() {
        var tag_id = $( this ).parents( 'tr' ).attr( 'id' ),
			author = $( 'td.author span', '#' + tag_id ).data( 'author' );

		if ( typeof( author ) !== 'undefined' ) {
			setTimeout( function() {
				$( 'select[name="term-author"]', '.inline-edit-row' ).val( author );
			}, 100 );
		}
    } );
} );
