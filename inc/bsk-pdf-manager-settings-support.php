<?php

class BSKPDFManagerSettingsSupport {

	var $_categories_db_tbl_name = '';
	var $_pdfs_db_tbl_name = '';
	var $_pdfs_upload_path = '';
	var $_pdfs_upload_folder = '';
	var $_bsk_pdf_manager_managment_obj = NULL;
	
	var $_bsk_pdf_manager_settings_name_open_target = '_bsk_pdf_manager_open_target';
   
	public function __construct( $args ) {
		global $wpdb;
		
		$this->_categories_db_tbl_name = $args['categories_db_tbl_name'];
		$this->_pdfs_db_tbl_name = $args['pdfs_db_tbl_name'];
		$this->_pdfs_upload_path = $args['pdf_upload_path'];
	    $this->_pdfs_upload_folder = $args['pdf_upload_folder'];
		$this->_bsk_pdf_manager_managment_obj = $args['management_obj'];
		
		$this->_pdfs_upload_path = $this->_pdfs_upload_path.$this->_pdfs_upload_folder;
		
		add_action( 'bsk_pdf_manager_settings_save', array($this, 'bsk_pdf_manager_settings_save_fun') );
	}
	
	function show_settings(){
		$open_target = get_option($this->_bsk_pdf_manager_settings_name_open_target, '');
		?>
        <div class="bsk_pdf_manager_settings">
        	<h4>Open PDF Document</h4>
            <div>
                <ul class="bsk-details-form">
                    <li>
                        <label>Target:</label>
                        <select name="bsk_pdf_manager_settings_target" id="bsk_pdf_manager_settings_target_id">
                        	<option value="_self" <?php if ($open_target == '_self') echo 'selected="selected"'; ?>>Load in the same frame as it was clicked</option>
                            <option value="_blank" <?php if ($open_target == '_blank') echo 'selected="selected"'; ?>>Load in a new window</option>
                            <option value="_parent" <?php if ($open_target == '_parent') echo 'selected="selected"'; ?>>Load in the parent frameset</option>
                            <option value="_top" <?php if ($open_target == '_top') echo 'selected="selected"'; ?>>Load in the full body of the window</option>
                        </select>
                    </li>
                </ul>
            </div>
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
		
		update_option($this->_bsk_pdf_manager_settings_name_open_target, $data['bsk_pdf_manager_settings_target']);
	}
}