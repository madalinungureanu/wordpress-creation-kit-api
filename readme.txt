Usage Example 1

 <?php
 
	$fint = array( 
		array( 'type' => 'text', 'title' => 'Title', 'description' => 'Description for this input' ), 
		array( 'type' => 'textarea', 'title' => 'Description' ), 
		array( 'type' => 'upload', 'title' => 'Image', 'description' => 'Upload a image' ), 
		array( 'type' => 'upload', 'title' => 'Video', 'description' => 'Upload a video' ) 
	);

	$args = array(
		'metabox_id' => 'rm_slider_content',
		'metabox_title' => 'Slideshow Class',
		'post_type' => 'slideshows',
		'meta_name' => 'rmscontent',
		'meta_array' => $fint	
	);

	new Custom_Fields_Creator( $args );

 ?> 

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

    Default: None 
	
	
How to add into a plugin:

1. Copy the foldder "custom-fields-creator" into the plugin dir
2. Change the class name "Custom_Fields_Creator" if multiple plugins use custom-fields-creator on the same site.
3. Include "custom-fields-creator.php" into the plugin file 
	
	/* include Custom Fields Creator API */
	require_once('custom-fields-creator/custom-fields-creator.php');

4. Use the API as in Exampe 1, in your plugin file or functions or whatever fits the situation.