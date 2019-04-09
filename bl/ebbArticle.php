<?php

if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); 

class RDP_EBB_ARTICLE {
    static function add( $title, $url, $book_id = '', $parent_key = '' ) { 
        if(empty($book_id))$book_id = intval(RDP_EBB_Utilities::globalRequest('book_id')); 
        if(empty($book_id)) return;
        
        $key = $url ? md5($url) : md5($title);
        $title = trim($title);
        $html = null;
        $body = null;        
  
        $book = get_post($book_id);
        $book->filter('');
        $bookMeta = $book->_ebook_metadata;

        // Array to store feedback to be returned to calling code
        $result = [
            'code'  => 200,
            'message'   => 'OK'
        ];

        $item = array(
                'type' => 'article',
                'content_type' => 'text/x-wiki',
                'title' => $title,
                'key' => $key,
                'timestamp' => time(),
                'url' => $url,
                'images' => array(),
        ); 
        
        if(!empty($url)):
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
        
            if (FALSE === $response):
                $errMsg = curl_error($curl);

                $result = [
                    'code'  => 400,
                    'message'   => "Unable to fetch content at $url.\nError message: $errMsg\nCode: Add article"
                ];  
            else:
                // Create new parser instance
                $html = new rdp_simple_html_dom(); 
                $html->load($response,true,false);              


                if($html){
                    $body = $html->find('body',0);
                    if($body){
                        $item['images'] = self::gatherImages($body,$item);
                    }
                }             
            endif;

            curl_close($curl);
        else:
            $result = [
                'code'  => 400,
                'message'   => "Missing article URL.\nCode: Add article"
            ];             
        endif;
        
        if($result['code'] == 200):
            if(!is_array($bookMeta)){
                $bookMeta = RDP_EBB_BOOK::bookMetadataStructure();
                $bookMeta['title'] = $title;
                $bookMeta['book_id'] = $book_id;
                $bookMeta['author_id'] = $book->post_author;
                $wp_user = new WP_User($book->post_author);
                $bookMeta['editor'] = $wp_user->display_name;            
                $bookMeta['publisher'] = $wp_user->display_name;            
            }        

            if($parent_key){
                $bookMeta['items'][$parent_key][$key] = $item;
            }else{
                $bookMeta['items'][$key] = $item;
            }

            $bookMeta['toc'] = RDP_EBB_BOOK::buildTOC($bookMeta['items']);
            update_post_meta($book_id, '_ebook_metadata', $bookMeta);


            // Begin JSON file update
            if(!empty($url)):
                RDP_EBB_CONTENT::scrub($url, $body);

                $itemFlat = array(
                    $key.'_type' => $item['type'],
                    $key.'_title' => $title,            
                    $key.'_url' => $url,
                    $key.'_plaintext' => $body->plaintext,
                    $key.'_html' => $body->outertext,
                    $key.'_images' => $item['images'],
                    $key.'_timestamp' => $item['timestamp']
                );        

                $bookObj = RDP_EBB_BOOK::fromJSONFile($book_id);

                if($bookObj){
                    $itemsOriginal = RDP_EBB_BOOK::object_to_array($bookObj->_ebook_metadata->items);
                    $bookObj->_ebook_metadata->items = array_merge($itemFlat, $itemsOriginal);
                    $bookObj->_ebook_metadata->toc = $bookMeta['toc'];
                 }else{
                    $bookObj = $book;
                    $bookMeta['items'] = $itemFlat;
                    $bookObj->_ebook_metadata = $bookMeta;
                }  

                RDP_EBB_BOOK::toJSONFile($bookObj);            
            endif;            
        endif;

        if($html){
            $html->clear(); 
            unset($html);             
        }

        return $result;        
    }//add   
    
    /**
     * 
     * @param rdp_simple_html_dom $body
     * @param array $item
     * @return array Image URLs
     */
    static function gatherImages($body,$item) {
        $images = array();
        $oURLPieces = parse_url($item['url']);  
        
        foreach($body->find('a.image img') as $img) {
            $src = $img->src;
            $srcSet = $img->srcset;
            $matches = [];
            
            if($srcSet){
                $srcs = explode(',', $srcSet);
                $raw = array_pop($srcs);
                preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $raw, $matches);
                if(!empty($matches))$src = trim($matches[0]);
            }else{
                preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $src, $matches); 
            }

            if(empty($matches)) continue;

            $oImgPieces = parse_url($src);
            if(empty($oImgPieces['scheme'])):
                $oImgPieces['scheme'] = $oURLPieces['scheme'];
            endif; 
            
            if(empty($oImgPieces['host'])):
                $oImgPieces['host'] = $oURLPieces['host'];
            endif;             

            $src = RDP_EBB_Utilities::unparse_url($oImgPieces);
            if(!in_array($src, $images)){
                $images[] = $src;
            }
        }        

        return $images;
    }//gatherImages    
    
    static function fetchEmbed() {
        header("Access-Control-Allow-Origin: *");
        $resource = RDP_EBB_Utilities::globalRequest('resource');

        $html = null;
        $body = null;  
        
        // Array to store feedback to be returned to client code
        $result = [
            'code'      => 200,
            'message'   => 'OK',
            'html'      => ''
        ];        
        
        if(empty($resource)):
            $result = [
                'code'      => 400,
                'message'   => 'Missing article URL.',
                'html'      => 'Missing article URL.'
            ];         
        else:
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $resource );
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
            
            if (FALSE === $response):
                $errMsg = curl_error($curl);
                $result = [
                    'code'  => 400,
                    'message'   => "Unable to fetch content at $url.\nError message: $errMsg\nCode: Fetch embed",
                    'html'   => "Unable to fetch content at $url.<br>Error message: $errMsg<br>Code: Fetch embed"
                ];  
            else:
                // Create new parser instance
                $html = new rdp_simple_html_dom(); 
                $html->load($response,true,false);              

                if($html){
                    $body = $html->find('body',0);
                }             
            endif;

            curl_close($curl);        
        endif;
            

        if($result['code'] == 200):
            RDP_EBB_CONTENT::scrub($resource, $body);
            $result['html'] = $body->outertext;
        endif;        

        echo json_encode($result);
        die();
        
    }//fetchEmbed
}//RDP_EBB_ARTICLE
