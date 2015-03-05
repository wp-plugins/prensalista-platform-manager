<?php

function prensalista_fopen_fetch_image($url) {
	$image = file_get_contents($url, false, $context);
	return $image;
}

function prensalista_curl_fetch_image($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$image = curl_exec($ch);
	curl_close($ch);
	return $image;
}

function prensalista_fetch_image($url) {
	if ( function_exists("curl_init") ) {
		return prensalista_curl_fetch_image($url);
	} elseif ( ini_get("allow_url_fopen") ) {
		return prensalista_fopen_fetch_image($url);
	}
}

function prensalista_download_image( $id = 0 , $imageurl = null , $featured_image = FALSE ){
	require_once(ABSPATH . 'wp-admin/includes/media.php');
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	require_once(ABSPATH . 'wp-admin/includes/image.php');

	$imageurl = stripslashes($imageurl);
	$uploads = wp_upload_dir();
	$post_id = $id;
	$ext = pathinfo( basename($imageurl) , PATHINFO_EXTENSION);
	
	$newfilename = basename($imageurl);
	
	$filename = wp_unique_filename( $uploads['path'], $newfilename, $unique_filename_callback = null );
	$wp_filetype = wp_check_filetype($filename, null );
	$fullpathfilename = $uploads['path'] . "/" . $filename;
	
	try {
		if ( !substr_count($wp_filetype['type'], "image") ) {
			// throw new Exception( basename($imageurl) . ' is not a valid image. ' . $wp_filetype['type']  . '' );
			return FALSE;
		}
	
		$image_string = prensalista_fetch_image($imageurl);
		$fileSaved = file_put_contents($uploads['path'] . "/" . $filename, $image_string);
		if ( !$fileSaved ) {
			// throw new Exception("The file cannot be saved.");
			return FALSE;
		}
		
		$post_author_id = get_post_field( 'post_author', $post_id );

		$attachment = array(
			 'post_mime_type' => $wp_filetype['type'],
			 'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
			 'post_content' => '',
			 'post_author' => $post_author_id,
			 'post_status' => 'inherit',
			 'guid' => $uploads['url'] . "/" . $filename
		);
		$attach_id = wp_insert_attachment( $attachment, $fullpathfilename, $post_id );
		if ( !$attach_id ) {
			// throw new Exception("Failed to save record into database.");
			return FALSE;
		}
		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		$attach_data = wp_generate_attachment_metadata( $attach_id, $fullpathfilename );
		wp_update_attachment_metadata( $attach_id,  $attach_data );


		if($featured_image)
			add_post_meta($post_id, '_thumbnail_id', $attach_id, true);	
	} catch (Exception $e) {
		// $error = '<div id="message" class="error"><p>' . $e->getMessage() . '</p></div>';
	}
}