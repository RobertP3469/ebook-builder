var $j=jQuery.noConflict();
// Use jQuery via $j(...)

jQuery(document).ready(rdp_ebb_admin_onReady);
function rdp_ebb_admin_onReady(){
    console.log('Enter: rdp_ebb_admin_onReady');
    const coverPreviewArea_wrap = $j('.coverPreviewArea_wrap');
    if(coverPreviewArea_wrap.length) initCoverBuilder();
    
    // Uploading files
    var file_frame;  
    var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
    
    jQuery('#btnUploadAltImage').on('click', function( event ){

        // If the media frame already exists, reopen it.
        if ( file_frame ) {
                // Set the post ID to what we want
                //file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
                // Open frame
                file_frame.open();
                return;
        } else {
                // Set the wp.media post id so the uploader grabs the ID we want when initialised
                //wp.media.model.settings.post.id = set_to_post_id;
        }
        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
                title: 'Select a image to upload',
                button: {
                        text: 'Use this image',
                },
                multiple: false	// Set to true to allow multiple files to be selected
        });
        // When an image is selected, run a callback.
        file_frame.on( 'select', function() {
                // We set multiple to false so only get one image from the uploader
                attachment = file_frame.state().get('selection').first().toJSON();
                // Do something with attachment.id and/or attachment.url here
console.log(attachment);
                $j('#alternative-image-preview' ).attr( 'src', attachment.url );
                $j('#alternative-image-url' ).val( attachment.url );
                $j('#txtImageURLInput').val( attachment.url );
                $j('#btnUploadAltImage').addClass('hidden');
                $j('#btnRemoveAltImage').removeClass('hidden');                
                // Restore the main post ID
                wp.media.model.settings.post.id = wp_media_post_id;
        });
                // Finally, open the modal
                file_frame.open();
    });
    
    
    $j('#btnRemoveAltImage').on('click',function(e){
        // check if txtImageURLInput equals alternative-image-url...
        if($j('#txtImageURLInput').val() === $j( '#alternative-image-url' ).val()){
            // ... if so, clear the value of txtImageURLInput
            $j('#txtImageURLInput').val( '' );            
        }
        
        // remove alternative image preview
        $j( '#alternative-image-preview' ).removeAttr( 'src' );
        // remove value of hidden input
        $j( '#alternative-image-url' ).val( '' );  

        // toggle buttons
        $j('#btnUploadAltImage').removeClass('hidden');
        $j('#btnRemoveAltImage').addClass('hidden');         
    });
 
    
    
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
        document.getElementById('alternative-image-url').value = '';
        document.getElementById('alternative-image-preview').src = '';
        
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