jQuery(document).ready(rdp_ebb_book_onReady);


function rdp_ebb_book_onReady(){
    rdp_ebb_handle_must_log_in();
}//rdp_wbb_book_onReady

function rdp_ebb_handle_must_log_in(){
    var $j=jQuery.noConflict();    
    // Use jQuery via $j(...)  
       
    if(typeof rdp_wbb_book == 'undefined')return;
    if($j('body').hasClass('logged-in'))return;

    $j('.book_show').on( "click", 'a.rdp_wbb_must_log_in' , function(event){
        event.preventDefault();  
        $j('#rdp_ebb_message').remove();
        $j(this).parent().append(rdp_wbb_book.log_in_msg);
    });     
}//rdp_wbb_handle_must_log_in