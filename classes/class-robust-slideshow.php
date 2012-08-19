<?php
/**
 * Define the main class for the Robust Slideshow plugin
 */
if ( ! class_exists( 'robust_slideshow' ) ) {
	class robust_slideshow {
		/**
		 * Create the robust_slideshow object
		 * @uses add_action() to add the robust_slideshow::register_types & 
		 * 		robust_slideshow::register_tax methods to the init action
		 */
		function __construct() {
			add_action( 'init', array( $this, 'register_types' ) );
			add_action( 'init', array( $this, 'register_tax' ) );
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
				'supports'      => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' )
			) );
			
			register_post_type( 'robust-slide', $args );
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
			
			add_action( 'admin_menu', array( $this, 'slideshow_admin_menu' ) );
			
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
	}
}