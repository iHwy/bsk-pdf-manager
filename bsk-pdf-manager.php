<?php

/*
Plugin Name: BSK PDF Manager
Description: Help you manager PDF documents. PDF documents can be filter by category. Support short code to show special PDF document or list all under special category. Widget display will be supported soon.
Version: 1.0.0
Author: bannersky
Author URI: http://www.bannersky.com/

------------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, 
or any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

*/


class BSKPDFManager {

	var $_bsk_pdf_manager_upload_folder = 'wp-content/uploads/bsk-pdf-manager/';
	var $_bsk_pdf_manager_upload_path = ABSPATH;
	var $_bsk_pdf_manager_admin_notice_message = array();

	var $_bsk_pdf_manager_cats_tbl_name = 'bsk_pdf_manager_cats';
	var $_bsk_pdf_manager_pdfs_tbl_name = 'bsk_pdf_manager_pdfs';
	
	//objects
	var $_bsk_pdf_manager_OBJ_dashboard = NULL;
	
	public function __construct() {
		global $wpdb;
		
		$this->_bsk_pdf_manager_cats_tbl_name = $wpdb->prefix.$this->_bsk_pdf_manager_cats_tbl_name;
		$this->_bsk_pdf_manager_pdfs_tbl_name = $wpdb->prefix.$this->_bsk_pdf_manager_pdfs_tbl_name;
		$this->_bsk_pdf_manager_tmpls_tools_tbl_name = $wpdb->prefix.$this->_bsk_pdf_manager_tmpls_tools_tbl_name;


		$this->_bsk_pdf_manager_upload_path = str_replace("\\", "/", $this->_bsk_pdf_manager_upload_path);
		//create folder to upload 
		$_bsk_pdf_manager_upload_folder = $this->_bsk_pdf_manager_upload_path.$this->_bsk_pdf_manager_upload_folder;
		if ( !is_dir($_bsk_pdf_manager_upload_folder) ) {
			if ( !wp_mkdir_p( $_bsk_pdf_manager_upload_folder ) ) {
				$msg = 'Directory <strong>' . $_bsk_pdf_manager_upload_folder . '</strong> can not be created. Please create it first yourself.';
				
				$this->_bsk_pdf_manager_admin_notice_message['upload_folder_missing']  = $msg;
			}
		}else{
			unset($this->_bsk_pdf_manager_admin_notice_message['upload_folder_missing']);
		}
		if ( !is_writeable( $_bsk_pdf_manager_upload_folder ) ) {
			$msg  = 'Directory <strong>' . $this->_bsk_pdf_manager_upload_folder . '</strong> is not writeable ! ';
			$msg .= 'Check <a href="http://codex.wordpress.org/Changing_File_Permissions">http://codex.wordpress.org/Changing_File_Permissions</a> for how to set the permission.';

			$this->_bsk_pdf_manager_admin_notice_message['upload_folder_not_writeable']  = $msg;
		}else{
			unset($this->_bsk_pdf_manager_admin_notice_message['upload_folder_not_writeable']);
		}

		if(is_admin()) {
			add_action('admin_notices', array($this, 'bsk_pdf_manager_admin_notice') );
			add_action('admin_enqueue_scripts', array(&$this, 'bsk_pdf_manager_enqueue_scripts_css') );
		}else{
			add_action('wp_enqueue_scripts', array(&$this, 'bsk_pdf_manager_enqueue_scripts_css') );
		}
		
		//include others class
		require_once( 'inc/bsk-pdf-dashboard.php' );
		$this->_bsk_pdf_manager_OBJ_dashboard = new BSKPDFManagerDashboard( $this );
		
		
		//shortcodes
		
		//hooks
		register_activation_hook(__FILE__, array($this, 'bsk_pdf_manager_activate') );
		register_deactivation_hook( __FILE__, array($this, 'bsk_pdf_manager_deactivate') );
		add_action('init', array($this, 'bsk_pdf_manager_post_action'));
	}
	
	function bsk_pdf_manager_activate(){
		$this->bsk_pdf_manager_create_table();
		
		// Clear the permalinks
		flush_rewrite_rules();
	}
	
	function bsk_pdf_manager_deactivate(){
		// Clear the permalinks
		flush_rewrite_rules();
	}
	
	function bsk_pdf_manager_enqueue_scripts_css(){
		
		wp_enqueue_script('jquery');
		
		if ( is_admin() ){
			wp_enqueue_style( 'bsk-pdf-manager-admin', plugins_url('css/bsk_pdf_manager_admin.css', __FILE__) );
			wp_enqueue_script( 'bsk-pdf-manager-admin', plugins_url('js/bsk_pdf_manager_admin.js', __FILE__), array('jquery') );
		}
	}
	
	function bsk_pdf_manager_admin_notice(){
		if (count($this->_bsk_pdf_manager_admin_notice_message) < 1){
			return;
		}
		echo '<div class="update-nag">';
		foreach($this->_bsk_pdf_manager_admin_notice_message as $msg){
			echo '<p>'.$msg.'</p>';
		}
		echo '</div>';
	}

	function bsk_pdf_manager_create_table(){
		global $wpdb;
		
		require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
		
		
		if (!empty ($wpdb->charset)){
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}
		if (!empty ($wpdb->collate)){
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}
		
		$table_name = $this->_bsk_pdf_manager_cats_tbl_name;
		$sql = "CREATE TABLE " . $table_name . " (
				      `id` int(11) NOT NULL AUTO_INCREMENT,
					  `cat_title` varchar(512) NOT NULL,
					  `last_date` datetime DEFAULT NULL,
					  PRIMARY KEY (`id`)
				) $charset_collate;";
		dbDelta($sql);
		
		$table_name = $this->_bsk_pdf_manager_pdfs_tbl_name;
		$sql = "CREATE TABLE " . $table_name . " (
				     `id` int(11) NOT NULL AUTO_INCREMENT,
					  `cat_id` int(11) NOT NULL,
					  `title` varchar(512) DEFAULT NULL,
					  `file_name` varchar(512) NOT NULL,
					  `last_date` datetime DEFAULT NULL,
					  PRIMARY KEY (`id`)
				) $charset_collate;";
		dbDelta($sql);
	}
	
	function bsk_pdf_manager_post_action(){
		if( isset( $_POST['bsk_pdf_manager_action'] ) && strlen($_POST['bsk_pdf_manager_action']) >0 ) {
			do_action( 'bsk_pdf_manager_' . $_POST['bsk_pdf_manager_action'], $_POST );
		}
	}
}

$BSK_PDF_manager = new BSKPDFManager();
