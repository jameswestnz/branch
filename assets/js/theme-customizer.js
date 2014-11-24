( function( $ ) {
	// Update the site title in real time...
	wp.customize( 'blogname', function( value ) {
		value.bind( function( newval ) {
			$('.site-title').text( newval );
		} );
	} );
} )( jQuery );