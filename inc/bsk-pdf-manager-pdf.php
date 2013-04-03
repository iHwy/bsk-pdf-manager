<?php

class BSKPDFManagerPDF {

	var $_categories_db_tbl_name = '';
	var $_pdfs_db_tbl_name = '';
	var $_pdfs_upload_path = '';
	var $_pdfs_upload_folder = '';
	var $_bsk_pdf_manager_managment_obj = NULL;
   
	public function __construct( $args ) {
		global $wpdb;
		
		$this->_categories_db_tbl_name = $args['categories_db_tbl_name'];
		$this->_pdfs_db_tbl_name = $args['pdfs_db_tbl_name'];
		$this->_pdfs_upload_path = $args['pdf_upload_path'];
	    $this->_pdfs_upload_folder = $args['pdf_upload_folder'];
		$this->_bsk_pdf_manager_managment_obj = $args['management_obj'];
		
		$this->_pdfs_upload_path = $this->_pdfs_upload_path.$this->_pdfs_upload_folder;
		
		add_action( 'bsk_pdf_manager_pdf_save', array($this, 'bsk_pdf_manager_pdf_save_fun') );
		
		add_shortcode('bsk-pdf-manager-pdf', array($this, 'bsk_pdf_manager_show_pdf') );
	}
	
	function pdf_edit( $pdf_id = -1 ){
		global $wpdb;
		
		//get all categories
		$sql = 'SELECT * FROM '.$this->_categories_db_tbl_name;
		$categories = $wpdb->get_results( $sql );
		
		$pdf_obj_array = array();
		if ($pdf_id > 0){
			$sql = 'SELECT * FROM '.$this->_pdfs_db_tbl_name.' WHERE id = '.$pdf_id;
			$pdfs_obj_array = $wpdb->get_results( $sql );
			if (count($pdfs_obj_array) > 0){
				$pdf_obj_array = (array)$pdfs_obj_array[0];
			}
		}
		$category_id = 0;
		if ( isset($pdf_obj_array['cat_id']) ){
			$category_id = $pdf_obj_array['cat_id'];
		}

		?>
        <div class="bsk_pdf_manager_pdf_edit">
		<h4>Please select category</h4>
		<select name="bsk_pdf_manager_pdf_edit_categories" id="bsk_pdf_manager_pdf_edit_categories_id">
        <option value="0">Please select category</option>
        <?php 
		foreach($categories as $category){ 
			if ($category->id == $category_id){
				echo '<option value="'.$category->id.'" selected="selected">'.$category->cat_title.'</option>';
			}else{
				echo '<option value="'.$category->id.'">'.$category->cat_title.'</option>';
			}
		} 
		?>
        </select>
        
        <?php
			$u_bytes = $this->bsk_pdf_manager_pdf_convert_hr_to_bytes( ini_get( 'upload_max_filesize' ) );
			$p_bytes = $this->bsk_pdf_manager_pdf_convert_hr_to_bytes( ini_get( 'post_max_size' ) );
			$maximumUploaded = floor(min($u_bytes, $p_bytes) / 1024).' K bytes.';
			if ($maximumUploaded > 1024){
				$maximumUploaded = floor( $maximumUploaded / 1024).' M bytes.';
			}
			
			
			if( $pdf_obj_array['file_name'] && file_exists($this->_pdfs_upload_path.$pdf_obj_array['file_name']) ){
				$file_url = get_option('home').'/'.$this->_pdfs_upload_folder.$pdf_obj_array['file_name'];
			}else{
				$file_str = '';
			}
		?>
        <h4>PDF Document</h4>
        <div>
            <ul class="bsk-details-form">
                <li>
                    <label>Titile:</label>
                    <input type="text" name="bsk_pdf_manager_pdf_titile" id="bsk_pdf_manager_pdf_titile_id" value="<?php echo $pdf_obj_array['title']; ?>" maxlength="512" />
                </li>
                <?php if ($pdf_id > 0 && $file_url){ ?>
                <li>
                    <label>Delete old ?</label>
                    <a href="<?php echo $file_url; ?>" target="_blank"><?php echo $pdf_obj_array['file_name']; ?></a>
                    <input type="checkbox" name="bsk_pdf_manager_pdf_file_rmv" id="bsk_pdf_manager_pdf_file_rmv_id" value="true" />
                    <input type="hidden" name="bsk_pdf_manager_pdf_file_old" id="bsk_pdf_manager_pdf_file_old_id" value="<?php echo $pdf_obj_array['file_name']; ?>" />
                </li>
                <?php } ?>
                <li>
                    <label>Please select a file to upload:</label>
                    <input type="file" name="bsk_pdf_file" id="bsk_pdf_file_id" value="Browse" />
                </li>
                <li>
                	<label>&nbsp;</label>
                    <span class="bsk_description">Maximum file size: <?php echo $maximumUploaded; ?></span>
                </li>
                <li>
                	<label>&nbsp;</label>
                    <span class="bsk_description">Only <b>.pdf</b> allowed.</span>
                </li>
                <li>
                	<input type="hidden" name="bsk_pdf_manager_action" value="pdf_save" />
                    <input type="hidden" name="bsk_pdf_manager_pdf_id" value="<?php echo $pdf_id; ?>" />
                    <?php echo wp_nonce_field( plugin_basename( __FILE__ ), 'bsk_pdf_manager_pdf_save_oper_nonce', true, false ); ?>
                </li>
            </ul>
          </div>
		</div><!-- end of <div class="rs_checklist_tmpls_tools_edit"> -->
		<?php
	}
	
	function bsk_pdf_manager_pdf_save_fun( $data ){
		global $wpdb;
		//check nonce field
		if ( !wp_verify_nonce( $data['bsk_pdf_manager_pdf_save_oper_nonce'], plugin_basename( __FILE__ ) ) ){
			return;
		}
		if ( !isset($data['bsk_pdf_manager_pdf_edit_categories']) ){
			return;
		}

		$pdf_id = trim($data['bsk_pdf_manager_pdf_id']);
		$pdf_data = array();
		$pdf_data['cat_id'] = $data['bsk_pdf_manager_pdf_edit_categories'];
		$pdf_data['title'] = $data['bsk_pdf_manager_pdf_titile'];
		$pdf_data['last_date'] = date('Y-m-d H:i:s', current_time('timestamp'));
		
		if (get_magic_quotes_gpc() || empty($quotes_sybase) || $quotes_sybase === 'off'){
			foreach($pdf_data as $key => $val){
				$pdf_data[$key] = stripcslashes($val); 
			}
		}
		
		if ($pdf_id > 0){
			//update
			if (isset($data['bsk_pdf_manager_pdf_file_rmv']) && $data['bsk_pdf_manager_pdf_file_rmv'] == 'true'){
				if ($data['bsk_pdf_manager_pdf_file_old']){
					unlink($this->_pdfs_upload_path.$data['bsk_pdf_manager_pdf_file_old']);
					$pdf_data['file_name'] = '';
				}
			}
			$return_detinate_name = $this->bsk_pdf_manager_pdf_upload_file($_FILES['template_tool_file'], $pdf_id);
			if ($return_detinate_name){
				$pdf_data['file_name'] = $return_detinate_name;
				//new one uploaded, the old one should be removed
				if ($data['bsk_pdf_manager_pdf_file_old']){
					unlink($this->_pdfs_upload_path.$data['bsk_pdf_manager_pdf_file_old']);
				}
			}
			unset($pdf_data['id']); //for update, dont't chagne id
			$wpdb->update( $this->_pdfs_db_tbl_name, $pdf_data, array('id' => $pdf_id) );
		}else{
			//insert
			$return = $wpdb->insert( $this->_pdfs_db_tbl_name, $pdf_data );
			if (!$return){
				$msg = 'Insert PDF failed.';
			}else{
				$new_pdf_id = $wpdb->insert_id;
				$return_detinate_name = $this->bsk_pdf_manager_pdf_upload_file($_FILES['bsk_pdf_file'], $new_pdf_id);
				if ($return_detinate_name){
					$wpdb->update( $this->_pdfs_db_tbl_name, array('file_name' => $return_detinate_name), array('id' => $new_pdf_id) );
				}else{
					$sql = 'DELETE FROM `'.$this->_pdfs_db_tbl_name.'` WHERE id ='.$new_pdf_id;
					$wpdb->query( $sql );
				}
			}
		}
	}
	
	function bsk_pdf_manager_pdf_upload_file($file, $destination_name_prefix){
		$allowedExts = array("pdf");
		$extension = end(explode(".", $file["name"]));
		if ($file["type"] != "application/pdf" ||
		    in_array($extension, $allowedExts) === false || 
			$file["error"] != 0 || 
			$file["name"] == ""
			){
			$msg = 'Invalid file. Only .pdf file allowed.';
			//show error message
			return false;
		}
		$destinate_file_name = $destination_name_prefix.'_'.sanitize_file_name($file["name"]);
		$ret = move_uploaded_file($file["tmp_name"], $this->_pdfs_upload_path.$destinate_file_name);
		if ($ret){
			return $destinate_file_name;
		}
		return false;
	}
	
	function bsk_pdf_manager_pdf_convert_hr_to_bytes( $size ) {
		$size  = strtolower( $size );
		$bytes = (int) $size;
		if ( strpos( $size, 'k' ) !== false )
			$bytes = intval( $size ) * 1024;
		elseif ( strpos( $size, 'm' ) !== false )
			$bytes = intval($size) * 1024 * 1024;
		elseif ( strpos( $size, 'g' ) !== false )
			$bytes = intval( $size ) * 1024 * 1024 * 1024;
		return $bytes;
	}
	
	function bsk_pdf_manager_show_pdf($atts, $content){
		global $wpdb;
		
		$id = $atts['id'];
		if ($id < 1){
			return '';
		}
		
		$str_header = '<ul>'."\n";
		$str_body = '';
		$str_footer .= '</ul>'."\n";
		//get pdf items in the category
		$sql = "SELECT * FROM `".$this->_pdfs_db_tbl_name."` WHERE `id` = ".$id." order by `title` ASC";
		$pdf_items = $wpdb->get_results($sql, ARRAY_A);
		if (count($pdf_items) < 1){
			return '';
		}
		$open_target_str = get_option($this->_bsk_pdf_manager_managment_obj->_bsk_pdf_manager_OBJ_settings_support->_bsk_pdf_manager_settings_name_open_target, '');
		if ($open_target_str){
			$open_target_str = 'target="'.$open_target_str.'"';
		}
		foreach($pdf_items as $pdf_item){
			if ( $pdf_item['file_name'] && file_exists($this->_pdfs_upload_path.$pdf_item['file_name']) ){
				$file_url = get_option('home').'/'.$this->_pdfs_upload_folder.$pdf_item['file_name'];
				$str_body .= '<li><a href="'.$file_url.'" '.$open_target_str.'>'.$pdf_item['title'].'</a></li>'."\n";
			}
		}
		if ($str_body){
			return $str_header.$str_body.$str_footer;
		}
		return '';
	}
}