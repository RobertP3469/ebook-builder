<?php

if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); 

class RDP_EBB_BOOK {
    
  
    
    public static function fetchItemsObject($book_id) {
        $bookMeta = get_post_meta($book_id, RDP_EBB_PLUGIN::$metadata_key,true);
        if(empty($bookMeta))$bookMeta = [];
        $items = (isset($bookMeta['items']))? $bookMeta['items'] : [] ;
        return $items;
    } //fetchItemsObject      
    
    public static function allImages($book_id) {
        $items = self::fetchItemsObject($book_id);
        $imageList = [];
                
        $gatherImages = function($o,$k) use (&$imageList,&$gatherImages){
            if($o['type'] === 'article'){
                $imageList[] = $o['images'];
            }
            foreach ($o as $key => $value) {
                if(is_array($value) && key_exists('key', $value)){
                    $gatherImages($value,$key);
                }
            }            
        }; //$gatherImages
        
        foreach ($items as $key => $value) {
            $gatherImages($value,$key);
        }

        $imagesUnique = [];
        array_map(function($n) use (&$imagesUnique){
            $imagesUnique = array_unique(array_merge($imagesUnique,$n));
        }, $imageList);

        return $imagesUnique;        
    }//allImages    
    
    public static function bookMetadataStructure(){
        global $current_user;
        $defaultMsg = '<span></span> ' . esc_html__('Please log in to read online.', RDP_EBB_PLUGIN::$plugin_slug);
        $settings = array(
            'show_cover' => '1',
            'show_title' => '1',
            'show_subtitle' => '0',
            'show_full_title' => '1',
            'show_editor' => '1',
            'show_editor_pic' => '1',
            'show_publisher' => '1',            
            'show_language' => '1',            
            'show_size' => '1',
            'show_toc' => '1',
            'show_content_beneath_cover' => '1',
            'cta_button' => '1',
            'content_location' => '1',
            'toc_links' => 'disabled',
            'log_in_msg' => $defaultMsg,
            'cover_size' => 'medium',
            
        );
        
        return array(
            'enabled' => 'on',
            'private' => '0',
            'guid' => RDP_EBB_Utilities::GUID(),
            'title' => 'Your title',
            'subtitle' => '',
            'cover_theme' => 'nico_6',
            'cover_image' => '',
            'editor' => $current_user->display_name,
            'publisher' => $current_user->display_name,
            'author_id' => $current_user->ID,
            'image_url' => '',
            'items' => [],
            'link' => '',
            'price_currency' => '',
            'price_amount' => '',
            'book_size' => '',
            'download_url' => '',
            'language' => '',
            'cta_button_content' => '',
            'toc' => '',
            'settings' => $settings,
        );           
    }//bookMetadataStructure   
    
    public static function handleFeaturedImage($book_id,$cover_image){
        $filename = sanitize_file_name('ebook-' . $book_id . '.png');
 	$file_array = array('name' => $filename); 
        
	if ( !function_exists('media_handle_upload') ) {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
	}        
  
	$tmp = download_url( $cover_image, 3000);        
	$file_array['tmp_name'] = $tmp;

	// If error storing temporarily, unlink
	if ( is_wp_error( $tmp ) ) {
            
            try {
                @unlink($file_array['tmp_name']);
            } catch (Exception $exc) {
                $msg = $exc->getTraceAsString();
            }

            
            $file_array['tmp_name'] = '';
            return $tmp;       
	}

	// do the validation and storage stuff
	$thumbnail_id = media_handle_sideload( $file_array, $book_id ); 
        
	// If error storing permanently, unlink
	if ( is_wp_error($thumbnail_id) ) {
            @unlink($file_array['tmp_name']);
            return $thumbnail_id;
	}
       
        
        if (has_post_thumbnail( $book_id ) ):
            $tn_id = get_post_thumbnail_id( $book_id );
            $result = delete_post_thumbnail($book_id);
            $result = wp_delete_attachment($tn_id, true);            
        endif;  
        
        $result = set_post_thumbnail( $book_id, $thumbnail_id );
    }//handleFeaturedImage     
}//RDP_EBB_BOOK
