<?php
/*
 * Text Domain: creare-wp-address-schema
 */

if(!class_exists('WP_Address_Schema_Posttype'))
{
    class WP_Address_Schema_Posttype
    {
        const POST_TYPE = "wp_address_schema";
		const TEXT_DOMAIN = 'wp-address-schema';
		private $global;
        private $_meta  = array(
			'company',
            'address1',
            'address2',
            'address3',
			'city',
			'county',
			'country',
			'postcode',
			'telephone',
			'fax', 
			'monday', 'monday_from', 'monday_to',
			'tuesday', 'tuesday_from', 'tuesday_to',
			'wednesday', 'wednesday_from', 'wednesday_to',
			'thursday', 'thursday_from', 'thursday_to',
			'friday', 'friday_from', 'friday_to',
			'saturday', 'saturday_from', 'saturday_to',
			'sunday', 'sunday_from', 'sunday_to',
			'seperate'
        );
		protected $inc_dir;
		protected $plugin_dir;
		protected $plugin_url;
		
		public function __construct()
		{
			add_action('init', array(&$this, 'init'));
			add_action('admin_init', array(&$this, 'admin_init'));
			// Add to admin_init function
			$this->global = $GLOBALS;
			// Set constants
			$this->inc_dir = ABSPATH.'/wp-content/plugins/WP-Address-Schema/inc/';
			$this->plugin_dir = ABSPATH.'/wp-content/plugins/WP-Address-Schema/';
			$this->plugin_url = plugins_url().'/WP-Address-Schema/';
		}
		
		public function init()
		{
			$this->create_post_type();
			add_action('save_post', array(&$this, 'save_post'));
			// Custom post type columns
			add_filter('manage_wp_address_schema_posts_columns', array(&$this, 'add_new_wp_address_schema_columns'));
			add_action('manage_wp_address_schema_posts_custom_column' , array(&$this, 'render_new_wp_address_schema_columns'), 10, 2);
			add_filter('post_row_actions', array(&$this, 'remove_post_row_actions'));
			// Yoast Column removal
			add_filter('wpseo_use_page_analysis', '__return_false');
			add_action('admin_enqueue_scripts', array(&$this, 'add_admin_css'));
			// Confirmation Message Filter
			add_filter('post_updated_messages', array(&$this, 'custom_admin_messages'));
		}
		
		public function add_admin_css($hook_suffix) {
			$typenow = $this->global['typenow']; 
			if ($typenow==self::POST_TYPE) {
				wp_register_style('address_schema_admin_css', $this->plugin_url.'css/admin.css', false, '1.0.0' );
       			wp_enqueue_style('address_schema_admin_css' );
			}
		}
		
		public function add_new_wp_address_schema_columns($columns) {
			$new_columns['title'] = _x('Address Title', 'column name');
			$new_columns['shortcode'] = _x('Shortcode', 'column name');
		   	return $new_columns;
		}
		
		public function render_new_wp_address_schema_columns($column,$post_id) {
			switch ( $column ) {
				case 'shortcode' :
					echo '[address_schema address="wp_as_'.$post_id.'"]';
					break;
			}
		}
		
		public function remove_permalinks($in){
			$post = $this->global['post'];
			if($post->post_type == self::POST_TYPE) {
				$out = preg_replace('~<div id="edit-slug-box".*</div>~Ui', '', $in);
			}
			return '';
		}
		
		public function remove_post_row_actions($actions) {
			
			if( get_post_type() === self::POST_TYPE ) {
				unset( $actions['inline hide-if-no-js'] );
			}
			return $actions;
	
		}
		
		public function create_post_type()
		{
			// register_post_type( $post_type, $args )
			register_post_type(self::POST_TYPE,
				array(
					'labels' => array(
						'name' => __('Addresses', self::TEXT_DOMAIN),
						'singular_name' => __('Address', self::TEXT_DOMAIN), 
						'all_items' => __('All Addresses', self::TEXT_DOMAIN),
						'add_new' => _x('Add Address', 'address', self::TEXT_DOMAIN),
						'add_new_item' => _x('Add Address', 'address', self::TEXT_DOMAIN),
						'edit_item' => _x('Edit Address', 'address', self::TEXT_DOMAIN),
						'new_item' => _x('New Address', 'address', self::TEXT_DOMAIN),
						'view_item' => _x('View Address', 'address', self::TEXT_DOMAIN),
						'search_item' => _x('Search Addresses', 'address', self::TEXT_DOMAIN),
						'not_found' => __("We're sorry, no addresses were found", self::TEXT_DOMAIN),
						'not_found_in_trash' => __('No addresses found in the trash', self::TEXT_DOMAIN),
					),
					'public' => false,
					'exlude_from_search' => true,
					'publicly_queryable' => false,
					'query_var' => false,
					'show_in_nav_menus'	=> false,
					'show_ui' => true,
					'capability_type' => 'page',
					'show_in_menu' => true,
					'menu_icon' => 'dashicons-location',
					'show_in_admin_bar' => false,
					'menu_position' => 20,
					'has_archive' => false,
					'rewrite' => false,
					'description' => __("A post type to contain different addresses for this site."),
					'supports' => array('title')
				)
			);
		}
		
		public function admin_init()
		{
			add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
		}
		
		public function custom_admin_messages($messages) {
			$post = $this->global['post'];
			$post_ID = $this->global['post_ID'];
			
			$post_type = get_post_type( $post_ID );
			$obj = get_post_type_object($post_type);
			
			$singular = $obj->labels->singular_name;
			
			$viewLink = ($obj->public) ?  ' <a href="%s">View '.strtolower($singular).'</a>' : "";
			$previewLink = ($obj->public) ? ' <a target="_blank" href="%s">Preview '.strtolower($singular).'</a>': "";
			$schedPreviewLink = ($obj->public) ? ' <a target="_blank" href="%2$s">Preview '.strtolower($singular).'</a>': "";
			
			$messages[$post_type] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => sprintf( __($singular.' updated.'.$viewLink), esc_url( get_permalink($post_ID) ) ),
			);
			return $messages;
		}

		
		public function add_meta_boxes()
		{
			//add_meta_box( $id, $title, $callback, $post_type, $context,$priority, $callback_args );
			add_meta_box( 
				sprintf('wp_address_schema_template_%s_address_section', self::POST_TYPE),
				_x('Address', 'address', self::TEXT_DOMAIN),
				array(&$this, 'add_inner_meta_box_address'),
				self::POST_TYPE
			);     
			add_meta_box( 
				sprintf('wp_address_schema_template_%s_openinghours_section', self::POST_TYPE),
				_x('Opening Hours', 'opening_hours', self::TEXT_DOMAIN),
				array(&$this, 'add_inner_meta_box_openinghours'),
				self::POST_TYPE
			);    
			add_meta_box( 
				sprintf('wp_address_schema_template_%s_options_section', self::POST_TYPE),
				_x('HTML Display Options', 'options', self::TEXT_DOMAIN),
				array(&$this, 'add_inner_meta_box_options'),
				self::POST_TYPE
			);  
			
			// Removal of standard/Yoast Metaboxes
			remove_meta_box('submitdiv', self::POST_TYPE, 'core');
			remove_meta_box('slugdiv', self::POST_TYPE, 'core');     
			remove_meta_box( 'wpseo_meta', self::POST_TYPE, 'normal' );   
			
			// Readd the Submit metabox
			add_meta_box(
				'submitdiv', 
				_x('Publish', 'publish', self::TEXT_DOMAIN),
				array(&$this, 'custom_post_submit_meta_box'),
				self::POST_TYPE
			);

		}
		
		public function add_inner_meta_box_address($post)
		{       
			// Render the metabox
			include('metaboxes/wp-address-schema-posttype-address.php');         
		}
		
		public function add_inner_meta_box_openinghours($post)
		{       
			// Render the metabox
			include('metaboxes/wp-address-schema-posttype-openinghours.php');         
		}
		
		public function add_inner_meta_box_options($post)
		{       
			// Render the metabox
			include('metaboxes/wp-address-schema-posttype-options.php');         
		}
		
		public function custom_post_submit_meta_box($post)
		{
			// Render the metabox
			include('metaboxes/wp-address-schema-posttype-submit.php'); 
		}
		
		public function save_post($post_id)
		{
			if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			{
				return;
			}
		
			if($_POST['post_type'] == self::POST_TYPE && current_user_can('edit_post', $post_id))
			{
				foreach($this->_meta as $field_name)
				{
					update_post_meta($post_id, $field_name, $_POST[$field_name]);
				}
			}
			else
			{
				return;
			}
		}
    } 
} 