jQuery( function() {
	jQuery( '<a href="#" title="Toggle advanced slideshow options" class="show-advanced">Show/hide advanced options</a>' )
		.click( function() {
			jQuery( '.hide-after' ).nextUntil( '.show-advanced' ).toggle();
			return false;
		} )
		.insertAfter( '.form-field:last' );
	jQuery( '.hide-after' ).nextUntil( '.show-advanced' ).hide();
} );