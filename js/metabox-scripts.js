jQuery( function() {
	jQuery( '<p>Below is a preview of the image or video. This is simply for purposes of ensuring the correct item has been selected, and does not show the actual size or layout of the slide.</p><div id="slideshow-image-preview" style="margin: 1em; padding: 1em; border: 1px solid #666;"></div>' ).insertAfter( jQuery( '#slide-image-id' ).closest( '.image-information' ) );
	
	jQuery( '#slide-image-id, #slide-content-type, #slide-video-url' ).change( function() {
		return rslideshow_update_preview();
	} );
	rslideshow_update_preview();
	
	function rslideshow_update_preview() {
		var sType = jQuery( '#slide-content-type' ).val();
		var prv = {};
		var imgID = null;
		switch( sType ) {
			case 'video' : 
				jQuery( '.video-information' ).show();
				jQuery( '.image-information' ).hide();
				doShortcode( jQuery( '#slide-video-url' ).val() );
				break;
			case 'other-image' : 
				jQuery( '.video-information' ).hide();
				jQuery( '.image-information' ).show();
				imgID = jQuery( '#slide-image-id' ).val();
				getAttachmentURL( imgID );
				break;
			case 'text' :
				jQuery( '.video-information' ).hide();
				jQuery( '.image-information' ).hide();
				jQuery( '#slideshow-image-preview' ).html( '' );
				break;
			case '' : 
			default :
				jQuery( '.video-information' ).hide();
				jQuery( '.image-information' ).hide();
				imgID = getThumbnailURL();
		}
		
		return false;
	}
	
	function doShortcode( content ) {
		if ( '' == content ) {
			jQuery( '#slideshow-image-preview' ).html( '<p>The video URL has not been specified, so no preview is available yet.</p>' );
			return;
		}
		
		var data = {
			'action' : 'slideshow_ajax_shortcode', 
			'content' : content
		}
		
		var result = '';
		
		jQuery.getJSON( ajaxurl, data, function( response ) {
			result = response;
			if ( undefined != result.value && '' != result.value )
				jQuery( '#slideshow-image-preview' ).html( result.value );
			else
				jQuery( '#slideshow-image-preview' ).html( '<p>There was an error displaying the preview.</p>' );
		} );
		
		return false;
	}
	
	function getThumbnailURL() {
		var qString = jQuery.deparam.querystring();
		
		var data = {
			'action' : 'slideshow_get_thumbnail_id', 
			'post_id' : qString.post
		}
		var result = '';
		
		jQuery.getJSON( ajaxurl, data, function( response ) {
			result = response;
			if ( undefined != result.url && '' != result.url )
				jQuery( '#slideshow-image-preview' ).html( '<img src="' + result.url + '" style="width: 100%; height: auto"/>' );
			else
				jQuery( '#slideshow-image-preview' ).html( '<p>There does not appear to be a featured image for this post, yet, so no preview is available.</p>' );
		} );
		
		return false;
	}
	
	function getAttachmentURL( imgID ) {
		if ( '' == imgID )
			return getThumbnailURL();
		
		var data = {
			'action' : 'slideshow_get_attachment_url', 
			'id' : imgID
		};
		
		var result = '';
		
		jQuery.getJSON( ajaxurl, data, function( response ) {
			result = response;
			if ( undefined != result.url && '' != result.url )
				jQuery( '#slideshow-image-preview' ).html( '<img src="' + result.url + '" style="width: 100%; height: auto"/>' );
			else
				jQuery( '#slideshow-image-preview' ).html( '<p>There was an error generating the preview image.</p>' );
		} );
		
		return false;
	}
} );