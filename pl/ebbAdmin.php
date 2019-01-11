<?php

if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); 

class RDP_EBB_ADMIN {
    private $version;
    
    public function __construct( $version ) {
        $this->version = $version;
        add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
    }//__construct
    
    
    public function adminEnqueueScripts($hook){
        global $typenow;
        if ( 'ebook' !== $typenow ) return;        

        wp_enqueue_script( 
                'rdp-ebb-admin', 
                plugins_url('js/admin.js', __FILE__), 
                ['jquery'], 
                $this->version ); 
        
        $params = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'ajax_nonce' => wp_create_nonce('rdp_ebb_ajax'),
        );
        wp_localize_script('rdp-ebb-admin', 'rdp_ebb_admin', $params);        
        
        
        wp_enqueue_script( 
                'thumbelina', 
                plugins_url('js/thumbelina.js', __FILE__), 
                [], 
                '1.0'); 
        
        wp_enqueue_style('thumbelina', 
                plugins_url('style/thumbelina.css', __FILE__),
                [],
                '1.0');        
        
        wp_enqueue_style('rdp-ebb-admin', 
                plugins_url('style/admin.css', __FILE__),
                ['thumbelina'],
                $this->version);

        do_action('rdp_ebb_admin_scripts_enqueued');        
    }  //adminEnqueueScripts  

    /*------------------------------------------------------------------------------
    Add admin menu
    ------------------------------------------------------------------------------*/
    static function add_menu_item(){
        if ( !current_user_can('activate_plugins') ) return;
        add_options_page( 'RDP eBook Builder', 'RDP EBB', 'manage_options', 'rdp-ebook-builder', 'RDP_EBB_ADMIN::generate_page' );
    
        add_action('save_post', 'RDP_EBB_ADMIN::save_book_meta', 1, 3);
    } //add_menu_item   
    
    /*------------------------------------------------------------------------------
    Render settings page
    ------------------------------------------------------------------------------*/
    static function generate_page()
    {  
	echo '<div class="wrap">';
        echo '<h2>';
        echo esc_html( get_admin_page_title() );
        echo '</h2>';
 
        echo '<form action="options.php" method="post">';
        settings_fields('rdp-ebook-builder');
        do_settings_sections('rdp-ebook-builder');
        echo '<p>';
        submit_button();
        echo '</p>';
        echo '</form>';        
    }     
    
    static function admin_page_init(){
        if ( !current_user_can('activate_plugins') ) return;
        //Add settings link to plugins page
        add_filter('plugin_action_links', array('RDP_EBB_ADMIN', 'add_settings_link'), 10, 2);
        
        register_setting(
            'rdp-ebook-builder',
            RDP_EBB_PLUGIN::$options_name,
            'RDP_EBB_ADMIN::options_validate'
        );
        
        
        // Book Display Settings
	add_settings_section(
            'rdp_ebb_book',
            esc_html__('Book Display','rdp-ebook-builder'),
            'RDP_EBB_ADMIN::my_book_text',
            'rdp-ebook-builder'
	); 
        
        add_settings_field(
            'fBookShowCover',
            esc_html__('Show Cover Image:','rdp-ebook-builder'),
            array('RDP_EBB_ADMIN', 'my_book_show_cover_input'),
            'rdp-ebook-builder',
            'rdp_ebb_book'
        );         
        
        add_settings_field(
            'fBookShowTitle',
            esc_html__('Show Title:','rdp-ebook-builder'),
            array('RDP_EBB_ADMIN', 'my_book_show_title_input'),
            'rdp-ebook-builder',
            'rdp_ebb_book'
        );         
        
        add_settings_field(
            'fBookShowSubtitle',
            esc_html__('Show Subtitle:','rdp-ebook-builder'),
            array('RDP_EBB_ADMIN', 'my_book_show_subtitle_input'),
            'rdp-ebook-builder',
            'rdp_ebb_book'
        );   
        
        add_settings_field(
            'fBookShowFullTitle',
            esc_html__( 'Use Full Title:','rdp-ebook-builder'),
            array('RDP_EBB_ADMIN', 'full_title_show_input'),
            'rdp-ebook-builder',
            'rdp_ebb_book'
        );          
        
        add_settings_field(
            'fBookShowEditor',
            esc_html__('Show Editor:','rdp-ebook-builder'),
            array('RDP_EBB_ADMIN', 'my_book_show_editor_input'),
            'rdp-ebook-builder',
            'rdp_ebb_book'
        );         
        
        add_settings_field(
            'fBookShowTOC',
            esc_html__('Show Table of Contents:','rdp-ebook-builder'),
            array('RDP_EBB_ADMIN', 'my_book_show_toc_input'),
            'rdp-ebook-builder',
            'rdp_ebb_book'
        );         

         add_settings_field(
            'sBookTOCLinks',
            esc_html__('Activate Table of Content Links:','rdp-ebook-builder'),
            array('RDP_EBB_ADMIN', 'my_book_show_toc_links_input'),
            'rdp-ebook-builder',
            'rdp_ebb_book'
        ); 
         
         add_settings_field(
            'sBookContentBeneathCover',
            '',
            array('RDP_EBB_ADMIN', 'my_book_content_beneath_cover_input'),
            'rdp-ebook-builder',
            'rdp_ebb_book'
        ); 
    }//admin_page_init  
    
    /*------------------------------------------------------------------------------
        Book Display Settings
    ------------------------------------------------------------------------------*/   
    static function my_book_text() {
        echo '<p>';
        esc_html_e( "Settings to use when displaying an individual book.", 'rdp-ebook-builder' ); 
        echo '</p>';
    }    
    
    static function my_book_show_cover_input(){
        $options = get_option( RDP_EBB_PLUGIN::$options_name );
        $default_settings = RDP_EBB_PLUGIN::default_settings();
        $value = isset($options['fBookShowCover'])? $options['fBookShowCover'] : $default_settings['fBookShowCover'];
        $value = intval($value);        

        echo "<input value='1' id='fBookShowCover' name='rdp_ebb_options[fBookShowCover]' type='checkbox' " . checked( $value , 1, false) . " />";
    }//my_book_show_cover_input 
    
    static function my_book_show_title_input(){
        $options = get_option( RDP_EBB_PLUGIN::$options_name );
        $default_settings = RDP_EBB_PLUGIN::default_settings();
        $value = isset($options['fBookShowTitle'])? $options['fBookShowTitle'] : $default_settings['fBookShowTitle'];
        $value = intval($value);          

        echo "<input value='1' id='fBookShowTitle' name='rdp_ebb_options[fBookShowTitle]' type='checkbox' " . checked( $value , 1, false) . " />";
    }//my_book_show_title_input 
    
    static function my_book_show_subtitle_input(){
        $options = get_option( RDP_EBB_PLUGIN::$options_name );
        $default_settings = RDP_EBB_PLUGIN::default_settings();
        $value = isset($options['fBookShowSubtitle'])? $options['fBookShowSubtitle'] : $default_settings['fBookShowSubtitle'];
        $value = intval($value);           

        echo "<input value='1' id='fBookShowSubtitle' name='rdp_ebb_options[fBookShowSubtitle]' type='checkbox' " . checked( $value , 1, false) . " />";
    }//my_book_show_subtitle_input
    
    static function full_title_show_input() {
        $options = get_option( RDP_EBB_PLUGIN::$options_name );
        $default_settings = RDP_EBB_PLUGIN::default_settings();
        $value = isset($options['fBookShowFullTitle'])? $options['fBookShowFullTitle'] : $default_settings['fBookShowFullTitle'];
        $value = intval($value);
        echo '<lable>';
        echo '<input type="checkbox" value="1" name="rdp_ebb_options[fBookShowFullTitle]" ' . checked( $value , 1, false) . '/> ';
        esc_html_e('Display book titles as combination of Title and Subtitle','rdp-ebook-builder');
        echo '</lable>';
    }//subtitle_show_input      
    
    static function my_book_show_editor_input(){
        $options = get_option( RDP_EBB_PLUGIN::$options_name );
        $default_settings = RDP_EBB_PLUGIN::default_settings();
        $value = isset($options['fBookShowEditor'])? $options['fBookShowEditor'] : $default_settings['fBookShowEditor'];
        $value = intval($value);          

        echo "<input value='1' id='fBookShowEditor' name='rdp_ebb_options[fBookShowEditor]' type='checkbox' " . checked( $value , 1, false) . " />";
    }//my_book_show_editor_input    
    
    static function my_book_show_toc_input(){
        $options = get_option( RDP_EBB_PLUGIN::$options_name );
        $default_settings = RDP_EBB_PLUGIN::default_settings();
        $value = isset($options['fBookShowTOC'])? $options['fBookShowTOC'] : $default_settings['fBookShowTOC'];
        $value = intval($value);          

        echo "<input value='1' id='fBookShowTOC' name='rdp_ebb_options[fBookShowTOC]' type='checkbox' " . checked( $value , 1, false) . " />";
    }//my_book_show_toc_input
    
    static function my_book_show_toc_links_input() {
        $options = get_option( RDP_EBB_PLUGIN::$options_name );
        $default_settings = RDP_EBB_PLUGIN::default_settings();
        $value = isset($options['sBookTOCLinks'])? $options['sBookTOCLinks'] : $default_settings['sBookTOCLinks'];
        
        echo '<label><input name="rdp_ebb_options[sBookTOCLinks]" type="radio" value="enabled" ' . checked($value,"enabled",false) . ' /> ';
        esc_html_e('Enabled &mdash; TOC links are enabled','rdp-ebook-builder');
        echo '</label>';
        echo '<br />';
        echo '<label><input name="rdp_ebb_options[sBookTOCLinks]" type="radio" value="logged-in" ' . checked($value,"logged-in",false). ' /> ';
        esc_html_e('Logged-in &mdash; TOC links are active only when a user is logged in','rdp-ebook-builder');
        echo '</label>';
        echo '<br />';  
        
        $sLabel = __('Text, HTML, and/or shortcode to display when a <b>non-logged-in person</b> clicks a TOC link.', 'rdp-ebook-builder');
        $sLabel2 = __('An empty SPAN element will display a notification icon.', 'rdp-ebook-builder');
        echo sprintf('<p>%s<br />%s</p>', $sLabel, $sLabel2);        
        $log_in_msg = isset($options['log_in_msg'])? $options['log_in_msg'] : $default_settings['log_in_msg'];
        $log_in_msg = esc_textarea($log_in_msg);
        echo '<textarea name="rdp_ebb_options[log_in_msg]"  rows="10" cols="50">' . $log_in_msg . '</textarea>';        
        
        
        echo '<br />';
        echo '<label><input name="rdp_ebb_options[sBookTOCLinks]" type="radio" value="disabled" ' . checked($value,"disabled",false) . ' /> ';
        esc_html_e('Disabled &mdash; TOC links are completely disabled, all the time','rdp-ebook-builder');
        echo '</label>'; 
       
    }
    
    static function my_book_content_beneath_cover_input() {
        $default_settings = RDP_EBB_PLUGIN::default_settings();
        $sLabel = esc_attr__('Content to Insert Beneath Book Cover Image', 'rdp-ebook-builder');
        $options = get_option( RDP_EBB_PLUGIN::$options_name );
        $text_string = isset($options['sBookContentBeneathCover'])? $options['sBookContentBeneathCover'] : $default_settings['sBookContentBeneathCover'];
        $text_string = esc_textarea($text_string);
        echo '<span class="alignleft">' . $sLabel . '</span><br />';
        echo '<textarea name="rdp_ebb_options[sBookContentBeneathCover]" id="txtMyBookContentBeneathCover"  rows="10" cols="50">' . $text_string . '</textarea>';        
   }//my_book_content_beneath_cover_input
   
   
    /*------------------------------------------------------------------------------
    Validate incoming data
    ------------------------------------------------------------------------------*/
   static function options_validate($input) {
       $default_settings = RDP_EBB_PLUGIN::default_settings();
 	$options = array(
                'sBookContentBeneathCover' => (isset($input['sBookContentBeneathCover'])? $input['sBookContentBeneathCover'] : $default_settings['sBookContentBeneathCover'] ),
                'books_per_rss'       => (isset($input['books_per_rss']) && intval($input['books_per_rss']) > 0 ? $input['books_per_rss'] : $default_settings['books_per_rss'] ),
                'fAllowClone'         => (isset( $input['fAllowClone']) && $input['fAllowClone'] == 1 ? 1 : 0 ),
                'fBookShowCover'      => (isset( $input['fBookShowCover']) && $input['fBookShowCover'] == 1 ? 1 : 0 ),
                'fBookShowTitle'      => (isset( $input['fBookShowTitle']) && $input['fBookShowTitle'] == 1 ? 1 : 0 ),
                'fBookShowSubtitle'   => (isset( $input['fBookShowSubtitle']) && $input['fBookShowSubtitle'] == 1 ? 1 : 0 ),
                'fBookShowFullTitle'  => (isset( $input['fBookShowFullTitle']) && $input['fBookShowFullTitle'] == 1 ? 1 : 0 ),
                'fBookShowEditor'     => (isset( $input['fBookShowEditor']) && $input['fBookShowEditor'] == 1 ? 1 : 0 ),
                'fBookShowTOC'        => (isset( $input['fBookShowTOC']) && $input['fBookShowTOC'] == 1 ? 1 : 0 ),
                'sBookTOCLinks'       => (isset( $input['sBookTOCLinks'])? $input['sBookTOCLinks'] : $default_settings['sBookTOCLinks'] ),
                'log_in_msg'          => (isset( $input['log_in_msg'])? $input['log_in_msg'] : $default_settings['log_in_msg'] )
            );
        return $options;
    } //options_validate 

    /*------------------------------------------------------------------------------
        Add Settings link to plugins page
    ------------------------------------------------------------------------------*/
    static function add_settings_link($links, $file) {
        if ($file == RDP_EBB_PLUGIN_BASENAME){
        $settings_link = '<a href="options-general.php?page=' . 'rdp-ebook-builder' . '">'.esc_html__("Settings", 'rdp-ebook-builder').'</a>';
         array_unshift($links, $settings_link);
        }
        return $links;
     }//add_settings_link 
     
     
    /*------------------------------------------------------------------------------
        Do import or save on update
    ------------------------------------------------------------------------------*/      
    static function save_book_meta($post_id, $post, $update){
        if ( 'ebook' !== $post->post_type ) return;
        if(!$update)return;
        
        remove_action('save_post', 'RDP_EBB_ADMIN::save_book_meta', 1, 3); 
        
        if(!RDP_EBB_Utilities::rgempty('btnRDPWBImport')):
            $sourceName = '';
            $source = strip_tags(RDP_EBB_Utilities::globalRequest('wb_source'));
            if(empty($source))return;
            
            $dataPass = array(
                'url' => '',
                'code' => '200',
                'message' => ''
            );  
            
            $baseURL = '';        
            $needle = 'https://www.lablynxpress.com';
            $test = substr($source, 0, strlen($needle));
            if($needle === $test) $sourceName = 'lablynxpress'; $baseURL =  $needle;           
            
            $needle = 'https://www.limswiki.org';
            $test = substr($source, 0, strlen($needle));
            if($needle === $test) $sourceName = 'limswiki'; $baseURL =  $needle; 
            
            switch ($sourceName) {
                case 'lablynxpress':
                case 'limswiki':
                    $dataPass = RDP_EBB_IMPORT::handleMediawikiImport($source, $dataPass, $baseURL, $post_id);

                    if($dataPass['code'] !== '200'):
                        for($x = 0; $x < count($dataPass['messages']); $x++){
                            $notice = array(
                                'id' => 'ebook-import'.($x+1),
                                'type' => 'error',
                                'message' => $dataPass['messages'][$x]
                            );
                            add_persistent_notice( $notice );                             
                        }

                    else:
                        $msgResult = __('Import complete.', 'rdp-ebook-builder');
                        add_persistent_notice( array(
                            'id' => 'ebook-import',
                            'type' => 'success',
                            'message' => $msgResult
                        ) );                
                    endif;                  

                    break;
                default:
                   $msgResult = __('Import Error', 'rdp-ebook-builder');
                   $msg = __("Not a valid URL.",'rdp-ebook-builder');
                   add_persistent_notice( array(
                       'id' => 'ebook-import',
                       'type' => 'error',
                       'message' => $msgResult . ': ' . $msg
                   ) );
                   break;                
            }
        else:
            $post->filter = '';
            $meta = $post->_ebook_metadata;  
            if(!$meta):
                $meta = RDP_EBB_BOOK::bookMetadataStructure();
            endif;   
            
            $alternative_image = RDP_EBB_Utilities::globalRequest('alternative-image-url','');
            if ( ! add_post_meta( $post->ID, '_alternative_image', $alternative_image, true ) ) { 
               update_post_meta( $post->ID, '_alternative_image', $alternative_image );
            }                      
            
            
            $meta['title'] = strip_tags(RDP_EBB_Utilities::globalRequest('post_title'));
            $meta['subtitle'] = strip_tags(RDP_EBB_Utilities::globalRequest('wb_subtitle'));
            $meta['editor'] = strip_tags(RDP_EBB_Utilities::globalRequest('wb_editor'));
            $meta['publisher'] = strip_tags(RDP_EBB_Utilities::globalRequest('wb_publisher'));
            $meta['download_url'] = strip_tags(RDP_EBB_Utilities::globalRequest('wb_download_url'));        
            $meta['author_id'] = RDP_EBB_Utilities::globalRequest('post_author_override');
            $meta['image_url'] = RDP_EBB_Utilities::globalRequest('bookImageURL');
            $meta['cover_theme'] = RDP_EBB_Utilities::globalRequest('bookCoverTheme');
            
            
            $params = [
                'cover_style'   => $meta['cover_theme'],
                'subtitle'      => $meta['subtitle'],
                'editor'        => $meta['editor'],
                'title'         => $meta['title'],
                'title_image'   => $meta['image_url'],
                'publisher'     => $meta['publisher']
            ];
            $sCoverImageURL = RDP_EBB_PLUGIN_BASEURL . '/pl/cover.php?';
            $sCoverImageURL .=  http_build_query($params);       
            $meta['cover_image'] = $sCoverImageURL;
            RDP_EBB_BOOK::handleFeaturedImage($post_id, $sCoverImageURL);
            
            $source = strip_tags(RDP_EBB_Utilities::globalRequest('wb_source'));
            if(empty($source)):
                $source = RDP_EBB_Utilities::rgar($meta, 'link','');                
            endif;
            $meta['link'] = $source;
            
            $settings['show_cover'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_cover'));
            $settings['show_title'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_title'));
            $settings['show_subtitle'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_subtitle'));
            $settings['show_full_title'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_full_title'));
            $settings['show_editor'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_editor'));
            $settings['show_editor_pic'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_editor_pic'));
            $settings['show_publisher'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_publisher'));
            $settings['show_language'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_language'));
            $settings['show_size'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_size'));
            $settings['show_toc'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_toc'));
            $settings['show_content_beneath_cover'] = intval(RDP_EBB_Utilities::globalRequest('wb_show_content_beneath_cover'));
            $settings['toc_links'] = strip_tags(RDP_EBB_Utilities::globalRequest('wb_toc_links'));
            $settings['cta_button'] = strip_tags(RDP_EBB_Utilities::globalRequest('wb_cta_button'));
            $settings['content_location'] = strip_tags(RDP_EBB_Utilities::globalRequest('wb_content_location'));
            $settings['log_in_msg'] = RDP_EBB_Utilities::globalRequest('wb_log_in_msg');
            $settings['cover_size'] = strip_tags(RDP_EBB_Utilities::globalRequest('wb_cover_size'));
            $meta['settings'] = $settings;
            
            RDP_EBB_Utilities::savePostMeta($post_id,$meta);  
            
            $bookObj = RDP_EBB_BOOK::fromJSONFile($post_id);
            if($bookObj){
                $bookObj->post_title = $meta['title'];
                $bookObj->_ebook_metadata->title = $meta['title'];
                $bookObj->_ebook_metadata->subtitle = $meta['subtitle'];
                $bookObj->_ebook_metadata->cover_theme = $meta['cover_theme'];
                $bookObj->_ebook_metadata->title_image = $meta['image_url'];
                $bookObj->_ebook_metadata->cover_image = $meta['cover_image'];
                $bookObj->_ebook_metadata->editor = $meta['editor'];
                $bookObj->_ebook_metadata->publisher = $meta['publisher'];
                $bookObj->_ebook_metadata->author_id = $meta['author_id'];
                RDP_EBB_BOOK::toJSONFile($bookObj);                
            }

        endif;
        
    }//save_book_meta
    
    
    /*------------------------------------------------------------------------------
        Set up metaboxes for custom post type edit page
    ------------------------------------------------------------------------------*/     
    static function add_metaboxes(){
        global $post;
        if($post->post_status !== 'auto-draft') add_meta_box("subtitle", "Subtitle", 'RDP_EBB_ADMIN::renderSubtitleMetabox', 'ebook', "normal", "high");    
        if($post->post_status !== 'auto-draft') add_meta_box("cover_builder", "Cover Builder", 'RDP_EBB_ADMIN::renderCoverBuilderMetabox', 'ebook', "normal", "high");    
        add_meta_box("import", "Import", 'RDP_EBB_ADMIN::renderImportMetabox', 'ebook', "normal", "high");        
        if($post->post_status !== 'auto-draft') add_meta_box("book_settings", "Settings", "RDP_EBB_ADMIN::renderSettingsMetabox", 'ebook', "normal", "high");
        if($post->post_status !== 'auto-draft') add_meta_box("book_meta", "Metadata", "RDP_EBB_ADMIN::renderMetadataMetabox", 'ebook', "normal", "high");             
    }//add_metaboxes  
    
    
    static function renderSettingsMetabox($post){
        // set filter to empty string so custom meta is returned
        $post->filter = '';
        $meta = $post->_ebook_metadata; 
        $settings = RDP_EBB_Utilities::rgar($meta, 'settings',[]);
        $default_settings = RDP_EBB_PLUGIN::default_settings();
        
        if(empty($settings)):
            $settings['show_cover'] = intval($default_settings['fBookShowCover']);              
            $settings['show_title'] = intval($default_settings['fBookShowTitle']);            
            $settings['show_subtitle'] = intval($default_settings['fBookShowSubtitle']); 
            $settings['show_full_title'] = intval($default_settings['fBookShowFullTitle']);              
            $settings['show_publisher'] = intval($default_settings['fBookShowPublisher']);            
            $settings['show_language'] = intval( $default_settings['fBookShowLanguage']);            
            $settings['show_editor'] = intval($default_settings['fBookShowLanguage']);
            $settings['show_editor_pic'] = intval($default_settings['fBookShowEditorPic']); 
            $settings['show_size'] = intval($default_settings['fBookShowSize']);             
            $settings['show_toc'] = intval($default_settings['fBookShowTOC']);
            $settings['show_content_beneath_cover'] = intval($default_settings['fBookShowContentBeneathCover']);
            $settings['toc_links'] = $default_settings['sBookTOCLinks'];
            $settings['cta_button'] = intval($default_settings['nBookCTAButton']);
            $settings['content_location'] = intval($default_settings['nBookContentLocation']);
            $settings['log_in_msg'] = $default_settings['log_in_msg'];
            $settings['cover_size'] = $default_settings['sCoverSize'];
        endif;

        $download_url = RDP_EBB_Utilities::rgar($meta, 'download_url','');
        echo '<p style="margin-bottom: 0;">';
        echo '<button id="btnGenerateBook" type="button">';
        _e('Generate Book', 'rdp-ebook-builder');
        echo '</button>';
        echo '<img id="wb_generating_book_indicator" style="display:none">';
        echo '</p>';
        echo '<p style="margin-bottom: 0;">';
        _e('Download URL', 'rdp-ebook-builder');
        echo ':</p>';
        echo '<input type="text" name="wb_download_url" id="wb_download_url" value="' . $download_url  . '" class="widefat" />';         

        
        // cover image
        echo '<p style="margin-bottom: 0;">';         
        $show_cover = RDP_EBB_Utilities::rgar($settings, 'show_cover',1);
        echo "<input name='wb_show_cover' id='wb_show_cover' type='checkbox' value='1' " . checked( intval($show_cover) , 1, false) . "/> " ;
        esc_html_e('Show cover image','rdp-ebook-builder');         
        echo '</p>';        
        
        // cover size
        echo '<p style="margin-bottom: 0;"><span style="width: 80px;display: inline-block;">';
        esc_html_e('Cover size','rdp-ebook-builder');
        echo ':</span> '; 
        $sizes = RDP_EBB_PLUGIN::coverSizes(); 
        $cover_size = RDP_EBB_Utilities::rgar($settings, 'cover_size','large');  
        echo '<select name="wb_cover_size" id="wb_cover_size">';
        foreach($sizes as $size){
            echo sprintf('<option value="%s" %s>%s</option>',$size,selected($cover_size,$size,false), ucwords($size) );
        }
        echo '</select>';  
        echo '</p>'; 

        
        // content beneath PediaPress cover
        echo '<p style="margin-bottom: 0;">';         
        $content_beneath_cover = RDP_EBB_Utilities::rgar($settings, 'show_content_beneath_cover',1); 
        echo "<input name='wb_show_content_beneath_cover' id='wb_show_content_beneath_cover' type='checkbox' value='1' " . checked( intval($content_beneath_cover) , 1, false) . "/> " ;
        esc_html_e('Show content beneath cover','rdp-ebook-builder');         
        echo '</p>';        
        
        
        // title
        echo '<p style="margin-bottom: 0;">';         
        $show_title = RDP_EBB_Utilities::rgar($settings, 'show_title',1);
        echo "<input name='wb_show_title' id='wb_show_title' type='checkbox' value='1' " . checked( intval($show_title) , 1, false) . "/> " ;
        esc_html_e('Show title','rdp-ebook-builder');         
        echo '</p>';  
        
        // subtitle
        echo '<p style="margin-bottom: 0;">';         
        $show_subtitle = RDP_EBB_Utilities::rgar($settings, 'show_subtitle',0);        
        echo "<input name='wb_show_subtitle' id='wb_show_subtitle' type='checkbox' value='1' " . checked( intval($show_subtitle) , 1, false) . "/> " ;
        esc_html_e('Show subtitle','rdp-ebook-builder');         
        echo '</p>';         
        
        // full title
        echo '<p style="margin-bottom: 0;">';         
        $show_full_title = RDP_EBB_Utilities::rgar($settings, 'show_full_title',1);         
        echo "<input name='wb_show_full_title' id='wb_show_full_title' type='checkbox' value='1' " . checked( intval($show_full_title) , 1, false) . "/> " ;
        esc_html_e('Use full title','rdp-ebook-builder'); 
        echo '<span class="description"> &mdash; ';
        esc_html_e('Show book titles as combination of Title and Subtitle','rdp-ebook-builder');
        echo '</span>';
        echo '</p>';  
        
        // editor
        echo '<p style="margin-bottom: 0;">';         
        $show_editor = RDP_EBB_Utilities::rgar($settings, 'show_editor',1);           
        echo "<input name='wb_show_editor' id='wb_show_editor' type='checkbox' value='1' " . checked( intval($show_editor) , 1, false) . "/> " ;
        esc_html_e('Show editor','rdp-ebook-builder');         
        echo '</p>';  
        
        // editor picture
        echo '<p style="margin-bottom: 0;">';         
        $show_editor_pic = RDP_EBB_Utilities::rgar($settings, 'show_editor_pic',1);         
        echo "<input name='wb_show_editor_pic' id='wb_show_editor_pic' type='checkbox' value='1' " . checked( intval($show_editor_pic) , 1, false) . "/> " ;
        esc_html_e('Show editor picture','rdp-ebook-builder');         
        echo '</p>';        
        
        // publisher
        echo '<p style="margin-bottom: 0;">';         
        $show_publisher = RDP_EBB_Utilities::rgar($settings, 'show_publisher',1);         
        echo "<input name='wb_show_publisher' id='wb_show_publisher' type='checkbox' value='1' " . checked( intval($show_publisher) , 1, false) . "/> " ;
        esc_html_e('Show publisher','rdp-ebook-builder');         
        echo '</p>';        
        
        // language
        echo '<p style="margin-bottom: 0;">';         
        $show_language = RDP_EBB_Utilities::rgar($settings, 'show_language',1);          
        echo "<input name='wb_show_language' id='wb_show_language' type='checkbox' value='1' " . checked( intval($show_language) , 1, false) . "/> " ;
        esc_html_e('Show language','rdp-ebook-builder');         
        echo '</p>';        
        
        // book size
        echo '<p style="margin-bottom: 0;">';         
        $show_size = RDP_EBB_Utilities::rgar($settings, 'show_size',1);         
        echo "<input name='wb_show_size' id='wb_show_size' type='checkbox' value='1' " . checked( intval($show_size) , 1, false) . "/> " ;
        esc_html_e('Show book size','rdp-ebook-builder');         
        echo '</p>'; 
        
        // ToC
        echo '<p>';         
        $show_toc = RDP_EBB_Utilities::rgar($settings, 'show_toc',1);         
        echo "<input name='wb_show_toc' id='wb_show_toc' type='checkbox' value='1' " . checked( intval($show_toc) , 1, false) . "/> " ;
        esc_html_e('Show table of contents','rdp-ebook-builder'); 
        echo '</p>';       
        
        
        $toc_links = RDP_EBB_Utilities::rgar($settings, 'toc_links', 'disabled');
        echo '<h2 style="margin-bottom: 0;">Table-of-Contents Links Setting</h2>';
        echo '<p style="margin-top: 0;">';
        echo '<label><input name="wb_toc_links" id="wb_toc_links" type="radio" value="enabled" ' . checked($toc_links,"enabled",false) . ' /> ';
        esc_html_e('Enabled &mdash; TOC links are enabled',  'rdp-ebook-builder');
        echo '</label>';
        echo '<br />';
        echo '<label><input name="wb_toc_links" id="wb_toc_links" type="radio" value="disabled" ' . checked($toc_links,"disabled",false) . ' /> ';
        esc_html_e('Disabled &mdash; TOC links are completely disabled, all the time','rdp-ebook-builder');
        echo '</label>';          
        
        echo '<br />';
        echo '<label><input name="wb_toc_links" id="wb_toc_links" type="radio" value="logged-in" ' . checked($toc_links,"logged-in",false). ' /> ';
        esc_html_e('Logged-in &mdash; TOC links are active only when a user is logged in','rdp-ebook-builder');
        echo '</label>';
        echo '<br />';   
        
        $sLabel = __('Text, HTML, and/or shortcode to display when a <b>non-logged-in person</b> clicks a TOC link.', 'rdp-ebook-builder');
        $sLabel2 = __('An empty SPAN element will display a notification icon.', 'rdp-ebook-builder');
        echo sprintf('<p>%s<br />%s</p>', $sLabel, $sLabel2);  
        $log_in_msg = RDP_EBB_Utilities::rgar($settings, 'log_in_msg', $default_settings['log_in_msg']);
        echo '<textarea name="wb_log_in_msg" id="wb_log_in_msg"  rows="10" cols="50">' . esc_textarea($log_in_msg) . '</textarea>';   
        echo '</p>';

        $cta_button = max(1, intval(RDP_EBB_Utilities::rgar($settings, 'cta_button',1)));
        echo '<h2 style="margin-bottom: 0;">Call-To-Action Button Setting</h2>';
        echo '<p style="margin-top: 0;">';
        echo '<label><input name="wb_cta_button" id="wb_cta_button" type="radio" value="1" ' . checked($cta_button,1,false) . ' /> ';
        esc_html_e('Logged-in &mdash; Must be logged-in and submit form to download',  'rdp-ebook-builder');
        echo '</label>';
        echo '<br />';
        echo '<label><input name="wb_cta_button" id="wb_cta_button" type="radio" value="2" ' . checked($cta_button,2,false) . ' /> ';
        esc_html_e('Form Only &mdash; Must submit form to download','rdp-ebook-builder');
        echo '</label>';          
        echo '<br />';
        echo '<label><input name="wb_cta_button" id="wb_cta_button" type="radio" value="3" ' . checked($cta_button,3,false). ' /> ';
        esc_html_e('Open &mdash; Can download with no catch','rdp-ebook-builder');
        echo '</label>';
        echo '</p>';        
        
//        $cta_button_content = RDP_EBB_Utilities::rgar($meta, 'cta_button_content','');
//        echo '<p style="margin-bottom: 0;">';
//        _e('Call-To-Action Content', 'rdp-ebook-builder');
//        echo ':<br><span>';
//        esc_html_e('Text, HTML, and/or shortcode to display in lightbox popup when the Call-To-Action button is clicked.','rdp-ebook-builder');
//        echo '</span></p>';
//        echo '<textarea name="wb_cta_button_content"  rows="10" cols="50" class="widefat" />';    
//        $text_string = esc_textarea($cta_button_content);
//        echo $text_string;
//        echo '</textarea>';   
        
        $content_location = max(1, intval(RDP_EBB_Utilities::rgar($settings, 'content_location',1)));
        echo '<h2 style="margin-bottom: 0;">Excerpt Location Setting</h2>';
        echo '<p style="margin-top: 0;">';
        echo '<label><input name="wb_content_location" id="wb_content_location" type="radio" value="1" ' . checked($content_location,1,false) . ' /> ';
        esc_html_e('Top &mdash; Above book cover and metadata',  'rdp-ebook-builder');
        echo '</label>';
        echo '<br />';
        echo '<label><input name="wb_content_location" id="wb_content_location" type="radio" value="2" ' . checked($content_location,2,false) . ' /> ';
        esc_html_e('Middle &mdash; Above Table of Contents','rdp-ebook-builder');
        echo '</label>';          
        echo '<br />';
        echo '<label><input name="wb_content_location" id="wb_content_location" type="radio" value="3" ' . checked($content_location,3,false). ' /> ';
        esc_html_e('Bottom &mdash; Below Table of Contents','rdp-ebook-builder');
        echo '</label>';
        echo '</p>';         

    }//renderSettingsMetabox  
    
    
    static function renderSubtitleMetabox($post){
        // Noncename needed to verify where the data originated
        echo '<input type="hidden" name="subtitle_nonce" id="subtitle_nonce" value="' .  wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
        // set filter to empty string so custom meta is returned
        $post->filter = '';
        $meta = $post->_ebook_metadata;
        // Get the subtitle data if it's already been entered
        $subtitle = RDP_EBB_Utilities::rgar($meta, 'subtitle','');

        // Echo out the field
        echo '<input type="text" name="wb_subtitle" id="wb_subtitle" value="' . $subtitle  . '" class="widefat" />';
    }//renderSubtitleMetabox    
    
    static function renderMetadataMetabox($post){
        // set filter to empty string so custom meta is returned
        $post->filter = '';
        $meta = $post->_ebook_metadata;

        echo '<p style="margin-bottom: 0;">';
        _e('Editor', 'rdp-ebook-builder');
        echo ':</p>';
        $editor = RDP_EBB_Utilities::rgar($meta, 'editor','');
        echo '<input type="text" name="wb_editor" id="wb_editor" value="' . $editor  . '" class="widefat" />';

        echo '<p style="margin-bottom: 0;">';
        _e('Publisher', 'rdp-ebook-builder');
        echo ':</p>';
        $publisher = RDP_EBB_Utilities::rgar($meta, 'publisher','');
        echo '<input type="text" name="wb_publisher" id="wb_publisher" value="' . $publisher  . '" class="widefat" />';        
    } //renderMetadataMetabox   
    
    static function renderImportMetabox($post){
        // set filter to empty string so custom meta is returned
        $post->filter = '';
        $meta = $post->_ebook_metadata;
        $source = RDP_EBB_Utilities::rgar($meta, 'link','');
        // Echo out the field
        $sDisabled = ''; //($post->post_status === 'auto-draft')? '' : 'disabled="disabled"';
        echo '<p style="margin-bottom: 0;">';
        _e('Source URL', 'rdp-ebook-builder');
        echo ':</p>';        
        echo '<input type="text" ' . $sDisabled . ' name="wb_source" value="' . esc_attr__($source, 'rdp-ebook-builder')   . '" class="widefat" />';  
        echo '<div id="rdp-hhh-import-action">';

        echo '<p align="right">';
        echo '<input name="btnRDPWBImport" type="submit" class="button button-primary button-large" onclick="this.form.submit();" id="btnRDPWBImport" value="' . esc_attr__('Import', 'rdp-ebook-builder')   . '">';
        echo '<p>';
        echo '</div>';
        echo '<div style="clear:both;"></div>';
    }//renderImportMetabox    
    
    static function renderCoverBuilderMetabox($post){
        $baseURL = RDP_EBB_PLUGIN_BASEURL;
        $post->filter = '';  
        $bookMeta = $post->_ebook_metadata;
        if(empty($bookMeta)) $bookMeta = []; 
        
        $allImages = RDP_EBB_BOOK::allImages($post->ID);
        
        $subtitle = RDP_EBB_Utilities::rgar($bookMeta, 'subtitle','');
        $publisher = RDP_EBB_Utilities::rgar($bookMeta, 'publisher','');
        $editor = RDP_EBB_Utilities::rgar($bookMeta, 'editor','');
        $coverStyle = RDP_EBB_Utilities::rgar($bookMeta, 'cover_theme');
        if(empty($coverStyle))$coverStyle = 'nico_6';
        $imageURL = (key_exists('image_url', $bookMeta))? $bookMeta['image_url'] : '';

        $titleEncoded = urlencode($post->post_title);
        $subtitleEncoded = urlencode($subtitle);
        $publisherEncoded = urlencode($publisher);
        $editorEncoded = urlencode($editor);
        $imageURLEncoded = (key_exists('image_url', $bookMeta))? urlencode($bookMeta['image_url']) : '';

        $sHTML = <<<EOH
                <div class="coverPreviewArea_wrap">
                <div style="display: flex;justify-content: center;margin-left: 8px;">
                <div class="coverPreviewArea" data-theme="{$coverStyle}">
                <div class="ready">
                <img class="coverImage" src="{$baseURL}/pl/cover.php?cover_style={$coverStyle}&width=201&subtitle={$subtitleEncoded}&editor={$editorEncoded}&publisher={$publisherEncoded}&title={$titleEncoded}&title_image={$imageURLEncoded}" alt="" width="201" height="311">
                </div><!-- .ready -->
                </div><!-- .coverPreviewArea -->

                <div id="color-theme">
                <span class="label">Color theme:</span>
                <div id="theme_chooser">

                <div class="theme theme_nico_0">
                <div class="theme_preview" style="background-color: rgb(0, 0, 0);" data-theme="nico_0"></div>
                </div><!-- .theme_nico_0 -->

                <div class="theme theme_nico_2">
                <div class="theme_preview" style="background-color: rgb(62, 62, 62);" data-theme="nico_2"></div>
                </div><!-- .theme_nico_2 -->

                <div class="theme theme_nico_3">
                <div class="theme_preview" style="background-color: rgb(255, 255, 255);" data-theme="nico_3"></div>
                </div><!-- .theme_nico_3 -->

                <div class="theme theme_nico_4">
                <div class="theme_preview" style="background-color: rgb(189, 180, 134);" data-theme="nico_4"></div>
                </div><!-- .theme_nico_4 -->

                <div class="theme theme_nico_5">
                <div class="theme_preview" style="background-color: rgb(204, 215, 85);" data-theme="nico_5"></div>
                </div><!-- .theme_nico_5 -->

                <div class="theme theme_nico_6">
                <div class="theme_preview" style="background-color: rgb(235, 239, 187);" data-theme="nico_6"></div>
                </div><!-- .theme_nico_6 -->

                <div class="theme theme_nico_7">
                <div class="theme_preview" style="background-color: rgb(81, 176, 108);" data-theme="nico_7"></div>
                </div><!-- .theme_nico_7 -->

                <div class="theme theme_nico_8">
                <div class="theme_preview" style="background-color: rgb(172, 206, 171);" data-theme="nico_8"></div>
                </div><!-- .theme_nico_8 -->

                <div class="theme theme_nico_9">
                <div class="theme_preview" style="background-color: rgb(23, 105, 83);" data-theme="nico_9"></div>
                </div><!-- .theme_nico_9 -->

                <div class="theme theme_nico_10">
                <div class="theme_preview" style="background-color: rgb(175, 221, 230);" data-theme="nico_10"></div>
                </div><!-- .theme_nico_10 -->

                <div class="theme theme_nico_11">
                <div class="theme_preview" style="background-color: rgb(35, 179, 200);" data-theme="nico_11"></div>
                </div><!-- .theme_nico_11 -->

                <div class="theme theme_nico_12">
                <div class="theme_preview" style="background-color: rgb(166, 183, 219);" data-theme="nico_12"></div>
                </div><!-- .theme_nico_12 -->

                <div class="theme theme_nico_13">
                <div class="theme_preview" style="background-color: rgb(50, 83, 157);" data-theme="nico_13"></div>
                </div><!-- .theme_nico_13 -->

                <div class="theme theme_nico_15">
                <div class="theme_preview" style="background-color: rgb(232, 85, 150);" data-theme="nico_15"></div>
                </div><!-- .theme_nico_15 -->

                <div class="theme theme_nico_16">
                <div class="theme_preview" style="background-color: rgb(193, 7, 68);" data-theme="nico_16"></div>
                </div><!-- .theme_nico_16 -->

                <div class="theme theme_nico_17">
                <div class="theme_preview" style="background-color: rgb(208, 24, 22);" data-theme="nico_17"></div>
                </div><!-- .theme_nico_17 -->

                <div class="theme theme_nico_18">
                <div class="theme_preview" style="background-color: rgb(241, 187, 142);" data-theme="nico_18"></div>
                </div><!-- .theme_nico_18 -->

                <div class="theme theme_nico_19">
                <div class="theme_preview" style="background-color: rgb(227, 122, 39);" data-theme="nico_19"></div>
                </div><!-- .theme_nico_19 -->

                <div class="theme theme_nico_20">
                <div class="theme_preview" style="background-color: rgb(254, 255, 112);" data-theme="nico_20"></div>
                </div><!-- .theme_nico_20 -->

                <div class="theme theme_nico_21">
                <div class="theme_preview" style="background-color: rgb(255, 222, 9);" data-theme="nico_21"></div>
                </div><!-- .theme_nico_21 -->

                </div><!-- #theme_chooser -->
                </div><!-- #color-theme -->   
                </div>
                </div>
                
                 
EOH;
      
        echo $sHTML;
        
        $imageChooseText = __('Choose an image for the cover','rdp-ebook-builder');
        $altImageHeaderText = __('Alternative image for cover','rdp-ebook-builder');
        $altImageChooseText = __('Choose or upload another image for the cover','rdp-ebook-builder'); 
        $altImage = get_post_meta($post->ID, '_alternative_image', true);
        
        echo '<input type="hidden" id="txtImageURLInput" name="bookImageURL" value="' . esc_attr($imageURL) . '" /> ';
        echo '<input type="hidden" id="txtCoverThemeInput" name="bookCoverTheme" value="' .$coverStyle. '" /> ';
        echo '<div class="image_chooser_wrap">';
        echo '<div id="image-choose-text" class="label">' . $imageChooseText . ':</div>';
        echo '<div id="cover_image">';
        echo '<div class="thumbelina-but horiz left">&#706;</div>';
        echo '<ul id="image_chooser" class="light">';
        
        $class = empty($imageURL)? "selected" : "";
        echo '<li class="image_preview no-image"><a class="image_chooser_link"><img class="cover-image-thumbnail no-image ' . $class . '"></a></li>';
        
        $wikipediaLogo = RDP_EBB_PLUGIN_BASEURL . '/pl/images/Wikipedia-logo.png';
        $class = ($imageURL === $wikipediaLogo)? "selected" : "";
        echo '<li><a class="image_chooser_link"><img class="cover-image-thumbnail ' . $class . '" style="max-height: 80px; width: auto;" src="' . esc_attr($wikipediaLogo) . '"></a></li>';
        
        foreach ($allImages as $image) {
            $class = ($imageURL === $image)? "selected" : "";
            echo '<li><a class="image_chooser_link"><img class="cover-image-thumbnail ' . $class . '" style="max-height: 80px; width: auto;" src="' . esc_attr($image) . '"></a></li>';  
        }

        echo '</ul><!-- #image_chooser -->';
        echo '<div class="thumbelina-but horiz right">&#707;</div>';
        echo '</div><!-- #cover_image --> ';
        
        printf ('<h2 style="margin-top: 10px; margin-bottom: 0; padding: 0;"><b>%s:</b></h2>', $altImageHeaderText);
        printf ('<p style="margin-top: 0; margin-bottom: 20px;">%s</p>', $altImageChooseText);
        
        
        echo "<div class='alternative-image-preview-wrapper'>";
        printf ("<img id='alternative-image-preview' src='%s' style='max-height: 100px;'>",  esc_attr($altImage));
        echo '</div><!-- .alternative-image-preview-wrapper -->';
        printf ('<button id="btnUploadAltImage" type="button" class="button %s">%s</button>', empty($altImage)? '' : 'hidden' ,esc_html( 'Select Alternative Image' ));
        printf ('<button id="btnRemoveAltImage" type="button" class="button %s">%s</button>', empty($altImage)? 'hidden' : '',esc_html( 'Remove' ));
        printf (" <input type='hidden' name='alternative-image-url' id='alternative-image-url' value='%s'>", esc_attr($altImage));
        
        echo '</div>';
    }//renderCoverBuilderMetabox  
    
    
    
    
    
    static function redirectPostLocationFilter($location, $post_id) {
        return get_edit_post_link( $post_id, 'url' );
    }//redirectPostLocationFilter      
    
}//RDP_EBB_ADMIN
