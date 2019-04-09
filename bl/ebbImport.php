<?php

if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); 

class RDP_EBB_IMPORT {
    
    public static function handleMediawikiImport($url,$dataPass,$baseURL,$book_id = 0) {
        
        $hasError = false;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url );
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');        
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_COOKIEFILE, "");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,0); 
        curl_setopt($curl, CURLOPT_TIMEOUT, 400); //timeout in seconds    
        set_time_limit(0);
        // Make the request
        $response = curl_exec($curl);

        $html = null;
        $body = null;
        

        if (FALSE === $response){
            $msg = __("Unable to retrieve book content.\nCode: cURL error",'rdp-ebook-builder');
            $dataPass['code'] = '400' ;
            $dataPass['messages'][] = RDP_EBB_PLUGIN::errorMessagePreamble() . $msg;
            $hasError = true;            
//            throw new Exception(curl_error($curl), curl_errno($curl));
        }
        curl_close($curl);

        if(!$hasError):
            $html = new rdp_simple_html_dom(); // Create new parser instance
            $html->load($response,true,false); 

            if(!$html){
                $msg = __("Unable to retrieve book content.\nCode: DOM Parser failed to load",'rdp-ebook-builder');
                $dataPass['code'] = '400' ;
                $dataPass['messages'][] = RDP_EBB_PLUGIN::errorMessagePreamble() . $msg;
                $hasError = true;
            }            
        endif;

        if(!$hasError):
            $body = $html->find('#content',0);
        

            if(!$body){
                $msg = __("Unable to retrieve book content.\nCode: No Content",'rdp-ebook-builder');
                $dataPass['code'] = '400' ;
                $dataPass['messages'][] = RDP_EBB_PLUGIN::errorMessagePreamble() . $msg;
                $hasError = true;
            }             
        endif;        
 
       if(!$hasError):
            // parse HTML in body object
           $dataPass = self::mediawikiContentPieces_Parse($body,$url,$baseURL,$book_id);
           if($dataPass['code'] == 200) $downloadURL = RDP_EBB_BOOK::generateHTMLFile($book_id);           
       endif;

        
        $html->clear(); 
        unset($html);   
     
        
        return $dataPass;        
    } //handleMediawikiImport  
    
    
    /**
     * 
     * @param rdp_simple_html_dom $body
     * @param string $URL
     * @param string $baseURL
     * @param int $book_id
     */    
    private static function mediawikiContentPieces_Parse(&$body,$URL,$baseURL,$book_id){
        global $current_user;
        $baseURLPieces = parse_url($baseURL);
        $book = get_post($book_id);
        $book->filter('');
        $bookMeta = $book->_ebook_metadata;
        $parentKey = '';
        

        // array to store error messages
         $dataPass = [
            'code' => 200,
            'messages' => []
        ];
        
        $sTitle = '';  
        $sSubtitle = '';
        $sEditor = $current_user->display_name;  
        $sPublisher = $current_user->display_name;  
        $sLanguage = '';
        $sCoverStyle = 'nico_6';
        $sCoverImageURL = RDP_EBB_PLUGIN_BASEURL . '/pl/cover.php?';
        $sTitleimageURL = '';  

        $headline = $body->find('h2 .mw-headline',0);
        if($headline){
            $sTitle = $headline->plaintext;
        }

        $headline = $body->find('h3 .mw-headline',0);
        if($headline){
            $sSubtitle = $headline->plaintext;
        }         
        
        if($bookMeta):
            $bookMeta['items'] = [];
            $bookMeta['toc'] = [];
            $bookMeta['title'] = $sTitle;
            $bookMeta['subtitle'] = $sSubtitle;
            $bookMeta['link'] = $URL;
            
            $params = [
                'cover_style'   => $bookMeta['cover_theme'],
                'subtitle'      => $bookMeta['subtitle'],
                'editor'        => $bookMeta['editor'],
                'title'         => $bookMeta['title'],
                'title_image'   => $bookMeta['image_url'],
                'publisher'     => $bookMeta['publisher']
            ];

            $sCoverImageURL .= http_build_query($params);             
            $bookMeta['cover_image'] = $sCoverImageURL;
            RDP_EBB_Utilities::savePostMeta($book_id,$bookMeta);
            
            $book->post_title = $bookMeta['title'];
            $post_id = wp_update_post( $book ); 

            RDP_EBB_BOOK::handleFeaturedImage($book_id, $bookMeta['cover_image']);            
            
        else:
            $params = [
                'cover_style'   => $sCoverStyle,
                'subtitle'      => $sSubtitle,
                'editor'        => $sEditor,
                'title'         => $sTitle,
                'title_image'   => $sTitleimageURL,
                'publisher'     => $sPublisher
            ];

            $sCoverImageURL .= http_build_query($params);             
            
            $bookMeta = RDP_EBB_BOOK::bookMetadataStructure();
            $bookMeta['title'] = $sTitle;
            $bookMeta['subtitle'] = $sSubtitle;
            $bookMeta['editor'] = $sEditor;
            $bookMeta['cover_theme'] = $sCoverStyle;
            $bookMeta['cover_image'] = $sCoverImageURL;
            $bookMeta['image_url'] = $sTitleimageURL;
            $bookMeta['language'] = $sLanguage;
            $bookMeta['link'] = $URL;
            $bookMeta['author_id'] = $book->post_author;
            $bookMeta['editor'] = $current_user->display_name;            
            $bookMeta['publisher'] = $current_user->display_name; 
            RDP_EBB_Utilities::savePostMeta($book_id,$bookMeta); 
            
            $book->post_title = $bookMeta['title'];
            $post_id = wp_update_post( $book );            
            
            RDP_EBB_BOOK::handleFeaturedImage($book_id, $bookMeta['cover_image']);
                   
        endif;
        
  
        RDP_EBB_BOOK::deleteJSONFile($book_id);   
        
        $toc = $body->find('#mw-content-text dl',0);
        if($toc){
            $items = $toc->children();
            foreach($items as $item){
                $x = $item->tag;
                switch ($item->tag) {
                    case 'dt':
                        $sTitle = wp_specialchars_decode($item->plaintext);
                        RDP_EBB_CHAPTER::add( $sTitle, $book_id );
                        $parentKey = md5($sTitle);
                        break;
                    case 'dd':
                        $sTitle = '';
                        $sURL = '';
                        $ret = $item->find('a',0);
                        if($ret){
                            $sTitle = wp_specialchars_decode($ret->plaintext);
                            $articleURLPieces = parse_url($ret->href);
                            if(empty($articleURLPieces['scheme'])):
                                $articleURLPieces['scheme'] = $baseURLPieces['scheme'];
                                $articleURLPieces['host'] = $baseURLPieces['host'];                                
                            endif;
                            

                            $sURL = RDP_EBB_Utilities::unparse_url($articleURLPieces);

                        }else{
                            $sTitle = RDP_EBB_Utilities::unXMLEntities($item->plaintext);
                        }
                                               
                        $result = RDP_EBB_ARTICLE::add($sTitle, $sURL, $book_id, $parentKey);
                        if($result['code'] !== 200):
                            $dataPass['code'] = 400;
                            $dataPass['messages'][] = $result['message'];
                        endif;
                        break;
                    default:
                        break;
                }
            }
        }

        return $dataPass;
    }//mediawikiContentPieces_Parse 
    
    

}//RDP_EBB_IMPORT
