<?php

class BSKPDFManagerSettingsSupport {

	var $_categories_db_tbl_name = '';
	var $_pdfs_db_tbl_name = '';
	var $_pdfs_upload_path = '';
	var $_pdfs_upload_folder = '';

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
		$this->_open_target_option_name = $args['open_target_option_name'];
		$this->_show_category_title_when_listing_pdfs = $args['show_category_title'];
		$this->_pdf_order_by_option_name = $args['pdf_order_by'];
		$this->_pdf_order_option_name = $args['pdf_order'];		
		
		$this->_pdfs_upload_path = $this->_pdfs_upload_path.$this->_pdfs_upload_folder;
		
		add_action( 'bsk_pdf_manager_settings_save', array($this, 'bsk_pdf_manager_settings_save_fun') );
	}
	
	function show_settings(){
		$open_target = get_option($this->_open_target_option_name, '');
		$show_title = get_option($this->_show_category_title_when_listing_pdfs, false);
		?>
        <div class="bsk_pdf_manager_settings" style="width:80%;">
        	<h3>Display SettingS</h3>
			<table>
				<tr>
                	<td style="width: 150px;">Open PDF Target:</td>
                    <td>
                    	<select name="bsk_pdf_manager_settings_target" id="bsk_pdf_manager_settings_target_id" style="width:150px;">
                        	<option value="_self" <?php if ($open_target == '_self') echo 'selected="selected"'; ?>>Same window</option>
                            <option value="_blank" <?php if ($open_target == '_blank') echo 'selected="selected"'; ?>>New window</option>
                        </select>
                    </td>
                </tr>
                <tr>
                	<td style="width: 150px;">Show category title: </td>
                    <td><input type="checkbox" name="bsk_pdf_manager_settings_show_cat_title" id="bsk_pdf_manager_settings_show_cat_title_id" <?php if($show_title) echo ' checked="checked"'; ?> /></td>
                </tr>
                <tr>
                	<td style="width: 150px;">Order PDF By: </td>
                    <td>
                    	<?php
							$order_by = get_option($this->_pdf_order_by_option_name, 'title');
						?>
                    	<select name="bsk_pdf_manager_order_by" id="bsk_pdf_manager_order_by_id" style="width:150px;" >
                        	<option value="last_date"<?php if( $order_by == 'last_date' ) echo ' selected="selected"'; ?>>Date</option>
                            <option value="title"<?php if( $order_by == 'title' ) echo ' selected="selected"'; ?>>Title</option>
                            <option value="file_name"<?php if( $order_by == 'file_name' ) echo ' selected="selected"'; ?>>File Name</option>
                        </select>
                        <?php
							$order = get_option($this->_pdf_order_option_name, 'ASC');
						?>
                        <select name="bsk_pdf_manager_order" id="bsk_pdf_manager_order_id" style="width:60px; margin-left:10px;" >
                        	<option value="ASC"<?php if( $order == 'ASC' ) echo ' selected="selected"'; ?>>ASC</option>
                            <option value="DESC"<?php if( $order == 'DESC' ) echo ' selected="selected"'; ?>>DESC</option>
                        </select>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="bsk_pdf_manager_action" value="settings_save" />
            <?php echo wp_nonce_field( plugin_basename( __FILE__ ), 'bsk_pdf_manager_settings_save_oper_nonce', true, false ); ?>
		</div><!-- end of <div class="bsk_pdf_manager_settings"> -->
		<?php
	}
	
	function show_support(){
		?>
		<div class="bsk_pdf_manager_support">
        	<h4>Plugin Support Centre</h4>
            <ul>
                <li><a href="http://www.bannersky.com/html/bsk-pdf-manager.html" target="_blank">Visit the Support Centre</a> if you have a question on using this plugin</li>
            </ul>
        </div>
    	<?php
	}
	
	function bsk_pdf_manager_settings_save_fun( $data ){
		global $wpdb;
		//check nonce field
		if ( !wp_verify_nonce( $data['bsk_pdf_manager_settings_save_oper_nonce'], plugin_basename( __FILE__ ) ) ){
			return;
		}
		
		update_option($this->_open_target_option_name, $data['bsk_pdf_manager_settings_target']);
		if(isset($data['bsk_pdf_manager_settings_show_cat_title'])){
			update_option($this->_show_category_title_when_listing_pdfs, true);
		}else{
			update_option($this->_show_category_title_when_listing_pdfs, false);
		}
		update_option($this->_pdf_order_by_option_name, $data['bsk_pdf_manager_order_by']);
		update_option($this->_pdf_order_option_name, $data['bsk_pdf_manager_order']);
	}
}