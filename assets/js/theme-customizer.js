( function( $ ) {
	//on load, refresh all elements
	/*parent.wp.customize.each(function(obj, key){
	});*/
				
	// Update the site title in real time...
	wp.customize( 'blogname', function( value ) {
		value.bind( function( newval ) {
			$('.site-title').text( newval );
		} );
	} );
	
	wp.customize( 'skin', function( value ) {
		value.bind( function( newval ) {
			// reload the iframe
			parent.wp.customize.previewer.refresh();
		} );
	} );
} )( jQuery );