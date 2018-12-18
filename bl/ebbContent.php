<?php

if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); 


class RDP_EBB_CONTENT {
    static function scrub($url,&$body) {
        $remove_elements = array(
            '#jump-to-nav',
            '#column-one',
            '#siteSub',
            '#contentSub',
            '#catlinks',
            'script',
            'style',
            'link[rel=stylesheet]',
            '.mw-inputbox-centered',
            'table.plainlinks',
            'div.plainlinks',
            '.mw-indicators',
            'form.header',
            '#page-actions',
            'math',
            '.editsection',
            '.mw-editsection',
            '#toc',
            '.infobox',
            '#mw-navigation',
            '#footer',
            'table[class*=ambox-Unreferenced]',
            '.mw-jump-link',
            '.navbox',
            '.vertical-navbox',
            '.collapseButton'
        );

        $remove_elements = apply_filters('rdp_ebb_scrub_remove_elements_filter', $remove_elements);
        
        foreach ( $remove_elements as $element ) {
            foreach($body->find($element) as $e) 
            {
               if($e) $e->outertext = '';
            }            
        }
        
        foreach($body->find('table') as $e) 
        {
            $e->style = str_replace('float:right;', '', $e->style);
            $e->width = null;
        } 
        
        $source = $body->find('.printfooter',0);
        if($source){
            $source->innertext = sprintf('Source: <a rel="external_link" class="external" href="%1$s">%1$s</a>', $url);
        }
        
        
        $oURLPieces = parse_url($url);  
        if(key_exists('path', $oURLPieces)) unset($oURLPieces['path']);
        if(key_exists('port', $oURLPieces)) unset($oURLPieces['port']);
        if(key_exists('user', $oURLPieces)) unset($oURLPieces['user']);
        if(key_exists('pass', $oURLPieces)) unset($oURLPieces['pass']);
        if(key_exists('query', $oURLPieces)) unset($oURLPieces['query']); 
        if(key_exists('fragment', $oURLPieces)) unset($oURLPieces['fragment']);       
        if(empty($oURLPieces['scheme']))$oURLPieces['scheme'] = 'http';  
        $rootSource = RDP_EBB_Utilities::unparse_url($oURLPieces); 
     
        foreach($body->find('img') as $img){
            $oImgPieces = parse_url($img->src);
            if(!is_array($oImgPieces)){
                $img->outertext = '';
                continue;
            }
            if(!key_exists('path', $oImgPieces)){
                $img->outertext = '';
                continue;                
            }    
            
            $sPath = $oImgPieces['path'];
            if(substr($sPath, 0, 3) == '../')$sPath = substr($sPath, 3);
            $oImgPieces['path'] = RDP_EBB_Utilities::leadingslashit($sPath);            
            
            if(!isset($oImgPieces['host'])):
                $oImgPieces['host'] = $oURLPieces['host'];
            endif;
            
            if(!isset($oImgPieces['scheme'])):
                $oImgPieces['scheme'] = $oURLPieces['scheme'];
            endif;
            
            $img->src = RDP_EBB_Utilities::unparse_url($oImgPieces);
            
            $data = 'data-file-width';
            $img->$data = null;
            $data = 'data-file-height';
            $img->$data = null;
            $img->srcset = null;
            if($img->width >= 400 || $img->height >= 400){
               $img->width = null;
               $img->height = null;
               $img->style = 'width: 100%;max-width: 400px;height: auto;';
            }
        }
        
        foreach($body->find('a') as $link){
            if(!isset($link->href) || substr($link->href, 0, 1) === '#') continue;

            $link->href = RDP_EBB_Utilities::entitiesPlain($link->href);
            $oLinkPieces = parse_url($link->href); 
            if(!is_array($oLinkPieces))  continue;
            
            $sQuery = parse_url($link->href,PHP_URL_QUERY);
            $oQueryPieces = array();
            parse_str($sQuery,$oQueryPieces);
            
            $pos = strpos($link->href, 'Special:');
            $fIsSpecial = !($pos === false);
            if($fIsSpecial){
                $link->outertext = $link->innertext;
                continue;
            }
          
            if(strtolower($link->innertext) == 'printable version'){
                $link->class .= ' external';

            }
            
            if(is_array($oQueryPieces) && isset($oQueryPieces['action']) && strtolower($oQueryPieces['action']) == 'edit'){
                    $link->outertext = '';
                    continue;
            }

            if(key_exists('path', $oLinkPieces)):
                $sPath = $oLinkPieces['path'];
                if(substr($sPath, 0, 3) == '../')$sPath = substr($sPath, 3);
                if(substr($sPath, 0, 2) == '..')$sPath = substr($sPath, 2);
                if(substr($sPath, 0, 1) != '/')$sPath = '/'.$sPath;
                $oLinkPieces['path'] = $sPath;                
            endif;
            
            if(!isset($oLinkPieces['scheme'])):
                $oLinkPieces['scheme'] = $oURLPieces['scheme'];
            endif;  

            if(!isset($oLinkPieces['host'])):
                $oLinkPieces['host'] = $oURLPieces['host'];
            endif;

            $link->href = RDP_EBB_Utilities::unparse_url($oLinkPieces);
            
        }  
        
        
        foreach($body->find('[id^=cite] a') as $anchor){
            if(isset($anchor->href)){
                $anchor->href = substr($anchor->href, strrpos($anchor->href,'#') );
            }
        }        
        
        $len = strlen($rootSource);        
        foreach($body->find('a') as $link){
            $fIsFile = false;
            $pos = -1;
            $classes = array();

            if(isset($link->class)){
                $classes = explode(' ',$link->class) ;
            }              

            $fIsExternal = in_array('external', $classes);


            if(isset($link->href)){
                if(!$fIsExternal):
                    $pos = (substr(strtolower($link->href), 0, $len) === $rootSource);
                    $fIsExternal = !($pos === true);                   
                endif;

                if($fIsExternal)$link->rel = 'external_link'; 

                $isCiteAnchor = true;
                if(substr($link->href, 0, 1) !== '#'){
                    $isCiteAnchor = false;  
                    $fIsFile = RDP_EBB_Utilities::isScriptStyleImgRequest($link->href);                  
                }                    
            }             

            if(isset($link->href) && ($isCiteAnchor === false)){
                    $link->target = '_blank';                    
            }

            if(!$fIsExternal && 
                isset($link->href) &&
                ($isCiteAnchor === false)){
                if(isset($link->class)){
                    $link->class .= ' wiki-link';
                }else{
                    $link->class = 'wiki-link';
                }
                
                $data = 'data-key';
                $link->$data = md5($link->href);
            }            
//
//            if( !$fIsExternal && 
//                isset($link->href) &&
//                ($pos === false)){
//                    $sHREF = $link->href;
//                    $encodedURL = urlencode($link->href);
//                    // restore hashtags
//                    $encodedURL = str_replace('%23', '#',$encodedURL);
//                    $params = array(
//                        'rdp_we_resource' => $encodedURL
//                    );
//                    $link->href = esc_attr(add_query_arg($params,$permalink));                
//                    $link->target = null;
//
//                    $sQuery = parse_url($sHREF,PHP_URL_QUERY);
//                    if($sQuery){
//                        parse_str($sQuery, $output);
//                        if(key_exists('rdp_we_resource', $output)){
//                            $sHREF = $output['rdp_we_resource'];
//                        }
//                    }
//
//                    if(!$fIsFile){
//                        $att = 'data-href';
//                        $link->$att = esc_attr($sHREF);
//                        $att = 'data-title';
//                        $link->$att = esc_attr($link->innertext);                    
//                        $link->title = esc_attr($link->innertext);                        
//                    }
//            }

        } 


        $sCite = 'cite_note';
        $len = strlen($sCite);
        foreach($body->find('[id]') as $element){
            if(substr($element->id, 0, $len) === $sCite)continue;
            if($element->class){
                if(strpos($element->class, 'mw-headline') !== false)continue;
            }
            $element->id = 'rdp-ebb-' . $element->id;
        } 
    }//content_scrub   
} //RDP_EBB_CONTENT
