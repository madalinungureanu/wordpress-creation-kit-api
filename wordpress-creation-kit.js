/* Add width to elements at startup */
jQuery(function(){
	jQuery('.mb-table-container tbody td').css('width', function(){ return jQuery(this).width() });
});

/* add reccord to the meta */
function addMeta(value, id, nonce){
	jQuery('#'+value).parent().css({'opacity':'0.4', 'position':'relative'}).append('<div id="mb-ajax-loading"></div>');
	/*object to hold the values */
	var values = {};
	
	jQuery('#'+value+' .mb-field').each(function(){
	
		var key = jQuery(this).attr('name');
		
		if(jQuery(this).attr('type') == 'checkbox' || jQuery(this).attr('type') == 'radio' ) {
			
			if( typeof values[key.toString()] === "undefined" )
				values[key.toString()] = '';
			
			if(jQuery(this).is(':checked')){
				if( values[key.toString()] == '' )
					values[key.toString()] += jQuery(this).val().toString();
				else
					values[key.toString()] += ', ' + jQuery(this).val().toString();
			}			
		}
		
		else		
			values[key.toString()] = jQuery(this).val().toString();
	});

	console.log(values);
	
	jQuery.post( ajaxurl ,  { action:"wck_add_meta"+value, meta:value, id:id, values:values, _ajax_nonce:nonce}, function(response) {	
			console.log(response);
			/* refresh the list */
			jQuery.post( ajaxurl ,  { action:"wck_refresh_list"+value, meta:value, id:id}, function(response) {					
				
				jQuery('#container_'+value).replaceWith(response);
				
				jQuery('.mb-table-container tbody td').css('width', function(){ return jQuery(this).width() });
				
				if( !jQuery( '#'+value ).hasClass('single') )
					mb_sortable_elements();
					
				jQuery('#'+value+' .mb-field').each(function(){
					if(jQuery(this).attr('type') == 'checkbox' || jQuery(this).attr('type') == 'radio' ) 
						jQuery(this).removeAttr( 'checked' );	
					else
						jQuery(this).val('');					
				});				
				jQuery('#'+value).parent().css('opacity','1');	
				
				/* Remove form if is single */
				if( jQuery( '#'+value ).hasClass('single') )
					jQuery( '#'+value ).remove();
				
				jQuery('#mb-ajax-loading').remove();
			});
		});	
}

/* remove reccord from the meta */
function removeMeta(value, id, element_id, nonce){
	
	var response = confirm( "Delete this item ?" );
	
	if( response == true ){
	
		jQuery('#'+value).parent().css({'opacity':'0.4', 'position':'relative'}).append('<div id="mb-ajax-loading"></div>');
		jQuery.post( ajaxurl ,  { action:"wck_remove_meta"+value, meta:value, id:id, element_id:element_id, _ajax_nonce:nonce}, function(response) {
		
				/* If single add the form */
				if( jQuery( '#container_'+value ).hasClass('single') ){
					jQuery.post( ajaxurl ,  { action:"wck_add_form"+value, meta:value, id:id }, function(response) {			
						jQuery( '#container_'+value ).before( response );
						jQuery( '#'+value ).addClass('single');	
					});
				}
				
				/* refresh the list */
				jQuery.post( ajaxurl ,  { action:"wck_refresh_list"+value, meta:value, id:id}, function(response) {	
					jQuery('#container_'+value).replaceWith(response);
					
					jQuery('.mb-table-container tbody td').css('width', function(){ return jQuery(this).width() });
					
					mb_sortable_elements();
					jQuery('#'+value).parent().css('opacity','1');
					jQuery('#mb-ajax-loading').remove();
				});
				
			});	
	}
}

/* swap two reccords */
/*function swapMetaMb(value, id, element_id, swap_with){
	jQuery('#'+value).parent().css({'opacity':'0.4', 'position':'relative'}).append('<div id="mb-ajax-loading"></div>');
	jQuery.post( ajaxurl ,  { action:"swap_meta_mb", meta:value, id:id, element_id:element_id, swap_with:swap_with}, function(response) {	
			
			jQuery.post( ajaxurl ,  { action:"refresh_list", meta:value, id:id}, function(response) {	
				jQuery('#container_'+value).replaceWith(response);				jQuery('#'+value).parent().css('opacity','1');				jQuery('#mb-ajax-loading').remove();				
			});
			
		});	
}
*/

/* reorder elements through drag and drop */
function mb_sortable_elements() {		
		jQuery( ".mb-table-container tbody" ).not( jQuery( ".mb-table-container.single tbody, .mb-table-container.not-sortable tbody" ) ).sortable({
			update: function(event, ui){
				
				var value = jQuery(this).parent().prev().attr('id');
				var id = jQuery(this).parent().attr('post');
				
				var result = jQuery(this).sortable('toArray');
				
				var values = {};
				for(var i in result)
				{
					values[i] = result[i].replace('element_','');
				}
				
				jQuery('#'+value).parent().css({'opacity':'0.4', 'position':'relative'}).append('<div id="mb-ajax-loading"></div>');
				
				jQuery.post( ajaxurl ,  { action:"wck_reorder_meta"+value, meta:value, id:id, values:values}, function(response) {			
					jQuery.post( ajaxurl ,  { action:"wck_refresh_list"+value, meta:value, id:id}, function(response) {
							jQuery('#container_'+value).replaceWith(response);
							
							jQuery('.mb-table-container tbody td').css('width', function(){ return jQuery(this).width() });
							
							mb_sortable_elements();
							jQuery('#'+value).parent().css('opacity','1');
							jQuery('#mb-ajax-loading').remove();				
					});
					
				});
			}
		});
		jQuery( "#sortable" ).disableSelection();	
}
jQuery(mb_sortable_elements);



/* show the update form */
function showUpdateFormMeta(value, id, element_id, nonce){
	if( jQuery( '#update_container_' + value + '_' + element_id ).length == 0 ){
		jQuery('#'+value).parent().css({'opacity':'0.4', 'position':'relative'}).append('<div id="mb-ajax-loading"></div>');
		
		jQuery( '#container_' + value + " tbody" ).sortable("disable");
		
		jQuery.post( ajaxurl ,  { action:"wck_show_update"+value, meta:value, id:id, element_id:element_id, _ajax_nonce:nonce}, function(response) {	
				//jQuery('#container_'+value+' #element_'+element_id).append(response);
				jQuery(response).insertAfter('#container_'+value+' #element_'+element_id);
				jQuery('#'+value).parent().css('opacity','1');
				jQuery('#mb-ajax-loading').remove();
		});
	}
}

/* remove the update form */
function removeUpdateForm( id ){
	jQuery( '#'+id ).remove();
}

/* update reccord */
function updateMeta(value, id, element_id, nonce){
	jQuery('#'+value).parent().css({'opacity':'0.4', 'position':'relative'}).append('<div id="mb-ajax-loading"></div>');
	var values = {};	
	jQuery('#update_container_'+value+'_'+element_id+' .mb-field').each(function(){
		var key = jQuery(this).attr('name');		
		
		if(jQuery(this).attr('type') == 'checkbox' || jQuery(this).attr('type') == 'radio' ) {
			
			if( typeof values[key.toString()] === "undefined" )
				values[key.toString()] = '';
			
			if(jQuery(this).is(':checked')){
				if( values[key.toString()] == '' )
					values[key.toString()] += jQuery(this).val().toString();
				else
					values[key.toString()] += ', ' + jQuery(this).val().toString();
			}			
		}
		
		else		
			values[key.toString()] = jQuery(this).val().toString();
		
	});	
	
	jQuery.post( ajaxurl ,  { action:"wck_update_meta"+value, meta:value, id:id, element_id:element_id, values:values, _ajax_nonce:nonce}, function(response) {			
			jQuery('#update_container_'+value+'_'+element_id).remove();
			/* refresh the list */
			jQuery.post( ajaxurl ,  { action:"wck_refresh_list"+value, meta:value, id:id}, function(response) {	
				jQuery('#container_'+value).replaceWith(response);
				
				jQuery('.mb-table-container tbody td').css('width', function(){ return jQuery(this).width() });
				
				mb_sortable_elements();
				jQuery('#'+value).parent().css('opacity','1');
				jQuery('#mb-ajax-loading').remove();				
			});
			
		});	
}

/* function syncs the translation */
function wckSyncTranslation(id){
	jQuery.post( ajaxurl ,  { action:"wck_sync_translation", id:id}, function(response) {			
			if( response == 'syncsuccess' )
				window.location.reload();			
		});	
}