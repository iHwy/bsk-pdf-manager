<?php
	class BSKPDFManagerWidget extends WP_Widget {
	
		/**
		 * Register widget with WordPress.
		 */
		public function __construct() {
			parent::__construct(
				'bsk_pdf_manager_widget', // Base ID
				'BSK PDF Manager', // Name
				array( 'description' => __( 'Display special PDFs in a widget area', 'text_domain' ), ) // Args
			);
		}
	
		/**
		 * Front-end display of widget.
		 *
		 * @see WP_Widget::widget()
		 *
		 * @param array $args     Widget arguments.
		 * @param array $instance Saved values from database.
		 */
		public function widget( $args, $instance ) {
			
			echo $args['before_widget'];

			if( trim($instance['widget_title']) ){
				echo '<h1 class="widget-title">'.$instance['widget_title'].'</h1>';
			}
			//id string
			$ids_string = trim($instance['bsk_pdf_manager_ids']);
			if( !$ids_string ){
				return;
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
			if( is_array($ids_array) && count($ids_array) > 0 ){
				global $BSK_PDF_manager, $wpdb;
				
				$pdfs_upload_path = $BSK_PDF_manager->_bsk_pdf_manager_upload_path.'/'.$BSK_PDF_manager->_bsk_pdf_manager_upload_folder;
				
				$sql = "SELECT * FROM `".$BSK_PDF_manager->_bsk_pdf_manager_pdfs_tbl_name."` WHERE `id` IN( ".$ids_string.")";
				$pdf_items = $wpdb->get_results( $sql );
				if( count($pdf_items) < 1 ){
					return;
				}
				$pdfs_obj_id_as_key_array = array();
				foreach($pdf_items as $pdf_item){
					$pdfs_obj_id_as_key_array[$pdf_item->id] = $pdf_item;
				}
				$open_target_str = get_option( '_bsk_pdf_manager_open_target', '' );
				if( $open_target_str ){
					$open_target_str = 'target="'.$open_target_str.'"';
				}
				echo '<ul>';
				foreach($ids_array as $id){
					if( !isset($pdfs_obj_id_as_key_array[$id]) ){
						continue;
					}
					$pdf_obj = $pdfs_obj_id_as_key_array[$id];
					if ( $pdf_obj->file_name && file_exists($pdfs_upload_path.$pdf_obj->file_name) ){
						$str_body .= '<li><a href="'.site_url().'/'.$BSK_PDF_manager->_bsk_pdf_manager_upload_folder.$pdf_obj->file_name.'" '.$open_target_str.'>'.$pdf_obj->title.'</a></li>'."\n";
					}
				}
				echo $str_body;
				echo '</ul>';
			}
			
			echo $args['after_widget'];
		}
	
		/**
		 * Sanitize widget form values as they are saved.
		 *
		 * @see WP_Widget::update()
		 *
		 * @param array $new_instance Values just sent to be saved.
		 * @param array $old_instance Previously saved values from database.
		 *
		 * @return array Updated safe values to be saved.
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = array();
			
			$instance['widget_title'] = strip_tags( $new_instance['widget_title'] );
			$instance['bsk_pdf_manager_ids'] = strip_tags( $new_instance['bsk_pdf_manager_ids'] );
			
			return $instance;
		}
	
		/**
		 * Back-end widget form.
		 *
		 * @see WP_Widget::form()
		 *
		 * @param array $instance Previously saved values from database.
		 */
		public function form( $instance ) {
			$bsk_pdf_manager_ids = isset( $instance[ 'bsk_pdf_manager_ids' ] ) ? $instance[ 'bsk_pdf_manager_ids' ] : '';
			$widget_title = isset( $instance[ 'widget_title' ] ) ? $instance[ 'widget_title' ] : '';
		?>
        <p>Title:<br />
        	<input name="<?php echo $this->get_field_name( 'widget_title' ); ?>" id="<?php echo $this->get_field_id( 'widget_title' ); ?>" value="<?php echo $widget_title; ?>" style="width:100%;" />
        </p>
        <p>PDF IDs:<br />
        	<input name="<?php echo $this->get_field_name( 'bsk_pdf_manager_ids' ); ?>" id="<?php echo $this->get_field_id( 'bsk_pdf_manager_ids' ); ?>" value="<?php echo $bsk_pdf_manager_ids; ?>" style="width:100%;" />
            <span style="font-style:inherit;">List of IDs, separated by comma, PDF order same as IDs order</span>
        </p>
        <?php
		}
	} // class