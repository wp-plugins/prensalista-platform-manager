<?php
function get_prensalista_short_url($post_id=0){
	if(get_post_meta($post_id, 'short_url', true))
		return get_post_meta($post_id, 'short_url', true);

	return FALSE;		
}

function prensalista_update_post_short_url($id, $custom_fields){
	if(isset($custom_fields['Content_Url_Shorten']) && $custom_fields['Content_Url_Shorten'] != ''){
		$short_url = $custom_fields['Content_Url_Shorten'];
		
		$exist = get_post_meta($id, 'short_url', true);
		if( ! empty( $exist ) ) {
			update_post_meta($id, 'short_url', $short_url, $exist);
		}
		else{
			add_post_meta($id, 'short_url', $short_url);
		}
	}
}

function prensalista_post_shortlink( $link , $ID ){
	if(get_post_meta($ID, 'short_url', true))
		return get_post_meta($ID, 'short_url', true);

	return $link;	
}

add_action( 'pre_get_shortlink', 'prensalista_post_shortlink', 10, 2);