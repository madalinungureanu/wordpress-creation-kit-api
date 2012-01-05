Usage Example 1

 <?php
 
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

 ?> 

For Frontend use like this:

<?php $meta = get_post_meta( $post->ID, 'rmscontent', true ); ?>




Default Parameters

<?php $args = array(
    'metabox_id' => '',
	'metabox_title' => 'Meta Box',
	'post_type' => 'post',
	'meta_name' => '',
	'meta_array' => array(),
	'page_template' => '',
	'post_id' => '',
	'single' => false ?> 
	
Parameters

$metabox_id
    (string) (required) HTML 'id' attribute of the edit screen section

        Default: None 

$metabox_title
    (string) (required) Title of the edit screen section, visible to user 
	
		Default: 'Meta Box' 

$post_type 
	(string) (required) The type of Write screen on which to show the edit screen section ('post', 'page', 'link', or 'custom_post_type' where custom_post_type is the custom post type slug)

    Default: 'post' 
	
$meta_name 
	(string) (required) The name of the meta key used to query for data 

    Default: None 
	
$meta_array 
	(array) (required) The array of fields used to create the form. See example above. Must be array( array() ). Type and Title are required.

    Default: None 
	
$page_template 
	(string) (optional) The name of the page template on wich you want the meta box to appear. If this is set than  $post_type can be omitted.

    Default: None  
	
$post_id 
	(string) (optional) The id of the post you want the meta box to appear. If this is set than  $post_type can be omitted.

    Default: None  	
	
$single 
	(boolean) (optional) Set this to true if you don't want a repeater box and you will be able to enter just one value.

    Default: false 
	
wpml_compatibility
	(boolean) (optional) Set this to true if you want to enable wpml compatibility
	
How to add into a plugin:

1. Copy the foldder "custom-fields-creator" into the plugin dir
2. Change the class name "Wordpress_Creation_Kit" if multiple plugins use custom-fields-creator on the same site.
3. Include "custom-fields-creator.php" into the plugin file 
	
	/* include Custom Fields Creator API */
	require_once('custom-fields-creator/custom-fields-creator.php');

4. Use the API as in Exampe 1, in your plugin file or functions or whatever fits the situation.


WPML Compatibility

When wpml_compatibility is true on a meta box, besides saving the contents of the box in one serialized custom field, we create automatically a custom field for every field in every entry. We do this because WPML can't handle serialized custom fields and also we will get good control on what actions we want to perform (don't translate, copy, translate ) on each of the fields. 

After the fields are translated with Icanlcalize and we have the translated post in our system, we can go on the translated post and press the "Syncronize WCK Translation" button which will create the serialized array from the individual custom fields.