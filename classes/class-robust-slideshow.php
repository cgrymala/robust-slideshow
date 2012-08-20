<?php
/**
 * Define the main class for the Robust Slideshow plugin
 */
if ( ! class_exists( 'robust_slideshow' ) ) {
	class robust_slideshow {
		var $slideshow_defaults = array(
			'size'   => null, 
			'width'  => 0, 
			'height' => 0, 
			'crop'   => false, 
		);
		
		/**
		 * Create the robust_slideshow object
		 * @uses add_action() to add the robust_slideshow::register_types & 
		 * 		robust_slideshow::register_tax methods to the init action
		 */
		function __construct() {
			add_action( 'init', array( $this, 'register_types' ) );
			add_action( 'init', array( $this, 'register_tax' ) );
			if ( is_admin() ) {
				wp_register_script( 'jquery-bbq', plugins_url( '/js/jquery-bbq/jquery.ba-bbq.min.js', dirname( __FILE__ ) ), array( 'jquery' ), '1.3-pre', true );
				wp_register_script( 'robust-slideshow-metabox-script', plugins_url( '/js/metabox-scripts.js', dirname( __FILE__ ) ), array( 'jquery-bbq' ), '0.1.28', true );
				wp_register_style( 'robust-slideshow-admin-styles', plugins_url( '/css/admin-styles.css', dirname( __FILE__ ) ), '0.1.5', array(), 'all' );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_style' ) );
			} else {
				wp_register_style( 'flex-slider', plugins_url( '/js/flex-slider/flexslider.css', dirname( __FILE__ ) ), array(), '2.1', 'all' );
				wp_register_script( 'flex-slider', plugins_url( '/js/flex-slider/jquery.flexslider-min.js', dirname( __FILE__ ) ), array( 'jquery' ), '2.1', true );
				wp_register_script( 'robust-slideshow', plugins_url( '/js/init-slideshow.js', dirname( __FILE__ ) ), array( 'flex-slider' ), '0.1', true );
			}
			
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
		 * Enqueue the stylesheet for the admin area
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
				'capability_type' => 'page',
				'has_archive'   => false, 
				'hierarchical'  => false,
				'menu_position' => null,
				'supports'      => array( 'title', 'editor', 'author', 'thumbnail' )
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
?>
<p><label for="slide-content-type"><?php _e( 'What type of item should display in the background of the slide?' ) ?></label> 
	<select class="widefat" name="robust[type]" id="slide-content-type">
    	<option value=""<?php selected( $meta_vals['type'], null ) ?>><?php _e( 'Use the featured image for this post' ) ?></option>
        <option value="video"<?php selected( $meta_vals['type'], 'video' ) ?>><?php _e( 'Embed the video specified below' ) ?></option>
        <option value="text"<?php selected( $meta_vals['type'], 'text' ) ?>><?php _e( 'Just display the content of this post' ) ?></option>
        <option value="other-image"<?php selected( $meta_vals['type'], 'other-image' ) ?>><?php _e( 'Use an image from the media library' ) ?></option>
    </select></p>
<h4><?php _e( 'Video Information' ) ?></h4>
<p><?php _e( 'If the video option is selected above, please specify the URL to the video that should be embedded' ) ?><br />
	<label for="slide-video-url"><?php _e( 'Video URL' ) ?></label>
    	<input type="url" class="widefat" name="robust[video-url]" id="slide-video-url" value="<?php echo esc_url( $meta_vals['video-url'] ) ?>" /></p>
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
			if ( ! current_user_can( 'edit_robust-slide', $post_id ) )
				return/* wp_die( 'Wrong permissions' )*/;
				
			if ( ! wp_verify_nonce( $_POST['_slide_content_nonce'], 'slide-content-meta' ) )
				return/* wp_die( 'Nonce not verified' )*/;
			
			if ( ! isset( $_POST['robust'] ) || empty( $_POST['robust'] ) || ! is_array( $_POST['robust'] ) )
				return/* wp_die( 'Form not complete' )*/;
			
			$vals = array();
			$input = $_POST['robust'];
			if ( isset( $input['type'] ) )
				$vals['type'] = $input['type'];
			if ( isset( $input['video-url'] ) && 'video' == $input['type'] )
				$vals['video-url'] = esc_url( $input['video-url'] );
			if ( isset( $input['image-id'] ) && 'other-image' == $input['type'] )
				$vals['image-id'] = $input['image-id'];
			
			update_post_meta( $post_id, 'slide-content-meta', $input );
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
				'menu_name' => __( 'Slideshow' ),
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
			
			/*add_action( 'admin_menu', array( $this, 'slideshow_admin_menu' ) );*/
			add_action( 'robust-slideshow_edit_form_fields', array( $this, 'slideshow_edit_fields' ) );
			add_action( 'robust-slideshow_add_form_fields', array( $this, 'slideshow_add_fields' ) );
			add_action( 'get_robust-slideshow', array( $this, 'get_slideshow_meta' ) );
			add_action( 'created_term', array( $this, 'save_slideshow_term' ) );
			add_action( 'edited_term', array( $this, 'save_slideshow_term' ) );
			
			register_taxonomy( 'robust-slideshow', array( 'robust-slide' ), $args );
		}
		
		/**
		 * Add a link to the Slides admin menu to manage slideshow terms
		 */
		function slideshow_admin_menu() {
			add_submenu_page( 'edit.php?post_type=robust-slide', __( 'Robust Slideshows' ), __( 'Slideshows' ), 'edit_pages', 'edit_robust_slideshows', array( $this, 'slideshow_edit' ) );
		}
		
		/**
		 * Output the page used to edit slideshows
		 */
		function slideshow_edit() {
?>
<div class="wrap">
<?php screen_icon(); ?>
<h2><?php echo $tax->labels->edit_item; ?></h2>
<div id="ajax-response"></div>
<p>This is where you will edit slideshows in the future</p>
</div>
<?php
		}
		
		/**
		 * Output the extra fields for editing a slideshow term
		 */
		function slideshow_edit_fields( $term ) {
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
		<tr class="form-field">
			<th scope="row" valign="top">
            	<label for="crop"><?php _e( 'Crop images to these exact dimensions?' ) ?></label>
            </th>
			<td>
            	<input type="checkbox" name="crop" id="crop" value="1"<?php checked( $term->crop ) ?> />
            </td>
		</tr>
<?php
		}
		
		/**
		 * Output the extra fields for adding a new slideshow term
		 */
		function slideshow_add_fields( $term ) {
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
<div class="form-field">
	<input type="checkbox" name="crop" id="tag-crop" value="1" />
    <label for="tag-crop"><?php _e( 'Crop images to these exact dimensions?' ) ?></label>
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
			
			update_option( 'slideshow-term-opts-' . $term_id, $opts );
		}
		
		/**
		 * Do the slideshow shortcode
		 */
		function do_shortcode( $atts ) {
			wp_enqueue_style( 'flex-slider' );
			wp_enqueue_script( 'robust-slideshow' );
			
			$atts = shortcode_atts( array( 'id' => null ), $atts );
			if ( empty( $atts['id'] ) )
				return;
			
			if ( is_numeric( $atts['id'] ) )
				$term = get_term( $atts['id'], 'robust-slideshow' );
			else
				$term = get_term_by( 'slug', $atts['id'], 'robust-slideshow' );
			
			if ( empty( $term ) )
				return;
			
			$slides = get_posts( array( 'robust-slideshow' => $term->slug, 'post_type' => 'robust-slide', 'numberposts' => -1 ) );
			if ( empty( $slides ) )
				return;
			
			$slide_size = empty( $term->size ) ? array( $term->width, $term->height ) : $term->size;
			if ( ! isset( $term->width ) || ! isset( $term->height ) ) {
				$tmp = preg_replace( '/robust\-slideshow\-.+\-(\d+)x(\d+)/', '$1x$2', $slide_size );
				$tmp = str_replace( '-cropped', '', $tmp );
				list( $term->width, $term->height ) = explode( 'x', $tmp );
			}
			
			$rt = '<div class="flexslider"><ul class="robust-slideshow slides">';
			foreach ( $slides as $slide ) {
				$meta = get_post_meta( $slide->ID, 'slide-content-meta', true );
				$rt .= '<li class="robust-slide"><figure>';
				switch( $meta['type'] ) {
					case 'video' :
						if ( ! class_exists( 'WP_oEmbed' ) )
							require_once( ABSPATH . WPINC . '/class-oembed.php' );
						
						$e = new WP_oEmbed;
						$rt .= '<!-- Preparing to embed the video located at ' . $meta['video-url'] . ' with a width of ' . $term->width . ' and a height of ' . $term->height . ' -->';
						$rt .= $e->get_html( $meta['video-url'], array( 'width' => $term->width, 'height' => $term->height ) );
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
						if ( has_post_thumbnail( $slide->ID ) )
							$rt .= get_the_post_thumbnail( $slide->ID, $slide_size );
						
						break;
				}
				
				if ( ! empty( $slide->post_title ) || ! empty( $slide->post_content ) ) {
					$rt .= '<figcaption class="flex-caption">';
					if ( ! empty( $slide->post_title ) )
						$rt .= '<h1>' . apply_filters( 'the_title', $slide->post_title ) . '</h1>';
					if ( ! empty( $slide->post_content ) )
						$rt .= '<div>' . apply_filters( 'the_content', $slide->post_content ) . '</div>';
					$rt .= '</figcaption>';
				}
				
				$rt .= '</figure></li>';
			}
			$rt .= '</ul>';
			
			return $rt;
		}
	}
}