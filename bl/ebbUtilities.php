<?php

/**
 * Description of ebbUtilities
 *
 * @author Owner
 */
class RDP_EBB_Utilities {
    public static function urlCheck( $url ) {
        if(strpos($url, 'lablynxpress.com')):
            $x = 1;
        endif;
        
        
        $options = get_option( RDP_EBB_PLUGIN::$options_name );
        $whitelist = empty($options['whitelist'])? RDP_EBB_PLUGIN::default_settings()['whitelist'] : $options['whitelist'];
        
        if(empty($whitelist)):
            return true;        
        endif;
        if(empty($url)) return false;

        $white_list = trim($whitelist);
        $white_list_pass = false;
        if ( ! empty( $white_list ) ) {
            $white_list_urls = preg_split( '/\r\n|\r|\n/', $white_list ); 
            // http://blog.motane.lu/2009/02/16/exploding-new-lines-in-php/
            
            $white_list_urls_trimmed = array_map("trim", $white_list_urls);
            
            foreach ( $white_list_urls_trimmed as $check_url ) {
                if(strpos($url, $check_url) !== false) {
                    $white_list_pass = true;
                    break;
                }
            }
        }

        return $white_list_pass;
    } //pass_url_check     
    
    
     /**
     * Prepends a leading slash.
     *
     * Will remove leading forward and backslashes if it exists already before adding
     * a leading forward slash. This prevents double slashing a string or path.
     *
     * The primary use of this is for paths and thus should be used for paths. It is
     * not restricted to paths and offers no specific path support.
     *
     * Opposite of {@see WordPress\trailingslashit()}.
     *
     * @param string $string What to add the leading slash to.
     * @return string String with leading slash added.
     */
    public static function leadingslashit( $string ){
            return '/' . self::unleadingslashit( $string );
    }

    /**
     * Removes leading forward slashes and backslashes if they exist.
     *
     * The primary use of this is for paths and thus should be used for paths. It is
     * not restricted to paths and offers no specific path support.
     *
     * Opposite of {@see WordPress\untrailingslashit()}.
     *
     * @param string $string What to remove the leading slashes from.
     * @return string String without the leading slashes.
     */
    public static function unleadingslashit( $string ){
            return ltrim( $string, '/\\' );
    }  

    static function entitiesPlain($string){
        return str_replace ( array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&quest;',  '&#39;' ), array ( '&', '"', "'", '<', '>', '?', "'" ), $string ); 
    }  
    
    
    static function unparse_url($parsed_url) { 
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
        $pass     = ($user || $pass) ? "$pass@" : ''; 
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
        return "$scheme$user$pass$host$port$path$query$fragment"; 
    } //unparse_url 
    
    public static function addAndOr(&$sql,$str, $con = 'AND'){
        if(empty($str))return;
        if(empty($sql)){
            $sql = $str;
        }else{
            $sql .= " $con " . $str;
        }
    }//addAndOr       
    
    static function unXMLEntities($string) { 
       return str_replace (array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;' ) , array ( '&', '"', "'", '<', '>' ), $string ); 
    }  
    
    static function xmlEntities($string) { 
       return str_replace ( array ( '&', '"', "'", '<', '>', '�' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&apos;' ), $string ); 
    }  
    
    static function is_valid_url ($url="") {

        if ($url=="") {
            $url=$this->url;
        }

        $url = @parse_url($url);

        if ( ! $url) {


            return false;
        }

        $url = array_map('trim', $url);
        $url['port'] = (!isset($url['port'])) ? 80 : (int)$url['port'];
        $path = (isset($url['path'])) ? $url['path'] : '';

        if ($path == '') {
            $path = '/';
        }

        $path .= ( isset ( $url['query'] ) ) ? "?$url[query]" : '';



        if ( isset ( $url['host'] ) AND $url['host'] != gethostbyname ( $url['host'] ) ) {
            if ( PHP_VERSION >= 5 ) {
                $headers = get_headers("$url[scheme]://$url[host]:$url[port]$path");
            }
            else {
                $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);

                if ( ! $fp ) {
                    return false;
                }
                fputs($fp, "HEAD $path HTTP/1.1\r\nHost: $url[host]\r\n\r\n");
                $headers = fread ( $fp, 128 );
                fclose ( $fp );
            }
            $headers = ( is_array ( $headers ) ) ? implode ( "\n", $headers ) : $headers;
            return ( bool ) preg_match ( '#^HTTP/.*\s+[(200|301|302)]+\s#i', $headers );
        }

        return false;
    }    
    
    static function abortExecution(){
        $rv = false;
        $wp_action = RDP_EBB_Utilities::globalRequest('action');
        if($wp_action == 'heartbeat')$rv = true;  
        $isScriptStyleImg = RDP_EBB_Utilities::isScriptStyleImgRequest();
        if($isScriptStyleImg)$rv = true;           
        return $rv;
    }//abortExecution
       
    static function savePostMeta($post_id,$meta) {
        if(!add_post_meta($post_id, '_ebook_metadata', $meta, true)){
            update_post_meta($post_id, '_ebook_metadata', $meta);
        }       
    }//handlePostMeta  
    
    static function globalRequest( $name, $default = null ) {
        $value = '';
        $array = $_GET;
        $found = false;

        if ( isset( $array[ $name ] ) ) {
                $value = $array[ $name ];
                $found = true;
        }else{
            $array = $_POST;
            if ( isset( $array[ $name ] ) ) {
                    $value = $array[ $name ];
                    $found = true;
            }                
        }
        return (empty( $found ) && $default !== null) ? $default : $value;
    } //globalRequest   

    static function isScriptStyleImgRequest($url = ''){
        if(empty($url)){
            $url = (isset($_SERVER['REQUEST_URI']))? $_SERVER['REQUEST_URI'] : '';
        }
        $arrExts = self::extensionList();
        $url_parts = parse_url($url);        
        $path = (empty($url_parts["path"]))? '' : $url_parts["path"];
        $urlExt = pathinfo($path, PATHINFO_EXTENSION);
        return key_exists($urlExt, $arrExts);
    }//isScriptStyleImgRequest 
    
    static function extensionList(){
        $ext = array();
        $mimes = wp_get_mime_types();

        foreach ($mimes as $key => $value) {
            $ak = explode('|', $key);
            $ext = array_merge($ext,$ak)  ;      
        }            
        
        return $ext;
    }//extensionList    
    
    static function pluginIsActive($input){
        $active = get_option('active_plugins');
        $active = implode(",", $active);
        $rv = false;
        switch ($input){
            case "we": 
                if (strpos($active, "rdp-wiki-embed")) $rv = true;
                break;             
        }//switch
        
       return $rv; 
    }//PluginIsActive 
    
    static function GUID()
    {
        if (function_exists('com_create_guid') === true)
        {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    } 
    
    static function isInt($myString){
        $isInt=preg_match('/^\s*([0-9]+)\s*$/', $myString); 
        return $isInt;
    }
    
    static function isImage($url){
        $imgExts = array("gif", "jpg", "jpeg", "png", "tiff", "tif", "bmp");
        $urlExt = pathinfo($url, PATHINFO_EXTENSION);
        return in_array($urlExt, $imgExts);
    }//isImage    
    

    public static function rgempty( $name, $array = null ) {
        if ( is_array( $name ) ) {
                return empty( $name );
        }

        if ( ! $array ) {
                $array = $_POST;
        }

        $val = self::rgar( $array, $name );

        return empty( $val );
    }//rgempty
    
    public static function rgget( $name, $array = null ) {
        if ( ! isset( $array ) ) {
                $array = $_GET;
        }

        if ( isset( $array[ $name ] ) ) {
                return $array[ $name ];
        }

        return '';
    }    

    public static function rgpost( $name, $do_stripslashes = true ) {
        if ( isset( $_POST[ $name ] ) ) {
                return $do_stripslashes ? stripslashes_deep( $_POST[ $name ] ) : $_POST[ $name ];
        }

        return '';
    }    
    
    public static function rgars( $array, $name ) {
            $names = explode( '/', $name );
            $val   = $array;
            foreach ( $names as $current_name ) {
                    $val = rgar( $val, $current_name );
            }

            return $val;
    }

    public static function rgar( $array, $prop, $default = null ) {
            $found = false;
            $value = '';
            
            if ( isset( $array[ $prop ] ) ) {
                    $value = $array[ $prop ];
                    $found = true;
            } 

            return (empty( $found ) && $default !== null) ? $default : $value;
    }   
    
    static function showMessage($message, $errormsg = false,$echo = true)
    {
        $sMSG = '';
        if( $errormsg )
        {
            $sMSG .= '<div id="rdp_ebb_message" class="alert error">';
        }
        else
        {
            $sMSG .= '<div id="rdp_ebb_message" class="alert success updated">';
        }

        $sMSG .= "<p><strong>$message</strong></p></div>";
        
        if($echo){
            echo $sMSG;
        }else{
            return $sMSG;
        }
    }//showMessage
    
    
    public static function rdp_remote_get($url){
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
        return curl_exec($curl);        
    }
    
} //RDP_EBB_Utilities
