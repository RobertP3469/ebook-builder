<?php

if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); 

class RDP_EBB_CHAPTER {
    static function add( $title, $book_id ) { 
        if(empty($book_id))$book_id = intval(RDP_EBB_Utilities::globalRequest('book_id')); 
        if(empty($book_id)) return;
               
        $bookObj = RDP_EBB_BOOK::fromJSONFile($book_id); 
        $book = get_post($book_id);        
        $book->filter('');
        $bookMeta = $book->_ebook_metadata;  
        $key = md5($title);
       
        $item = array(
                'type'      => 'chapter',
                'title'     => $title,
                'key'       => $key
        );
        
        if(!is_array($bookMeta)){
            $bookMeta = RDP_EBB_BOOK::bookMetadataStructure();
            $bookMeta['title'] = $title;
            $bookMeta['book_id'] = $book_id;
            $bookMeta['author_id'] = $book->post_author;
            $wp_user = new WP_User($book->post_author);
            $bookMeta['editor'] = $wp_user->display_name;            
            $bookMeta['publisher'] = $wp_user->display_name;            
        }        
        
        $bookMeta['items'][$key] = $item;
        $bookMeta['toc'] = RDP_EBB_BOOK::buildTOC($bookMeta['items']);        
        update_post_meta($book_id, '_ebook_metadata', $bookMeta);        

         // Do JSON file update
        if($bookObj){
            $bookObj->_ebook_metadata->toc = $bookMeta['toc'];
         }else{
            $bookObj = $book;
            $bookObj->_ebook_metadata = $bookMeta;
        }   
        RDP_EBB_BOOK::toJSONFile($bookObj);
        
        
    }//add    
}//RDP_EBB_CHAPTER
