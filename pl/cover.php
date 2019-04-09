<?php
namespace GDText;

use GDText\Box;
use GDText\Color;
use GDText\TextWrapping;

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

class RDP_WBB_COVER {
    
    private $_im;
    private $_theme = 'nico_6';
    private $_title = '';
    private $_subTitle = '';
    private $_editor = '';
    private $_publisher = '';
    private static $STANDARD_LINE_HEIGHT = 1;
    private $_fontFileLocation = '';
    
    public function __construct() {
        clearstatcache();
        
        $coverStyle = (isset($_GET['cover_style']))? $_GET['cover_style'] : 'nico_6' ;
        
        if(!key_exists($coverStyle, $this->colors()))$coverStyle = 'nico_6' ;
        
        $this->_theme = $coverStyle;
        $this->_title = (isset($_GET['title']))? self::entitiesPlain(trim(urldecode($_GET['title'])))  : '' ;       
        $this->_subTitle = (isset($_GET['subtitle']))? self::entitiesPlain(trim(urldecode($_GET['subtitle']))) : '' ;
        $this->_editor = (isset($_GET['editor']))? self::entitiesPlain(trim(urldecode($_GET['editor']))) : '' ;     
        $this->_publisher = (isset($_GET['publisher']))? self::entitiesPlain(trim(urldecode($_GET['publisher']))) : '' ;     
        $image = (isset($_GET['title_image']))? trim(urldecode($_GET['title_image'])) : '' ;
        
        require_once 'gd-text/Color.php';         
        require_once 'gd-text/HorizontalAlignment.php';         
        require_once 'gd-text/TextWrapping.php';         
        require_once 'gd-text/VerticalAlignment.php'; 
        require_once 'gd-text/Box.php'; 
        
        $this->_fontFileLocation = dirname(__FILE__) . '/FreeSerif.ttf';
        
    }//__construct
    
    function render() {

        putenv('GDFONTPATH=' . realpath('.'));

        $imgPath = 'images/' . $this->_theme . '.png';
        $this->_im = imagecreatefrompng($imgPath);
        $im = imagecreatefrompng($imgPath);
        $titleFontSize = $this->addTitle($im);
        $this->addSubtitle($im,$titleFontSize);
        $this->addEditor($im);
        $this->addCoverImage();
        $this->addPublisher($im);


        header("Content-type: image/png");
        imagepng($this->_im);        
    }//render
    
    
    private function addCoverImage() {
        $image = (isset($_GET['title_image']))? $_GET['title_image'] : '' ;  
        if(empty($image))return;
        
        $titleImagePieces = explode(":", $image);
        
        if(count($titleImagePieces)>1)$image = $titleImagePieces[1];
        
        $max_width = 180;
        $max_height = 180;
        
//        $type = exif_imagetype($image);

        
        $oURLPieces = parse_url($image);
        if(empty($oURLPieces['scheme']))$oURLPieces['scheme'] = 'http';        
        $sSourceDomain = $oURLPieces['scheme'].'://'.$oURLPieces['host'];  
        if(key_exists('path', $oURLPieces))$sPath = $oURLPieces['path'];
        $image = $sSourceDomain . $sPath;

        
        $size = GetImageSize($image);
        $width = $size[0];
        $height = $size[1];

        $x_ratio = $max_width / $width;
        $y_ratio = $max_height / $height;

//        if ( ($width <= $max_width) && ($height <= $max_height) ) {
//          $tn_width = $width;
//          $tn_height = $height;
//        }
//        else
            if (($x_ratio * $height) < $max_height) {
          $tn_height = ceil($x_ratio * $height);
          $tn_width = $max_width;
        }
        else {
          $tn_width = ceil($y_ratio * $width);
          $tn_height = $max_height;
        }

        $src = null;
        
        switch ($size[2]) {
            case IMG_JPEG:
                $src = imagecreatefromjpeg($image);
                break;
            case IMG_WBMP:
                $src = imagecreatefromwbmp($image);
                break;
            case IMG_GIF:
                $src = imagecreatefromgif($image);
                break;
            default:
                $src = imagecreatefrompng($image);
                break;
        }

        
        $dst = imagecreatetruecolor($tn_width,$tn_height);
        
        $rgb = imagecolorat($this->_im, 10, 120);
        $colors = imagecolorsforindex($this->_im, $rgb);
        
        $back_color = imagecolorallocate($dst, $colors['red'], $colors['green'], $colors['blue']);
        imagefill($dst, 0, 0, $back_color);
        // Make the background transparent
        imagecolortransparent($dst, $back_color);        
        
        imagecopyresampled($dst, $src, 0, 0, 0, 0,
        $tn_width,$tn_height,$width,$height);

    
        
        $yAdjust = ($max_height - $tn_height)/2;
        $xAdjust = ($max_width - $tn_width)/2;
        imagecopymerge($this->_im, $dst, 10 + $xAdjust, 112 + $yAdjust, 0, 0, $tn_width, $tn_height, 100);        
    }//addCoverImage
    
    
    private function addPublisher($im){
        if(empty($this->_publisher))return;
        $box = $this->mockPublisherBox($im);
        $len = strlen($this->_publisher);
        $text = 'Publisher: ' . $this->_publisher;
        $lines = 1;
        $heightLimit = 15;        
        $nFontSize = $this->pickFontSize($box,$text,$lines,9);
        $lineCount = min($box->getLineCount(),$lines);  
        $textHeight = ($nFontSize * .75) * $lineCount;  
        $box = $this->mockPublisherBox($this->_im);
        
        if($textHeight > $heightLimit):
            $nFontSize = $this->downsize($nFontSize, $lineCount, $heightLimit);
            $box->setTextAlign('right', 'top');
        endif;        
                       
        $box->setFontSize($nFontSize);
        $box->setLineHeight(self::$STANDARD_LINE_HEIGHT);        
        $box->draw($text);  
        
    }//addPublisher
    
    private function mockPublisherBox($im) {
        $box = new Box($im);
//        $box->enableDebug();
        $box->setFontFace($this->_fontFileLocation); 
        $color = $this->colors()[$this->_theme][1];
        $box->setFontColor(new Color($color,$color,$color));
        $box->setBox(8, 299, 180, 10);
        $box->setTextAlign('left', 'center');        
        $box->setTextWrapping(TextWrapping::NoWrap); 
        return $box;        
    }//mockEditorBox     
   
    private function addEditor($im) {
        if(empty($this->_editor))return;
        $box = $this->mockEditorBox($im); 
        $len = strlen($this->_editor);
        $text = 'Editor: ' . $this->_editor;
        $lines = 1;
        $heightLimit = 10;
        
        if($len > 37):
            $x = $len / 37;
            $lines = min((int) ceil($x), $lines);             
        endif;        

        $nFontSize = $this->pickFontSize($box,$text,$lines,10);
        $lineCount = min($box->getLineCount(),$lines);  
        $textHeight = ($nFontSize * .75) * $lineCount;           

        $box = $this->mockEditorBox($this->_im);
        
        if($textHeight > $heightLimit):
            $nFontSize = $this->downsize($nFontSize, $lineCount, $heightLimit);
            $box->setTextAlign('right', 'top');
        endif;        
                       
        $box->setFontSize($nFontSize);
        $box->setLineHeight(self::$STANDARD_LINE_HEIGHT);        
        $box->draw($text);         
    }//addSubtitle   
    
    private function mockEditorBox($im) {
        $box = new Box($im);
//        $box->enableDebug();
        $box->setFontFace($this->_fontFileLocation); 
        $color = $this->colors()[$this->_theme][1];
        $box->setFontColor(new Color($color,$color,$color));
        $box->setBox(8, 10, 180, 10);
        $box->setTextAlign('right', 'top');        
        $box->setTextWrapping(TextWrapping::WrapWithOverflow); 
        return $box;        
    }//mockEditorBox    
    
     
    private function addSubtitle($im,$fsize) {
        if(empty($this->_subTitle))return;
        $box = $this->mockSubTitleBox($im);  
        $len = strlen($this->_subTitle);
        $lines = 1;
        $heightLimit = 10;
        
//        if($len >= 47):
//            $x = $len / 47;
//            $lines = min((int) ceil($x), 4);             
//        endif;
        
        $size_limit = $fsize - 3;
        $nFontSize = $this->pickFontSize($box,$this->_subTitle,$lines,$size_limit);
        $lineCount = min($box->getLineCount(),$lines);  
        $textHeight = ($nFontSize * .75) * $lineCount;        

        $box = $this->mockSubTitleBox($this->_im);
        
        if($textHeight > $heightLimit):
            $nFontSize = $this->downsize($nFontSize, $lineCount, $heightLimit);
            $box->setTextAlign('right', 'top');
        endif;        
                
        $box->setFontSize($nFontSize);
        $box->setLineHeight(self::$STANDARD_LINE_HEIGHT);        
        $box->draw($this->_subTitle);         
    }//addSubtitle
       
    
    private function mockSubTitleBox($im) {
        $box = new Box($im);
//        $box->enableDebug();
        $box->setFontFace($this->_fontFileLocation); 
        $color = $this->colors()[$this->_theme][1];
        $box->setFontColor(new Color($color,$color,$color));
        $box->setBox(8, 90, 180, 16);
        $box->setTextAlign('right', 'top');        
        $box->setTextWrapping(TextWrapping::WrapWithOverflow); 
        return $box;        
    }//mockSubTitleBox   

    private function addTitle($im) {
         if(empty($this->_title))return;
        $box = $this->mockTitleBox($im);  
        $len = strlen($this->_title);
        $lines = 3;
        $heightLimit = 30;

        $nFontSize = $this->pickFontSize($box,$this->_title,$lines);
        $lineCount = min($box->getLineCount(),$lines);
        $textHeight = ($nFontSize * .75) * $lineCount;
        $box = $this->mockTitleBox($this->_im);
        
        if($textHeight > $heightLimit):
            $nFontSize = $this->downsize($nFontSize, $lineCount, $heightLimit);
            $box->setTextAlign('right', 'top');
        endif;        
        
        $box->setFontSize($nFontSize);
        $box->setLineHeight(self::$STANDARD_LINE_HEIGHT);        
        $box->draw($this->_title);
        return $nFontSize;
    }//addTitle
           
    private function mockTitleBox($im) {
        $box = new Box($im);
//        $box->enableDebug();
        $box->setFontFace($this->_fontFileLocation); 
        $color = $this->colors()[$this->_theme][0];
        $box->setFontColor(new Color($color,$color,$color));

        $box->setBox(8, 34, 180, 30);
        $box->setTextAlign('right', 'bottom');        
        $box->setTextWrapping(TextWrapping::WrapWithOverflow); 
        return $box;        
    }//mockTitleBox
    
    private function downsize($fsize,$lineCount,$height_limit) {
        $new_size = $fsize - 1;
        while ((($new_size * .75) * $lineCount) > $height_limit) {
            $new_size--;
        }
        return $new_size;
    }//downsize    

    private function pickFontSize($box, $text, $line_limit = 3, $size_limit = 18) {
        $track = array();
        for ($i = 6; $i <= $size_limit; $i++) {
            $box->setLineHeight(self::$STANDARD_LINE_HEIGHT);            
            $box->setFontSize($i);
            $box->draw($text); 

            $lineCount = $box->getLineCount();
            if($lineCount > $line_limit)break;
            $track[$i] = $box->getLineCount();
        }  
        
        $trackReverse = array_reverse($track,true);

        $size = key($trackReverse);
        return $size;
    }//pickFontSize
    
    private function colors() {
        $combos = array(
          'nico_0'  => array(255,191),
          'nico_2'  => array(255,191),
          'nico_3'  => array(95,127),
          'nico_4'  => array(95,255),
          'nico_5'  => array(31,63),
          'nico_6'  => array(63,95),
          'nico_7'  => array(63,223),
          'nico_8'  => array(63,95),
          'nico_9'  => array(191,0),
          'nico_10'  => array(63,95),
          'nico_11'  => array(223,63),
          'nico_12'  => array(255,63),
          'nico_13'  => array(159,0),
          'nico_15'  => array(223,63),
          'nico_16'  => array(223,31),
          'nico_17'  => array(31,191),
          'nico_18'  => array(255,63),
          'nico_19'  => array(31,223),
          'nico_20'  => array(31,95),
          'nico_21'  => array(31,95)
        );
        
        return $combos;
    }//colors
    
    static function entitiesPlain($string){
        return str_replace ( array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&quest;',  '&#39;', '&nbsp;' ), array ( '&', '"', "'", '<', '>', '?', "'"," " ), $string ); 
    } //entitiesPlain    
}//RDP_WBB_COVER

$oRDP_WBB_COVER = new RDP_WBB_COVER();
$oRDP_WBB_COVER->render();


   