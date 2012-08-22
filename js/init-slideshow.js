jQuery( function() {
	if( typeof slideshowOpts === 'undefined' ) {
		slideshowOpts = {
			'controlNav' : false, 
			'directionNav' : false, 
			'randomize' : false, 
			'pauseOnHover' : false, 
			'pausePlay' : false, 
			'animation' : 'fade', 
			'slideshowSpeed' : 7000, 
			'animationSpeed' : 500
		};
	}
	if ( ! 'show_title' in slideshowOpts )
		slideshowOpts.show_title = false;
	if ( ! 'show_caption' in slideshowOpts )
		slideshowOpts.show_caption = false;
		
	if ( slideshowOpts.show_title == false && slideshowOpts.show_caption == false ) {
		jQuery( '.flex-caption' ).hide();
	} else if ( slideshowOpts.show_caption == false ) {
		jQuery( '.flex-caption > div' ).hide();
	} else if ( slideshowOpts.show_title == false ) {
		jQuery( '.flex-caption > h1' ).hide();
	}
	jQuery( '.flexslider' ).flexslider( slideshowOpts );
	jQuery( '.flexslider' ).css( { 'margin' : 0, 'border' : 'none', 'height' : 300, 'width' : 672, 'overflow' : 'hidden' } );
	jQuery( '.flexslider figure' ).css( { 'margin' : 0, 'padding' : 0 } );
	jQuery( '.flexslider iframe' ).css( { 'margin' : '0 auto' } );
} );