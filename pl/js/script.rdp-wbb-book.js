jQuery(document).ready(rdp_ebb_book_onReady);


function rdp_ebb_book_onReady(){
    
    // handle redirect after login
    var redirectPath = Cookies.get('rdp_ll_login_redirect');
    var loggedIn = $j('body').hasClass('logged-in');
    if(loggedIn && redirectPath && redirectPath !== undefined){
        Cookies.remove('rdp_ll_login_redirect', { path: '/' });
        window.location.href = redirectPath;
    }    
    
    rdp_ebb_handle_must_log_in();
}//rdp_wbb_book_onReady

function rdp_ebb_handle_must_log_in(){
    var $j=jQuery.noConflict();    
    // Use jQuery via $j(...)  
       
    if(typeof rdp_wbb_book == 'undefined')return;
    if($j('body').hasClass('logged-in'))return;

    $j('.book_show').on( "click", 'a.rdp_wbb_must_log_in' , function(event){
        event.preventDefault(); 
        
        var redirectURL = $j(this).data('href');
        var date = new Date();
        date.setTime(date.getTime()+(40*1000));
        Cookies.set('rdp_ll_login_redirect', redirectURL, {expires: date, path: '/' })        
        
        $j('#rdp_ebb_message').remove();
        $j(this).parent().append(rdp_wbb_book.log_in_msg);
    });     
}//rdp_wbb_handle_must_log_in