<?php  
/*  
Plugin Name: Auto Save Remote Image 
Plugin URI: https://github.com/cristoslc/wp-auto-save-remote-image  
Description: This plugin automatically downloads the first remote image from a post and sets it as the featured image.
Version: 1.4
Author: Cristos L-C 
Disclaimer: No warranty or guarantee of any kind!  Use this in your own risk.  
*/
add_action('publish_post', 'fetch_images');

function fetch_images( $post_ID )  
{	
	//Check to make sure function is not executed more than once on save
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
	return;

	if ( !current_user_can('edit_post', $post_ID) ) 
	return;

	//Check if there is already a featured image; if there is, then quit.
	if ( '' != get_the_post_thumbnail() )
	return;

	remove_action('publish_post', 'fetch_images');	
		
	$post = get_post($post_ID);   

	$first_image = '';
	
	if(preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches)){
		$first_image = $matches [1] [0];
	}

	if (strpos($first_image,$_SERVER['HTTP_HOST'])===false)
	{
			
		//Fetch and Store the Image	
		$get = wp_remote_get( $first_image );
		$type = wp_remote_retrieve_header( $get, 'content-type' );
		$mirror = wp_upload_bits(rawurldecode(basename( $first_image )), '', wp_remote_retrieve_body( $get ) );
	
		//Attachment options
		$attachment = array(
		'post_title'=> basename( $first_image ),
		'post_mime_type' => $type
		);
		
		// Add the image to your media library and set as featured image
		$attach_id = wp_insert_attachment( $attachment, $mirror['file'], $post_ID );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $first_image );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		set_post_thumbnail( $post_ID, $attach_id );
	
		$updated = str_replace($first_image, $mirror['url'], $post->post_content);
	    
	    //Replace the image in the post
	    wp_update_post(array('ID' => $post_ID, 'post_content' => $updated));
	
	    // re-hook this function
	    add_action('publish_post', 'fetch_images');		
	}
}
?>
