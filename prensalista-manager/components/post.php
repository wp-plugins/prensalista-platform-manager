<?php
function prensalista_custom_fields($raw_custom_fields)
{
	if(!is_array($raw_custom_fields))
		return array();

	$custom_fields = array();
	foreach($raw_custom_fields as $i => $cf)
	{
		$prensalista_field = $cf['key'];

		switch($prensalista_field){
			case 'Author0_EmailAddress' :
				$prensalista_field = 'prensalista_author_email';
				break;

			case 'Author0_ProfileUrl' :
				$prensalista_field = 'prensalista_author_profile';
				break;

			case 'Author0_Bio' :
				$prensalista_field = 'prensalista_author_bio';
				break;

			case 'Author0_UserName' :
				$prensalista_field = 'prensalista_author';
				break;

			case 'Author0_ImageUrl' :
				$prensalista_field = 'prensalista_author_avatar';
				break;
		}

		$k = sanitize_text_field($prensalista_field);
		$v = sanitize_text_field($cf['value']);
		$custom_fields[$k] = $v;
	}

	return $custom_fields;
}

function prensalista_is_protected_meta($protected_fields, $field)
{
	if(!in_array($field, $protected_fields))
		return false;

	if(function_exists('is_protected_meta'))
		return is_protected_meta($field, 'post');

	return ($field[0] == '_');
}

function prensalista_protected_custom_fields($custom_fields)
{		
	if(!isset($custom_fields['_prensalista_protected']))
		return array();

	$protected_fields = array();
	foreach(explode('|', $custom_fields['_prensalista_protected']) as $p)
	{
		list($prefix, $keywords) = explode(':', $p);

		$prefix = trim($prefix);
		if(empty($keywords))
		{	
			$protected_fields[] = "_${prefix}";
			continue;
		}

		foreach(explode(',', $keywords) as $k)
		{
			$kk = trim($k);
			$protected_fields[] = "_${prefix}_${kk}";
		}
	}	
		
	$pcf = array();
	foreach($custom_fields as $k => $v)
	{	
		if(prensalista_is_protected_meta($protected_fields, $k))
			$pcf[$k] = $v;																								  
	}
	
	return $pcf;
}

function prensalista_update_array_custom_fields($id, $custom_fields)
{
	$prefix = '_prensalista_array_';
	foreach($custom_fields as $k => $v)
	{
		if(strpos($k, $prefix) === 0)
		{
			$meta_key = str_replace($prefix, '', $k);
			delete_post_meta($id, $meta_key);

			if(empty($v))
				continue;
			
			$meta_values = @json_decode(@base64_decode($v), true);
			if(!is_array($meta_values))
				continue;

			foreach($meta_values as $meta_value)
				add_post_meta($id, $meta_key, $meta_value);
		}
	}
}

function prensalista_update_post_data($data, $custom_fields, $blog_id=0)
{
	// if this is a draft then clear the 'publish date' or set our own
	if($data['post_status'] == 'draft')
	{
		if(isset($custom_fields['prensalista_publish_date']))
		{
			$post_date = $custom_fields['prensalista_publish_date']; // UTC
			$data['post_date'] = get_date_from_gmt($post_date);
			$data['post_date_gmt'] = $post_date;
		}
		else
		{
			$data['post_date'] = '0000-00-00 00:00:00';
			$data['post_date_gmt'] = '0000-00-00 00:00:00';
		}	
		
	}


	// set our custom type
	if(PRENSALISTA_WP3 && isset($custom_fields['prensalista_custom_type']))
	{
		$custom_type = $custom_fields['prensalista_custom_type'];
		if(!empty($custom_type) && post_type_exists($custom_type))
			$data['post_type'] = $custom_type;
	}

	if(isset($custom_fields['Content_Summary'])){
		$data['post_excerpt'] = $custom_fields['Content_Summary'];
	}

	// exit early in preview mode because we don't want to create the user just yet
	if(isset($GLOBALS['PRENSALISTA_PREVIEW']))
		return $data;

	// create user if necessary
	$uid = prensalista_create_user($custom_fields, $blog_id);

	// set our post author
	if($uid !== false && $data['post_author'] != $uid)
		$data['post_author'] = $uid;

	return $data;
}

function prensalista_is_simple_field($k)
{
	// keys must be in this format: _simple_fields_fieldGroupID_1_fieldID_1_numInSet_0
	return preg_match('/^_simple_fields_fieldGroupID_[0-9]+_fieldID_[0-9]+_numInSet_[0-9]+$/', $k);
}

function prensalista_update_simple_fields($id, $custom_fields)
{
	global $wpdb;

	// remove any existing Simple Fields
	$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id = $id AND meta_key LIKE '_simple_fields_fieldGroupID_%'");

	// store Simple Fields specific protected custom fields
	foreach($custom_fields as $k => $v) 
	{	
		// keys must be in this format: _simple_fields_fieldGroupID_1_fieldID_1_numInSet_0
		if(prensalista_is_simple_field($k))
		{	
			$value = $custom_fields[$k];

			// is this an image?
			$matches = prensalista_validate_image_url($value);
			if(!empty($matches))
			{ 
				$image = prensalista_get_attachment_by_url($value);

				// if the image was found, set the ID
				if(!empty($image) && is_object($image))
					add_post_meta($id, $k, $image->ID);
			}	
			else // default is text field/area
			{	
				add_post_meta($id, $k, $value);
			}	
		}	
	}
}

function prensalista_update_post_image_fields($id, $custom_fields)
{
	foreach($custom_fields as $k => $v) 
	{	
		// skip simple fields because those are being handled differently
		if(prensalista_is_simple_field($k))
			continue;

		$value = $custom_fields[$k];

		// is this an image?
		$matches = prensalista_validate_image_url($value);
		if(empty($matches))
			continue;

		// find the image based on the URL
		$image = prensalista_get_attachment_by_url($value);
		if(empty($image) || !is_object($image))
			continue;

		delete_post_meta($id, $k);
		add_post_meta($id, $k, $image->ID);
	}
}

function prensalista_update_hash_custom_fields($id, $custom_fields)
{
	$prefix = '_prensalista_hash_';
	foreach($custom_fields as $k => $v) 
	{   
		// starts with?
		if(strpos($k, $prefix) === 0)
		{   
			$kk = str_replace($prefix, '', $k);
			delete_post_meta($id, $kk);

			if(empty($v))
				continue;

			$vv = @json_decode(@base64_decode($v), true);
			if(is_array($vv))
				add_post_meta($id, $kk, $vv);
		}
	}
}

function prensalista_update_post_meta_data($id, $custom_fields)
{
	// set any "array" custom fields
	prensalista_update_array_custom_fields($id, $custom_fields);

	// set any "hash" custom fields
	prensalista_update_hash_custom_fields($id, $custom_fields);

	// set our featured image
	if(isset($custom_fields['prensalista_featured_image']))
	{
		// look up the image by URL which is the GUID (too bad there's NO wp_ specific method to do this, oh well!)
		$thumbnail = prensalista_get_attachment_by_url($custom_fields['prensalista_featured_image']);

		// if the image was found, set it as the featured image for the current post
		if(!empty($thumbnail))
		{
			// We support 2.9 and up so let's do this the old fashioned way
			// >= 3.0.1 and up has "set_post_thumbnail" available which does this little piece of mockery for us ...
			update_post_meta($id, '_thumbnail_id', $thumbnail->ID);
		}
	}

	// store our image custom fields as IDs instead of URLs
	$settings = prensalista_settings();
	
	if(isset($settings['image_custom_fields']) && $settings['image_custom_fields'] == 'on')
		prensalista_update_post_image_fields($id, $custom_fields);

	if(isset($settings['short_url']) && $settings['short_url'] == 'on')
		prensalista_update_post_short_url($id, $custom_fields);

	if(isset($settings['tracking']) && $settings['tracking'] == 'on')
		prensalista_post_tracking($id, $custom_fields);

	// store our protected custom field required by our analytics
	if(isset($custom_fields['_prensalista_analytics_post_id']))
	{
		delete_post_meta($id, '_prensalista_analytics');

		// join them into one for performance and speed
		$prensalista_analytics = array();
		foreach($custom_fields as $k => $v)
		{
			// starts with?
			if(strpos($k, '_prensalista_analytics_') === 0)
			{
				$kk = str_replace('_prensalista_analytics_', '', $k);
				$prensalista_analytics[$kk] = $v;
			}
		}

		add_post_meta($id, '_prensalista_analytics', $prensalista_analytics);
	}

	// store other implicitly 'allowed' protected custom fields
	if(isset($custom_fields['_prensalista_protected']))
	{
		foreach(prensalista_protected_custom_fields($custom_fields) as $k => $v)
		{
			delete_post_meta($id, $k);
			if(!empty($v)) add_post_meta($id, $k, $v);
		}
	}

	// check and store protected custom fields used by Simple Fields
	if(defined('EASY_FIELDS_VERSION') || class_exists('simple_fields'))
		prensalista_update_simple_fields($id, $custom_fields);

	// assign and/or create categories
	if(isset($custom_fields['Content_CategoryName']) && $custom_fields['Content_CategoryName'] !="" ){
		$cat_id = array();
		$cat_names = explode(",",$custom_fields['Content_CategoryName']);
		foreach ($cat_names as $cat_name) {
			$category_id = get_cat_ID($cat_name);		
			if($category_id !== 0){
				$cat_id[] = $category_id;
			}
			else{
				$cat_id[] = wp_create_category( $cat_name, 0);			
			}
		}
		$cat_id = array_filter($cat_id);
		if (!empty($cat_id)) {
			wp_set_post_categories($id, $cat_id, false);
		}
	}

	// match custom fields to custom taxonomies if appropriate
	$taxonomies = array_keys(get_taxonomies(array('_builtin' => false), 'names'));
	if(!empty($taxonomies))
	{
		foreach($custom_fields as $k => $v)
		{																													  
			if(in_array($k, $taxonomies))
			{
				wp_set_object_terms($id, explode(',', $v), $k);
				delete_post_meta($id, $k);
			}
		}
	}
	
	// download the image only once
	$action = did_action( 'pre_post_update' );
    if ( 0 === $action )
    { 
		if(isset($custom_fields['Content_ImageCount']) && $custom_fields['Content_ImageCount'] > 0){
			for($ii = 1 ; $ii <= $custom_fields['Content_ImageCount'] ; $ii++ ){
				if($custom_fields['Image'.$ii.'_Url'] != ''){
					$featured_image = FALSE;

					if($custom_fields['Image'.$ii.'_Featured'] == '1')
						$featured_image = TRUE;
					
					prensalista_download_image($id, $custom_fields['Image'.$ii.'_Url'], $featured_image);
				}
			}
		}
	}
}

function prensalista_get_xmlrpc_server()
{
	if(!defined('XMLRPC_REQUEST'))
		return false;

	global $wp_xmlrpc_server;
	if(empty($wp_xmlrpc_server))
		return false;

	$methods = array('metaWeblog.newPost', 'metaWeblog.editPost', 'prensalista.newPost', 'prensalista.editPost', 'prensalista.getPreview');
	if(!in_array($wp_xmlrpc_server->message->methodName, $methods))
		return false;

	return $wp_xmlrpc_server;
}

function prensalista_on_insert_post_data($data, $postarr)
{
	$xmlrpc_server = prensalista_get_xmlrpc_server();
	if($xmlrpc_server == false)
		return $data;

	$message = $xmlrpc_server->message;
	$args = $message->params; // create a copy
	$xmlrpc_server->escape($args);

	$custom_fields = prensalista_custom_fields($args[3]['custom_fields']);
	return prensalista_update_post_data($data, $custom_fields, intval($args[0]));
}

function prensalista_on_insert_post($id)
{
	$xmlrpc_server = prensalista_get_xmlrpc_server();
	if($xmlrpc_server == false)
		return false;

	$message = $xmlrpc_server->message;
	$args = $message->params; // create a copy
	$xmlrpc_server->escape($args);

	$custom_fields = prensalista_custom_fields($args[3]['custom_fields']);		
	prensalista_update_post_meta_data($id, $custom_fields);	
}

add_filter('wp_insert_post_data', 'prensalista_on_insert_post_data', '999', 2);
add_action('wp_insert_post', 'prensalista_on_insert_post');
?>