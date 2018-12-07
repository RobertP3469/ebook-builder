<?php

if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); 

class RDP_EBB_IMPORT {
    
    public static function handleMediawikiImport($URL,$dataPass,$sErrorMsgPreamble,$baseURL,$book_id = 0) {
        $hasError = false;
        $curl = curl_init();
        // Make the request
        curl_setopt($curl, CURLOPT_URL, $URL );
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_COOKIEFILE, "/tmp/cookie.txt");
        curl_setopt($curl, CURLOPT_COOKIEJAR, "/tmp/cookie.txt");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);

        if (FALSE === $response){
            $msg = __("Unable to retrieve book content.\nCode: cURL error",'rdp-wiki-book-builder');
            $dataPass['code'] = '400' ;
            $dataPass['message'] = $sErrorMsgPreamble . $msg;
            $hasError = true;            
            throw new Exception(curl_error($curl), curl_errno($curl));
        }
        curl_close($curl);

        $html = new rdp_simple_html_dom(); // Create new parser instance
        $html->load($response,true,false); 
        
        if(!$html){
            $msg = __("Unable to retrieve book content.\nCode: DOM Parser failed to load",'rdp-wiki-book-builder');
            $dataPass['code'] = '400' ;
            $dataPass['message'] = $sErrorMsgPreamble . $msg;
            $hasError = true;
            return $dataPass;
        }
        
        $body = $html->find('#content',0);

        if(!$body){
            $msg = __("Unable to retrieve book content.\nCode: No Content",'rdp-wiki-book-builder');
            $dataPass['code'] = '400' ;
            $dataPass['message'] = $sErrorMsgPreamble . $msg;
            $hasError = true;
            return $dataPass;
        }  
       
        // parse HTML in body object
        self::mediawikiContentPieces_Parse($body,$URL,$baseURL,$book_id);
//       $downloadURL = RDP_WBB_BOOK::generateHTMLFile($book_id);
        
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

        if($bookMeta):
            $bookMeta['items'] = [];
            $bookMeta['toc'] = [];

            if(!add_post_meta($book_id, '_ebook_metadata', $bookMeta, true)):
                update_post_meta($book_id, '_ebook_metadata', $bookMeta);
            endif;
            
        else:
           
            if(!add_post_meta($book_id, '_ebook_metadata', $bookMeta, true)){
                update_post_meta($book_id, '_ebook_metadata', $bookMeta);
            }    
            RDP_EBB_BOOK::handleFeaturedImage($book_id, $sCoverImageURL);
                   
        endif;
        
  
        RDP_EBB_BOOK::deleteJSONFile($book_id);   
        
        $toc = $body->find('#mw-content-text dl',0);
        if($toc){
            $items = $toc->children();
            foreach($items as $item){
                $x = $item->tag;
                switch ($item->tag) {
                    case 'dt':
                        $sTitle = RDP_EBB_Utilities::unXMLEntities($item->plaintext);
                        RDP_EBB_CHAPTER::add( $sTitle );
                        $parentKey = md5($sTitle);
                        break;
                    case 'dd':
                        $sTitle = '';
                        $sURL = '';
                        $ret = $item->find('a',0);
                        if($ret){
                            $sTitle = RDP_EBB_Utilities::unXMLEntities($ret->plaintext);
                            $articleURLPieces = parse_url($ret->href);
                            if(empty($articleURLPieces['scheme'])):
                                $articleURLPieces['scheme'] = $baseURLPieces['scheme'];
                                $articleURLPieces['host'] = $baseURLPieces['host'];                                
                            endif;
                            

                            $sURL = RDP_EBB_Utilities::unparse_url($articleURLPieces);

                        }else{
                            $sTitle = RDP_EBB_Utilities::unXMLEntities($item->plaintext);
                        }
                        
                        RDP_EBB_ARTICLE::add($sTitle, $sURL, $parentKey);
                        break;
                    default:
                        break;
                }
            }
        }


    }//mediawikiContentPieces_Parse 
    
    
    private static function prepareMetadata() {
        $sTitle = '';  
        $sSubtitle = '';
        $sEditor = $current_user->display_name;  
        $sPublisher = $current_user->display_name;  
        $sLanguage = '';
        $sCoverStyle = 'nico_6';
        $sCoverImageURL = RDP_EBB_PLUGIN_BASEURL . '/pl/cover.php';
        $sTitleimageURL = '';  
        $parentKey = '';

        $headline = $body->find('h2 .mw-headline',0);
        if($headline){
            $sTitle = $headline->plaintext;
        }

        $headline = $body->find('h3 .mw-headline',0);
        if($headline){
            $sSubtitle = $headline->plaintext;
        }   

        $params = [
            'cover_style'   => $sCoverStyle,
            'subtitle'      => $sSubtitle,
            'editor'        => $sEditor,
            'title'         => $sTitle,
            'title_image'   => $sTitleimageURL,
            'publisher'     => $sPublisher
        ];

        $sCoverImageURL .= '?' . http_build_query($params);            

        $book->post_title = $sTitle;
        $post_id = wp_update_post( $book );

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
        return $bookMeta;
    }//prepareMetadata
}//RDP_EBB_IMPORT
