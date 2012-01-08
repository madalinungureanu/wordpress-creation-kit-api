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

new Wordpress_Creation_Kit( $args );


On the frontend:

$meta = get_post_meta( $post->ID, 'rmscontent', true );

*/

class Wordpress_Creation_Kit{
	
	private $defaults = array(
							'metabox_id' => '',
							'metabox_title' => 'Meta Box',
							'post_type' => 'post',
							'meta_name' => '',
							'meta_array' => array(),
							'page_template' => '',
							'post_id' => '',
							'single' => false,
							'wpml_compatibility' => false,
							'sortable' => true,
							'context' => 'post_meta'
						);
	private $args;	
	
	
	/* Constructor method for the class. */
	function __construct( $args ) {	

		/* Global that will hold all the arguments for all the custom boxes */
		global $wck_objects;
		
		/* Merge the input arguments and the defaults. */
		$this->args = wp_parse_args( $args, $this->defaults );
		
		/* Add the settings for this box to the global object */
		$wck_objects[$this->args['metabox_id']] = $this->args;
		
		/*print scripts*/
		add_action('admin_enqueue_scripts', array( &$this, 'wck_print_scripts' ));
		
		/*print styles */
		add_action('admin_print_styles', array( &$this, 'wck_print_css' ));
		
		// Set up the AJAX hooks
		add_action("wp_ajax_wck_add_meta".$this->args['meta_name'], array( &$this, 'wck_add_meta') );
		add_action("wp_ajax_wck_update_meta".$this->args['meta_name'], array( &$this, 'wck_update_meta') );
		add_action("wp_ajax_wck_show_update".$this->args['meta_name'], array( &$this, 'wck_show_update_form') );
		add_action("wp_ajax_wck_refresh_list".$this->args['meta_name'], array( &$this, 'wck_refresh_list') );
		add_action("wp_ajax_wck_add_form".$this->args['meta_name'], array( &$this, 'wck_add_form') );
		add_action("wp_ajax_wck_remove_meta".$this->args['meta_name'], array( &$this, 'wck_remove_meta') );
		//add_action("wp_ajax_swap_meta_mb", array( & $this, 'mb_swap_meta') );
		add_action("wp_ajax_wck_reorder_meta".$this->args['meta_name'], array( &$this, 'wck_reorder_meta') );
		
		/* modify Insert into post button */
		add_action('admin_head-media-upload-popup', array( &$this, 'wck_media_upload_popup_head') );
		
		/* custom functionality for upload video */
		add_filter('media_send_to_editor', array( &$this, 'wck_media_send_to_editor' ), 15, 2 );
				
		add_action('add_meta_boxes', array( &$this, 'wck_add_metabox') );	
		
		/* hook to add a side metabox with the Syncronize translation button */
		add_action('add_meta_boxes', array( &$this, 'wck_add_sync_translation_metabox' ) );
		
		/* ajax hook the syncronization function */
		add_action("wp_ajax_wck_sync_translation", array( &$this, 'wck_sync_translation_ajax' ) );
		
	}
	
	
	//add metabox using wordpress api

	function wck_add_metabox() {	
		if( $this->args['context'] == 'post_meta' ){
			if( $this->args['post_id'] == '' && $this->args['page_template'] == '' )
				add_meta_box($this->args['metabox_id'], $this->args['metabox_title'], array( &$this, 'wck_content' ), $this->args['post_type'], 'normal', 'low',  array( 'meta_name' => $this->args['meta_name'], 'meta_array' => $this->args['meta_array']) );
			else{
				$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;
				
				if( $this->args['post_id'] != '' && $this->args['page_template'] != '' ){
					$template_file = get_post_meta($post_id,'_wp_page_template',TRUE);				
					if( $this->args['post_id'] == $post_id && $template_file == $this->args['page_template'] )
						add_meta_box($this->args['metabox_id'], $this->args['metabox_title'], array( &$this, 'wck_content' ), 'page', 'normal', 'low',  array( 'meta_name' => $this->args['meta_name'], 'meta_array' => $this->args['meta_array'] ) );
				}
				else{
				
					if( $this->args['post_id'] != '' ){
						if( $this->args['post_id'] == $post_id ){
							$post_type = get_post_type( $post_id );
							add_meta_box($this->args['metabox_id'], $this->args['metabox_title'], array( &$this, 'wck_content' ), $post_type, 'normal', 'low',  array( 'meta_name' => $this->args['meta_name'], 'meta_array' => $this->args['meta_array'] ) );
						}
					}
					
					if(  $this->args['page_template'] != '' ){
						$template_file = get_post_meta($post_id,'_wp_page_template',TRUE);	
						if ( $template_file == $this->args['page_template'] )
							add_meta_box($this->args['metabox_id'], $this->args['metabox_title'], array( &$this, 'wck_content' ), 'page', 'normal', 'low',  array( 'meta_name' => $this->args['meta_name'], 'meta_array' => $this->args['meta_array']) );
					}			
					
				}			
				
			}		
		}
		else if( $this->args['context'] == 'option' ){
			add_meta_box($this->args['metabox_id'], $this->args['metabox_title'], array( &$this, 'wck_content' ), $this->args['post_type'], 'normal', 'low',  array( 'meta_name' => $this->args['meta_name'], 'meta_array' => $this->args['meta_array']) );
		}
	}	

	function wck_content($post, $metabox){		
		//output the add form 
		if( $this->args['single'] ){
			
			if( $this->args['context'] == 'post_meta' )
				$meta_val = get_post_meta( $post->ID, $metabox['args']['meta_name'], true );
			else if ( $this->args['context'] == 'option' )
				$meta_val = get_option( $metabox['args']['meta_name'] );			
			
			if( empty( $meta_val ) )
				self::create_add_form($metabox['args']['meta_array'], $metabox['args']['meta_name'], $post);
		}
		else
			self::create_add_form($metabox['args']['meta_array'], $metabox['args']['meta_name'], $post);
		//output the entries
		echo self::wck_output_meta_content($metabox['args']['meta_name'], $post->ID, $metabox['args']['meta_array']);
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
	 
	function wck_output_form_field( $meta, $details, $value = '', $context = '' ){
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
		$nonce = wp_create_nonce( 'wck-add-meta' );
		?>
		<div id="<?php echo $meta ?>" style="padding:10px 0;" <?php if( $this->args['single'] ) echo 'class="single"' ?>>
		<ul class="mb-list-entry-fields">
		<?php
		foreach ($fields as $details ){			
			?>
				<li>
					<?php echo self::wck_output_form_field( $meta, $details ); ?>
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
		
		$update_nonce = wp_create_nonce( 'wck-update-entry' );
		
		// create the $fields_myname variable dinamically so we can use the global one
		//$fields = 'fields_'.$meta;
		//global $$fields;
				
		if( $this->args['context'] == 'post_meta' )
			$results = get_post_meta($id, $meta, true);
		else if ( $this->args['context'] == 'option' )
			$results = get_option( $meta );
		
		
		$nr = count($results[$element_id])+4;
		$form = '';
		$form .= '<tr id="update_container_'.$meta.'_'.$element_id.'"><td colspan="'.$nr.'">';
		
		if($results != null){
			$i = 0;
			$form .= '<ul class="mb-list-entry-fields">';
			
			foreach($results[$element_id] as $key => $value){				
				$details = $fields[$i];
				$form .= '<li>';
				
				$form .= self::wck_output_form_field( $meta, $details, $value, 'edit_form' ); 
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
	function wck_output_meta_content($meta, $id, $fields){
		
		$edit_nonce = wp_create_nonce( 'wck-edit-entry' );
		$delete_nonce = wp_create_nonce( 'wck-delete-entry' );		
		
		if( $this->args['context'] == 'post_meta' )
			$results = get_post_meta($id, $meta, true);
		else if ( $this->args['context'] == 'option' )
			$results = get_option( $meta );
		
		$list = '';
		$list .= '<table id="container_'.esc_attr($meta).'" class="mb-table-container widefat';
		
		if( $this->args['single'] ) $list .= ' single';
		if( !$this->args['sortable'] ) $list .= ' not-sortable';
		
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
	function wck_print_scripts($hook){
		if( $this->args['context'] == 'post_meta' ) {
			if( 'post.php' == $hook || 'post-new.php' == $hook){
				wp_enqueue_script( 'jquery-ui-draggable' );
				wp_enqueue_script( 'jquery-ui-droppable' );
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script('wordpress-creation-kit', plugins_url('/wordpress-creation-kit.js', __FILE__), array('jquery') );
			}
		}
		elseif( $this->args['context'] == 'option' ){
			if( $this->args['post_type'] == $hook ){
				wp_enqueue_script( 'jquery-ui-draggable' );
				wp_enqueue_script( 'jquery-ui-droppable' );
				wp_enqueue_script( 'jquery-ui-sortable' );
				wp_enqueue_script('wordpress-creation-kit', plugins_url('/wordpress-creation-kit.js', __FILE__), array('jquery') );
			}
		}
	}
	

	/* print css*/
	function wck_print_css(){
		wp_register_style('wordpress-creation-kit-css', plugins_url('/wordpress-creation-kit.css', __FILE__));
		wp_enqueue_style('wordpress-creation-kit-css');	
	}
	

	/* ajax add a reccord to the meta */
	function wck_add_meta(){
		check_ajax_referer( "wck-add-meta" );	
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		$values = $_POST['values'];
		
		if( $this->args['context'] == 'post_meta' )
			$results = get_post_meta($id, $meta, true);
		else if ( $this->args['context'] == 'option' )
			$results = get_option( $meta );
		
		$results[] = $values;
		
		if( $this->args['context'] == 'post_meta' )
			update_post_meta($id, $meta, $results);
		else if ( $this->args['context'] == 'option' )
			update_option( $meta, $results );
		
		/* if wpml_compatibility is true add for each entry separete post meta for every element of the form  */
		if( $this->args['wpml_compatibility'] && $this->args['context'] == 'post_meta' ){
			
			$meta_suffix = count( $results );
			$i=1;
			foreach( $values as $name => $value ){
				update_post_meta($id, 'wckwpml_'.$meta.'_'.$name.'_'.$meta_suffix.'_'.$i, $value);
				$i++;
			}
		}
		
		exit;
	}

	/* ajax update a reccord in the meta */
	function wck_update_meta(){
		check_ajax_referer( "wck-update-entry" );
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		$element_id = $_POST['element_id'];	
		$values = $_POST['values'];
		
		
		
		if( $this->args['context'] == 'post_meta' )
			$results = get_post_meta($id, $meta, true);
		else if ( $this->args['context'] == 'option' )
			$results = get_option( $meta );
		
		$results[$element_id] = $values;
		
		
		
		if( $this->args['context'] == 'post_meta' )
			update_post_meta($id, $meta, $results);
		else if ( $this->args['context'] == 'option' )
			update_option( $meta, $results );
		
		/* if wpml_compatibility is true update the coresponding post metas for every element of the form  */
		if( $this->args['wpml_compatibility'] && $this->args['context'] == 'post_meta' ){
			
			$meta_suffix = $element_id + 1;
			$i = 1;
			foreach( $values as $name => $value ){
				update_post_meta($id, 'wckwpml_'.$meta.'_'.$name.'_'.$meta_suffix.'_'.$i, $value);
				$i++;
			}
		}
		
		exit;
	}

	/* ajax to refresh the meta content */
	function wck_refresh_list(){
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		echo self::wck_output_meta_content($meta, $id, $this->args['meta_array']);
		exit;
	}
	
	/* ajax to add the form for single */
	function wck_add_form(){		
		$meta = $_POST['meta'];
		$id = absint( $_POST['id'] );
		$post = get_post($id);
		self::create_add_form($this->args['meta_array'], $meta, $post );	
		exit;
	}
	

	/* ajax to show the update form */
	function wck_show_update_form(){
		check_ajax_referer( "wck-edit-entry" );
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		$element_id = $_POST['element_id'];	
		echo self::mb_update_form($this->args['meta_array'], $meta, $id, $element_id);
		exit;
	}

	/* ajax to remove a reccord from the meta */
	function wck_remove_meta(){
		check_ajax_referer( "wck-delete-entry" );
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		$element_id = absint($_POST['element_id']);	
		
		if( $this->args['context'] == 'post_meta' )
			$results = get_post_meta($id, $meta, true);
		else if ( $this->args['context'] == 'option' )
			$results = get_option( $meta );
		
		$old_results = $results;
		unset($results[$element_id]);
		/* reset the keys for the array */
		$results = array_values($results);
		
		if( $this->args['context'] == 'post_meta' )
			update_post_meta($id, $meta, $results);
		else if ( $this->args['context'] == 'option' )
			update_option( $meta, $results );
		
		
		
		/* TODO: optimize so that it updates from the deleted element forward */
		/* if wpml_compatibility is true delete the coresponding post metas */
		if( $this->args['wpml_compatibility'] && $this->args['context'] == 'post_meta' ){			
			
			$meta_suffix = 1;			
						
			foreach( $results as $result ){
				$i = 1;
				foreach ( $result as $name => $value){					
					update_post_meta($id, 'wckwpml_'.$meta.'_'.$name.'_'.$meta_suffix.'_'.$i, $value);
					$i++;
				}
				$meta_suffix++;			
			}
			
			if( count( $results ) == 0 )
				$results = $old_results;
			
			foreach( $results as $result ){
				$i = 1;
				foreach ( $result as $name => $value){
					delete_post_meta( $id, 'wckwpml_'.$meta.'_'.$name.'_'.$meta_suffix.'_'.$i );
					$i++;
				}
				break;
			}
		}
		
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
	function wck_reorder_meta(){
		$meta = $_POST['meta'];
		$id = absint($_POST['id']);
		$elements_id = $_POST['values'];			
		
		if( $this->args['context'] == 'post_meta' )
			$results = get_post_meta($id, $meta, true);
		else if ( $this->args['context'] == 'option' )
			$results = get_option( $meta );
		
		$new_results = array();
		foreach($elements_id as $element_id){
			$new_results[] = $results[$element_id];
		}
		
		$results = $new_results;
		
		if( $this->args['context'] == 'post_meta' )
			update_post_meta($id, $meta, $results);
		else if ( $this->args['context'] == 'option' )
			update_option( $meta, $results );
		
		
		/* if wpml_compatibility is true reorder all the coresponding post metas  */
		if( $this->args['wpml_compatibility'] && $this->args['context'] == 'post_meta' ){			
			
			$meta_suffix = 1;
			foreach( $new_results as $result ){
				$i = 1;
				foreach ( $result as $name => $value){					
					update_post_meta($id, 'wckwpml_'.$meta.'_'.$name.'_'.$meta_suffix.'_'.$i, $value);
					$i++;
				}
				$meta_suffix++;
			}		
			
		}		
		
		exit;
	}

	/* modify Insert into post button */	
	function wck_media_upload_popup_head()
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

	function wck_media_send_to_editor($html, $id)
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
	
	/* WPML Compatibility */
	
	/**
	 * Function that ads the side metabox with the Syncronize translation button. 
	 * The meta box is only added if the lang attribute  is set and 
	 * if any of the custom fields has the 'wckwpml' prefix.
	 */
	function wck_add_sync_translation_metabox(){
		global $post;	
			
		if( isset( $_GET['lang'] ) ){
			
			$has_wck_with_wpml_compatibility = false;
			$custom_field_keys = get_post_custom_keys( $post->ID );
			foreach( $custom_field_keys as $custom_field_key ){
				$custom_field_key = explode( '_', $custom_field_key );
				if( $custom_field_key[0] == 'wckwpml' ){
					$has_wck_with_wpml_compatibility = true;
					break;
				}
			}
			
			if($has_wck_with_wpml_compatibility){
				add_meta_box( 'wck_sync_translation', 'Syncronize WCK', array( &$this, 'wck_add_sync_box' ), $post->post_type, 'side', 'low' );
			}
			
		}			
	}

	/**
	 * Callback for the add_meta_box function that ads the "Syncronize WCK Translation" button.
	 */
	function wck_add_sync_box(){
		global $post;
		?>	
		<span id="wck_sync" class="button" onclick="wckSyncTranslation(<?php echo $post->ID; ?>)"><?php _e( 'Syncronize WCK Translation', 'fustom_fields_creator' ) ?></span>
		<?php 
	}



	/**
	 * Function that recreates the serialized metas from the individual meta fields.
	 */
	function wck_sync_translation_ajax(){		
			$post_id = $_POST['id'];		
			
			/* get all the custom fields keys for the post */
			$custom_field_keys = (array)get_post_custom_keys( $post_id );	
			
			/* initialize an array that will hold all the arrays for all the wck boxes */
			$wck_array = array();		
			
			/* go through all the custom fields and if it is a custom field created automaticaly for the translation add it to the  $wck_array array*/
			foreach( $custom_field_keys as $cf ){
				
				$cf_name_array = explode( '_', $cf );
				
				/* a custom field added for the translation will have this form
					'wckwpml_{meta name}_{field name}_{entry position}_{field position}'
				*/
				if( count( $cf_name_array ) >= 5 ){
					
					$cf_name = implode( '_', array_slice( $cf_name_array, 1, -3 ) );
					
					if( $cf_name_array[0] == 'wckwpml' ){
						
						$wck_key = $cf_name_array[ count($cf_name_array) -3 ];
						$wck_position = $cf_name_array[ count($cf_name_array) -2 ];
						$wck_field_position = $cf_name_array[ count($cf_name_array) -1 ];					
						
						/* "$wck_position - 1" is required because fields in wck by default start at 0 and the additional
						translation fields start at 1 */
						$wck_array[$cf_name][$wck_position - 1][$wck_field_position][$wck_key] = get_post_meta($post_id,$cf,true);
						
					}
				}
			}
			
			
			
			if( !empty( $wck_array ) ){
				/* sort the array so that the entry order and fields order are synced */
				self::deep_ksort( $wck_array );
				
				/* remove the field position level in the array because it was added just so we could keep the field 
				order in place */
				$wck_array = self::wck_reconstruct_array($wck_array);						
				
				/* add the translated meta to the post */
				foreach( $wck_array as $wck_key => $wck_meta ){					
					update_post_meta( $post_id, $wck_key, $wck_meta );					
				}							
				echo('syncsuccess');
			}
		
		exit;
	}

	/**
	 * Function that deep sorts a multy array by numeric key
	 */ 
	function deep_ksort(&$arr) {
		ksort($arr);
		foreach ($arr as &$a) {
			if (is_array($a) && !empty($a)) {
				self::deep_ksort($a);
			}
		}
	}

	/**
	 * Function that removes the field position level 
	 */ 
	function wck_reconstruct_array($wck_array){	
		foreach( $wck_array as $wck_array_key => $wck_meta ){								
			foreach( $wck_meta as $wck_meta_key => $wck_entry ){
				foreach( $wck_entry as $wck_entry_key => $wck_field ){
					$wck_array[$wck_array_key][$wck_meta_key][key($wck_field)] = current($wck_field);
					unset($wck_array[$wck_array_key][$wck_meta_key][$wck_entry_key]);					
				}
			}
		}
		return $wck_array;
	}
	
	
	function wck_get_meta_boxes( $screen = null ){
		global $wp_meta_boxes, $wck_objects;	
			
		if ( empty( $screen ) )
			$screen = get_current_screen();
		elseif ( is_string( $screen ) )
			$screen = convert_to_screen( $screen );	
		
		$page = $screen->id;	
		
		$wck_meta_boxes = array();
		
		if( !empty( $wck_objects ) && !empty( $wp_meta_boxes[$page]['normal']['low'] ) ){
			foreach( $wck_objects as $key => $wck_object ){
				if( array_key_exists( $key, $wp_meta_boxes[$page]['normal']['low'] ) )
					$wck_meta_boxes[] = $key;
			}
		}
		
		return $wck_meta_boxes;
	}
	
}

/* WPML Compatibility */
//add_filter('icl_data_from_pro_translation', 'wck_wpml_get_translation');
/*function wck_wpml_get_translation($translation){
	global $sitepress_settings;
	$sitepress_settings['wck_wpml_translation'] = $translation;
	
	return $translation;
}*/

/*add_action('init', 'bnngjnfkjgn');
function bnngjnfkjgn(){
	global $sitepress_settings;
	$wck_wpml_translation = 'asdasdasdasfsadasdas';
	$sitepress_settings['wck_wpml_translation'] = 'nknd,fgjkdfljgd;lfgl;d';
	var_dump($sitepress_settings);
}

add_action('admin_footer', 'jkjkghjkgjhkjgkh');
function jkjkghjkgjhkjgkh(){
	global $sitepress_settings;
	var_dump($sitepress_settings);
}
*/
//add_action( 'icl_pro_translation_saved', 'wck_update_translation_meta_boxes', 10, 2 );
/*function wck_update_translation_meta_boxes($new_post_id, $translation){
	global $sitepress_settings;
	//$translation = $sitepress_settings['wck_wpml_translation'];
	var_dump($translation);
	
	$custom_field_keys = get_post_custom_keys( $translation['original_id'] );
	$wck_array = array();
	
	foreach((array)$sitepress_settings['translation-management']['custom_fields_translation'] as $cf => $op){
		$cf_name_array = explode( '_', $cf );
		if( count( $cf_name_array ) >= 4 ){
			$cf_name = implode( '_', array_slice( $cf_name_array, 1, -2 ) );
			
			if( in_array( $cf_name, $custom_field_keys ) && $cf_name_array[0] == 'wckwpml' ){
				
				$wck_position = $cf_name_array[ count($cf_name_array) -1 ];
				$wck_key = $cf_name_array[ count($cf_name_array) -2 ];
				
				if ($op == 1) 
					$wck_array[$cf_name][$wck_position][$wck_key] = get_post_meta($translation['original_id'],$cf,true);
				elseif( $op == 2 && isset($translation['field-'.$cf] ) ){
					$field_translation = $translation['field-'.$cf];
					$field_type = $translation['field-'.$cf.'-type'];
					if ($field_type == 'custom_field') {
						$field_translation = str_replace ( '&#0A;', "\n", $field_translation );                                
						// always decode html entities  eg decode &amp; to &
						$field_translation = html_entity_decode($field_translation);
						$wck_array[$cf_name][$wck_position][$wck_key] = $field_translation;
					}            
				}
			}
		}		
	}
	
	if( !empty( $wck_array ) ){
		foreach( $wck_array as $wck_key => $wck_meta ){
			update_post_meta( $new_post_id, $wck_key, $wck_meta );
		}
	}	
	
}*/

/*
Helper class that creates admin menu pages ( both top level menu pages and submenu pages )
Default Usage: 

$args = array(
			'page_type' => 'menu_page',
			'page_title' => '',
			'menu_title' => '',
			'capability' => '',
			'menu_slug' => '',
			'icon_url' => '',
			'position' => '',
			'parent_slug' => ''			
		);

'page_type'		(string) (required) The type of page you want to add. Possible values: 'menu_page', 'submenu_page'
'page_title' 	(string) (required) The text to be displayed in the title tags and header of 
				the page when the menu is selected
'menu_title'	(string) (required) The on-screen name text for the menu
'capability'	(string) (required) The capability required for this menu to be displayed to
				the user.
'menu_slug'	    (string) (required) The slug name to refer to this menu by (should be unique 
				for this menu).
'icon_url'	    (string) (optional for 'page_type' => 'menu_page') The url to the icon to be used for this menu. 
				This parameter is optional. Icons should be fairly small, around 16 x 16 pixels 
				for best results.
'position'	    (integer) (optional for 'page_type' => 'menu_page') The position in the menu order this menu 
				should appear. 
				By default, if this parameter is omitted, the menu will appear at the bottom 
				of the menu structure. The higher the number, the lower its position in the menu. 
				WARNING: if 2 menu items use the same position attribute, one of the items may be 
				overwritten so that only one item displays!
'parent_slug' 	(string) (required for 'page_type' => 'submenu_page' ) The slug name for the parent menu 
				(or the file name of a standard WordPress admin page) For examples see http://codex.wordpress.org/Function_Reference/add_submenu_page $parent_slug parameter
'priority'	    (int) (optional) How important your function is. Alter this to make your function 
				be called before or after other functions. The default is 10, so (for example) setting it to 5 would make it run earlier and setting it to 12 would make it run later. 				

public $hookname ( for required for 'page_type' => 'menu_page' ) string used internally to 
				 track menu page callbacks for outputting the page inside the global $menu array
				 ( for required for 'page_type' => 'submenu_page' ) The resulting page's hook_suffix,
				 or false if the user does not have the capability required.  				
*/

class WCK_Page_Creator{

	private $defaults = array(
							'page_type' => 'menu_page',
							'page_title' => '',
							'menu_title' => '',
							'capability' => '',
							'menu_slug' => '',
							'icon_url' => '',
							'position' => '',
							'parent_slug' => '',
							'priority' => 10
						);
	private $args;
	public $hookname;
	
	
	/* Constructor method for the class. */
	function __construct( $args ) {	

		/* Global that will hold all the arguments for all the menu pages */
		global $wck_pages;
		
		/* Merge the input arguments and the defaults. */
		$this->args = wp_parse_args( $args, $this->defaults );
		
		/* Add the settings for this page to the global object */
		$wck_pages[$this->args['page_title']] = $this->args;
		
		/* Hook the page function to 'admin_menu'. */
		add_action( 'admin_menu', array( &$this, 'wck_page_init' ), $this->args['priority'] );				
	}

	
	/**
	 * Function that creates the admin page
	 */
	function wck_page_init(){			
		
		/* Create the page using either add_menu_page or add_submenu_page functions depending on the 'page_type' parameter. */
		if( $this->args['page_type'] == 'menu_page' ){
			$this->hookname = add_menu_page( $this->args['page_title'], $this->args['menu_title'], $this->args['capability'], $this->args['menu_slug'], array( &$this, 'wck_page_template' ), $this->args['icon_url'], $this->args['position'] );
		}
		else if( $this->args['page_type'] == 'submenu_page' ){
			$this->hookname = add_submenu_page( $this->args['parent_slug'], $this->args['page_title'], $this->args['menu_title'], $this->args['capability'], $this->args['menu_slug'], array( &$this, 'wck_page_template' ) );
		}

		/* Create a hook for adding meta boxes. */
		add_action( "load-{$this->hookname}", array( &$this, 'wck_settings_page_add_meta_boxes' ) );
	}
	
	/**
	 * Do action 'add_meta_boxes'. This hook isn't executed bu  default on a admin page so we have ot add it.
	 */
	function wck_settings_page_add_meta_boxes() {					
		do_action( 'add_meta_boxes', $this->hookname );
	}

	/**
	 * Outputs default template for the page. It contains placeholders for metaboxes. It also
	 * provides two action hooks 'wck_before_meta_boxes' and 'wck_after_meta_boxes'.
	 */
	function wck_page_template(){		
		?>		
		<div class="wrap">			

			<h2><?php echo $this->args['page_title'] ?></h2>			
			
			<div id="poststuff">
			
				<?php do_action( 'wck_before_meta_boxes', $this->hookname ); ?>
				
				<div class="metabox-holder">
					<div class="post-box-container column-1 normal"><?php do_meta_boxes( $this->hookname, 'normal', null ); ?></div>
					<div class="post-box-container column-2 side"><?php do_meta_boxes( $this->hookname, 'side', null ); ?></div>
					<div class="post-box-container column-3 advanced"><?php do_meta_boxes( $this->hookname, 'advanced', null ); ?></div>
				</div>			
				
				<?php do_action( 'wck_after_meta_boxes', $this->hookname ); ?>

			</div><!-- #poststuff -->

		</div><!-- .wrap -->
		<?php
	}
}
?>