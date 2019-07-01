/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */


$(document).ready(function(){
    
    var btnOriginalText = 'Send order to Xero';
    $('#details .order-info-box').before('<a href="javascript:;" class="send-to-xero-btn submit btn small">'+btnOriginalText+'</a>');
    
    // register click event on btn
    $('.send-to-xero-btn').click(function(){
        var orderId = $('input[name="orderId"]').val();
        if(orderId){
            var btn = $(this);
            $(btn).off('click');
            btn.text('Sending to Xero...').addClass('disabled');
            $.get("/admin/sendordertoxero", { orderId: orderId } )
            .done(function( data ) {
                btn.text('Sent to Xero');
                console.log(data);
            })
            .fail(function(e) {
                btn.text('Error, check logs');
            });
        }
    })
    
})
