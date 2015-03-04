<?php
function prensalista_xmlrpc_version()
{
	return PRENSALISTA_VERSION;
}

function prensalista_xmlrpc_die($message)
{
	$error = new IXR_Error(500, print_r($message, true));
	die($error->getXml());
}

function prensalista_xmlrpc_newPost($args)
{
	global $wp_xmlrpc_server;
	$post_id = $wp_xmlrpc_server->mw_newPost($args);

	if(is_string($post_id))
	{
		prensalista_wpml_new_post($post_id, $args);
		prensalista_wpml_update_terms($post_id, $args);
	}

	return $post_id;
}

function prensalista_xmlrpc_editPost($args)
{
	global $wp_xmlrpc_server, $current_site;
	
	if(PRENSALISTA_WP3DOT4 == false)
	{
		prensalista_wpml_do_action(null, true);
		
		$result = $wp_xmlrpc_server->mw_editPost($args);

		if($result === true)
			prensalista_wpml_update_terms($args[0], $args);

		return $result;
	}

	$_args = $args;
	$wp_xmlrpc_server->escape($_args);

	$blog_id	= $current_site->id;
	$post_id	= intval($_args[0]);
	$username	= $_args[1];
	$password	= $_args[2];
	$data		= $_args[3];
	$publish	= $_args[4];

	if(!$user = $wp_xmlrpc_server->login($username, $password))
		return $wp_xmlrpc_server->error;
	
	if(!current_user_can('edit_post', $post_id))
		return new IXR_Error(401, __('Sorry, you cannot edit this post.'));

	$post = get_post($post_id);
	if(!is_object($post) || !isset($post->ID))
		return new IXR_Error(404, __('Invalid post ID.'));

	if(in_array($post->post_type, array('post', 'page')))
	{
		prensalista_wpml_do_action(null, true);

		$result = $wp_xmlrpc_server->mw_editPost($args);

		if($result === true)
			prensalista_wpml_update_terms($post_id, $args);

		return $result;
	}

	// to avoid double escaping the content structure in wp_editPost
	// point data to the original structure
	$data = $args[3];

	$content_struct = array();
	$content_struct['post_type'] = $post->post_type; 
	$content_struct['post_status'] = $publish ? 'publish' : 'draft';

	if(isset($data['title']))
		$content_struct['post_title'] = $data['title'];

	if(isset($data['description']))
		$content_struct['post_content'] = $data['description'];

	if(isset($data['custom_fields']))
		$content_struct['custom_fields'] = $data['custom_fields'];

	if(isset($data['mt_excerpt']))
		$content_struct['post_excerpt'] = $data['mt_excerpt'];

	if(isset($data['mt_keywords']) && !empty($data['mt_keywords']))
		$content_struct['terms_names']['post_tag'] = explode(',', $data['mt_keywords']);

	if(isset($data['categories']) && !empty($data['categories']) && is_array($data['categories']))
		$content_struct['terms_names']['category'] = $data['categories'];

	prensalista_wpml_do_action('metaWeblog.editPost', true);
	$result = $wp_xmlrpc_server->wp_editPost(array($blog_id, $args[1], $args[2], $args[0], $content_struct));

	if($result === true)
		prensalista_wpml_update_terms($post_id, $args);

	return $result;
}

function prensalista_xmlrpc_getPost($args)
{
	global $wp_xmlrpc_server;
	prensalista_wpml_do_action(null, true);
	return $wp_xmlrpc_server->mw_getPost($args);
}

function prensalista_xmlrpc_newMediaObject($args)
{
	global $wpdb, $wp_xmlrpc_server;

	$_args = $args;

	$blog_id	= intval($_args[0]);
	$username	= $wpdb->escape($_args[1]);
	$password	= $wpdb->escape($_args[2]);
	$data		= $_args[3];

	if(!$user = $wp_xmlrpc_server->login($username, $password))
		return $wp_xmlrpc_server->error;

	if(!current_user_can('upload_files'))
		return new IXR_Error(401, __('You are not allowed to upload files to this site.'));

	if(is_array($data) && isset($data['overwrite']) && $data['overwrite'] && isset($data['name']) && !empty($data['name']))
	{   
		$attachment = $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_title = %s LIMIT 1", $data['name']));

		if(!empty($attachment))
			wp_delete_attachment($attachment->ID);

		// do not pass overwrite to mw_newMediaObject down below because that has
		// a slightly different and unwanted behaviour in this case
		unset($args[3]['overwrite']);
	}

	$image = $wp_xmlrpc_server->mw_newMediaObject($args);
	if(!is_array($image) || empty($image['url']))
		return $image;

	$attachment = prensalista_get_attachment_by_url($image['url']);

	if(empty($attachment))
		return $image;

	$update_attachment = false;

	if(isset($data['description']))
	{
		$attachment->post_content = sanitize_text_field($data['description']);
		$update_attachment = true;
	}

	if(isset($data['title']))
	{
		$attachment->post_title	= sanitize_text_field($data['title']);
		$update_attachment = true;
	}

	if(isset($data['caption']))
	{
		$attachment->post_excerpt = sanitize_text_field($data['caption']);
		$update_attachment = true;
	}

	if($update_attachment) 
		wp_update_post($attachment);

	if(isset($data['alt'])) 
		add_post_meta($attachment->ID, '_wp_attachment_image_alt', sanitize_text_field($data['alt']));

	if(!isset($image['id']))
		$image['id'] = $attachment->ID;

	return $image;
}

function prensalista_xmlrpc_getPermalink($args)
{
	global $wp_xmlrpc_server;
	$wp_xmlrpc_server->escape($args);

	$post_id	= intval($args[0]);
	$username	= $args[1];
	$password	= $args[2];

	if(!$user = $wp_xmlrpc_server->login($username, $password))
		return $wp_xmlrpc_server->error;
	
	if(!current_user_can('edit_post', $post_id))
		return new IXR_Error(401, __('Sorry, you cannot edit this post.'));

	$post = get_post($post_id);
	if(!is_object($post) || !isset($post->ID))
		return new IXR_Error(401, __('Sorry, you cannot edit this post.'));

	// play nice with the utterly broken No Category Parents plugin :) (sigh)
	if(function_exists('myfilter_category') || function_exists('my_insert_rewrite_rules'))
	{
		remove_filter('pre_post_link'       ,'filter_category');
		remove_filter('user_trailingslashit','myfilter_category');
		remove_filter('category_link'       ,'filter_category_link');
		remove_filter('rewrite_rules_array' ,'my_insert_rewrite_rules');
		remove_filter('query_vars'          ,'my_insert_query_vars');
	}

	list($permalink, $post_name) = get_sample_permalink($post->ID);
	$permalink = str_replace(array('%postname%', '%pagename%'), $post_name, $permalink);

	if(strpos($permalink, "%") === false) # make sure it doesn't contain %day%, etc.
			return $permalink;

	return get_permalink($post);
}

function prensalista_xmlrpc_wck_is_installed()
{
    return defined('WCK_PLUGIN_DIR');
}

function prensalista_xmlrpc($methods)
{
	$methods['prensalista.version']			= 'prensalista_xmlrpc_version';
	$methods['prensalista.newPost']			= 'prensalista_xmlrpc_newPost';
	$methods['prensalista.editPost']		= 'prensalista_xmlrpc_editPost';
	$methods['prensalista.getPost']			= 'prensalista_xmlrpc_getPost';
	$methods['prensalista.newMediaObject']	= 'prensalista_xmlrpc_newMediaObject';
	$methods['prensalista.getPermalink']	= 'prensalista_xmlrpc_getPermalink';
	$methods['prensalista.wckIsInstalled'] 	= 'prensalista_xmlrpc_wck_is_installed';
	return $methods;
}
add_filter('xmlrpc_methods', 'prensalista_xmlrpc');
?>
