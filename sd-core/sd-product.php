<?php
function sell_downloads_debug($mssg){
	$h = fopen(dirname(__FILE__).'/test.txt', 'a');			
	fwrite($h, $mssg.'|');
	fclose($h);
}

if(!class_exists('SDProduct')){
	class SDProduct{
		/*
		* @var integer
		*/
		private $id;
		
		/*
		* @var object
		*/
		private $product_data 	= array();
		private $post_data 	= array();
		private $type	= array();
		
		/**
		* SDProduct constructor
		*
		* @access public
		* @return void
		*/
		function __construct($id){
			global $wpdb;
			
			$this->id = $id;
			// Read general data
			$data = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.SDDB_POST_DATA." WHERE id=%d", array($id)));
			if($data) $this->product_data = (array)$data;
			
			$this->post_data = get_post($id, 'ARRAY_A');
			
			// Read the file type
			$this->type = (array)wp_get_object_terms($id, 'sd_type');
			
		} // End __construct
		
		function __get($name){
			switch($name){
				case 'type':
					return $this->genre;
				break;
				case 'cover':
				case 'file':
				case 'demo':
					if(isset($this->product_data[$name])){
						return $this->get_file_url($this->product_data[$name]);
					}else{
						return null;
					}
				break;
				default:
					if(isset($this->product_data[$name])){
						return $this->product_data[$name];
					}elseif(isset($this->post_data[$name])){
						return $this->post_data[$name];
					}else{
						return null;
					}	
			} // End switch
		} // End __get
		
		function __set($name, $value){
			global $wpdb;

			if(
				isset($this->product_data[$name]) && 
				$wpdb->update(
					$wpdb->prefix.SDDB_POST_DATA, 
					array($name => $value),
					array('id' => $this->id)
				)
			){
				$this->product_data[$name] = $value;
			}
		} // End __set
		
		function __isset($name){
			return isset($this->product_data[$name]) || isset($this->post_data[$name]);
		} // End __isset
		
		/*
		* Display content
		*/
		function get_file_url($url){
			if(preg_match('/attachment_id=(\d+)/', $url, $matches)){
				return wp_get_attachment_url( $matches[1]);
			}
			return $url;
		} // End get_file_url
		
		function display_content($mode, $tpl_engine, $output='echo'){
            $action  = SD_H_URL.'?sd_action=buynow';
			$product_data = array(
				'title' => $this->post_title,
				'cover' => $this->cover,
				'link'	=> get_permalink($this->id),
				'popularity' => $this->plays,
                'social' => null,
                'price' => null,
				'has_types' => null
			);
			
            if(get_option('sd_social_buttons')){
                $product_data['social'] = get_permalink( $this->id );
            }
            
            if($this->time) $product_data['time'] = $this->time;
			if($this->year) $product_data['year'] = $this->year;
			if($this->info) $product_data['info'] = $this->info;
			
            if(count($this->type)){
				$product_data['has_types'] = true;
				$artists = array();
				foreach($this->type as $type){
					$types[] = array('data' => '<a href="'.get_term_link($type).'">'.$type->name.'</a>');
				}
				$tpl_engine->set_loop('types', $types);
			}
			
            if(!empty($this->file)){    
                if(get_option('sd_paypal_enabled') && get_option('sd_paypal_email') && !empty($this->price)){
                    $currency_symbol = get_option('sd_paypal_currency_symbol', SD_PAYPAL_CURRENCY_SYMBOL);
                    $product_data['price'] = ((!empty($currency_symbol)) ? $currency_symbol.sprintf("%.2f", $this->price) : sprintf("%.2f", $this->price).get_option('sd_paypal_currency', SD_PAYPAL_CURRENCY));
                    $paypal_button = SD_URL.'/paypal_buttons/'.get_option('sd_paypal_button', SD_PAYPAL_BUTTON);
                    $product_data['salesbutton'] = '<form action="'.$action.'" method="post"><input type="hidden" name="sd_product_type" value="single" /><input type="hidden" name="sd_product_id" value="'.$this->id.'" /><input type="image" src="'.$paypal_button.'" style="padding-top:5px;" /></form>';
                }else{
                    $product_data['salesbutton']  = '<a href="'.$this->file.'" target="_blank">'.__('Download Here', SD_TEXT_DOMAIN).'</a>';
                }
            }    
			
			if($mode == 'store' || $mode == 'multiple'){
				if($mode == 'store')
					$tpl_engine->set_file('product', 'product.tpl.html');
				else	
					$tpl_engine->set_file('product', 'product_multiple.tpl.html');
					
				$tpl_engine->set_var('product', $product_data);
			}elseif($mode == 'single'){
				$this->plays += 1;
				$tpl_engine->set_file('product', 'product_single.tpl.html');
				$sd_main_page = get_option('sd_main_page', SD_MAIN_PAGE);
				if($sd_main_page){
					$product_data['store_page'] = $sd_main_page;
				}
				
				$demo = $this->demo;
				
				if( !empty( $demo ) ){
					$ext = pathinfo( $demo, PATHINFO_EXTENSION );
					$type = '';
					$class = '';
				
					if( !empty( $ext ) && get_option( 'sd_online_demo', SD_ONLINE_DEMO) ){
						switch( strtolower( $ext ) ){
							case 'pdf':
								$type = 'application/pdf';
							break;
							case 'ps':
								$type = 'application/postscript';
							break;
							case 'odt':
								$type = 'application/vnd.oasis.opendocument.text';
							break;
							case 'ods':
								$type = 'application/vnd.oasis.opendocument.spreadsheet';
							break;
							case 'odp':
								$type = 'application/vnd.oasis.opendocument.presentation';
							break;
							case 'sxw':
								$type = 'application/vnd.sun.xml.writer';
							break;
							case 'sxc':
								$type = 'application/vnd.sun.xml.calc';
							break;
							case 'sxi':
								$type = 'application/vnd.sun.xml.impress';
							break;
							case 'doc':
							case 'docx':
								$type = 'application/msword';
							break;
							case 'xls':
								$type = 'application/vnd.ms-excel';
							break;
							case 'ppt':
								$type = 'application/vnd.ms-powerpoint';
							break;
							case 'rtf':
								$type = 'text/rtf';
							break;
							case 'txt':
								$type = 'text/plain';
							break;
							case 'wav':
							case 'mp3':
							case 'ogg':
							case 'mid':
								$type = 'audio';
							break;
							case 'mpg':
							case 'avi':
							case 'wmv':
							case 'mov':
							case 'mp4':
							case 'm4v':
							case 'flv':
								$type = 'video';
							break;
							
						}
					}

					if( !empty( $type ) ){
						
						switch( $type ){
							case 'audio':
								$product_data['demo'] = '<br /><audio class="sd-demo-media" src="'.$demo.'"></audio><br />';
							break;
							case 'video':
								$product_data['demo'] = '<br /><video class="sd-demo-media" src="'.$demo.'"></video><br />';
							break;
							default:
								$type = 'mtype="'.$type.'"';
								$class = 'class="sd-demo-link"';
								$product_data['demo'] = '<a href="/?sd_action=demo&file='.urlencode($demo).'" target="_blank" '.$type.' '.$class.' >'.__('View File Demo', SD_TEXT_DOMAIN).'</a>';
						}
					} else {
						$product_data['demo'] = '<a href="/?sd_action=demo&file='.urlencode($demo).'" target="_blank" '.$type.' '.$class.' >'.__('Download File Demo', SD_TEXT_DOMAIN).'</a>';
					}
				} else {
					$product_data['demo'] = '';
				}
				
				if(strlen($this->post_content)){
					$product_data['description'] 	= $this->post_content;
				}	
				
				$tpl_engine->set_var('product', $product_data);
			}
			
			return $tpl_engine->parse('product', $output);
		} // End display
		
		/*
		* Class method print_metabox, for metabox generation print
		*
		* @return void
		*/
		public static function print_metabox(){
			global $wpdb, $post;
			
			$query = "SELECT * FROM ".$wpdb->prefix.SDDB_POST_DATA." as data WHERE data.id = {$post->ID};";
			$data = $wpdb->get_row($query);

			$type_post_list = wp_get_object_terms($post->ID, 'sd_type');
			$type_list = get_terms('sd_type', array( 'hide_empty' => 0 ));

			wp_nonce_field( plugin_basename( __FILE__ ), 'sd_product_box_content_nonce' );
			echo '
				<table class="form-table product-data">
					<tr>
						<td>
							'.__('Sales price:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="sd_price" id="sd_price" value="'.(($data && $data->price) ? esc_attr(sprintf("%.2f", $data->price)) : '').'" /> '.get_option('sd_paypal_currency', SD_PAYPAL_CURRENCY).'
                            <span class="sd_more_info_hndl" style="margin-left: 10px;"><a href="javascript:void(0);" onclick="sd_display_more_info( this );">[ + more information]</a></span>
                            <div class="sd_more_info">
                                <p>If let empty the product\'s price, the Sell Downloads assumes the product will be distributed for free, and displays a download link in place of the button for purchasing</p>
                                <a href="javascript:void(0)" onclick="sd_hide_more_info( this );">[ + less information]</a>
                            </div>
						</td>
					</tr>
					<tr>
						<td>
							'.__('File for sale:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="sd_file_path" class="file_path" id="sd_file_path" value="'.(($data && $data->file) ? esc_attr($data->file) : '').'" placeholder="'.__('File path/URL', SD_TEXT_DOMAIN).'" /> <input type="button" class="button_for_upload_sd button" value="'.__('Upload a file', SD_TEXT_DOMAIN).'" />
						</td>
					</tr>
					<tr>
						<td>
							'.__('File for demo:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="sd_demo_file_path" id="sd_demo_file_path" class="file_path"  value="'.(($data && $data->demo) ? esc_attr($data->demo) : '').'" placeholder="'.__('File path/URL', SD_TEXT_DOMAIN).'" /> <input type="button" class="button_for_upload_sd button" value="'.__('Upload a file', SD_TEXT_DOMAIN).'" />
						</td>
					</tr>
					<tr>
						<td valign="top">
							'.__('File type:', SD_TEXT_DOMAIN).'
						</td>
						<td><div id="sd_type_list">';
						
						if($type_post_list){
							foreach($type_post_list as $type){
								echo '<div class="sd-property-container"><input type="hidden" name="sd_type[]" value="'.esc_attr($type->name).'" /><input type="button" onclick="sd_remove(this);" class="button" value="'.esc_attr($type->name).' [x]"></div>';
							}
							
						}
						echo '</div><div style="clear:both;"><select onchange="sd_select_element(this, \'sd_type_list\', \'sd_type\');"><option value="none">'.__('Select an File Type', SD_TEXT_DOMAIN).'</option>';
						if($type_list){
							foreach($type_list as $type){
								echo '<option value="'.esc_attr($type->name).'">'.$type->name.'</option>';
							}
						}	
						echo '		
								 </select>
								 <input type="text" id="new_type" placeholder="'.__('Enter a new file type', SD_TEXT_DOMAIN).'">
								 <input type="button" value="'.__('Add file type', SD_TEXT_DOMAIN).'" class="button" onclick="sd_add_element(\'new_type\', \'sd_type_list\', \'sd_type_new\');"/><br />
								 <span class="sd-comment">'.__('Select an File Type from the list or enter new one', SD_TEXT_DOMAIN).'</span>
							</div>	
						</td>
					</tr>
					<tr>
						<td>
							'.__('Image:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="sd_cover" class="file_path" id="sd_cover" value="'.(($data && $data->cover) ? $data->cover : '').'" placeholder="'.__('File path/URL', SD_TEXT_DOMAIN).'" /> <input type="button" class="button_for_upload_sd button" value="'.__('Upload a file', SD_TEXT_DOMAIN).'" />
						</td>
					</tr>
					<tr>
						<td>
							'.__('Duration:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="sd_time" id="sd_time" value="'.(($data && $data->time) ? $data->time : '').'" /> <span class="sd-comment">'.__('For example 00:00', SD_TEXT_DOMAIN).'</span>
						</td>
					</tr>
					<tr>
						<td>
							'.__('Publication Year:', SD_TEXT_DOMAIN).'
						</td>
						<td>
							<input type="text" name="sd_year" id="sd_year" value="'.(($data && $data->year) ? $data->year : '').'" /> <span class="sd-comment">'.__('For example 1999', SD_TEXT_DOMAIN).'</span>
						</td>
					</tr>
					<tr>
						<td style="white-space:nowrap;">
							'.__('Additional information:', SD_TEXT_DOMAIN).'
						</td>
						<td style="width:100%;">
							<input type="text" name="sd_info" id="sd_info" value="'.(($data && $data->info) ? $data->info : '').'" placeholder="'.__('Page URL', SD_TEXT_DOMAIN).'" /> <span class="sd-comment">'.__('Different webpage with additional information', SD_TEXT_DOMAIN).'</span>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
								For reporting an issue or to request a customization, <a href="http://wordpress.dwbooster.com/contact-us" target="_blank">CLICK HERE</a>
							</p>
						</td>
					</tr>
				</table>
			';
		} // End print_metabox
		
        public static function print_discount_metabox(){
            ?>
            
            <!--DISCOUNT BOX -->
            <div style="color:#FF0000;">The discounts management is available only in the commercial version of plugin. <a href="http://wordpress.dwbooster.com/content-tools/sell-downloads">Press Here</a></div>
            <h4><?php _e('Scheduled Discounts', SD_TEXT_DOMAIN);?></h4>
            <table class="form-table sd_discount_table" style="border:1px dotted #dfdfdf;">
                <tr>
                    <td style="font-weight:bold;"><?php _e('New price in '.$currency, SD_TEXT_DOMAIN); ?></td>
                    <td style="font-weight:bold;"><?php _e('Valid from dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                    <td style="font-weight:bold;"><?php _e('Valid to dd/mm/yyyy', SD_TEXT_DOMAIN); ?></td>
                    <td style="font-weight:bold;"><?php _e('Promotional text', SD_TEXT_DOMAIN); ?></td>
                    <td style="font-weight:bold;"><?php _e('Status', SD_TEXT_DOMAIN); ?></td>
                    <td></td>
                </tr>
            </table>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('New price (*)', SD_TEXT_DOMAIN); ?></th>
                    <td><input type="text" DISABLED /> <?php echo get_option('sd_paypal_currency', SD_PAYPAL_LANGUAGE); ?></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Valid from (dd/mm/yyyy)', SD_TEXT_DOMAIN); ?></th>
                    <td><input type="text" DISABLED /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Valid to (dd/mm/yyyy)', SD_TEXT_DOMAIN); ?></th>
                    <td><input type="text" DISABLED /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Promotional text', SD_TEXT_DOMAIN); ?></th>
                    <td><textarea DISABLED cols="60"></textarea></td>
                </tr>
                <tr><td colspan="2"><input type="button" class="button" value="<?php _e('Add/Update Discount'); ?>" DISABLED ></td></tr>
            </table>
            <?php 
        } // End print_discount_metabox
        
        
		/*
		* Save the song data
		*
		* @access public
		* @return void
		*/
		public static function save_data(){
			global $wpdb, $post;

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

			if ( !wp_verify_nonce( $_POST['sd_product_box_content_nonce'], plugin_basename( __FILE__ ) ) )
			return;

			if ( 'page' == $_POST['post_type'] ) {
				if ( !current_user_can( 'edit_page', $post_id ) )
				return;
			} else {
				if ( !current_user_can( 'edit_post', $post_id ) )
				return;
			}

			$id = $post->ID;
			$data = array(
						'time'  	=> $_POST['sd_time'],
						'file'  	=> $_POST['sd_file_path'],
						'demo'  	=> $_POST['sd_demo_file_path'],
						'info' 		=> $_POST['sd_info'],
						'cover' 	=> $_POST['sd_cover'],
						'price' 	=> $_POST['sd_price'],
						'year'      => $_POST['sd_year']
					);
			$format = array('%s', '%s', '%s', '%s', '%s', '%f', '%s');
			$table = $wpdb->prefix.SDDB_POST_DATA;
			if(0 < $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE id=$id;") ){
				// Set an update query
				$wpdb->update(
					$table, 
					$data,
					array('id'=>$id),
					$format,
					array('%d')
				);
				
			}else{
				// Set an insert query
				$data['id'] = $id;
				$wpdb->insert(
					$table,
					$data,
					$format
				);
				
			}

			// Clear the file types lists and then set the new ones
			wp_set_object_terms($id, null, 'sd_type');
			
			// Set the file types list
			if(isset($_POST['sd_type'])){
				wp_set_object_terms($id, $_POST['sd_type'], 'sd_type', true);
			}	
			
			if(isset($_POST['sd_type_new'])){
				wp_set_object_terms($id, $_POST['sd_type_new'], 'sd_type', true);
			}
		} // End save_data
		
		/*
		* Create the list of properties to display of products
		* @param array
		* @return array
		*/	
		public static function columns($columns){
			return array(
				'cb'	 => '<input type="checkbox" />',
				'title'	 => __('Product Name', SD_TEXT_DOMAIN),
				'type'  =>__( 'File Type', SD_TEXT_DOMAIN),
				'plays'  =>__('Downloads', SD_TEXT_DOMAIN),
				'purchases' => __('Purchases', SD_TEXT_DOMAIN),
				'date'	 => __('Date', SD_TEXT_DOMAIN)
		   );
		} // End columns
		
		/*
		* Extrat the songs data for song list
		*/
		public static function columns_data($column){
			global $post;
			$obj = new SDProduct($post->ID);
			
			switch ($column){
				case "type":
					echo sell_downloads_extract_attr_as_str($obj->type, 'name', ', ');
				break;
				case "plays":
					echo $obj->plays;
				break;
				case "purchases":
					echo $obj->purchases;
				break;
			} // End switch
		} // End columns_data 
		
	}// End SDProduct class
} // Class exists check

?>