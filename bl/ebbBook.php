<?php

if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); 

class RDP_EBB_BOOK {
    private $_version;
    private $_options = array();
    private $_default_settings = array();
    private $_book = null;

    public function __construct( $version,$options ) {
        $this->_version = $version;
        $this->_options = $options;
        $this->_default_settings = RDP_EBB_PLUGIN::default_settings();
    }//__construct 

    public function render($content){
        global $wp_query;
        $sInlineHTML = '';
        $book_id = $wp_query->get_queried_object_id();
        $this->_book = get_post($book_id);
        $post_author = $this->_book->post_author;
//        if(is_preview()){
//            $this->_book = wp_get_post_autosave($book_id, $post_author);
//        }

        $this->_book->filter = '';
        $meta = $this->_book->_ebook_metadata;
        $settings = $meta['settings'];
        $this->enqueueStylesScripts($meta,$settings);
        
        $sClasses = '';
        if(empty($settings['show_cover']))$sClasses .= ' no-cover';
        if(empty($settings['show_title']))$sClasses .= ' no-title';
        if(empty($settings['show_subtitle']))$sClasses .= ' no-subtitle';
        if(empty($settings['show_full_title']))$sClasses .= ' no-full-title';
        if(empty($settings['show_editor']))$sClasses .= ' no-editor';         
        if(empty($settings['show_editor_pic']))$sClasses .= ' no-editor-pic';         
        if(empty($settings['show_publisher']))$sClasses .= ' no-publisher';         
        if(empty($settings['show_language']))$sClasses .= ' no-language';         
        if(empty($settings['show_size']))$sClasses .= ' no-size';         
        if(empty($settings['show_toc']))$sClasses .= ' no-toc';         
        if(empty($settings['show_content_beneath_cover']))$sClasses .= ' no-content-beneath-cover';         
        
        $content_location = max(1, intval(RDP_EBB_Utilities::rgar($settings, 'content_location',1)));
        
        $sMainContentClasses = apply_filters('rdp_wbb_book_main_content_classes', $sClasses ) ;        
        $sHTML = '<div id="rdp-wbb-book" class="book_show' . $sMainContentClasses . '" data-id="' . esc_attr($book_id) . '" data-guid="' . esc_attr($meta['guid']) . '">';
        
        if($content_location == 1) $sHTML .= $content;
        
        $sHTML .= '<div class="wrap" style="clear: right;"><div class="s1 w4"><div id="coverPreviewArea" class="' . esc_attr($settings['cover_size']) . '">';   
        
        $sHTML .= $this->renderCover($book_id, $meta, $settings);
          
        $sHTML .= '</div><!-- #coverPreviewArea --></div><!-- .s1 .w4 -->'; 
        
        $sHTML .= $this->renderMetadata($book_id, $meta, $settings, $sClasses, $sInlineHTML);

        $sHTML .= '<div class="clear">&nbsp;</div>';
        $sHTML .= '</div><!-- .wrap -->';  
        
        if($content_location == 2) $sHTML .= $content;

        $sHTML .= $this->buildTOCHTML($meta, $settings);
        
        if($content_location == 3) $sHTML .= $content;
        $sHTML .= '</div><!-- #rdp-wbb-book -->'; 
        $sHTML .= $sInlineHTML;
        return $sHTML;
    }//render    
    
    
    private function renderCover($book_id,$meta,$settings) {
        $sHTML = '';
        if (empty($settings['show_cover']) )return $sHTML;
        
        $attachment_id = get_post_thumbnail_id( $book_id );
        $image_attributes = wp_get_attachment_image_src( $attachment_id,'full' );
        $src = ( $image_attributes )? $image_attributes[0] : RDP_EBB_PLUGIN_BASEURL . '/pl/images/mystery-book-cover.png';
        $sHTML .= '<div class="ready">';
        $sHTML .= '<img id="coverImage" src="' . esc_attr($src) . '" alt="' . esc_attr($meta['title']) . '" class="' . esc_attr($settings['cover_size']) . '" />';
        $sHTML .= '</div><!-- .ready -->'; 
        
        
        if(!empty($settings['show_content_beneath_cover']) && !empty($this->_options['sBookContentBeneathCover'])){
            $sHTML .= '<div id="contentBeneathCover">' . $this->_options['sBookContentBeneathCover'] . '</div>';
        }
         
        return $sHTML;
    }//renderCover   
    
    
    private function renderMetadata($book_id,$meta,$settings,$sClasses, &$sInlineHTML) {
        $sHTML = ''; 
        $metaHTMLOpen = '<div id="rdp-wbb-metadata" class="s0l w4">';
        $metaHTML = '';
        $fIncludeMeta = !empty($settings['show_title'])
                        || !empty($settings['show_subtitle'])
                        || !empty($settings['show_full_title'])
                        || !empty($settings['show_editor'])
                        || !empty($settings['show_publisher'])
                        || !empty($settings['show_language'])
                        || !empty($settings['show_size']);  

        if($fIncludeMeta):
            $sTitle = (!empty($meta['title']))? $meta['title'] : '';
            $sSubtitle = (!empty($meta['subtitle']))? $meta['subtitle'] : '';            
            $FullTitle = (!empty($meta['subtitle']))? $sTitle . ': ' . $sSubtitle : $sTitle; 
            

            if(!empty($settings['show_title']) || !empty($settings['show_full_title'])){
                $metaHTML .= '<p><label id="rdp-wbb-title-label">Title:</label><span id="title">';
                $metaHTML .= ($settings['show_full_title'] == 1)? esc_html($FullTitle) : esc_html($sTitle);
                $metaHTML .= '</span></p>';
            }   
            if(empty($settings['show_full_title']) && !empty($settings['show_subtitle']) && !empty($meta['subtitle'])) $metaHTML .= '<p><label id="rdp-wbb-subtitle-label">Subtitle:</label><span id="subtitle">' . esc_html($meta['subtitle']) . '</span></p>';
            if(!empty($settings['show_editor']) && !empty($meta['editor'])):
                $URL = '';
                $metaHTML .= '<p><label id="rdp-wbb-editor-label">Editor:</label>';
                if(!empty($settings['show_editor_pic'])):
                    $URL = get_author_posts_url($meta['author_id']);
                    $metaHTML .= sprintf('<a href="%s" class="editor-url">', $URL);
                    $metaHTML .= get_avatar($meta['author_id'], 36, 'mm');
                    $metaHTML .= '</a>';
                endif;
                $metaHTML .= '<span id="editor">';
                if($URL):
                    $metaHTML .= sprintf('<a href="%s" class="editor-url">%s</a>', $URL, esc_html($meta['editor']));
                else:
                    $metaHTML .= esc_html($meta['editor']);
                endif;
                    
                $metaHTML .=  '</span></p>';
            endif;

            if( !empty($settings['show_publisher']) && !empty($meta['publisher'])) $metaHTML .= '<p><label id="rdp-wbb-publisher-label">Publisher:</label><span id="publisher">' . $meta['publisher'] . '</span></p>';
            if( !empty($settings['show_language']) && !empty($meta['language'])) $metaHTML .= '<p><label id="rdp-wbb-language-label">Language:</label><span id="language">' . $meta['language'] . '</span></p>';
            if( !empty($settings['show_size']) && !empty($meta['book_size'])) $metaHTML .= '<p><label id="rdp-wbb-book-size-label">Book size:</label><span id="book-size">' . $meta['book_size'] . '</span></p>';           
        endif;


        if(!empty($meta['download_url'])){
            $btnText = __('Download FREE eBook Edition',RDP_EBB_PLUGIN::$plugin_slug);
            $sDownloadButton = '';
            $cta_button = intval($settings['cta_button']);
            switch ($cta_button) {
                case 3:
                    $download_url = esc_attr($meta['download_url']);
                    $sDownloadButton .= "<div id='rdp-wbb-cta-button-box' class='medium'><a class='rdp-wbb-cta-button orange medium' href='{$download_url}'>{$btnText}</a></div>";
                    $metaHTML .= apply_filters('rdp_wbb_cta_button', $sDownloadButton, $meta, $btnText) ; 

                    break;
                default:
                    
                    $sDownloadButton .= "<div id='rdp-wbb-cta-button-box' class='medium'><a class='rdp-wbb-cta-button orange medium' href='#rdp_wbb_cta_inline_content'>{$btnText}</a></div>";
                    $metaHTML .= apply_filters('rdp_wbb_cta_button', $sDownloadButton, $meta, $btnText) ;                    
                    $sInlineHTML .= "<div id='rdp_wbb_cta_inline_content_wrapper' style='display:none'><div id='rdp_wbb_cta_inline_content' class='$sClasses'>";
                    $sInlineHTML .= '<div class="rdp_wbb_cta_button_content">';                    
                    if($cta_button == 1  && !is_user_logged_in()):
                        $sInlineHTML .= do_shortcode('[limsbook require_login=1 form_id=1]');
                    else:
                        $sInlineHTML .= do_shortcode('[limsbook require_login=0 form_id=1]');
                    endif;
                    
                    $sInlineHTML .= "</div><!-- .rdp_wbb_cta_button_content -->";                 
                    $sInlineHTML .= "</div><!-- #rdp_wbb_cta_inline_content --></div>";                     
                    break;
            }
             
        }
        
        $metaHTMLClose = '</div><!-- #rdp-wbb-metadata -->';
        if($metaHTML):
            $sHTML .= $metaHTMLOpen . $metaHTML . $metaHTMLClose;
        endif;
        return $sHTML;
    }//renderMetadata
    
    
    public function buildTOCHTML($meta,$settings) {
        $JSON = json_decode(json_encode($meta['toc']));
        $linkState = (empty($settings['toc_links']))? $this->_default_settings['sBookTOCLinks'] : $settings['toc_links'];
        $heading = __('Table of Contents','ebook-builder');
        $tocHTML = '<div class="clear"></div><h2>'. $heading . '</h2>';
        $tocHTML .= '<ul class="rdp-wbb-outline">';
        
        $run = function($item) use ($linkState,&$run) {
            $sHTML = '';

            switch($item->type){
                case 'chapter':
                    $sHTML .= '<li class="chapter">' . __('Chapter', 'rdp-wiki-book-builder') . ': ' . esc_html($item->name) ;

                    break;
                default:
                    $sHTML .= RDP_EBB_BOOK::buildArticleItem($item, $linkState);
                    break;
            }
            
            if(property_exists($item,'children')):
                $sHTML .= '<ul>';
                foreach ($item->children as $child) {
                    $sHTML .= $run($child);
                }
                $sHTML .= '</ul>';
            endif; 
            
            return $sHTML . '</li>';
        };
        
        if($JSON){
            foreach ($JSON as $item) {
               $tocHTML .= $run($item);
            }            
        }

        
        $tocHTML .= '</ul><!-- .rdp-wbb-outline -->';
        $tocHTML = apply_filters('rdp_ebb_toc', $tocHTML, $meta);        
        
        return $tocHTML;
    }//buildTOCHTML   
    
    
    public static function buildArticleItem($item,$link_state) {
        global $post;
        $sURL = esc_attr($item->pageUrl);
        $sTitle = esc_attr($item->name);
        $sText = esc_html($item->name);
        $sKey = esc_attr($item->id);    
        $tocHTML = '<li class="' . esc_attr($item->type) . '">';
        
        if(empty($sURL)){
            $tocHTML .= $sTitle;
        }else{
             switch ($link_state) {
                case 'disabled':
                    $tocHTML .= $sTitle;
                    break;
                default:
                    if($link_state == 'logged-in' && !is_user_logged_in()):
                        $tocHTML .= sprintf('<a class="external rdp_wbb_must_log_in">%s</a>',$sText);
                    else:
                        $fileURL = RDP_EBB_PLUGIN_BASEURL . '/dl/' . $post->ID . '.html';
                        $src = add_query_arg(['ebb-key'=>$sKey],$fileURL);
                        $tocHTML .= sprintf('<a target="_new" href="%s" class="external" title="%s" data-guid="%s">%s</a>',$src,$sTitle,$sKey,$sText);
                    endif;

            }                           
        }        
        
        return $tocHTML ;
    }//buildArticleItem    
    
    
    public function enqueueStylesScripts($meta,$settings){
        if(wp_script_is('rdp-wbb-book'))return;
        wp_enqueue_script( 'rdp-wbb-book', 
                RDP_EBB_PLUGIN_BASEURL . '/pl/js/script.rdp-wbb-book.js' , 
                array( 'jquery','jquery-query' ), 
                RDP_EBB_PLUGIN::$version, 
                TRUE);
        
        $params = array(
            'has_content' => (empty($meta['cta_button_content']))? 0 : 1,
            'html' => '',
            'links_active' => 1,
            'log_in_msg' => ''
            ); 

        $sLinks = (empty($settings['toc_links']))? $this->_default_settings['sBookTOCLinks'] : $settings['toc_links'];
        switch ($sLinks) {
            case 'logged-in':
                if(!is_user_logged_in()){
                    $params['links_active'] = 0;
                    $log_in_msg = RDP_EBB_Utilities::rgar($settings, 'log_in_msg');
                    if(empty($log_in_msg)) $log_in_msg = RDP_EBB_Utilities::rgar($this->_options, 'log_in_msg', $this->_default_settings['log_in_msg']);
                    $msg = RDP_EBB_Utilities::showMessage($log_in_msg, true, false);
                    $params['log_in_msg'] = do_shortcode($msg);
                }
                break;
            case 'disabled':
                $params['links_active'] = 0;
                break;
            default:
                break;
        } 
        
        wp_localize_script( 'rdp-wbb-book', 'rdp_wbb_book', $params );  


        do_action('rdp_ebb_book_scripts_enqueued');
    }//scriptsEnqueue      
  
    
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
    
    /**
     * Build TOC from book content items
     * 
     * @param Int|Array $param Either a post ID or an array of content items
     * @return Array Table of contents
     */
    public static function buildTOC($param) {
        $items = $param;
        if(is_numeric($param)){
            $items = self::fetchItemsObject($param);         
        }

        $JSON = json_decode(json_encode($items));        
        $toc = [];
        
        $run = function($item) use (&$run) {
            $x = 1;
            $return = array(
                    'type' => $item->type,
                    'name' => $item->title,
                    'id' => $item->key                            
            ); 
            
            if($item->type === 'article'){
                $return['pageUrl'] = $item->url;
            }
            
            $children = [];
            foreach ($item as $key=>$value) {
                if(is_object($value)){
                    if(!property_exists($value, 'type')) continue;
                    $children[] = $run($value);
                }
            }
            if(!empty($children))$return['children'] = $children;
            return $return;
        };

        foreach ($JSON as $item) {
            if(!property_exists($item, 'type')) continue;
            $toc[] = $run($item); 
        }        

        return $toc;
    }//buildTOC    
    
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
        if (has_post_thumbnail( $book_id ) ):
            $tn_id = get_post_thumbnail_id( $book_id );
            $result = delete_post_thumbnail($book_id);
            $result = wp_delete_attachment($tn_id, true);            
        endif;          
        
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
       
        

        
        $result = set_post_thumbnail( $book_id, $thumbnail_id );
    }//handleFeaturedImage  
    
    public static function generateHTMLFile($book_id = null){
        if(empty($book_id)) $book_id = intval(RDP_EBB_Utilities::globalRequest('book_id'));
        $created = false;
        $filepath = RDP_EBB_PLUGIN_BASEDIR.'dl/' . $book_id . '.html'; 
        
        $params = [
            'ebb_action'    => 'book_download',
            'book_id'       => $book_id
        ];
        $downloadURL = add_query_arg($params,home_url());
        
        $book = self::fromJSONFile($book_id); 
        if(!$book){
            $tmpBook = get_post($book_id);
            $tmpBook->filter = '';
            $tmpBook->_ebook_metadata = $tmpBook->_ebook_metadata;

//            self::toJSONFile($tmpBook);
//            $book = self::fromJSONFile($book_id);
            $book = json_decode(json_encode($tmpBook));
        }
        
        if($book){
            $created = self::toHTMLFile($book,$filepath);
            if(!$created){
                $downloadURL = '';
            }else{
                // update post meta with download url
                $bookMeta = get_post_meta($book_id, '_ebook_metadata', true);
                if(!is_array($bookMeta)) $bookMeta = self::bookMetadataStructure();
                $bookMeta['download_url'] = $downloadURL;
                RDP_EBB_Utilities::savePostMeta($book_id,$bookMeta); 
                
                // update JSON file with download url
                $book->_ebook_metadata->download_url = $downloadURL;
                self::toJSONFile($book);
                
            }
        }         

        if(defined( 'DOING_AJAX' ) && DOING_AJAX){
            echo json_encode(
                        [
                            'download_url' => $downloadURL,
                            'code' => $created ? 200 : 400
                        ]
                    );
            die();
        }else{
            return $downloadURL;
        }
        
    }//generateHTMLFile    
    
  
    /**
     * 
     * @param WP_POST $book
     * @param string $filepath
     * @return boolean Indicate if file was saved to disk
     */
    public static function toHTMLFile($book,$filepath) {
        if(file_exists($filepath)){
            unlink($filepath);
        }

        $bookTemplate = self::template();
        $html = new rdp_simple_html_dom();
        $html->load($bookTemplate);

        if($html->find('#datalet-book',0)):
            $sJSON = json_encode($book);
            $html->find('#datalet-book',0)->innertext = RDP_EBB_Utilities::xmlEntities($sJSON);
        endif;
        
        $titlePage = self::buildTitlePage($book);
        $html->find('#datalet-title-page',0)->innertext = $titlePage;     
        
        $body = $html->find('body',0);
        if($body):
            $attr = 'data-book-id';
            $body->$attr = $book->ID;
            $body->class = 'ebook';
        endif;
        
        $saved = true;
        try {
            $html->save($filepath);
        } catch (Exception $exc) {
            //echo $exc->getTraceAsString();
            $saved = false;
        }  finally {
            $html->clear(); 
            unset($html);
        }
        
        return $saved;
    } //toHTML 
    
    private static function buildTitlePage($book) {
        $subtitle = empty($book->_ebook_metadata->subtitle)? '' : '<div id="subtitle">'.$book->_ebook_metadata->subtitle.'</div>';
        $editor = empty($book->_ebook_metadata->editor)? '' : '<div id="editor">Editor: '.$book->_ebook_metadata->editor.'</div>';
        $publisher = empty($book->_ebook_metadata->publisher)? '' : '<div id="publisher">Publisher: '.$book->_ebook_metadata->publisher.'</div>';
//        $title_image = $book->_ebook_metadata->cover_image;
        $title_image = get_the_post_thumbnail_url($book->ID,'full');
        
        $bookCover = ($title_image)? base64_encode(file_get_contents($title_image)) : '';
        
        $sHTML = <<<EOH
        <div class="wrap" id="title-page">
            <div id="handle"></div>    
            <image id="cover-image" src="data:image/png;base64,$bookCover" />
            <div id="title-container">
            <div id="title">{$book->post_title}</div>
            {$subtitle}
            </div>
            <div id="credits-container">
            {$editor}
            {$publisher}
            </div>
        </div>
EOH;
        return $sHTML;
    }//buildTitlePage      
    
    public static function toJSONFile($book){
        $filepath = RDP_EBB_PLUGIN_BASEDIR.'/json/' . $book->ID . '.json';
        self::deleteJSONFile($book->ID);
        $sJSON = json_encode($book);
        $flatDB = fopen($filepath, "w") or die("Unable to open/create file!");
        fwrite($flatDB, $sJSON);
        fclose($flatDB);        
    }//toJSONFile    
    
    public static function fromJSONFile($book_id = null) {
        $book_id = (empty($book_id))? intval(RDP_EBB_Utilities::globalRequest('book_id')) : $book_id;
        $filepath = RDP_EBB_PLUGIN_BASEDIR.'json/' . $book_id . '.json';
        if(!file_exists($filepath)) return false;        
        $sJSON = file_get_contents($filepath);
        $book = json_decode($sJSON);
        return $book;
    }//fromJSONFile 
    
    public static function deleteJSONFile($book_id) {
        $filepath = RDP_EBB_PLUGIN_BASEDIR.'/json/' . $book_id . '.json';
        if(file_exists($filepath)){
            unlink($filepath);
        }        
    }//deleteJSONFile  
    
    public static function object_to_array($data) {
        if(is_array($data) || is_object($data)){
            $result = array();
            foreach($data as $key => $value){
                $result[$key] = self::object_to_array($value);
            }
            return $result;
        }
        return $data;
    } //object_to_array  
    
    private static function template() {
        return file_get_contents(RDP_EBB_PLUGIN_BASEDIR.'/bl/template.html');
    }//template  
    
    public static function downloadHandler() {
        $book_id = intval(RDP_EBB_Utilities::globalRequest('book_id'));
        $book = get_post($book_id);
        if($book){
            $name = sanitize_title($book->post_title);
            header("Content-disposition: attachment; filename=$name.html");
            header("Content-type: text/html");
            readfile(RDP_EBB_PLUGIN_BASEURL.'/dl/' . $book_id . '.html');            
        }

        die();
    }//downloadHandler   
    
    
    public function injectHTMLFile($content) {
        global $post;
        
        $src = RDP_EBB_PLUGIN_BASEURL.'/dl/' . $post->ID . '.html';
        
        return sprintf('<iframe id="ebook" src="%s"></iframe>', $src);
        
    }//injectHTMLFile
    
    public function embedBook($content) {
        global $post;
        $sHTML = '<div class="ebook-wrapper">';
        
        
        $sHTML .= '</div><!-- .ebook-wrapper -->';
        
        $book = self::fromJSONFile($post->ID);
        $sJSON = RDP_EBB_Utilities::xmlEntities(json_encode($book));
//        $sJSON = _wp_specialchars(json_encode($book),ENT_QUOTES);
        $sHTML .= '<code style="display: none" id="datalet-book">' . $sJSON . '</code>';
        
        $titlePage = self::buildTitlePage($book);
        $sHTML .= '<code style="display: none" id="datalet-title-page">' . $titlePage . '</code>';

        $sHTML .= '<code style="display: none" id="datalet-search-results"></code>';
        
        $sHTML .= '<code style="display: none" id="datalet-about-page">';
        $sHTML .= '    <div class="wrap" id="about-page">';
        $sHTML .= '        <div id="handle"></div>';
        $sHTML .= '        <a id="wikipress-logo-link" href="https://www.wiki-press-book.pub/" target="_new"><img id="wikipress-logo" src="data:image/png;base64,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"></a>';
        $sHTML .= '        <div id="copy">Copyright <span id="copyright-year"></span> <a href="https://www.lablynx.com/" target="_new">LabLynx Inc.</a> All rights reserved.</div>';
        $sHTML .= '    </div>';
        $sHTML .= '</code>';      
        
        return $sHTML;
    }//embedBook
}//RDP_EBB_BOOK
