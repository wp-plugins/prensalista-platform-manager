<?php
function prensalista_userphoto_the_author_photo(){
	global $authordata, $curauthor;
		if(!empty($authordata) && $authordata->ID)
			if(get_usermeta($authordata->ID, 'prensalista_user_photo_url'))
				return get_usermeta($authordata->ID, 'prensalista_user_photo_url');

	return FALSE;		
}

function prensalista_user_fopen_fetch_image($url) {
	$image = file_get_contents($url, false, $context);
	return $image;
}

function prensalista_user_curl_fetch_image($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$image = curl_exec($ch);
	curl_close($ch);
	return $image;
}

function prensalista_user_fetch_image($url) {
	if ( function_exists("curl_init") ) {
		return prensalista_user_curl_fetch_image($url);
	} elseif ( ini_get("allow_url_fopen") ) {
		return prensalista_user_fopen_fetch_image($url);
	}
}

function prensalista_update_user_photo_by_url($user_id = 0, $custom_fields = 0){
	if(isset($custom_fields['Author0_IsValidImage']) && $custom_fields['Author0_IsValidImage'] == '1'){
		if(isset($custom_fields['prensalista_author_avatar']) && $custom_fields['prensalista_author_avatar'] != ''){
			$imageurl = $custom_fields['prensalista_author_avatar'];
			
			require_once(ABSPATH . 'wp-admin/includes/media.php');
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			require_once(ABSPATH . 'wp-admin/includes/image.php');

			$imageurl = stripslashes($imageurl);
			$uploads = wp_upload_dir();
			$ext = pathinfo( basename($imageurl) , PATHINFO_EXTENSION);

			$newfilename = basename($imageurl);
	
			$filename = wp_unique_filename( $uploads['path'], $newfilename, $unique_filename_callback = null );
			$wp_filetype = wp_check_filetype($filename, null );
			
			$fullpathfilename = $uploads['path'] . "/" . $filename;

			try {
				if ( !substr_count($wp_filetype['type'], "image") ) {
					return FALSE;
				}
			
				$image_string = prensalista_user_fetch_image($imageurl);
				$fileSaved = file_put_contents($uploads['path'] . "/" . $filename, $image_string);
				if ( !$fileSaved ) {
					return FALSE;
				}
				
				$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
					'post_content' => '',
					'post_author' => $user_id,
					'post_status' => 'inherit',
					'guid' => $uploads['url'] . "/" . $filename
				);
				$attach_id = wp_insert_attachment( $attachment, $fullpathfilename );

				if ( !$attach_id ) {
					// throw new Exception("Failed to save record into database.");
					return FALSE;
				}

				require_once(ABSPATH . "wp-admin" . '/includes/image.php');
				$attach_data = wp_generate_attachment_metadata( $attach_id, $fullpathfilename );
				wp_update_attachment_metadata( $attach_id,  $attach_data );
				
				$value = esc_sql($uploads['url'] . "/" . $filename);				
				update_user_meta($user_id, 'prensalista_user_photo_url', $value);

			} catch (Exception $e) {
				
			}
		}
	}
}
// Apply filter
add_filter( 'get_avatar' , 'prensalista_custom_avatar' , 1 , 5 );

function prensalista_custom_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
    $user = false;

    if ( is_numeric( $id_or_email ) ) {

        $id = (int) $id_or_email;
        $user = get_user_by( 'id' , $id );

    } elseif ( is_object( $id_or_email ) ) {

        if ( ! empty( $id_or_email->user_id ) ) {
            $id = (int) $id_or_email->user_id;
            $user = get_user_by( 'id' , $id );
        }

    } else {
        $user = get_user_by( 'email', $id_or_email );	
    }

    if ( $user && is_object( $user ) ) {
		if(get_usermeta($user->data->ID, 'prensalista_user_photo_url'))
			$pimg_url =  get_usermeta($user->data->ID, 'prensalista_user_photo_url');
        if ( $pimg_url ) {
        	//email_friendu( $pimg_url, $user->data->ID);
            $avatar = $pimg_url;
            $avatar = "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        }

    }
    return $avatar;
}
?>