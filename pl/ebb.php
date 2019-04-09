<?php

if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); 

class RDP_EBB {
    /**
    * Singleton class instance
    *
    * @since 0.1.0
    * @access private
    * @var object RDP_WBB
    */           
    private static $_instance = NULL;        
    
    /**
    * Class contructor
    *
    * @since    0.1.0
    * @access private
    */       
    private function __construct( $version,$options ) {
        $this->_version = $version;
        $this->_options = $options;
        add_action( 'wp_enqueue_scripts', array($this, 'stylesEnqueue'), 998); 
        //add_action( 'wp_enqueue_scripts', array($this, 'scriptsEnqueue'), 997); 
    }//__construct

    /**
     * retrieve singleton class instance
     * @return instance reference to RDP_WBB
     */
    public static function get_instance($version,$options){
        if (NULL === self::$_instance) self::$_instance = new self($version,$options);
        return self::$_instance;
    }//get_instance      

    public function stylesEnqueue(){
        wp_enqueue_style(
                'rdp-wbb-common',
                plugins_url('style/rdp-wbb.style.css', __FILE__ ),
                array(),
                $this->_version
        );      
        
        $filename = get_stylesheet_directory() . '/rdp-wbb.custom.css';
        if (file_exists($filename)) {
            wp_register_style( 'rdp-wbb-style-custom', get_stylesheet_directory_uri() . '/rdp-wbb.custom.css' );
            wp_enqueue_style( 'rdp-wbb-style-custom' );
        }         
        
        do_action('rdp_wbb_styles_enqueued');
    }//stylesEnqueue    
}//RDP_EBB
