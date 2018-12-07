var $j=jQuery.noConflict();
// Use jQuery via $j(...)
var wikipress_book_builder_timeoutId = 0;

jQuery(document).ready(rdp_ebb_admin_onReady);
function rdp_ebb_admin_onReady(){
    console.log('Enter: rdp_ebb_admin_onReady');
    const coverPreviewArea_wrap = $j('.coverPreviewArea_wrap');
    if(coverPreviewArea_wrap.length) initCoverBuilder();
    
}//rdp_ebb_admin_onReady

function initCoverBuilder(){
    $j("#cover_image")
            .Thumbelina({
                $bwdBut:$j('#cover_image .left'),    // Selector to left button.
                $fwdBut:$j('#cover_image .right')    // Selector to right button.
            });
            
    const cover_theme = $j('.coverPreviewArea').data('theme');  
    $j('#theme_chooser .theme_'+ cover_theme).addClass('selected');

       
    $j(document).on('click','.theme_preview',function(){
        var theme = $j(this).data('theme');
        $j('.coverPreviewArea').data('theme',theme);
        $j('#txtCoverThemeInput').val(theme);
        $j('#theme_chooser .selected').removeClass('selected');
        $j(this).parent().addClass('selected');
    }); 
    
    $j(document).on('click','a.image_chooser_link',function(e){
        const img = document.querySelector("#image_chooser .selected");
        if(img)img.classList.remove('selected');

        var target = rdp_ebb_book_get_source_element(e);        
        target.classList.add('selected');

        var sSrc = target.classList.contains('no-image')? '' : target.src;
        console.log('sSrc = '+sSrc);
        
        document.getElementById('txtImageURLInput').value = sSrc;
    });  
    
    $j(document).on('click','#btnGenerateBook',generateBook);
        
}//initCoverBuilder


function rdp_ebb_book_get_source_element(e){
    if( !e ) e = window.event;
    var target;
    if(e.target||e.srcElement){
        target = e.target||e.srcElement;
    }else target = e;  
    return target;    
}//rdp_ebb_book_get_source_element


function generateBook(e){

    let params = (new URL(document.location)).searchParams;
    let postID = parseInt(params.get("post"));
    
    dataIn = {
        book_id: postID,
        action: 'rdp_ebb_generate_book',
        security: rdp_ebb_admin.ajax_nonce
    };
    $j.ajax({
        type: "post",
        url: rdp_ebb_admin.ajax_url,
        data: dataIn,
        beforeSend: function(){
            $j('#wb_generating_book_indicator').toggle();
            $j('#btnGenerateBook').toggle();
            
        }
    })
    .done(function( data ) {
        $j('#wb_generating_book_indicator').toggle();
        $j('#btnGenerateBook').toggle();        
        console.log(data);
//        img.addClass('selected');
        myData = (new Function("return " + data))();
        
        if(myData.code != 200){
            alert('Error: generate book failed.');
            return;
        }
        
        $j('#wb_download_url').val(myData.download_url);
        
    });    
    
}//generateBook