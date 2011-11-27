<?php 
/* Copyright 2011 Ungureanu Madalin (email : madalin@reflectionmedia.ro)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

/* 

Usage Example 1:


$fint = array( 
		array( 'type' => 'text', 'title' => 'Title', 'description' => 'Description for this input' ), 
		array( 'type' => 'textarea', 'title' => 'Description' ), 
		array( 'type' => 'upload', 'title' => 'Image', 'description' => 'Upload a image' ), 
		array( 'type' => 'select', 'title' => 'Select This', 'options' => array( 'Option 1', 'Option 2', 'Option 3' ) ),	
		array( 'type' => 'checkbox', 'title' => 'Check This', 'options' => array( 'Option 1', 'Option 2', 'Option 3' ) ), 	
		array( 'type' => 'radio', 'title' => 'Radio This', 'options' => array( 'Radio 1', 'Radio 2', 'Radio 3' ) ), 
	);

$args = array(
	'metabox_id' => 'rm_slider_content',
	'metabox_title' => 'Slideshow Class',
	'post_type' => 'slideshows',
	'meta_name' => 'rmscontent',
	'meta_array' => $fint	
);

new Custom_Fields_Creator( $args );


On the frontend:

$meta = get_post_meta( $post->ID, 'rmscontent', true );

*/

class Custom_Fields_Creator{
	
	private $defaults = array(
							'metabox_id' => '',
							'metabox_title' => 'Meta Box',
							'post_type' => 'post',
							'meta_name' => '',
							'meta_array' => array(),
							'page_template' => '',
							'post_id' => '',
							'single' => false
						);
	private $args;	
	
	
	/* Constructor method for the class. */
	function __construct( $args ) {		
		
		/* Merge the input arguments and the defaults. */
		$this->args = wp_parse_args( $args, $this->defaults );
		
		/*print scripts*/
		add_action('admin_enqueue_scripts', array( &$this, 'cfc_print_scripts' ));
		
		/*print styles */
		add_action('admin_print_styles', array( &$this, 'cfc_print_css' ));
		
		// Set up the AJAX hooks
		add_action("wp_ajax_cfc_add_meta", array( &$this, 'cfc_add_meta') );
		add_action("wp_ajax_cfc_update_meta", array( &$this, 'cfc_update_meta') );
		add_action("wp_ajax_cfc_show_update".$this->args['meta_name'], array( &$this, 'cfc_show_update_form') );
		add_action("wp_ajax_cfc_refresh_list".$this->args['meta_name'], array( &$this, 'cfc_refresh_list') );
		add_action("wp_ajax_cfc_add_form".$this->args['meta_name'], array( &$this, 'cfc_add_form') );
		add_action("wp_ajax_cfc_remove_meta", array( &$this, 'cfc_remove_meta') );
		//add_action("wp_ajax_swap_meta_mb", array( & $this, 'mb_swap_meta') );
		add_action("wp_ajax_cfc_reorder_meta", array( &$this, 'cfc_reorder_meta') );
		
		/* modify Insert into post button */
		add_action('admin_head-media-upload-popup', array( &$this, 'cfc_media_upload_popup_head') );
		
		/* custom functionality for upload video */
		add_filter('media_send_to_editor', array( &$this, 'cfc_media_send_to_editor' ), 15, 2 );
				
		add_action('add_meta_boxes', array( &$this, 'cfc_add_metabox') );	
		
	}
	
	
	//add metabox using wordpress api

	function cfc_add_metabox() {	
	
		if( $this->args['post_id'] == '' && $this->args['page_template'] == '' )
			add_meta_box($this->args['metabox_id'], $this->args['metabox_title'], array( &$this, 'cfc_content' ), $this->args['post_type'], 'normal', 'low',  array( 'meta_name' => $this->args['meta_name'], 'meta_array' => $this->args['meta_array']) );
		else{
			$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;
			
			if( $this->args['post_id'] != '' && $this->args['page_template'] != '' ){
				$template_file = get_post_meta($post_id,'_wp_page_template',TRUE);				
				if( $this->args['post_id'] == $post_id && $template_file == $this->args['page_template'] )
					add_meta_box($this->args['metabox_id'], $this->args['metabox_title'], array( &$this, 'cfc_content' ), 'page', 'normal', 'low',  array( 'meta_name' => $this->args['meta_name'], 'meta_array' => $this->args['meta_array'] ) );
			}
			else{
			
				if( $this->args['post_id'] != '' ){
					if( $this->args['post_id'] == $post_id ){
						$post_type = get_post_type( $post_id );
						add_meta_box($this->args['metabox_id'], $this->args['metabox_title'], array( &$this, 'cfc_content' ), $post_type, 'normal', 'low',  array( 'meta_name' => $this->args['meta_name'], 'meta_array' => $this->args['meta_array'] ) );
					}
				}
				
				if(  $this->args['page_template'] != '' ){
					$template_file = get_post_meta($post_id,'_wp_page_template',TRUE);	
					if ( $template_file == $this->args['page_template'] )
						add_meta_box($this->args['metabox_id'], $this->args['metabox_title'], array( &$this, 'cfc_content' ), 'page', 'normal', 'low',  array( 'meta_name' => $this->args['meta_name'], 'meta_array' => $this->args['meta_array']) );
				}			
				
			}			
			
		}		
		
	}	

	function cfc_content($post, $metabox){		
		//output the add form 
		if( $this->args['single'] ){
			$meta_val = get_post_meta( $post->ID, $metabox['args']['meta_name'], true );
			if( empty( $meta_val ) )
				self::create_add_form($metabox['args']['meta_array'], $metabox['args']['meta_name'], $post);
		}
		else
			self::create_add_form($metabox['args']['meta_array'], $metabox['args']['meta_name'], $post);
		//output the entries
		echo self::cfc_output_meta_content($metabox['args']['meta_name'], $post->ID, $metabox['args']['meta_array']);
	}
	
	/**
	 * The function used to create a form element
	 *
	 * @since 1.0.0
	 *
	 * @param string $meta Meta name.	 
	 * @param array $details Contains the details for the field.	 
	 * @param string $value Contains input value;
	 * @param string $context Context where the function is used. Depending on it some actions are preformed.;
	 * @return string $element input element html string.
	 */
	 
	function cfc_output_form_field( $meta, $details, $value = '', $context = '' ){
		$element = '';
		
		if( $context == 'edit_form' ){
			$edit_class = '.mb-table-container ';
			$var_prefix = 'edit';
		}
		
		if($details['type'] == 'text'){
			$element .= '<input type="text" name="'. esc_attr( sanitize_title_with_dashes( remove_accents( $details['title'] ) ) ) .'" value="'. $value .'" class="mb-text-input mb-field"/>';
		} 
		
		if($details['type'] == 'textarea'){
			$element .= '<textarea name="'. esc_attr( sanitize_title_with_dashes( remove_accents( $details['title'] ) ) ) .'"  style="vertical-align:top;" class="mb-textarea mb-field">'. $value .'</textarea>';
		}
		
		if($details['type'] == 'select'){
			$element .= '<select name="'. esc_attr( sanitize_title_with_dashes( remove_accents( $details['title'] ) ) ) .'" class="mb-select mb-field" >';
				$element .= '<option value="default">Select</option>';
				
				if( !empty( $details['options'] ) ){
						foreach( $details['options'] as $option ){
							$element .= '<option value="'. $option .'"  '. selected( $option, $value, false ) .' >'. $option .'</option>';
						}
				}				
				
			$element .= '</select>';
		}
		
		if($details['type'] == 'checkbox'){
			
			if( !empty( $details['options'] ) ){
					foreach( $details['options'] as $option ){
						$found = false;
						
						if ( strpos($value, $option) !== false ) 
							$found = true;
						$element .= '<input type="checkbox" name="'. esc_attr( sanitize_title_with_dashes( remove_accents( $details['title'] ) ) ) .'" value="'. $option .'"  '. checked( $found, true, false ) .'class="mb-checkbox mb-field" />'. $option .' ' ;
					}
			}
			
		}
		
		if($details['type'] == 'radio'){
			
			if( !empty( $details['options'] ) ){
					foreach( $details['options'] as $option ){
						$found = false;
						
						if ( strpos($value, $option) !== false ) 
							$found = true;
						$element .= '<input type="radio" name="'. esc_attr( sanitize_title_with_dashes( remove_accents( $details['title'] ) ) ) .'" value="'. $option .'"  '. checked( $found, true, false ) .'class="mb-radio mb-field" />'. $option .' ' ;
					}
			}
			
		}		
		
		
		if($details['type'] == 'upload'){
			$element .= '<input id="'. esc_attr($meta.$details['title']) .'" type="text" size="36" name="'. esc_attr( sanitize_title_with_dashes( remove_accents ( $details['title'] ) ) ) .'" value="'. $value .'" class="mb-text-input mb-field"/>';
			$element .= '<a id="upload_'. esc_attr(strtolower($details['title'])) .'_button" class="button" onclick="tb_show(\'\', \'media-upload.php?type=file&amp;mb_type='. $var_prefix  . esc_js(strtolower($meta.$details['title'])).'&amp;TB_iframe=true\');">Upload '. $details['title'] .' </a>';
			$element .= '<script type="text/javascript">';				
				$element .= 'window.'. $var_prefix . strtolower($meta.$details['title']) .' = jQuery(\''.$edit_class.'#'. $meta.$details['title'].'\');';
			$element .= '</script>';
		}
		
		if($details['type'] != 'upload'){
			$element .= '<label for="'. esc_attr($details['title']) .'">'. ucfirst($details['title']) .'</label>';
		}
		
		if( !empty( $details['description'] ) ){
			$element .= '<p class="description">'. $details['description'].'</p>';
		}
		
		return $element;
				
	}
	
		
	/**
	 * The function used to create the form for adding records
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields Contains the desired inputs in the repeater field. Must be like: array('Key:type').
	 * Key is used for the name attribute of the field, label of the field and as the meta_key.
	 * Supported types: input, textarea, upload
	 * @param string $meta It is used in update_post_meta($id, $meta, $results);. Use '_' prefix if you don't want 
	 * the meta to apear in custom fields box.
	 * @param object $post Post object
	 */
	function create_add_form($fields, $meta, $post){
		$nonce = wp_create_nonce( 'cfc-add-meta' );
		?>
		<div id="<?php echo $meta ?>" style="padding:10px 0;" <?php if( $this->args['single'] ) echo 'class="single"' ?>>
		<ul class="mb-list-entry-fields">
		<?php
		foreach ($fields as $details ){			
			?>
				<li>
					<?php echo self::cfc_output_form_field( $meta, $details ); ?>
				</li>
			<?php
		}
		?>
		<li><a href="javascript:void(0)" class="button mbadd" onclick="addMeta('<?php echo esc_js($meta); ?>', '<?php echo esc_js($post->ID); ?>', '<?php echo esc_js($nonce); ?>')"><span>&nbsp;</span></a></li>
		</ul>
		</div>
		<?php
	}
	
	/**
	 * The function used to display a form to update a reccord from meta
	 *
	 * @since 1.0.0
	 *	 
	 * @param string $meta It is used in get_post_meta($id, $meta, $results);. Use '_' prefix if you don't want 
	 * the meta to apear in custom fields box.
	 * @param int $id Post id
	 * @param int $element_id The id of the reccord. The meta is stored as array(array());
	 */
	function mb_update_form($fields, $meta, $id, $element_id){
		
		$update_nonce = wp_create_nonce( 'cfc-update-entry' );
		
		// create the $fields_myname variable dinamically so we can use the global one
		//$fields = 'fields_'.$meta;
		//global $$fields;
		
		$results = get_post_meta($id, $meta, true);
		$nr = count($results[$element_id])+4;
		$form = '';
		$form .= '<tr id="update_container_'.$meta.'_'.$element_id.'"><td colspan="'.$nr.'">';
		
		if($results != null){
			$i = 0;
			$form .= '<ul class="mb-list-entry-fields">';
			
			foreach($results[$element_id] as $key => $value){				
				$details = $fields[$i];
				$form .= '<li>';
				
				$form .= self::cfc_output_form_field( $meta, $details, $value, 'edit_form' ); 
				/*
				if ($details['type'] == 'text') { 
					$form .= '<input type="text" name="'.esc_attr($key).'" value="'.esc_attr($value).'" class="mb-text-input mb-field"/>'; 
				}
				if ($details['type'] == 'textarea'){
					$form .= '<textarea name="'.esc_attr($key).'" style="vertical-align:top;" class="mb-textarea mb-field">' . $value . '</textarea>'; 
				}
				if ($details['type'] == 'upload'){
					$form .= '<input name="'.esc_attr($key).'" style="vertical-align:top;" value="'.esc_attr($value).'" class="mb-text-input mb-field"/>'; 
				}
				$form .= ' <label for="'.esc_attr($key).'">'.$details['title'].'</label>';*/
				$form .= '</li>';
				$i++;
			}
			$form .= '<li><a href="javascript:void(0)" class="button mbupdate" onclick=\'updateMeta("'.esc_js($meta).'", "'.esc_js($id).'", "'.esc_js($element_id).'", "'.esc_js($update_nonce).'")\'><span>&nbsp;</span></a></li>';
			$form .= '</ul>';
		}
		//var_dump($$fields);
		$form .= '</td></tr>';

		
		return $form;
	}

		
	/**
	 * The function used to output the content of a meta
	 *
	 * @since 1.0.0
	 *	 
	 * @param string $meta It is used in get_post_meta($id, $meta, $results);. Use '_' prefix if you don't want 
	 * the meta to apear in custom fields box.
	 * @param int $id Post id
	 */
	function cfc_output_meta_content($meta, $id, $fields){
		
		$edit_nonce = wp_create_nonce( 'cfc-edit-entry' );
		$delete_nonce = wp_create_nonce( 'cfc-delete-entry' );
		
		$results = get_post_meta($id, $meta, true);
		
		$list = '';
		$list .= '<table id="container_'.esc_attr($meta).'" class="mb-table-container widefat';
		
		if( $this->args['single'] ) $list .= ' single';
		
		$list .= '" post="'.esc_attr($id).'">';		
		
		
		if($results != null){
			$list .= '<thead><tr><th>Content</th><th>Edit</th><th>Delete</th></tr></thead>';
			$i=0;
			foreach ($results as $result){			
			
				$list .= '<tr id="element_'.$i.'">'; 
				$list .= '<td><ul>';
				
				$j = 0;
				foreach($result as $key => $value){			
					$details = $fields[$j];
					$list .= '<li><strong>'.$details['title'].': </strong>'.$value.' </li>';				
					$j++;
				}
				$list .= '</ul></td>';				
				$list .= '<td style="text-align:center;vertical-align:middle;"><a href="javascript:void(0)" class="button mbedit" onclick=\'showUpdateFormMeta("'.esc_js($meta).'", "'.esc_js($id).'", "'.esc_js($i).'", "'.esc_js($edit_nonce).'")\'><span>&nbsp</span></a></td>';
				$list .= '<td style="text-align:center;vertical-align:middle;"><a href="javascript:void(0)" class="button mbdelete" onclick=\'removeMeta("'.esc_js($meta).'", "'.esc_js($id).'", "'.esc_js($i).'", "'.esc_js($delete_nonce).'")\'><span>&nbsp</span></a></td>';
				/*if($i != 0 && count($results) > 1){
					$j= $i-1;
					$list .= '<td><a href="javascript:void(0)" class="button moveup" onclick=\'swapMetaMb("'.$meta.'", "'.$id.'", "'.$i.'", "'. $j .'")\'><span>&nbsp;</span></a></td>';
				}
				if($i != count($results)-1){
					$j= $i+1;
					$list .= '<td><a href="javascript:void(0)" class="button movedown" onclick=\'swapMetaMb("'.$meta.'", "'.$id.'", "'.$i.'", "'. $j .'")\'><span>&nbsp;</span></a></td>';
				}*/
				$list .= '</tr>';
				$i++;
			}
		}
		$list .= '</table>';
		return $list;
	}

	/* enque the js*/
	function cfc_print_scripts($hook){
		if('post.php' == $hook || 'post-new.php' == $hook){
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-droppable' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script('custom-fields-creator', plugins_url('/custom-fields-creator.js', __FILE__), array('jquery') );
		}
	}
	

	/* print css*/
	function cfc_print_css(){
		wp_register_style('custom-fields-creator-css', plugins_url('/custom-fields-creator.css', __FILE__));
		wp_enqueue_style('custom-fields-creator-css');	
	}
	

	/* ajax add a reccord to the meta */
	function cfc_add_meta(){
		check_ajax_referer( "cfc-add-meta" );	
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		$values = $_POST['values'];
		$results = get_post_meta($id, $meta, true);
		$results[] = $values;
		update_post_meta($id, $meta, $results);
		exit;
	}

	/* ajax update a reccord in the meta */
	function cfc_update_meta(){
		check_ajax_referer( "cfc-update-entry" );
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		$element_id = $_POST['element_id'];	
		$values = $_POST['values'];
		$results = get_post_meta($id, $meta, true);
		$results[$element_id] = $values;
		update_post_meta($id, $meta, $results);
		exit;
	}

	/* ajax to refresh the meta content */
	function cfc_refresh_list(){
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		echo self::cfc_output_meta_content($meta, $id, $this->args['meta_array']);
		exit;
	}
	
	/* ajax to add the form for single */
	function cfc_add_form(){		
		$meta = $_POST['meta'];
		$id = absint( $_POST['id'] );
		$post = get_post($id);
		self::create_add_form($this->args['meta_array'], $meta, $post );	
		exit;
	}
	

	/* ajax to show the update form */
	function cfc_show_update_form(){
		check_ajax_referer( "cfc-edit-entry" );
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		$element_id = $_POST['element_id'];	
		echo self::mb_update_form($this->args['meta_array'], $meta, $id, $element_id);
		exit;
	}

	/* ajax to remove a reccord from the meta */
	function cfc_remove_meta(){
		check_ajax_referer( "cfc-delete-entry" );
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		$element_id = absint($_POST['element_id']);	
		$results = get_post_meta($id, $meta, true);
		unset($results[$element_id]);
		/* reset the keys for the array */
		$results = array_values($results);
		update_post_meta($id, $meta, $results);
		exit;
	}



	/* ajax to swap two reccords */
	/*function mb_swap_meta(){
		$meta = $_POST['meta'];
		$id = $_POST['id'];
		$element_id = $_POST['element_id'];	
		$swap_with = $_POST['swap_with'];	
		$results = get_post_meta($id, $meta, true);
		
		$temp = $results[$element_id];
		$results[$element_id] = $results[$swap_with];
		$results[$swap_with] = $temp;
		
		update_post_meta($id, $meta, $results);
		exit;
	}*/

	/* ajax to reorder records */
	function cfc_reorder_meta(){
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		$elements_id = $_POST['values'];	
		
		$results = get_post_meta($id, $meta, true);
		$new_results = array();
		foreach($elements_id as $element_id){
			$new_results[] = $results[$element_id];
		}
		
		$results = $new_results;
		
		update_post_meta($id, $meta, $results);
		exit;
	}

	/* modify Insert into post button */	
	function cfc_media_upload_popup_head()
	{
		if( ( isset( $_GET["mb_type"] ) ) )
		{
			?>
			<style type="text/css">
				#media-upload-header #sidemenu li#tab-type_url,
				#media-upload-header #sidemenu li#tab-gallery {
					display: none;
				}
				
				#media-items tr.url,
				#media-items tr.align,
				#media-items tr.image_alt,
				#media-items tr.image-size,
				#media-items tr.post_excerpt,
				#media-items tr.post_content,
				#media-items tr.image_alt p,
				#media-items table thead input.button,
				#media-items table thead img.imgedit-wait-spin,
				#media-items tr.submit a.wp-post-thumbnail {
					display: none;
				} 

				.media-item table thead img {
					border: #DFDFDF solid 1px; 
					margin-right: 10px;
				}

			</style>
			<script type="text/javascript">
			(function($){
			
				$(document).ready(function(){
				
					$('#media-items').bind('DOMNodeInserted',function(){
						$('input[value="Insert into Post"]').each(function(){
							$(this).attr('value','<?php _e("Select File")?>');
						});
					}).trigger('DOMNodeInserted');
					
					$('form#filter').each(function(){
						
						$(this).append('<input type="hidden" name="mb_type" value="<?php echo $_GET['mb_type']; ?>" />');
						
					});
				});
							
			})(jQuery);
			</script>
			<?php
		}
	}

	/* custom functionality for upload video */

	function cfc_media_send_to_editor($html, $id)
	{
		parse_str($_POST["_wp_http_referer"], $arr_postinfo);
		
		if(isset($arr_postinfo["mb_type"]))
		{
			$file_src = wp_get_attachment_url($id);
		
			?>
			<script type="text/javascript">				
				
				self.parent.window. <?php echo $arr_postinfo["mb_type"];?> .val('<?php echo $file_src; ?>');
				
				self.parent.tb_remove();
				
			</script>
			<?php
			exit;
		} 
		else 
		{
			return $html;
		}
		
	}
}

?>