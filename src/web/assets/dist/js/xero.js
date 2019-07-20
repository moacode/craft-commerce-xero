/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */


$(document).ready(function(){

    if (sentToXero) {
        var btnOriginalText = 'Invoice exists in Xero';
    } else {
        var btnOriginalText = 'Send invoice to Xero';
    }
    
    $('#details .order-info-box').before('<a href="javascript:;" class="send-to-xero-btn submit btn small">'+btnOriginalText+'</a>');
    
    // register click event on btn (if order isn't in Xero)
    if (!sentToXero) {
        $('.send-to-xero-btn').click(function(){
            var orderId = $('input[name="orderId"]').val();
            if(orderId){
                var btn = $(this);
                $(btn).off('click');
                btn.text('Sending to Xero...').addClass('disabled');
                $.get("/admin/sendordertoxero", { orderId: orderId } )
                .done(function( data ) {
                    btn.text('Successfully sent to Xero');
                    console.log(data);
                })
                .fail(function(e) {
                    btn.text('Unable to send Invoice');
                });
            }
        });
    } else {
        var btn = $('.send-to-xero-btn');
        btn.addClass('disabled');
    }
    
})
