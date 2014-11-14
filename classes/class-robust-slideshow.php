<?php
/**
 * Define the main class for the Robust Slideshow plugin
 * @version 1.4
 */
if ( ! class_exists( 'robust_slideshow' ) ) {
	class robust_slideshow {
		var $slideshow_defaults = array(
			'size'         => null, 
			'width'        => 0, 
			'height'       => 0, 
			'crop'         => false, 
			'animation'    => 'fade', 
			'slideshowSpeed' => 7000, 
			'animationSpeed' => 500, 
			'randomize'    => false, 
			'pauseOnHover' => false, 
			'controlNav'   => false, 
			'directionNav' => false, 
			'pausePlay'    => false, 
			'video'        => true, 
			'manualPause'  => true, 
		);
		
		/**
		 * Create the robust_slideshow object
		 * @uses add_action() to add the robust_slideshow::register_types & 
		 * 		robust_slideshow::register_tax methods to the init action
		 */
		function __construct() {
			add_action( 'init', array( $this, 'register_types' ) );
			add_action( 'init', array( $this, 'register_tax' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			
			add_shortcode( 'slideshow', array( $this, 'do_shortcode' ) );
			
			add_action( 'wp_ajax_slideshow_ajax_shortcode', array( $this, 'slideshow_ajax_shortcode' ) );
			add_action( 'wp_ajax_slideshow_get_thumbnail_id', array( $this, 'get_thumbnail_id' ) );
			add_action( 'wp_ajax_slideshow_get_attachment_url', array( $this, 'get_attachment_url' ) );
			
			$sizes = get_option( 'robust-slideshow-sizes', array() );
			foreach ( $sizes as $k => $size ) {
				$sizename = sprintf( 'robust-slideshow-%s-%sx%s%s', $k, $size['width'], $size['height'], ( $size['crop'] ? '-cropped' : '' ) ); 
				add_image_size( $sizename, $size['width'], $size['height'], $size['crop'] );
			}
		}
		
		/**
		 * Enqueue any scripts or styles we need for this plugin
		 */
		function enqueue_scripts() {
			if ( is_admin() ) {
				wp_register_script( 'jquery-bbq', plugins_url( '/js/jquery-bbq/jquery.ba-bbq.min.js', dirname( __FILE__ ) ), array( 'jquery' ), '1.3-pre', true );
				wp_register_script( 'robust-slideshow-metabox-script', plugins_url( '/js/metabox-scripts.js', dirname( __FILE__ ) ), array( 'jquery-bbq' ), '0.1.32', true );
				wp_register_script( 'edit-robust-slideshow', plugins_url( '/js/slideshow-edit.js', dirname( __FILE__ ) ), array( 'jquery' ), '0.1.3', true );
				wp_register_style( 'robust-slideshow-admin-styles', plugins_url( '/css/admin-styles.css', dirname( __FILE__ ) ), '0.1.6', array(), 'all' );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_style' ) );
			} else {
				wp_register_style( 'flex-slider', plugins_url( '/js/flex-slider/flexslider.css', dirname( __FILE__ ) ), array(), '2.1', 'all' );
				wp_register_style( 'robust-slideshow', plugins_url( '/css/robust-slider.css', dirname( __FILE__ ) ), array( 'flex-slider' ), '1.1', 'all' );
				wp_register_style( 'nyromodal', plugins_url( '/js/nyromodal/styles/nyroModal.css', dirname( __FILE__ ) ), array(), '2014-10-17', 'all' );
				wp_register_script( 'flex-slider', plugins_url( '/js/flex-slider/jquery.flexslider-min.js', dirname( __FILE__ ) ), array( 'jquery' ), '2.1', true );
				wp_register_script( 'nyromodal', plugins_url( '/js/nyromodal/js/jquery.nyroModal.js', dirname( __FILE__ ) ), array( 'jquery' ), '2014-10-17', true );
				wp_register_script( 'bootstrap', plugins_url( '/js/bootstrap.js', dirname( __FILE__ ) ), array( 'jquery' ), 1, true );
				wp_register_script( 'embedly-jquery', '//cdn.embed.ly/jquery.embedly-3.1.1.min.js', array( 'jquery', 'bootstrap' ), '3.1.1', true );
				wp_register_script( 'robust-slideshow', plugins_url( '/js/init-slideshow.js', dirname( __FILE__ ) ), array( 'flex-slider', 'embedly-jquery' ), '1.1.19', true );
			}
		}
		
		/**
		 * Enqueue the stylesheet for the admin area
		 * This style sheet contains the styles for the slideshow icons
		 * 		used in the admin area, so it should be enqueued on all
		 * 		admin pages
		 */
		function enqueue_admin_style() {
			wp_enqueue_style( 'robust-slideshow-admin-styles' );
		}
		
		/**
		 * Register the slideshow post type
		 */
		function register_types() {
			$labels = apply_filters( 'robust-slideshow-post-type-labels', array(
				'name'          => _x( 'Slides', 'post type general name' ), 
				'singular_name' => _x( 'Slide', 'post type singular name' ), 
				'add_new'       => _x( 'Add New', 'robust-slide' ), 
				'add_new_item'  => __( 'Add New Slide' ), 
				'edit_item'     => __( 'Edit Slide' ), 
				'new_item'      => __( 'New Slide' ), 
				'all_items'     => __( 'All Slides' ), 
				'view_item'     => __( 'View Slide' ), 
				'search_items'  => __( 'Search Slides' ), 
				'not_found'     =>  __( 'No slides found' ), 
				'not_found_in_trash' => __( 'No slides found in Trash' ), 
				'parent_item_colon' => '', 
				'menu_name'     => __( 'Robust Slides' ), 
			) );
			$args = apply_filters( 'robust-slideshow-post-type-args', array(
				'labels'        => $labels,
				'public'        => true,
				'publicly_queryable' => true,
				'show_ui'       => true, 
				'show_in_menu'  => true, 
				'query_var'     => 'robust-slide',
				'rewrite'       => array(
					'slug'       => 'slide', 
					'with_front' => false, 
					'feeds'      => false, 
					'pages'      => false, 
				), 
				'capability_type' => 'post',
				'has_archive'   => false, 
				'hierarchical'  => false,
				'menu_position' => null,
				'supports'      => array( 'title', 'editor', 'author', 'thumbnail', 'page-attributes' )
			) );
			
			register_post_type( 'robust-slide', $args );
			
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_post' ) );
		}
		
		/**
		 * Register any necessary meta boxes
		 */
		function add_meta_boxes() {
			add_meta_box( 'slide-content-info', __( 'Slide Content Details' ), array( $this, 'slide_content_metabox' ), 'robust-slide', 'normal', 'high' );
		}
		
		/**
		 * Create the metabox for slide content meta information
		 */
		function slide_content_metabox() {
			wp_enqueue_script( 'robust-slideshow-metabox-script' );
			
			wp_nonce_field( 'slide-content-meta', '_slide_content_nonce' );
			$meta_vals = get_post_meta( $_REQUEST['post'], 'slide-content-meta', true );
			if ( empty( $meta_vals ) )
				$meta_vals = array();
			$meta_vals = array_merge( apply_filters( 'robust-slideshow-default-meta', array(
				'type' => null, 
			) ), $meta_vals );
			$meta_vals['show_title'] = in_array( $meta_vals['show_title'], array( '1', 1, true, 'true' ) );
			$meta_vals['show_caption'] = in_array( $meta_vals['show_caption'], array( '1', 1, true, 'true' ) );
?>
<p><label for="slide-link"><?php _e( 'How should the slide link to the full post?' ) ?></label>
	<select class="widefat" name="robust[link]" id="slide-link">
    	<option value=""<?php selected( $meta_vals['link'], null ) ?>><?php _e( 'Do not link this slide' ) ?></option>
        <option value="whole"<?php selected( $meta_vals['link'], 'whole' ) ?>><?php _e( 'Link the whole slide' ) ?></option>
        <option value="content"<?php selected( $meta_vals['link'], 'content' ) ?>><?php _e( 'Link the title and caption' ) ?></option>
        <option value="title"<?php selected( $meta_vals['link'], 'title' ) ?>><?php _e( 'Just link the title' ) ?></option>
        <option value="caption"<?php selected( $meta_vals['link'], 'caption' ) ?>><?php _e( 'Just link the caption' ) ?></option>
    </select>
    <br/><em><?php printf( __( 'If linked, the slide will link to the full post.%s If the whole slide, the "title and caption" or "just the caption" are linked, any links inside of the slide content will not work.<br/>If you choose a video to display within this slide, you should not choose to link the whole slide. That will interfere with the performance of the video.' ), class_exists( 'CWS_PageLinksTo' ) ? __( ' If you would like the slide to link somewhere else, use the Page Links To box below.' ) : ' ' ) ?></em></p>
<p><input type="checkbox" name="robust[showtitle]" id="show-title" value="1"<?php checked( $meta_vals['show_title'] ) ?> />
	<label for="show-title"><?php _e( 'Display slide title?' ) ?></label></p>
<p><input type="checkbox" name="robust[showcaption]" id="show-caption" value="1"<?php checked( $meta_vals['show_caption'] ) ?> />
	<label for="show-caption"><?php _e( 'Display slide content as caption?' ) ?></label></p>
<p><label for="slide-content-type"><?php _e( 'What type of item should display in the background of the slide?' ) ?></label> 
	<select class="widefat" name="robust[type]" id="slide-content-type">
    	<option value=""<?php selected( $meta_vals['type'], null ) ?>><?php _e( 'Use the featured image for this post' ) ?></option>
        <option value="video"<?php selected( $meta_vals['type'], 'video' ) ?>><?php _e( 'Embed the video specified below' ) ?></option>
        <option value="text"<?php selected( $meta_vals['type'], 'text' ) ?>><?php _e( 'Just display the content of this post' ) ?></option>
        <option value="other-image"<?php selected( $meta_vals['type'], 'other-image' ) ?>><?php _e( 'Use an image from the media library' ) ?></option>
    </select></p>
<p><input type="checkbox" name="robust[lightbox]" id="slide-link-lightbox" value="1"<?php checked( $meta_vals['lightbox'] ) ?> />
	<label for="slide-link-lightbox"><?php _e( 'Open link in lightbox/modal window?' ) ?></label></p>
<div class="video-information">
    <h4><?php _e( 'Video Information' ) ?></h4>
    <p><?php _e( 'If the video option is selected above, please specify the URL to the video that should be embedded' ) ?><br />
        <label for="slide-video-url"><?php _e( 'Video URL' ) ?></label>
            <input type="url" class="widefat" name="robust[video-url]" id="slide-video-url" value="<?php echo esc_url( $meta_vals['video-url'] ) ?>" /></p>
</div>
<div class="image-information">
    <h4><?php _e( 'Image Choice' ) ?></h4>
    <p><?php _e( 'If you chose to use an image from the media library (other than the featured image for this post), please choose the appropriate media item.' ) ?><br />
        <label for="slide-image-id"><?php _e( 'Image' ) ?></label>
            <select class="widefat" name="robust[image-id]" id="slide-image-id">
                <option value=""<?php selected( $meta_vals['image-id'], null ) ?>><?php _e( 'Use the featured image' ) ?></option>
<?php
			$images = get_posts( array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => null, 'post_mime_type' => array( 'image/png', 'image/jpeg', 'image/gif' ) ) );
			if ( ! empty( $images ) ) {
				foreach ( $images as $img ) {
?>
                <option value="<?php echo $img->ID ?>" title="<?php echo esc_attr( $img->post_title ) ?>"<?php selected( $meta_vals['image-id'], $img->ID ) ?>><?php echo $img->post_title ?></option>
<?php
				}
			}
?>
            </select></p>
</div>
<?php
		}
		
		function slideshow_ajax_shortcode() {
			$content = isset( $_GET['content'] ) ? $_GET['content'] : null;
			if ( empty( $content ) ) {
				echo json_encode( array( 'message' => 'The content was empty for some reason' ) );
				exit;
			}
			
			$content = $url = urldecode( $content );
			$content = '[embed]' . $content . '[/embed]';
			
			/*echo '<pre><code>' . $content . '</code></pre>';
			
			echo '<pre><code>';
			echo apply_filters( 'the_content', $content );
			echo '</code></pre>';
			
			exit;*/
			
			if ( ! class_exists( 'WP_oEmbed' ) )
				require_once( ABSPATH . WPINC . '/class-oembed.php' );
				
			$e = new WP_oEmbed;
			/*$url = 'http://www.youtube.com/oembed?maxwidth=600&maxheight=600&url=' . urlencode( $url );*/
			
			$rt = array();
			$rt['original_content'] = $content;
			$rt['message'] = 'Preparing to do a shortcode of some sort';
			$rt['value'] = $e->get_html( $url );
			/*$rt['parsed_url'] = parse_url( $url );
			$rt['response'] = $response = wp_remote_get( $url );
			$rt['status'] = wp_remote_retrieve_response_code( $response );
			$rt['body'] = wp_remote_retrieve_body( $response );*/
			
			/*echo '<pre><code>';
			var_dump( $rt );
			echo '</code></pre>';
			exit;*/
			
			echo json_encode( $rt );
			
			exit;
		}
		
		function get_thumbnail_id() {
			$post_id = isset( $_GET['post_id'] ) ? $_GET['post_id'] : null;
			if ( empty( $post_id ) ) {
				exit;
			}
			
			if ( ! has_post_thumbnail( $post_id ) )
				exit;
			
			$imgid = get_post_thumbnail_id( $post_id );
			list( $url, $width, $height ) = wp_get_attachment_image_src( $imgid, 'large' );
			$rt = array( 'url' => $url, 'width' => $width, 'height' => $height, 'value' => $imgid );
			$rt['message'] =  'Preparing to echo the ID ' . $imgid . ' as the thumbnail ID of ' . $post_id;
			
			echo json_encode( $rt );
			
			exit;
		}
		
		function get_attachment_url() {
			$id = isset( $_GET['id'] ) ? $_GET['id'] : null;
			if ( empty( $id ) ) {
				echo json_encode( array( 'message' => 'The ID was empty for some reason' ) );
				exit;
			}
			
			list( $url, $width, $height ) = wp_get_attachment_image_src( $id, 'large' );
			$img['url'] = $url;
			$img['width'] = $width;
			$img['height'] = $height;
			$img['message'] = 'Preparing to send the URL of ' . $img['url'] . ' as the src of ' . $id;
			echo json_encode( $img );
			exit;
		}
		
		/**
		 * Save additional meta information about a slide post
		 */
		function save_post( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return;
			if ( 'robust-slide' !== $_POST['post_type'] )
				return/* wp_die( 'The post type was wrong' )*/;
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return/* wp_die( 'Wrong permissions' )*/;
				
			if ( ! wp_verify_nonce( $_POST['_slide_content_nonce'], 'slide-content-meta' ) )
				return/* wp_die( 'Nonce not verified' )*/;
			
			if ( ! isset( $_POST['robust'] ) || empty( $_POST['robust'] ) || ! is_array( $_POST['robust'] ) )
				return/* wp_die( 'Form not complete' )*/;
			
			$vals = array();
			$input = $_POST['robust'];
			
			if ( isset( $input['link'] ) )
				$vals['link'] = $input['link'];
			
			$vals['show_title'] = $vals['show_caption'] = false;
			if ( isset( $input['showtitle'] ) && '1' == $input['showtitle'] )
				$vals['show_title'] = true;
			if ( isset( $input['showcaption'] ) && '1' == $input['showcaption'] )
				$vals['show_caption'] = true;
			
			if ( isset( $input['lightbox'] ) && '1' == $input['lightbox'] )
				$vals['lightbox'] = true;
			
			if ( isset( $input['type'] ) )
				$vals['type'] = $input['type'];
			if ( isset( $input['video-url'] ) && 'video' == $input['type'] )
				$vals['video-url'] = esc_url( $input['video-url'] );
			if ( isset( $input['image-id'] ) && 'other-image' == $input['type'] )
				$vals['image-id'] = $input['image-id'];
			
			$tmp = update_post_meta( $post_id, 'slide-content-meta', $vals );
		}
		
		/**
		 * Register the appropriate taxonomy to organize slideshows
		 */
		function register_tax() {
			$labels = apply_filters( 'robust-slideshow-taxonomy-labels', array(
				'name' => _x( 'Slideshows', 'taxonomy general name' ),
				'singular_name' => _x( 'Slideshow', 'taxonomy singular name' ),
				'search_items' =>  __( 'Search Slideshows' ),
				'all_items' => __( 'All Slideshows' ),
				'parent_item' => __( 'Parent Slideshow' ),
				'parent_item_colon' => __( 'Parent Slideshow:' ),
				'edit_item' => __( 'Edit Slideshow' ), 
				'update_item' => __( 'Update Slideshow' ),
				'add_new_item' => __( 'Add New Slideshow' ),
				'new_item_name' => __( 'New Slideshow Name' ),
				'menu_name' => __( 'Edit Slideshows' ),
			) );
			$args = apply_filters( 'robust-slideshow-taxonomy-args', array(
				'hierarchical' => true, 
				'labels'       => $labels, 
				'public'       => false, 
				'show_ui'      => true, 
				'query_var'    => 'robust-slideshow', 
				'rewrite'       => array(
					'slug'       => 'slideshow', 
					'with_front' => false, 
					'feeds'      => false, 
					'pages'      => false, 
				), 
			) );
			
			add_action( 'robust-slideshow_edit_form_fields', array( $this, 'slideshow_edit_fields' ) );
			add_action( 'robust-slideshow_add_form_fields', array( $this, 'slideshow_add_fields' ) );
			add_action( 'get_robust-slideshow', array( $this, 'get_slideshow_meta' ) );
			add_action( 'created_term', array( $this, 'save_slideshow_term' ) );
			add_action( 'edited_term', array( $this, 'save_slideshow_term' ) );
			
			register_taxonomy( 'robust-slideshow', array( 'robust-slide' ), $args );
		}
		
		/**
		 * Output the extra fields for editing a slideshow term
		 */
		function slideshow_edit_fields( $term ) {
			wp_enqueue_script( 'edit-robust-slideshow' );
			$sizes = get_intermediate_image_sizes();
?>
		<tr>
        	<th scope="col" colspan="2">
            	<h3><?php _e( 'Slideshow Dimensions' ) ?></h3>
				<?php wp_nonce_field( 'slideshow-dim-fields', '_rs_dim_nonce' ) ?>
            </th>
        </tr>
        <tr class="form-field">
        	<th scope="row" valign="top">
				<label for="tag-image-size"><?php _e( 'Image size:' ) ?></label>
            </th>
            <td>
                <select name="image-size" id="tag-image-size">
                    <option value=""<?php selected( $term->size, null ) ?>><?php _e( 'Custom dimensions (specified below)' ) ?></option>
<?php
			foreach ( $sizes as $size ) {
?>
                    <option value="<?php echo esc_attr( $size ) ?>"<?php selected( $term->size, $size ) ?>><?php echo $size ?></option>
<?php
			}
?>
                </select>
            </td>
        </tr>
        <tr>
        	<td colspan="2">
				<p><em><?php _e( 'If you specify custom dimensions below, be sure to choose the "Custom dimensions" option above, otherwise, the custom dimensions will not be saved.' ) ?></em></p>
            </td>
        </tr>
		<tr class="form-field">
			<th scope="row" valign="top">
            	<label for="width"><?php _e( 'Slideshow Width' ) ?></label>
            </th>
			<td>
            	<input name="width" id="width" type="number" min="0" max="2500" value="<?php echo $term->width ?>" />
            </td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
            	<label for="height"><?php _e( 'Slideshow Height' ) ?></label>
            </th>
			<td>
            	<input name="height" id="height" type="number" min="0" max="2500" value="<?php echo $term->height ?>" />
            </td>
		</tr>
		<tr class="form-field hide-after">
			<th scope="row" valign="top">
            	<label for="crop"><?php _e( 'Crop images to these exact dimensions?' ) ?></label>
            </th>
			<td>
            	<input type="checkbox" name="crop" id="crop" value="1"<?php checked( $term->crop ) ?> />
            </td>
		</tr>
		<tr>
        	<th scope="col" colspan="2">
            	<h3><?php _e( 'Slideshow Appearance' ) ?></h3>
            </th>
        </tr>
		<tr>
        	<th scope="col" colspan="2">
            	<h3><?php _e( 'Slideshow Behavior' ) ?></h3>
            </th>
        </tr>
		<tr class="form-field">
			<th scope="row" valign="top">
            	<label for="animation"><?php _e( 'Slide transition:' ) ?></label>
            </th>
			<td>
            	<select name="animation" id="animation">
                	<option value="fade"<?php selected( $term->animation, 'fade' ) ?>><?php _e( 'Fade' ) ?></option>
                    <option value="slide-horizontal"<?php selected( $term->animation, 'slide-horizontal' ) ?>><?php _e( 'Slide horizontally' ) ?></option>
                    <option value="slide-vertical"<?php selected( $term->animation, 'slide-vertical' ) ?>><?php _e( 'Slide vertically' ) ?></option>
                </select>
            </td>
		</tr>
        <tr class="form-field">
        	<th scope="row" valign="top">
            	<label for="slideshowSpeed"><?php _e( 'How long should each slide appear before transitioning to the next?' ) ?></label>
            </th>
            <td>
            	<input type="number" name="slideshowSpeed" id="slideshowSpeed" value="<?php echo intval( $term->slideshowSpeed ) ?>" />
                <p><em>Please specify a number in milliseconds (1000 milliseconds = 1 second)</em></p>
            </td>
        </tr>
        <tr class="form-field">
        	<th scope="row" valign="top">
            	<label for="animationSpeed"><?php _e( 'How long should the animation between slides last?' ) ?></label>
            </th>
            <td>
            	<input type="number" name="animationSpeed" id="animationSpeed" value="<?php echo intval( $term->animationSpeed ) ?>" />
                <p><em>Please specify a number in milliseconds (1000 milliseconds = 1 second)</em></p>
            </td>
        </tr>
		<tr class="form-field">
			<th scope="row" valign="top">
            	<label for="randomize"><?php _e( 'Randomize the order of the slides?' ) ?></label>
            </th>
			<td>
            	<input type="checkbox" name="randomize" id="randomize" value="1"<?php checked( $term->randomize ) ?> />
            </td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
            	<label for="pauseOnHover"><?php _e( 'Pause the slideshow when someone hovers over a slide?' ) ?></label>
            </th>
			<td>
            	<input type="checkbox" name="pauseOnHover" id="pauseOnHover" value="1"<?php checked( $term->pauseOnHover ) ?> />
            </td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
            	<label for="controlNav"><?php _e( 'Show indicators of how many slides are in the slideshow?' ) ?></label>
            </th>
			<td>
            	<input type="checkbox" name="controlNav" id="controlNav" value="1"<?php checked( $term->controlNav ) ?> />
            </td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
            	<label for="directionNav"><?php _e( 'Show left/right arrows to advance/reverse slides?' ) ?></label>
            </th>
			<td>
            	<input type="checkbox" name="directionNav" id="directionNav" value="1"<?php checked( $term->directionNav ) ?> />
            </td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
            	<label for="pausePlay"><?php _e( 'Show pause/play buttons?' ) ?></label>
            </th>
			<td>
            	<input type="checkbox" name="pausePlay" id="pausePlay" value="1"<?php checked( $term->pausePlay ) ?> />
            </td>
		</tr>
<?php
		}
		
		/**
		 * Output the extra fields for adding a new slideshow term
		 */
		function slideshow_add_fields( $term ) {
			wp_enqueue_script( 'edit-robust-slideshow' );
			$sizes = get_intermediate_image_sizes();
?>
<h3><?php _e( 'Slideshow Dimensions' ) ?></h3>
<p><em><?php _e( 'If you create a new image size, you may need to regenerate your image thumbnails before the new size will work properly within the slideshow. If you do not have the <a href="http://wordpress.org/extend/plugins/regenerate-thumbnails/">Regenerate Thumbnails</a> plugin installed, you should install and activate it.' ) ?></em></p>
<?php wp_nonce_field( 'slideshow-dim-fields', '_rs_dim_nonce' ) ?>
<div class="form-field">
	<label for="tag-image-size"><?php _e( 'Image size:' ) ?></label>
    <select name="image-size" id="tag-image-size">
    	<option value=""<?php selected( $term->size, null ) ?>><?php _e( 'Custom dimensions (specified below)' ) ?></option>
<?php
			foreach ( $sizes as $size ) {
?>
        <option value="<?php echo esc_attr( $size ) ?>"<?php selected( $term->size, $size ) ?>><?php echo $size ?></option>
<?php
			}
?>
    </select>
</div>
<p><em><?php _e( 'If you specify custom dimensions below, be sure to choose the "Custom dimensions" option above, otherwise, the custom dimensions will not be saved. Once you set the custom dimensions and save the term, those custom dimensions will be used to create a new image size.' ) ?></em></p>
<div class="form-field">
	<label for="tag-width"><?php _e( 'Slideshow Width' ); ?></label>
	<input name="width" id="tag-width" type="number" min="0" max="2500" value="" />
</div>
<div class="form-field">
	<label for="tag-height"><?php _e( 'Slideshow Height' ); ?></label>
	<input name="height" id="tag-height" type="number" min="0" max="2500" value="" />
</div>
<div class="form-field hide-after">
    <label for="tag-crop"><?php _e( 'Crop images to these exact dimensions?' ) ?></label>
		<input type="checkbox" name="crop" id="tag-crop" value="1" />
</div>
<h3><?php _e( 'Slideshow Behavior' ) ?></h3>
<div class="form-field">
	<label for="animation"><?php _e( 'Slide transition:' ) ?></label>
        <select name="animation" id="animation">
            <option value="fade"<?php selected( $this->slideshow_defaults['animation'], 'fade' ) ?>><?php _e( 'Fade' ) ?></option>
            <option value="slide-horizontal"<?php selected( $this->slideshow_defaults['animation'], 'slide-horizontal' ) ?>><?php _e( 'Slide horizontally' ) ?></option>
            <option value="slide-vertical"<?php selected( $this->slideshow_defaults['animation'], 'slide-vertical' ) ?>><?php _e( 'Slide vertically' ) ?></option>
        </select>
</div>
<div class="form-field">
	<label for="slideshowSpeed"><?php _e( 'How long should each slide appear before transitioning to the next?' ) ?></label>
        <input type="number" name="slideshowSpeed" id="slideshowSpeed" value="<?php echo intval( $this->slideshow_defaults['slideshowSpeed'] ) ?>" />
        <p><em>Please specify a number in milliseconds (1000 milliseconds = 1 second)</em></p>
</div>
<div class="form-field">
	<label for="animationSpeed"><?php _e( 'How long should the animation between slides last?' ) ?></label>
        <input type="number" name="animationSpeed" id="animationSpeed" value="<?php echo intval( $this->slideshow_defaults['animationSpeed'] ) ?>" />
        <p><em>Please specify a number in milliseconds (1000 milliseconds = 1 second)</em></p>
</div>
<div class="form-field">
	<label for="randomize"><?php _e( 'Randomize the order of the slides?' ) ?></label>
        <input type="checkbox" name="randomize" id="randomize" value="1"<?php checked( $this->slideshow_defaults['randomize'] ) ?> />
</div>
<div class="form-field">
	<label for="pauseOnHover"><?php _e( 'Pause the slideshow when someone hovers over a slide?' ) ?></label>
        <input type="checkbox" name="pauseOnHover" id="pauseOnHover" value="1"<?php checked( $this->slideshow_defaults['pauseOnHover'] ) ?> />
</div>
<div class="form-field">
	<label for="controlNav"><?php _e( 'Show indicators of how many slides are in the slideshow?' ) ?></label>
        <input type="checkbox" name="controlNav" id="controlNav" value="1"<?php checked( $this->slideshow_defaults['controlNav'] ) ?> />
</div>
<div class="form-field">
	<label for="directionNav"><?php _e( 'Show left/right arrows to advance/reverse slides?' ) ?></label>
        <input type="checkbox" name="directionNav" id="directionNav" value="1"<?php checked( $this->slideshow_defaults['directionNav'] ) ?> />
</div>
<div class="form-field">
	<label for="pausePlay"><?php _e( 'Show pause/play buttons?' ) ?></label>
        <input type="checkbox" name="pausePlay" id="pausePlay" value="1"<?php checked( $this->slideshow_defaults['pausePlay'] ) ?> />
</div>
<?php
		}
		
		/**
		 * Retrieve additional meta information about a specific term
		 */
		function get_slideshow_meta( $term ) {
			$tmp = get_option( 'slideshow-term-opts-' . $term->term_id, array() );
			$tmp = array_merge( $this->slideshow_defaults, $tmp );
			
			foreach ( $tmp as $k => $v ) {
				$term->$k = $v;
			}
			
			return $term;
		}
		
		/**
		 * Save any changes made to slideshow data
		 */
		function save_slideshow_term( $term_id ) {
			if ( ! wp_verify_nonce( $_POST['_rs_dim_nonce'], 'slideshow-dim-fields' ) )
				return $term_id;
			
			$opts = array();
			if ( isset( $_POST['image-size'] ) && ! empty( $_POST['image-size'] ) ) {
				$opts['size'] = $_POST['image-size'];
				$opts['width'] = null;
				$opts['height'] = null;
			} else {
				$opts['size'] = null;
				if ( isset( $_POST['width'] ) && intval( $_POST['width'] ) )
					$opts['width'] = intval( $_POST['width'] );
				if ( isset( $_POST['height'] ) && intval( $_POST['height'] ) )
					$opts['height'] = intval( $_POST['height'] );
				$opts['crop'] = isset( $_POST['crop'] ) && '1' == $_POST['crop'] ? true : false;
			}
			
			if ( isset( $opts['height'] ) && isset( $opts['width'] ) ) {
				$sizes = get_option( 'robust-slideshow-sizes', array() );
				delete_option( 'robust-slideshow-size' );
				$term = get_term( $term_id, 'robust-slideshow' );
				$sizes[$term->slug] = array( 'width' => $opts['width'], 'height' => $opts['height'], 'crop' => $opts['crop'] );
				update_option( 'robust-slideshow-sizes', $sizes );
				$sizename = sprintf( 'robust-slideshow-%s-%sx%s%s', $term->slug, $opts['width'], $opts['height'], ( $opts['crop'] ? '-cropped' : '' ) ); 
				$opts['width'] = $opts['height'] = $opts['crop'] = null;
				$opts['size'] = $sizename;
			}
			
			$cb = array( 'randomize', 'pauseOnHover', 'controlNav', 'directionNav', 'pausePlay' );
			foreach ( $cb as $k ) {
				$opts[$k] = isset( $_POST[$k] ) && '1' == $_POST[$k];
			}
			$opts['animation'] = $_POST['animation'];
			if ( empty( $opts['animation'] ) )
				$opts['animation'] = 'fade';
			$opts['slideshowSpeed'] = isset( $_POST['slideshowSpeed'] ) && is_numeric( $_POST['slideshowSpeed'] ) ? intval( $_POST['slideshowSpeed'] ) : null;
			$opts['animationSpeed'] = isset( $_POST['animationSpeed'] ) && is_numeric( $_POST['animationSpeed'] ) ? intval( $_POST['animationSpeed'] ) : null;
			
			update_option( 'slideshow-term-opts-' . $term_id, $opts );
		}
		
		/**
		 * Do the slideshow shortcode
		 */
		function do_shortcode( $atts ) {
			$atts = shortcode_atts( array( 'id' => null ), $atts );
			if ( empty( $atts['id'] ) )
				return;
			
			if ( is_numeric( $atts['id'] ) )
				$term = get_term( $atts['id'], 'robust-slideshow' );
			else
				$term = get_term_by( 'slug', $atts['id'], 'robust-slideshow' );
			
			$term->pauseOnAction = true;
			
			if ( empty( $term ) )
				return;
			
			$slide_query = new WP_Query( array( 'robust-slideshow' => $term->slug, 'post_type' => 'robust-slide', 'posts_per_page' => -1, 'orderby' => 'menu_order' ) );
			if ( ! $slide_query->have_posts() )
				return;
			
			$slide_size = empty( $term->size ) ? array( $term->width, $term->height ) : $term->size;
			if ( ! isset( $term->width ) || ! isset( $term->height ) ) {
				$tmp = preg_replace( '/robust\-slideshow\-.+\-(\d+)x(\d+)/', '$1x$2', $slide_size );
				$tmp = str_replace( '-cropped', '', $tmp );
				list( $term->width, $term->height ) = explode( 'x', $tmp );
			}
			
			$rt = '<div class="flexslider"><ul class="robust-slideshow slides">';
			while( $slide_query->have_posts() ) : $slide_query->the_post();
				global $more, $post;
				$oldmore = $more;
				$more = 0;
				$meta = get_post_meta( get_the_ID(), 'slide-content-meta', true );
				$meta['show_title'] = in_array( $meta['show_title'], array( '1', 1, true, 'true' ) );
				$meta['show_caption'] = in_array( $meta['show_caption'], array( '1', 1, true, 'true' ) );
				$rt .= '<li class="robust-slide"><figure id="slide-' . esc_attr( $post->post_name ) . '">';
				if ( 'whole' == $meta['link'] ) {
					$rt .= sprintf( '<a href="%1$s"%2$s>', get_permalink(), ( $meta['lightbox'] ? ' class="nyroModal"' : '' ) );
				}
				switch( $meta['type'] ) {
					case 'video' :
						if ( ! class_exists( 'WP_oEmbed' ) )
							require_once( ABSPATH . WPINC . '/class-oembed.php' );
						
						$meta['video-url'] = add_query_arg( 'wmode', 'transparent', $meta['video-url'] );
						/*$meta['video-url'] = esc_url( $meta['video-url'] );*/
						
						$e = new WP_oEmbed;
						/*$rt .= "\n<!-- Preparing to oEmbed the following URL: " . $meta['video-url'] . " -->\n";*/
						$rt .= $e->get_html( $meta['video-url'], array( 'width' => ( $term->width * .5 ), 'height' => $term->height ) );
						break;
					case 'text' :
						break;
					case 'other-image' :
						if ( array_key_exists( 'image-id', $meta ) && ! empty( $meta['image-id'] ) ) {
							$rt .= wp_get_attachment_image( $meta['image-id'], $slide_size );
							break;
						}
					case '' :
					default : 
						if ( has_post_thumbnail() )
							$rt .= get_the_post_thumbnail( get_the_ID(), $slide_size );
						
						break;
				}
				
				if ( ( $meta['show_title'] && ! empty( $post->post_title ) ) || ( $meta['show_caption'] && ! empty( $post->post_content ) ) ) {
					$rt .= '<figcaption class="flex-caption">';
					if ( 'content' == $meta['link'] ) {
						$rt .= sprintf( '<a href="%1$s"%2$s>', get_permalink(), ( $meta['lightbox'] ? ' class="nyroModal"' : '' ) );
					}
					if ( $meta['show_title'] && ! empty( $post->post_title ) ) {
						$rt .= '<h1>';
						if ( 'title' == $meta['link'] ) {
							$rt .= sprintf( '<a href="%1$s"%2$s>', get_permalink(), ( $meta['lightbox'] ? ' class="nyroModal"' : '' ) );
						}
						$rt .= get_the_title();
						if ( 'title' == $meta['link'] ) {
							$rt .= '</a>';
						}
						$rt .= '</h1>';
					}
					if ( $meta['show_caption'] && ! empty( $post->post_content ) ) {
						$rt .= '<div>';
						if ( 'caption' == $meta['link'] ) {
							$rt .= sprintf( '<a href="%1$s"%2$s>', get_permalink(), ( $meta['lightbox'] ? ' class="nyroModal"' : '' ) );
						}
						$rt .= get_the_content( 'Read more' );
						if ( 'caption' == $meta['link'] ) {
							$rt .= '</a>';
						}
						$rt .= '</div>';
					}
					
					if ( 'content' == $meta['link'] ) {
						$rt .= '</a>';
					}
					$rt .= '</figcaption>';
				}
				
				if ( 'whole' == $meta['link'] ) {
					$rt .= '</a>';
				}
				
				$rt .= '</figure></li>';
			endwhile;
			$rt .= '</ul></div>';
			wp_reset_postdata();
			$more = $oldmore;
			
			$term->script_origin = get_bloginfo( 'siteurl' );
			
			wp_enqueue_style( 'robust-slideshow' );
			wp_localize_script( 'robust-slideshow', 'slideshowOpts', (array) $term );
			wp_enqueue_script( 'robust-slideshow' );
			add_action( 'wp_footer', array( $this, 'video_modal' ), 99 );
			/*wp_enqueue_style( 'anything-theme' );
			wp_enqueue_script( 'robust-slideshow' );
			wp_enqueue_style( 'robust-slideshow' );*/
			
			return $rt;
		}
		
		/**
		 * Video modal area
		 */
		function video_modal() {
?>
      <div id="video-modal" class="modal hide " tabindex="-1" role="dialog">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">X</button>
          <h3></h3>
        </div>
        <div class="modal-body">
          <p></p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true">Close</button>
        </div>
      </div>
<?php
		}
	}
}