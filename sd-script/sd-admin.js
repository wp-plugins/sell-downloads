jQuery(function(){
	(function($){
		// Methods definition
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
		
		window["send_to_download_url_sd"] = function(html) {

			file_url = jQuery(html).attr('href');
			if (file_url) {
				jQuery(file_path_field).val(file_url);
			}
			tb_remove();
			window.send_to_editor = window.send_to_editor_default;

		}
		
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
		
		window['delete_purchase_sd'] = function(id){
			if(confirm('Are you sure to delete the purchase record?')){
				var f = $('#purchase_form');
				f.append('<input type="hidden" name="purchase_id" value="'+id+'" />');
				f[0].submit();
			}	
		};
		
		// Main application
		var file_path_field;
		window["send_to_editor_default"] = window.send_to_editor;
		jQuery('.product-data').bind('click', function(evt){
            if($(evt.target).hasClass('button_for_upload')){
                file_path_field = $(evt.target).parent().find('.file_path');

                formfield = jQuery(file_path_field).attr('name');

                window.send_to_editor = window.send_to_download_url;

                tb_show('', 'media-upload.php?post_id=' + sell_downloads.post_id + '&amp;TB_iframe=true');
                return false;
            }    
        });
	})(jQuery)
})