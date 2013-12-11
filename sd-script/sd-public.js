(function($){
	
	$('.sell-downloads-tabs').click(function(evt){
		var m = $(this),
			t = $(evt.target);
			
		m.find('.active-tab').removeClass('active-tab');
		t.addClass('active-tab');
		$('.sell-downloads-tabs-container').removeClass('active-tab').eq(m.children().index(t)).addClass('active-tab');
	});
	
	// ****** FUNCTIONS FOR DEMO ****** //
	$( document ).on( 'click', '.sd-demo-close', function( evt ){
		evt.preventDefault();
		
		var e  = $( evt.target );
		    c  = e.parents( '.sd-demo-container' ),
			sl = c.attr( 'sl' ),
			st = c.attr( 'st' );

		c.remove();
		$( 'body,html' ).removeClass( 'sd-demo' ).scrollLeft( sl ).scrollTop( st );
	} );
	
	$( window ).on( 'resize', function(){
		var b  = $( 'body' ),
		    h  = b.height();
		
		b.find( '#sd_demo_object' ).attr( 'height', ( h - b.find( '.sd-demo-head' ).height() ) + 'px' );
	} );
	
	$( '.sd-demo-link' ).click(function( evt ){
		evt.preventDefault();
		
		var e  = $( evt.target ),
		    m  = $(e).attr( 'mtype' ),
			l  = e.attr( 'href' ),
			i  = l.indexOf( 'file=' ),
			t  = $( 'html' ),
			b  = $( 'body' ),
			close_txt = ( typeof sd_global != 'undefined' && typeof sd_global.texts != 'undefined' && typeof sd_global.texts.close_demo != 'undefined' ) ? sd_global.texts.close_demo : 'close',
			download_txt = ( typeof sd_global != 'undefined' && typeof sd_global.texts != 'undefined' && typeof sd_global.texts.download_demo != 'undefined' ) ? sd_global.texts.download_demo : 'download file',
			plugin_fault_txt = ( typeof sd_global != 'undefined' && typeof sd_global.texts != 'undefined' && typeof sd_global.texts.plugin_fault != 'undefined' ) ? sd_global.texts.plugin_fault : 'The Object to display the demo file is not enabled in your browser. CLICK HERE to download the demo file',
			sl = b.scrollLeft(),
			st = b.scrollTop();
			
		l = decodeURIComponent( l.substr( i+5 ) );
		
		t.addClass( 'sd-demo' );
		b.addClass( 'sd-demo' );
		
		var h = $( window ).height();

		b.append( $( '<div class="sd-demo-container" sl="'+sl+'" st="'+st+'" style="height:100%;width:100%;position:absolute;top:0;left:0;z-index:99999;">\
		                <div class="sd-demo-head">\
						  <a href="'+e.attr( 'href' )+'">'+download_txt+'</a>\
						  <a href="#" class="sd-demo-close">'+close_txt+'</a>\
						</div>\
					    <div class="sd-demo-body">\
			              <object id="sd_demo_object" data="'+l+'" type="'+m+'" width="100%"> \
						     <div style="margin-top:40px;">\
				             <a href="'+e.attr( 'href' )+'">'+plugin_fault_txt+'</a>\
							 </div>\
			              </object>\
					    </div>\
					  </div>' ) );
		
		b.find( '#sd_demo_object' ).attr( 'height', ( h - b.find( '.sd-demo-head' ).height() ) + 'px' );
	});
	
	$( '.sd-demo-media' ).mediaelementplayer();
	
})(jQuery);