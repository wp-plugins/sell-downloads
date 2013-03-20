(function($){
	
	$('.sell-downloads-tabs').click(function(evt){
		var m = $(this),
			t = $(evt.target);
			
		m.find('.active-tab').removeClass('active-tab');
		t.addClass('active-tab');
		$('.sell-downloads-tabs-container').removeClass('active-tab').eq(m.children().index(t)).addClass('active-tab');
	});
	
    $('.view-cart-btn').live('click', function(){
        document.location = sd_global['url']+'/sd-core/sd-shopping-cart.php';
    });
    
    $('.shopping-cart-btn').live('click', function(){
        var e = $(this);
        e.fadeTo('fast', 0.5);
        $.getJSON(sd_global['url']+'/sd-core/sd-sc-ajax.php', {'action':'add','product_id':e.attr('alt')}, function(data){
            e.fadeTo('fast', 1);
            if(data['error'] != undefined){
                alert(data['error']);
            }else{
                e.unbind('click');
                e.attr('src', sd_global['url']+'/paypal_buttons/shopping_cart/button_f.gif').removeClass('shopping-cart-btn').addClass('view-cart-btn');
                $('.sd-sc-items-number').html(data['success']);
            }    
		});
	});
	window['sd_add_cart'] = function(e, id){
        $(e).fadeTo('fast', 0.5);
		
	}
})(jQuery);