<?php

/*
Plugin Name: RDP eBook Builder
Plugin URI: http://robert-d-payne.com/
Description: Build books from wiki pages. Requires RDP Wiki Embed plugin.
Version: 0.1.0
Author: Robert D Payne
Author URI: http://robert-d-payne.com/
License: GPLv2 or later
*/


if ( ! defined( 'WPINC' ) ) {
    die;
}
// Turn off all error reporting
//error_reporting(E_ALL^ E_WARNING);


if (!class_exists('RDP_EBB_PLUGIN', FALSE)) {
    $dir = plugin_dir_path( __FILE__ );
    define('RDP_EBB_PLUGIN_BASEDIR', $dir);
    define('RDP_EBB_PLUGIN_BASEURL',plugins_url( null, __FILE__ ) );
    define('RDP_EBB_PLUGIN_BASENAME', plugin_basename(__FILE__));

    require_once 'bl/EBBUtilities.php';     
    
    class RDP_EBB_PLUGIN {
        public static $plugin_slug = 'rdp-ebook-builder'; 
        public static $options_name = 'rdp_ebb_options'; 
        public static $metadata_key = '_ebook_metadata';

        public static $version = '0.1.0';
        public static $paper_sizes = array('A4','Letter');
        private $_instanceBook = null;
        private $_options = array();        
        
        function __construct() {
            //  prevent running code unnecessarily
            if(RDP_EBB_Utilities::abortExecution())return;   
            
            $options = get_option( RDP_EBB_PLUGIN::$options_name );
            if(empty($options)) $options = self::default_settings();
            if(is_array($options))$this->_options = $options;  
            
            $this->load_dependencies();   
     
            add_action( 'init', 'RDP_EBB_PLUGIN::initialize' );
           
            
            // run the plugin
            add_action('wp_loaded',array( $this, 'run'),1);             
        }//__construct
        
        static function default_settings() {
            return array(
                'sOrder' => 'ASC',
                'sOrderBy' => 'title',                
                'books_per_rss' => '10',
                'sBookContentBeneathCover' => '',
                'fBookShowContentBeneathCover' => '1',
                'fShowEditors' => '1',
                'fShowCats' => '1',
                'fShowTags' => '1',
                'fBookShowCover' => '1',
                'fBookShowTitle' => '1',
                'fBookShowSubtitle' => '0',
                'fBookShowFullTitle' => '1',
                'fBookShowEditor' => '1',
                'fBookShowEditorPic' => '1',
                'fBookShowPublisher' => '1',
                'fBookShowLanguage' => '1',
                'fBookShowSize' => '1',
                'fBookShowTOC' => '1',
                'nBookContentLocation' => '1',
                'nBookCTAButton' => '1',
                'sBookTOCLinks' => 'disabled',
                'log_in_msg' => '<span></span> Please log in to read online.',
                'sCoverSize' => 'large',
                'whitelist' => "en.wikipedia.org
     en.wikibooks.org
     pediapress.com",            
            );        
        }//default_settings 
        
        static function colors() {
            $combos = array(
              'nico_0'  => array(159,95),
              'nico_2'  => array(191,127),
              'nico_3'  => array(95,127),
              'nico_4'  => array(95,255),
              'nico_5'  => array(63,127),
              'nico_6'  => array(95,159),
              'nico_7'  => array(63,223),
              'nico_8'  => array(63,225),
              'nico_9'  => array(191,0),
              'nico_10'  => array(63,255),
              'nico_11'  => array(223,63),
              'nico_12'  => array(223,63),
              'nico_13'  => array(159,0),
              'nico_15'  => array(223,63),
              'nico_16'  => array(223,31),
              'nico_17'  => array(63,191),
              'nico_18'  => array(255,63),
              'nico_19'  => array(63,223),
              'nico_20'  => array(63,127),
              'nico_21'  => array(63,127)
            );

            return $combos;
        }//colors  


        public static function coverSizes() {
           return array('small','medium','large');
        }//buttonSizes  

        private function load_dependencies() {
            if (is_admin()){
                require_once 'bl/WP_Persistent_Notices.php';
                require_once 'pl/ebbAdmin.php';
            }

            require_once 'bl/simple_html_dom.php'; 
            require_once 'bl/ebbImport.php';
            require_once 'bl/ebbBook.php';
            require_once 'bl/ebbChapter.php';
            require_once 'bl/ebbArticle.php';
            require_once 'pl/ebb.php';             
        }//load_dependencies 
        
        private function define_front_hooks(){
            if(is_admin())return; 
            if(defined( 'DOING_AJAX' ) && DOING_AJAX)return;
            if(!has_filter('widget_text','do_shortcode'))add_filter('widget_text','do_shortcode',11);

            add_action( 'template_redirect', array(&$this,'handle_filters') );
        }//define_front_hooks    
        
        function handle_filters() {
            global $wp_query;

            if($wp_query->is_archive){
                return;
            }

            $fIsBook = is_singular( 'ebook' );

            if(!$fIsBook ){
                return;
            }

            $this->_instanceBook = new RDP_EBB_BOOK(self::$version,$this->_options);
            add_filter('the_content', array(&$this->_instanceBook, 'render')); 
        }//handle_filters         
        
        
        private function define_admin_hooks() {
            if(!is_admin())return;
            if(defined( 'DOING_AJAX' ) && DOING_AJAX)return;
            self::cpt_init();
            $RDP_EBB_ADMIN = new RDP_EBB_ADMIN(self::$version);
            add_action('admin_menu', 'RDP_EBB_ADMIN::add_menu_item');
            add_action('admin_init', 'RDP_EBB_ADMIN::admin_page_init');
//            add_action( 'admin_footer', 'RDP_WBB_Utilities::enqueueAutosaveScript' );
        }//define_admin_hooks 
        
        private function define_ajax_hooks(){
            if(!defined( 'DOING_AJAX' ) || !DOING_AJAX)return;
        }//define_ajax_hooks
        
        public static function initialize() {
            self::cpt_init(); 
        }//initialize
        
        public static function install() {
            self::initialize();
            flush_rewrite_rules();            
        } //install    
        
        public static function deactivate() {
            flush_rewrite_rules();            
        } //install          

        public function run() {
            $this->define_front_hooks();
            $this->define_admin_hooks();
            $this->define_ajax_hooks();  
        } //run  

        private static function cpt_init(){

            // Add categories
            $labels = array(
                    'name'              => _x( 'eBook Categories', 'taxonomy general name' ),
                    'singular_name'     => _x( 'eBook Category', 'taxonomy singular name' ),
                    'search_items'      => __( 'Search Categories' ),
                    'all_items'         => __( 'All Categories' ),
                    'parent_item'       => __( 'Parent Category' ),
                    'parent_item_colon' => __( 'Parent Category:' ),
                    'edit_item'         => __( 'Edit Category' ),
                    'update_item'       => __( 'Update Category' ),
                    'add_new_item'      => __( 'Add New Category' ),
                    'new_item_name'     => __( 'New Category Name' ),
                    'menu_name'         => __( 'Categories' ),
            );

            $args = array(
                    'hierarchical'      => true,
                    'labels'            => $labels,
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'update_count_callback' => '_update_post_term_count',                
                    'query_var'         => true,
                    'rewrite'           => array( 'slug' => 'ebook-category' ),
            );

            register_taxonomy( 'ebook_category', array( 'ebook' ), $args ); 

            // Add tags 
            $labels = array(
                    'name'                       => _x( 'eBook Tags', 'taxonomy general name' ),
                    'singular_name'              => _x( 'eBook Tag', 'taxonomy singular name' ),
                    'search_items'               => __( 'Search Tags' ),
                    'popular_items'              => __( 'Popular Tags' ),
                    'all_items'                  => __( 'All Tags' ),
                    'parent_item'                => null,
                    'parent_item_colon'          => null,
                    'edit_item'                  => __( 'Edit Tag' ),
                    'update_item'                => __( 'Update Tag' ),
                    'add_new_item'               => __( 'Add New Tag' ),
                    'new_item_name'              => __( 'New Tag Name' ),
                    'separate_items_with_commas' => __( 'Separate tags with commas' ),
                    'add_or_remove_items'        => __( 'Add or remove tags' ),
                    'choose_from_most_used'      => __( 'Choose from the most used tags' ),
                    'not_found'                  => __( 'No tags found.' ),
                    'menu_name'                  => __( 'Tags' ),
            );

            $args = array(
                    'hierarchical'          => false,
                    'labels'                => $labels,
                    'show_ui'               => true,
                    'show_admin_column'     => true,
                    'update_count_callback' => '_update_post_term_count',
                    'query_var'             => true,
                    'rewrite'               => array( 'slug' => 'ebook-tag' ),
            );

            register_taxonomy( 'ebook_tag', 'ebook', $args );           

            $labels = array(
                    'name'               => _x( 'eBooks', 'post type general name', self::$plugin_slug ),
                    'singular_name'      => _x( 'eBook', 'post type singular name', self::$plugin_slug ),
                    'menu_name'          => _x( 'eBooks', 'admin menu', self::$plugin_slug ),
                    'name_admin_bar'     => _x( 'eBook', 'add new on admin bar', self::$plugin_slug ),
                    'add_new'            => _x( 'Add New', 'book', self::$plugin_slug ),
                    'add_new_item'       => __( 'Add New eBook', self::$plugin_slug ),
                    'new_item'           => __( 'New eBook', self::$plugin_slug ),
                    'edit_item'          => __( 'Edit eBook', self::$plugin_slug ),
                    'view_item'          => __( 'View eBook', self::$plugin_slug ),
                    'all_items'          => __( 'All eBooks', self::$plugin_slug ),
                    'search_items'       => __( 'Search eBooks', self::$plugin_slug ),
                    'parent_item_colon'  => __( 'Parent eBooks:', self::$plugin_slug ),
                    'not_found'          => __( 'No eBooks found.', self::$plugin_slug ),
                    'not_found_in_trash' => __( 'No eBooks found in Trash.', self::$plugin_slug )
            );
            $args = array(
                    'labels'             => $labels,
                    'public'             => true,
                    'publicly_queryable' => true,
                    'show_ui'            => true,
                    'show_in_menu'       => true,
                    'query_var'          => true,
                    'rewrite'            => true,
                    'capability_type'    => 'post',
                    'has_archive'        => true,
                    'hierarchical'       => false,
                    'menu_position'      => null,
                    'taxonomies'        => array( 'ebook_category', 'ebook_tag '),
                    'supports'           => array( 'title','editor', 'author', 'thumbnail','custom_fields' ),
                    'register_meta_box_cb' => 'RDP_EBB_ADMIN::add_metaboxes'
            );

            register_post_type( 'ebook', $args ); 

            /**
            *   To Activate Custom Post Type Single page
            *   @see http://en.bainternet.info/2011/custom-post-type-getting-404-on-permalinks
            */
            $set = get_option('post_type_rules_flased_wiki_book');
            if ($set !== true){
               flush_rewrite_rules(false);
               update_option('post_type_rules_flased_wiki_book',true);
            }      
        }//cpt_init        
        

    }//RDP_EBB_PLUGIN  
    
   $oRDP_EBB_PLUGIN = new RDP_EBB_PLUGIN();
    register_activation_hook( __FILE__, array( 'RDP_EBB_PLUGIN', 'install' ) );
    register_deactivation_hook( __FILE__, array( 'RDP_EBB_PLUGIN', 'deactivate' )); 
}

