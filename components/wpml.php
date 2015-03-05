<?php
function prensalista_wpml_is_installed()
{
	global $sitepress;
	return (class_exists('SitePress') || isset($sitepress));
}

function prensalista_wpml_get_language_code($post_id, $args, $trid)
{
	global $sitepress;

	if(!prensalista_wpml_is_installed())
		return null;

	if(empty($post_id) || empty($args) || !is_array($args[3]))
		return null;

	if(!isset($args[3]['custom_fields']) || !is_array($args[3]['custom_fields']))
		return null;

	$post_type = get_post_type($post_id);
	if(!$sitepress->is_translated_post_type($post_type))
		return null;

	$custom_fields = $args[3]['custom_fields'];

	$language_code = null;
	foreach($custom_fields as $cf)
	{
		switch($cf['key'])
		{
			// if we got trid then this is a translation of something
			// and we should ignore anything below and we let the 
			// default behaviour
			case '_wpml_trid':
			{
				if($trid)
					return null;
			}
			break;

			case '_wpml_language':
				$language_code = $cf['value'];
				break;
		}
	}

	// if we do not have a language code or if the language code is the default
	// language then we let the default behaviour
	if($language_code == null || $language_code == $sitepress->get_default_language())
		return null;

	if(!array_key_exists($language_code, $sitepress->get_active_languages()))
		return null;

	return $language_code;
}

function prensalista_wpml_do_action($action, $str_to_int)
{
	if(!prensalista_wpml_is_installed())
		return;

	if($str_to_int)
	{
		$pattern = '/<value><string>(.*?)<\/string><\/value>/';
		$replacement = '<value><int>$1</int></value>';

		global $HTTP_RAW_POST_DATA;
		$HTTP_RAW_POST_DATA = preg_replace($pattern, $replacement, $HTTP_RAW_POST_DATA, 1);
	}

	if($action != null)
		do_action('xmlrpc_call', $action);
}

function prensalista_wpml_new_post($post_id, $args)
{
	global $sitepress;
	email_faheem('faheem hasan');
	$language_code = prensalista_wpml_get_language_code($post_id, $args, true);
	if($language_code == null)
		return;

	$post_type = 'post_' . get_post_type($post_id);
	$sitepress->set_element_language_details($post_id, $post_type, false, $language_code);
}

function prensalista_wpml_update_terms($post_id, $args)
{
	global $sitepress;

	$language_code = prensalista_wpml_get_language_code($post_id, $args, false);
	if($language_code == null)
		return;

	$post_type = get_post_type($post_id);
	$taxonomies = get_object_taxonomies($post_type);
	foreach($taxonomies as $taxonomy)
	{
		if(!$sitepress->is_translated_taxonomy($taxonomy))
			continue;

		$term_ids = array();

		$terms = wp_get_object_terms($post_id, $taxonomy);
		foreach($terms as $term)
		{
			$translated_term = $sitepress->get_term_by_name_and_lang($term->name, $taxonomy, $language_code);

			if(is_object($translated_term))
				$term_ids[] = intval($translated_term->term_id);
		}

		$terms = wp_set_object_terms($post_id, $term_ids, $taxonomy);

		// HACK, HACK, HACK - in some cases the first wp_set_object_terms doesn't
		// seem to set the terms; to fix this we just call wp_set_object_terms again
		if(is_array($terms) && !empty($terms))
		{
			$terms = wp_get_object_terms($post_id, $taxonomy);
			if(empty($terms))
				wp_set_object_terms($post_id, $term_ids, $taxonomy);
		}
	}
}

?>
