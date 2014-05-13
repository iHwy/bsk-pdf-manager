<?php

class BSKPDFManagerCategory {

	var $_categories_db_tbl_name = '';
	var $_pdfs_db_tbl_name = '';
	var $_pdfs_upload_path = '';
	var $_pdfs_upload_folder = '';
    var $_bsk_pdf_manager_managment_obj = NULL;
	var $_bsk_categories_page_name = '';
	
	var $_plugin_pages_name = array();
	var $_open_target_option_name = '';
	var $_show_category_title_when_listing_pdfs = '';
	var $_pdf_order_by_option_name = '';
	var $_pdf_order_option_name = '';	
   
	public function __construct( $args ) {
		global $wpdb;
		
		$this->_categories_db_tbl_name = $args['categories_db_tbl_name'];
	    $this->_pdfs_db_tbl_name = $args['pdfs_db_tbl_name'];
		$this->_pdfs_upload_path = $args['pdf_upload_path'];
	    $this->_pdfs_upload_folder = $args['pdf_upload_folder'];
	    $this->_bsk_pdf_manager_managment_obj = $args['management_obj'];
		$this->_plugin_pages_name = $args['pages_name_A'];
		$this->_open_target_option_name = $args['open_target_option_name'];
		$this->_show_category_title_when_listing_pdfs = $args['show_category_title'];
		$this->_pdf_order_by_option_name = $args['pdf_order_by'];
		$this->_pdf_order_option_name = $args['pdf_order'];		
		
		$this->_bsk_categories_page_name = $this->_plugin_pages_name['category'];
		$this->_pdfs_upload_path = $this->_pdfs_upload_path.$this->_pdfs_upload_folder;
		
		add_action('bsk_pdf_manager_category_save', array($this, 'bsk_pdf_manager_category_save_fun'));
		add_shortcode('bsk-pdf-manager-list-category', array($this, 'bsk_pdf_manager_list_pdfs_by_cat') );
	}
	
	function bsk_pdf_manager_category_edit( $category_id = -1 ){
		global $wpdb;
		
		$cat_title = '';
		if ($category_id > 0){
			$sql = 'SELECT * FROM '.$this->_categories_db_tbl_name.' WHERE id = '.$category_id;
			$category_obj_array = $wpdb->get_results( $sql );
			if (count($category_obj_array) > 0){
				$cat_title = $category_obj_array[0]->cat_title;
			}
		}
		
		$str = '<div class="bsk_pdf_manager_category_edit">';
		$str .='<h4>Category Title</h4>';
		$str .='<p><input type="text" name="cat_title" id="cat_title_id" value="'.$cat_title.'" maxlength="512" /></p>';
		$str .='<p>
					<input type="hidden" name="bsk_pdf_manager_action" value="category_save" />
					<input type="hidden" name="bsk_pdf_manager_category_id" value="'.$category_id.'" />'.
					wp_nonce_field( plugin_basename( __FILE__ ), 'bsk_pdf_manager_category_save_oper_nonce', true, false ).'
				</p>
				</div>';
		
		echo $str;
	}
	
	function bsk_pdf_manager_category_save_fun( $data ){
		global $wpdb;
		//check nonce field
		if ( !wp_verify_nonce( $data['bsk_pdf_manager_category_save_oper_nonce'], plugin_basename( __FILE__ ) ) ){
			return;
		}
		
		if ( !isset($data['bsk_pdf_manager_category_id']) ){
			return;
		}
		$id = $data['bsk_pdf_manager_category_id'];
		$title = trim($data['cat_title']);
		$last_date = date( 'Y-m-d H:i:s', current_time('timestamp') );
		
		if (get_magic_quotes_gpc() || empty($quotes_sybase) || $quotes_sybase === 'off'){
			$title = stripcslashes($title); 
		}
		
		if ( $id > 0 ){
			$wpdb->update( $this->_categories_db_tbl_name, array( 'cat_title' => $title, 'last_date' => $last_date), array( 'id' => $id ) );
		}else if($id == -1){
			//insert
			$wpdb->insert( $this->_categories_db_tbl_name, array( 'cat_title' => $title, 'last_date' => $last_date) );
		}
		
		$redirect_to = admin_url( 'admin.php?page='.$this->_bsk_categories_page_name );
		wp_redirect( $redirect_to );
		exit;
	}
	
	function bsk_pdf_manager_list_pdfs_by_cat($atts, $content){
		global $wpdb;
		
		extract( shortcode_atts( array('id' => ''), $atts ) );
		
		//id string
		$ids_string = trim($id);
		if( !$ids_string ){
			return '';
		}
		if( $ids_string && is_string($ids_string) ){
			$ids_array = explode(',', $ids_string);
			foreach($ids_array as $key => $pdf_id){
				$pdf_id = intval(trim($pdf_id));
				if( is_int($pdf_id) == false ){
					unset($ids_array[$key]);
				}
				$ids_array[$key] = $pdf_id;
			}
		}
		if( is_array($ids_array) == false || count($ids_array) < 1 ){
			return '';
		}
		$ids_string = implode(',', $ids_array);
		$ids_string = trim($ids_string, ',');
		
		$sql = 'SELECT * FROM `'.$this->_categories_db_tbl_name.'` WHERE id IN('.$ids_string.') ORDER BY `cat_title` ASC';
		$categories = $wpdb->get_results($sql, ARRAY_A);
		if (count($categories) < 1) {
			return '';
		}
		
		$order_by = get_option($this->_pdf_order_by_option_name, 'title');
		$order = get_option($this->_pdf_order_option_name, 'ASC');
		$home_url = site_url();
		$show_cat_title = get_option($this->_show_category_title_when_listing_pdfs, false);
		foreach( $categories as $category){
			$forStr .=	'<div class="bsk-pdf-category">'."\n";
			if($show_cat_title){
				$forStr .=	'<h2>'.$category['cat_title'].'</h2>'."\n";
			}
			//get pdf items in the category
			$sql = "SELECT * FROM `".$this->_pdfs_db_tbl_name."` WHERE `cat_id` = ".$category['id']." order by `$order_by` $order";
			$pdf_items = $wpdb->get_results($sql, ARRAY_A);
			if (count($pdf_items) < 1){
				$forStr .=  '</div>'."\n";
				continue;
			}
			$forStr .= '<ul class="bsk-special-pdfs-container">'."\n";
			$open_target_str = get_option($this->_open_target_option_name, '');
			if( $open_target_str == '_blank' ){
				$open_target_str = 'target="'.$open_target_str.'"';
			}else{
				$open_target_str = '';
			}
			foreach($pdf_items as $pdf_item){
				if( $pdf_item['file_name'] && file_exists($this->_pdfs_upload_path.$pdf_item['file_name']) ){
					$file_url = $home_url.'/'.$this->_pdfs_upload_folder.$pdf_item['file_name'];
					$forStr .= '<li><a href="'.$file_url.'" '.$open_target_str.'>'.$pdf_item['title'].'</a></li>'."\n";
				}
			}
			$forStr .= '</ul>'."\n";

			$forStr .=  '</div>'."\n";
		}
	
		return $forStr;
	}
}