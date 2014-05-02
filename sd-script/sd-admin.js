jQuery(function(){
	(function($){
		var reports = []; //Array of reports used to hide or display items from reports
		
		// Sales Reports
		window[ 'sd_reload_report' ] = function( e ){
			var e  			  = $(e),
				report_id 	  = e.attr( 'report' ),
				report  	  = reports[ report_id ],
				datasets 	  = [],
				container_id  = '#'+e.attr( 'container' ),
				type 		  = e.attr( 'chart_type' ),
				checked_items = $( 'input[report="'+report_id+'"]:CHECKED' ),
				dataObj;
			
			checked_items.each( function(){ 
				var i = $(this).attr( 'item' );
				if( type == 'Pie' ) datasets.push( report[ i ] );
				else datasets.push( report.datasets[ i ] );
			} );
			
			if ( type == 'Pie' ) dataObj = datasets;
			else dataObj = { 'labels' : report.labels, 'datasets' : datasets };
			
			new Chart( $( container_id ).find( 'canvas' ).get(0).getContext( '2d' ) )[ type ]( dataObj, { scaleStartValue: 0 } );
		};
		
		window[ 'sd_load_report' ] = function( el, id, title, data, type, label, value ){
			function get_random_color() {
				var letters = '0123456789ABCDEF'.split('');
				var color = '#';
				for (var i = 0; i < 6; i++ ) {
					color += letters[Math.round(Math.random() * 15)];
				}
				return color;
			};
			
			if(el.checked){
				var container = $( '#'+id );
				
				if( container.html().length){
					container.show();
				}else{
					if( typeof sd_global != 'undefined' ){
						var from  = $( '[name="from_year"]' ).val()+'-'+$( '[name="from_month"]' ).val()+'-'+$( '[name="from_day"]' ).val(),
							to    = $( '[name="to_year"]' ).val()+'-'+$( '[name="to_month"]' ).val()+'-'+$( '[name="to_day"]' ).val();

						jQuery.getJSON( sd_global.aurl, { 'sd_action' : 'paypal-data', 'data' : data, 'from' : from, 'to' : to }, (function( id, title, type, label, value ){
								return function( data ){
											var datasets = [],
												dataObj,
												legend = '',
												color,
												tmp,
												index = reports.length;
											
											
											for( var i in data ){
												var v = Math.round( data[ i ][ value ] );
												if( typeof tmp == 'undefined' || tmp == null || data[ i ][ label ] != tmp ){
													color 	= get_random_color();
													tmp 	= data[ i ][ label ];
													legend 	+= '<div style="float:left;padding-right:5px;"><input type="checkbox" CHECKED chart_type="'+type+'" container="'+id+'" report="'+index+'" item="'+i+'" onclick="sd_reload_report( this );" /></div><div class="sd-legend-color" style="background:'+color+'"></div><div class="sd-legend-text">'+tmp+'</div><br />';
													if( type == 'Pie' ) datasets.push( { 'value' : v, 'color' : color } );
													else datasets.push( { 'fillColor' : color, 'strokeColor' : color, data:[ v ] } );
													
												}else{
													datasets[ datasets.length - 1][ 'data' ].push( v );
												}
											}
											
											var e = $( '#'+id );
											e.html('<div class="sd-chart-title">'+title+'</div><div class="sd-chart-legend"></div><div style="float:left;"><canvas width="400" height="400" ></canvas></div><div style="clear:both;"></div>');
											
											// Create legend
											e.find( '.sd-chart-legend').html( legend );
											
											if( type == 'Pie' ) dataObj = datasets;
											else dataObj = { 'labels' : [ 'Currencies' ], 'datasets' : datasets };
											
											reports[index] = dataObj;
											var chartObj = new Chart( e.find( 'canvas' ).get(0).getContext( '2d' ) )[ type ]( dataObj );
											e.show();
										} 
							})( id, title, type, label, value )
						);
					}
				}	
			}else{
				$( '#'+id ).hide();
			}	
		};

		// Methods definition
		window[ 'sd_display_more_info' ] = function( e ){
            e = $( e );
            e.parent().hide().next( '.sd_more_info' ).show();
        };
        
        window[ 'sd_hide_more_info' ] = function( e ){
            e = $( e );
            e.parent().hide().prev( '.sd_more_info_hndl' ).show();
        };
        
        window['sd_remove'] = function(e){
			$(e).parents('.sd-property-container').remove();
		};
		
		window['sd_select_element'] = function(e, add_to, new_element_name){
			var v = e.options[e.selectedIndex].value,
				t = e.options[e.selectedIndex].text;
			if(v != 'none'){
				$('#'+add_to).append(
					'<div class="sd-property-container"><input type="hidden" name="'+new_element_name+'[]" value="'+v+'" /><input type="button" onclick="sd_remove(this);" class="button" value="'+t+' [x]"></div>'
				);
			}	
		};
		
		window['sd_add_element'] = function(input_id, add_to, new_element_name){
			var n = $('#'+input_id),
				v = n.val();
			n.val('');	
			if( !/^\s*$/.test(v)){
				$('#'+add_to).append(
					'<div class="sd-property-container"><input type="hidden" name="'+new_element_name+'[]" value="'+v+'" /><input type="button" onclick="sd_remove(this);" class="button" value="'+v+' [x]"></div>'
				);
			}	
		};
		
		window ['open_insertion_sell_downloads_window'] = function(){
			var tags = sell_downloads.tags,
				cont = $(tags.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"'));
			
			cont.dialog({
				dialogClass: 'wp-dialog',
				modal: true,
				closeOnEscape: true,
                close:function(){
                    $(this).remove();
                },
				buttons: [
					{text: 'OK', click: function() {
						var c 	= $('#columns'),
							t   = $('#type'),
							sc  = '[sell_downloads';

						var v = c.val();
						if(/\d+/.test(v) && v > 1) sc += ' columns='+v; 
						if(t[0].selectedIndex) sc += ' type='+t[0].options[t[0].selectedIndex].value;
						sc += ']';
						if(send_to_editor) send_to_editor(sc);
						$(this).dialog("close"); 
                    }}
				]
			});
		};
		
		window['sd_delete_purchase'] = function(id){
			if(confirm('Are you sure to delete the purchase record?')){
				var f = $('#purchase_form');
				f.append('<input type="hidden" name="delete_purchase_id" value="'+id+'" />');
				f[0].submit();
			}	
		};
		
		window['sd_reset_purchase'] = function(id){
			var f = $('#purchase_form');
			f.append('<input type="hidden" name="reset_purchase_id" value="'+id+'" />');
			f[0].submit();
		};
		
        window['sd_show_purchase'] = function(id){
			var f = $('#purchase_form');
			f.append('<input type="hidden" name="show_purchase_id" value="'+id+'" />');
			f[0].submit();
		};
		
		// Main application
		jQuery('.product-data').bind('click', function(evt){
            if($(evt.target).hasClass('button_for_upload_sd')){
                var file_path_field = $(evt.target).parent().find('.file_path');
				var cfg = {
						title: 'Select Media File',
						button: {
						text: 'Select Item'
						},
						multiple: false
				};
				
				if( file_path_field.attr( 'id' ) == "sd_cover" )
				{
					cfg[ 'library' ] = { type: 'image' };
				}
				
				var media = wp.media( cfg ).on('select', 
					(function( field ){
						return function() {
							var attachment = media.state().get('selection').first().toJSON();
							var url = attachment.url;
							field.val( url );
						};
					})( file_path_field )	
				).open();
				return false;
            }    
        });
	})(jQuery)
})