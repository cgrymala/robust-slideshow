var rsVideoIDs = {};
var uniqueIDs = 1;

var tag = document.createElement('script');
	  tag.src = "//www.youtube.com/iframe_api";
	  var firstScriptTag = document.getElementsByTagName('script')[0];
	  firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

var players = {};
var YTPlayerReady = false;
function onYouTubeIframeAPIReady() {
	YTPlayerReady = true;
}
function setUpYTPlayers() {
	for( var i in rsVideoIDs ) {
		players[i] = new YT.Player( i, {
			events : {
				'onStateChange' : onPlayerStateChange
			}
		} );
	}
}
function onPlayerStateChange( event ) {
	if ( ! jQuery ) {
		return;
	}
	if ( event.data == YT.PlayerState.PLAYING ) {
		jQuery( '.flexslider' ).flexslider( 'pause' );
	} else if ( event.data == YT.PlayerState.PAUSED || event.data == YT.PlayerState.ENDED ) {
		jQuery( '.flexslider' ).flexslider( 'play' );
	}
}

jQuery( function() {
	jQuery( '.flexslider iframe' ).each( function() {
		jQuery( this ).attr( 'src', jQuery( this ).attr( 'src' ) + '&wmode=transparent&enablejsapi=1&origin=' + slideshowOpts.script_origin );
		jQuery( this ).attr( 'wmode', 'transparent' );
	} );
	
	if( typeof slideshowOpts === 'undefined' ) {
		slideshowOpts = {
			'controlNav' : false, 
			'directionNav' : false, 
			'randomize' : false, 
			'pauseOnHover' : false, 
			'pausePlay' : false, 
			'animation' : 'fade', 
			'slideshowSpeed' : 7000, 
			'animationSpeed' : 500,
			'pauseOnAction' : true
		};
	}
	if ( 'width' in slideshowOpts && 'height' in slideshowOpts ) {
		jQuery( '.flexslider' ).css( { 'width' : slideshowOpts.width, 'height' : slideshowOpts.height } );
	}
	slideshowOpts.video = true;
	slideshowOpts.start = function() { initYTPlayers() };
	slideshowOpts.before = function() {
		if ( typeof( players ) === 'undefined' )
			return;
		
		for ( var i in players ) {
			var player = players[i];
			if ( 'pauseVideo' in player ) {
				player.pauseVideo();
			}
		}
		return true;
	};
	
	var mySlider = jQuery( '.flexslider' ).flexslider( slideshowOpts );
	/*setTimeout( initYTPlayers, 100 );*/
	function initYTPlayers() {
		if ( jQuery( '.flexslider iframe[src*="youtube.com"]' ).length <= 0 ) {
			return;
		}
		if ( YTPlayerReady ) {
			jQuery( '.flexslider iframe[src*="youtube.com"]' ).each( function() {
				if ( jQuery( this ).attr( 'id' ) ) {
					return;
				}
				var myID = 'video-' + uniqueIDs;
				jQuery( this ).attr( 'id', myID );
				players[myID] = new YT.Player( myID, {
					events : {
						'onStateChange' : onPlayerStateChange
					}
				} );
				uniqueIDs++;
			} )
		} else {
			setTimeout( initYTPlayers, 100 );
		}
	}
} );