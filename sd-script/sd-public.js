(function($){
	
	$('.sell-downloads-tabs').click(function(evt){
		var m = $(this),
			t = $(evt.target);
			
		m.find('.active-tab').removeClass('active-tab');
		t.addClass('active-tab');
		$('.sell-downloads-tabs-container').removeClass('active-tab').eq(m.children().index(t)).addClass('active-tab');
	});
})(jQuery);