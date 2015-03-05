<?php
function prensalista_settings_url()
{
	return admin_url('admin.php?page='.PRENSALISTA_DEFAULT_SETTINGS_KEY);
}

function prensalista_settings()
{
	$defaults = array(
		'attr_create_user'					=> 'on', 
		'attr_update_user_bio'				=> 'on', 
		'attr_update_user_meta'				=> 'on',
		'attr_update_user_photo'			=> 'on',
		'attr_update_existing_user_bio'		=> 'on', 
		'attr_update_existing_user_meta'	=> 'on',
		'attr_update_existing_user_photo'	=> 'on',
		'image_custom_fields'				=> 'on',
		'mode_production'					=> 'on',
		'short_url'							=> 'on',
		'tracking'							=> 'on',
		'preview'							=> 'on'
	);
	return wp_parse_args((array) get_option(PRENSALISTA_DEFAULT_SETTINGS_KEY), $defaults);
}

function prensalista_settings_update($settings)
{
	if(!is_array($settings)) $settings = array();
	update_option(PRENSALISTA_DEFAULT_SETTINGS_KEY,$settings);
}

function prensalista_admin_init()
{
	wp_register_style(PRENSALISTA_DEFAULT_SETTINGS_KEY, plugins_url('options.css', __FILE__));
	wp_enqueue_style(PRENSALISTA_DEFAULT_SETTINGS_KEY);
}

function prensalista_settings_menu() 
{
	if(function_exists("add_menu_page"))
	    //add_submenu_page('options-general.php','Prensalista', 'Prensalista', 'manage_options', 'prensalista_settings', 'prensalista_settings_options');
		add_menu_page( 'Prensalista posts manager', 'Prensalista', 'manage_options', 'prensalista_settings', 'prensalista_settings_options', '',  76.34  );
}

function prensalista_page_settings_link($links, $file) 
{
	if($file == PRENSALISTA_BASENAME) 
	{
		$link = '<a href="'.prensalista_settings_url().'">Settings</a>';
		array_unshift($links, $link); 
	}

	return $links;
}

function prensalista_settings_checkbox($instance, $name, $label)
{
	$state = ($instance[$name] == 'on') ?' checked="checked"' : '';
	return '<blockquote><input type="checkbox" name="' . PRENSALISTA_DEFAULT_SETTINGS_KEY . '[' . $name  . ']" ' . $state . '/> ' . $label . '</blockquote>';
}

function prensalista_settings_form($instance)
{
	$attr_options = '<h3>Mode</h3>';
	$attr_options .= '<blockquote>';
	$attr_options .= prensalista_settings_checkbox($instance, 'mode_production', 'Is this plugin on production mode');
	$attr_options .= '</blockquote>';

	$attr_options .= '<h3>Attribution Options</h3>';
	$attr_options .= prensalista_settings_checkbox($instance, 'attr_create_user', 'Create a new WordPress user for each promoted user unless their account (based on email) already exists.');
	$attr_options .= '<blockquote>';
	$attr_options .= prensalista_settings_checkbox($instance, 'attr_update_user_bio', 'Update new Wordpress user\'s bio based on promoted user.');
	$attr_options .= prensalista_settings_checkbox($instance, 'attr_update_user_meta', 'Update new Wordpress user\'s metadata based on promoted user.');
	$attr_options .= prensalista_settings_checkbox($instance, 'attr_update_user_photo', 'Update new Wordpress user\'s <b>*</b>profile photo (avatar) based on promoted user.');
	$attr_options .= '</blockquote>';
	$attr_options .= prensalista_settings_checkbox($instance, 'attr_update_existing_user_bio', 'Update existing Wordpress user\'s bio based on promoted user.');
	$attr_options .= prensalista_settings_checkbox($instance, 'attr_update_existing_user_meta', 'Update existing Wordpress user\'s metadata based on promoted user.');
	$attr_options .= prensalista_settings_checkbox($instance, 'attr_update_existing_user_photo', 'Update existing Wordpress user\'s <b>*</b>profile photo (avatar) based on promoted user.');
	
	$attr_options .= '<h3>Custom Field Options</h3>';
	$attr_options .= '<blockquote>';
	$attr_options .= prensalista_settings_checkbox($instance, 'image_custom_fields', 'Image Custom fields');
	$attr_options .= '</blockquote>';

	$attr_options .= '<h3>Preview Options</h3>';
	$attr_options .= '<blockquote>';
	$attr_options .= prensalista_settings_checkbox($instance, 'preview', 'Turn Preview On/Off');
	$attr_options .= '</blockquote>';


	$attr_options .= '<h3>Short URL</h3>';
	$attr_options .= '<blockquote>';
	$attr_options .= prensalista_settings_checkbox($instance, 'short_url', 'Use Prensalista short URL Service');
	$attr_options .= '</blockquote>';

	$attr_options .= '<h3>Tracking</h3>';
	$attr_options .= '<blockquote>';
	$attr_options .= prensalista_settings_checkbox($instance, 'tracking', 'Allow the plugin to provide analytics related to this site to your organization');
	$attr_options .= '</blockquote>';

	echo '
		<form action="" method="post" autocomplete="off" id="options_form">
		'.$attr_options.'
		<blockquote>
			<p class="submit">
				<input type="submit" value="Update Settings" id="submit" class="button-primary" name="submit"/>
			</p>
		</form>
	</div>';
}

function prensalista_message($msg, $style="updated")
{
	echo "<div class=\"${style} fade\" id=\"message\"><p><strong>{$msg}</strong></p></div>";
}

function prensalista_settings_form_update($new_instance, $old_instance)
{
	if(!is_array($new_instance)) $new_instance = array();

	$instance = array(
		'attr_create_user'					=> '', 
		'attr_update_user_meta'				=> '', 
		'attr_update_user_photo'			=> '',
		'attr_update_user_bio'				=> '',
		'attr_update_existing_user_meta'	=> '', 
		'attr_update_existing_user_photo'	=> '',
		'attr_update_existing_user_bio'		=> '',
		'image_custom_fields'				=> '',
		'mode_production'					=> '',
		'short_url'							=> '',
		'tracking'							=> '',
		'preview'							=> ''
	);

	foreach($instance as $k => $v)
		if($new_instance[$k] == 'on')
			$instance[$k] = 'on';

	prensalista_settings_update($instance);
	prensalista_message("Settings successfully updated.");
	return $instance;
}

function prensalista_settings_send_email($name, $email, $subject, $body)
{
	$headers = "
		From: \"${name}\" <${email}>\n, 
		Reply-To: \"${name}\" <${email}>\n,
		Content-Type: text/html; charset=UTF-8\n
	";

	@set_time_limit(45); // 45 seconds should suffice ...
	@wp_mail(PRENSALISTA_EMAIL, $subject, $body, $headers);
}

function prensalista_settings_mailer_add_attachments(&$mailer)
{
	// attach various 'system' information, like active theme and plugins
	$mailer->addStringAttachment(print_r(prensalista_system_info(), true), 'sysinfo.txt');
	// attach phpinfo()
	$mailer->addStringAttachment(prensalista_capture_output('phpinfo'), 'phpinfo.html');
}

function prensalista_settings_support_form_update()
{
	$version = PRENSALISTA_VERSION;

	$fields = array();
	foreach(array('url', 'email', 'name', 'subject', 'body') as $field)
	{
		if(empty($_POST[$field]))
		{
			prensalista_message("You must fill in all the required information.", "error");
			return;
		}

		$fields[$field] = $_POST[$field];
	}

	extract($fields, EXTR_SKIP);

	$body = "<p>$body</p>";
	$body .= "<table border='1' cellpadding='5'>";
	$body .= "<tr><td><strong>url</strong></td><td><a href='${url}' target='_blank'>${url}</a></td></tr>";

	if(!empty($_POST['wp_username']) && !empty($_POST['wp_password']))
	{
		$body .= "<tr><td><strong>username</strong></td><td>${_POST['wp_username']}</td></tr>";
		$body .= "<tr><td><strong>password</strong></td><td>${_POST['wp_password']}</td></tr>";
	}
	else
	{
		$body .= "<tr><td><strong>username</strong></td><td>n/a</td></tr>";
		$body .= "<tr><td><strong>password</strong></td><td>n/a</td></tr>";
	}
	
	$body .= "</table>";

	add_action('phpmailer_init', 'prensalista_settings_mailer_add_attachments');
	
	// prensalista_settings_send_email(addslashes($name), $email, "[Prensalista Plugin ${version} Support] - ${subject}", $body);
	prensalista_settings_send_email(addslashes($name), $email, "WordPress Plugin Support - ${subject}", $body);
	
	remove_action('phpmailer_init', 'prensalista_settings_mailer_add_attachments');

	prensalista_message("Successfully sent. We'll get back to you shortly.");
}

function prensalista_settings_tab($tab=null)
{
	if($tab == null) return (isset($_GET['tab'])?$_GET['tab']:'tab1');
	return ($_GET['tab'] == $tab);
}

function prensalista_settings_support_form_input($label, $options)
{
	$name = $options['name'];
	$value = isset($options['value']) ? htmlspecialchars($options['value']) : '';

	if(isset($options['textarea']))
		return "<dt><strong>${label}:</strong></dt><dd><textarea cols=\"54\" rows=\"8\" name=\"${name}\">${value}</textarea></dd>";

	if(isset($options['password']))
		return "<dt><strong>${label}:</strong></dt><dd><input type=\"password\" name=\"${name}\" value=\"${value}\"/></dd>";

	return "<dt><strong>${label}:</strong></dt><dd><input type=\"text\" name=\"${name}\" value=\"${value}\"/></dd>";
}

function prensalista_settings_support_form_list($inputs)
{
	$list = '';
	
	foreach($inputs as $label => $options)
		$list .= prensalista_settings_support_form_input($label, $options);

	return "<dl>{$list}</dl>";
}

function prensalista_capture_output($fn)
{
	$result = '';

	if(function_exists($fn))
	{
		ob_start();
		call_user_func($fn);
		$result = ob_get_contents();
		ob_end_clean();
	}

	return $result;
}

function prensalista_gmt_filetime($filename)
{
	$format = 'Y-m-d H:i:s';
	return get_gmt_from_date(mysql2date($format, date($format, @filemtime($filename))));
}

function prensalista_system_info()
{
	global $wp_version, $wp_db_version, $wpdb;

   	$mysql_version = $wpdb->get_var('SELECT VERSION()');
	$settings = prensalista_settings();

	$plugins = array();

	foreach(get_plugins() as $file => $plugin)
	{
		if(is_plugin_active($file))
		{
			$plugin['LastModified'] = prensalista_gmt_filetime(WP_PLUGIN_DIR . '/' . $file);
			$plugins[$file] = $plugin;
		}
	}

	$theme = get_theme(get_current_theme());
		
	$files = array();
	foreach($theme['Template Files'] as $file)
		$files[$file] = prensalista_gmt_filetime($file);

	$theme['Template Files'] = $files;

	$files = array();
	foreach($theme['Stylesheet Files'] as $file)
		$files[$file] = prensalista_gmt_filetime($file);

	$theme['Stylesheet Files'] = $files;

	return array
	(
		'prensalista_plugin' => array
		(
			'version'		=> PRENSALISTA_VERSION, 
			'create users'	=> $settings['attr_create_user']
		),
		'wordpress' => array
		(
			'version'		=> $wp_version,
			'db version'	=> $wp_db_version,
			'url'			=> site_url(),
			'email'			=> get_bloginfo('admin_email'),
			'multisite'		=> PRENSALISTA_MU ? 'yes' : 'no',
			'plugins'		=> $plugins,
			'theme'			=> $theme
		),
		'mysql' => array
		(
			'version' => $mysql_version
		)
	);
}

function prensalista_settings_support_form($settings)
{
	$required_fields = array
	(
		'Site URL'		=> array('name' => 'url'	, 'value'		=> site_url()), 
		'Name'			=> array('name' => 'name'),
		'E-mail'		=> array('name' => 'email'	, 'value'		=> get_bloginfo('admin_email')),
		'Subject'		=> array('name' => 'subject'),
		'Issue / Notes'	=> array('name' => 'body', 'textarea'	=> true)
	);
	
	$additional_fields = array
	(
		'WP admin username'	=> array('name' => 'wp_username'),
		'WP admin password' => array('name' => 'wp_password', 'password' => true)
	);

	$required = prensalista_settings_support_form_list($required_fields);
	$additional = prensalista_settings_support_form_list($additional_fields);

	echo "<form action=\"\" method=\"post\" autocomplete=\"off\" id=\"options_form\">
		  <div class=\"prensalista-support\">
			<h3>Required Information</h3>
			<blockquote>${required}</blockquote>
			<h3>Additional Information</h3>
			<blockquote>${additional}</blockquote>
			<h3>Note(s)</h3>
			<blockquote>
				<ol>
					<li>Your submitted data will not be stored and will be used for debugging purposes <strong>ONLY</strong>.</li>
					<li>Please do not provide your primary WP admin's credentials. Instead, please create a new temporary account as an administrator role that can be removed later.</li>
				</ol>
			</blockquote>
			<p class=\"submit\"><input type=\"submit\" value=\"Send\" id=\"submit\" class=\"button-primary\" name=\"submit\"/></p>
		  </div>
		  </form>";
}

function prensalista_settings_debug($option, $settings)
{
	switch($option)
	{
		case "report":
		{
			echo "<textarea rows='30' cols='80'>";
			print_r(prensalista_system_info());
			echo "</textarea>";
	
			echo "<textarea rows='30' cols='80'>";
			print_r(prensalista_capture_output('phpinfo'));
			echo "</textarea>";
		}
		break;
	}

	die();
}

function prensalista_settings_options() 
{
    if(!current_user_can('manage_options'))  
        wp_die('You do not have sufficient permissions to access this page.');

	$old_instance = prensalista_settings();

	$tabs = array("settings"=>"Settings","support"=>"Support");

	$tab = prensalista_settings_tab();
	if(!isset($tabs[$tab])) $tab = "settings";

	if(!empty($_GET['debug']))
		prensalista_settings_debug($_GET['debug'], $old_instance);

	// prensalista_update_user_photo_by_url();

	echo '<div class="wrap"><div class="prensalista_top"></div><h2 class="prensalista_title">PRENSALISTA SETTINGS</h2>';
	
	if(isset($_POST['submit']))
	{
		if($tab == "support")
			prensalista_settings_support_form_update();
		else
			$old_instance = prensalista_settings_form_update($_POST[PRENSALISTA_DEFAULT_SETTINGS_KEY], $old_instance);
	}

	foreach($tabs as $t=>$v)
	{
		$selected = ($t == $tab) ? " selected" : "";
		echo  '<a href="'.prensalista_settings_url().'&tab='.$t.'" class="prensalista-tab'.$selected.'">'.$v.'</a>';
	}

	echo '<div class="prensalista-tabbed-settings">';

	switch($tab)
	{
		case 'support':
			prensalista_settings_support_form($old_instance);
			break;

		default:
			prensalista_settings_form($old_instance);
			break;
	}

	echo '</div>';
}

add_action('admin_init', 'prensalista_admin_init');
add_action('admin_menu', 'prensalista_settings_menu');
add_filter('plugin_action_links', 'prensalista_page_settings_link', 10, 2);
?>
