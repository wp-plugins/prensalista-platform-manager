<?php
function prensalista_post_tracking($id, $custom_fields){
	if ( get_post_status ( $id ) == 'publish' ) {
		// DO NOTHING
	} else {
		return TRUE;
	}

	$url = '';
	$parmalink = get_permalink($id);

	$settings = prensalista_settings();

	if(isset($settings['mode_production']) && $settings['mode_production'] == 'on')
		$url .= PUBLISHED_PRODUCTION_URL;
	else
		$url .= PUBLISHED_STAGING_URL;

	$url .= $custom_fields['Content_Organization_ExchangeKey'].'/';
	$url .= $custom_fields['Content_WorkGroup_ExchangeKey'].'/';
	$url .= $custom_fields['Content_ExchangeKey'].'/';
	$url .= $id.'/';
	$url .= '?CanonicalURL=';
	$url .= urlencode($parmalink);

	$curl = curl_init();
	curl_setopt_array(
		$curl,
		array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
		)
	);
	curl_exec($curl);
	curl_close($curl);
}

function prensalista_post_content($id){
	global $wpdb;
	$thepost = $wpdb->get_row( @$wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID = $id" ) );
	return wpautop($thepost->post_content);
}

function prensalista_visited_post_tracking(){
	$id = get_the_ID();
	$settings = prensalista_settings();
	if(isset($settings['tracking']) && $settings['tracking'] == 'on'){
		if(!@session_id()) {
			@session_start();
			$session_id = @session_id();
		}
		else{			
			$session_id = @session_id();
		}

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		if(isset($settings['mode_production']) && $settings['mode_production'] == 'on')
			$url .= VISITED_PRODUCTION_URL;
		else
			$url .= VISITED_STAGING_URL;

		$custom_fields = array();

		$exist = get_post_meta($id, 'Content_Organization_ExchangeKey', true);
		if(empty($exist)) {
			return prensalista_post_content($id);
		}
		else{
			$custom_fields['Content_Organization_ExchangeKey'] = $exist;
		}

		$exist = get_post_meta($id, 'Content_WorkGroup_ExchangeKey', true);
		if(empty($exist)) {
			return prensalista_post_content($id);
		}
		else{
			$custom_fields['Content_WorkGroup_ExchangeKey'] = $exist;
		}

		$exist = get_post_meta($id, 'Content_ExchangeKey', true);
		if(empty($exist)) {
			return prensalista_post_content($id);
		}
		else{
			$custom_fields['Content_ExchangeKey'] = $exist;
		}

		$url .= $custom_fields['Content_Organization_ExchangeKey'].'/';
		$url .= $custom_fields['Content_WorkGroup_ExchangeKey'].'/';
		$url .= $custom_fields['Content_ExchangeKey'].'/';
		$url .= $id.'/';
		$url .= prensalista_session_current().'/';
		$url .= $ip;

		$curl = curl_init();
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url,
			)
		);
		curl_exec($curl);
		curl_close($curl);
	}
	
	return prensalista_post_content($id);
}

add_action('the_content', 'prensalista_visited_post_tracking' , 999);

function prensalista_post_published_tracking( $id, $post ) {
    $settings = prensalista_settings();
	if(isset($settings['tracking']) && $settings['tracking'] == 'on'){
		if(isset($settings['mode_production']) && $settings['mode_production'] == 'on')
			$url .= PUBLISHED_PRODUCTION_URL;
		else
			$url .= PUBLISHED_STAGING_URL;

		$flag = TRUE;

		$custom_fields = array();

		$exist = get_post_meta($id, 'Content_Organization_ExchangeKey', true);
		if(empty($exist)) {
			$flag = FALSE; 
		}
		else{
			$custom_fields['Content_Organization_ExchangeKey'] = $exist;
		}

		$exist = get_post_meta($id, 'Content_WorkGroup_ExchangeKey', true);
		if(empty($exist)) {
			$flag = FALSE; 
		}
		else{
			$custom_fields['Content_WorkGroup_ExchangeKey'] = $exist;
		}

		$exist = get_post_meta($id, 'Content_ExchangeKey', true);
		if(empty($exist)) {
			$flag = FALSE; 
		}
		else{
			$custom_fields['Content_ExchangeKey'] = $exist;
		}

		$parmalink = get_permalink($id);

		$url .= $custom_fields['Content_Organization_ExchangeKey'].'/';
		$url .= $custom_fields['Content_WorkGroup_ExchangeKey'].'/';
		$url .= $custom_fields['Content_ExchangeKey'].'/';
		$url .= $id.'/';
		$url .= '?CanonicalURL=';
		$url .= urlencode($parmalink);
		
		if($flag){
			$curl = curl_init();
			curl_setopt_array(
				$curl,
				array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => $url,
				)
			);
			curl_exec($curl);
			curl_close($curl);
		}
	}  
}

add_action( 'publish_post', 'prensalista_post_published_tracking', 10, 2 );