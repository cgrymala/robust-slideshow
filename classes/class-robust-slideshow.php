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
				wp_register_style( 'robust-slideshow-admin-styles', plugins_url( '/css/admin-styles.css', dirname( __FILE__ ) ), '0.1.5', array(), 'all' );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_style' ) );
			}
			
			add_shortcode( 'slideshow', array( $this, 'do_shortcode' ) );
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
			wp_nonce_field( 'slide-content-meta', '_slide_content_nonce' );
			$meta_vals = get_post_meta( 'slide-content-meta', true );
			if ( empty( $meta_vals ) )
				$meta_vals = array();
			$meta_vals = array_merge( apply_filters( 'robust-slideshow-default-meta', array(
				'type' => null, 
			) ), $meta_vals );
?>
<p><label for="slide-content-type"><?php _e( 'What type of item should display in the background of the slide?' ) ?></label> 
	<select class="widefat" name="type" id="slide-content-type">
    	<option value=""<?php selected( $meta_vals['type'], null ) ?>><?php _e( 'Use the featured image for this post' ) ?></option>
        <option value="video"<?php selected( $meta_vals['type'], 'video' ) ?>><?php _e( 'Embed the video specified below' ) ?></option>
        <option value="text"<?php selected( $meta_vals['type'], 'text' ) ?>><?php _e( 'Just display the content of this post' ) ?></option>
        <option value="other-image"<?php selected( $meta_vals['type'], 'other-image' ) ?>><?php _e( 'Use an image from the media library' ) ?></option>
    </select></p>
<h4><?php _e( 'Video Information' ) ?></h4>
<p><?php _e( 'If the video option is selected above, please specify the URL to the video that should be embedded' ) ?><br />
	<label for="slide-video-url"><?php _e( 'Video URL' ) ?></label>
    	<input type="url" class="widefat" name="video-url" id="slide-video-url" value="<?php echo esc_url( $meta_vals['video-url'] ) ?>" /></p>
<h4><?php _e( 'Image Choice' ) ?></h4>
<p><?php _e( 'If you chose to use an image from the media library (other than the featured image for this post), please choose the appropriate media item.' ) ?><br />
	<label for="slide-image-id"><?php _e( 'Image' ) ?></label>
    	<select class="widefat" name="image-id" id="slide-image-id">
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
		
		/**
		 * Save additional meta information about a slide post
		 */
		function save_post( $post_id ) {
			return $post_id;
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
<?php
		}
		
		/**
		 * Output the extra fields for adding a new slideshow term
		 */
		function slideshow_add_fields( $term ) {
			$sizes = get_intermediate_image_sizes();
?>
<h3><?php _e( 'Slideshow Dimensions' ) ?></h3>
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
<p><em><?php _e( 'If you specify custom dimensions below, be sure to choose the "Custom dimensions" option above, otherwise, the custom dimensions will not be saved.' ) ?></em></p>
<div class="form-field">
	<label for="tag-width"><?php _e( 'Slideshow Width' ); ?></label>
	<input name="width" id="tag-width" type="number" min="0" max="2500" value="" />
</div>
<div class="form-field">
	<label for="tag-height"><?php _e( 'Slideshow Height' ); ?></label>
	<input name="height" id="tag-height" type="number" min="0" max="2500" value="" />
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
			}
			
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
			
			if ( empty( $term ) )
				return;
			
			$slides = get_posts( array( 'robust-slideshow' => $term->slug, 'post_type' => 'robust-slide', 'numberposts' => -1 ) );
			if ( empty( $slides ) )
				return;
			
			$slide_size = empty( $term->size ) ? array( $term->width, $term->height ) : $term->size;
			
			$rt = '<ul class="robust-slideshow">';
			foreach ( $slides as $slide ) {
				$rt .= '<li class="robust-slide"><figure>';
				if ( has_post_thumbnail( $slide->ID ) ) {
					$rt .= get_the_post_thumbnail( $slide->ID, $slide_size );
					if ( ! empty( $slide->post_title ) || ! empty( $slide->post_content ) ) {
						$rt .= '<figcaption>';
						if ( ! empty( $slide->post_title ) )
							$rt .= '<h1>' . apply_filters( 'the_title', $slide->post_title ) . '</h1>';
						if ( ! empty( $slide->post_content ) )
							$rt .= '<div>' . apply_filters( 'the_content', $slide->post_content ) . '</div>';
						$rt .= '</figcaption>';
					}
				} else {
					/* Do some stuff that needs to happen for non-image slides */
				}
				$rt .= '</figure></li>';
			}
			$rt .= '</ul>';
			
			return $rt;
		}
	}
}