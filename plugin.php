<?php

/**
 * Plugin Name:       BibleLink Multilingual
 * Plugin URI:        https://bible-link.globalrize.org/
 * Description:       Transforms Bible references on your website to interactive popups. 
 * Version:           1.0.18
 * Requires at least: 3.1.0
 * Requires PHP:      5.4
 * Author:            GlobalRize
 * Author URI:        https://www.globalrize.org/
 * License:           GPL v3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

// *************************************************************
// Data
// *************************************************************

// Constants
// Use define() for runtime consts and const for compiletime
if (defined('ICL_SITEPRESS_VERSION') && !ICL_PLUGIN_INACTIVE && class_exists('SitePress')) {
	define('BLM_BIBLE_LINK_WPML_IS_ACTIVE', true);
	define('BLM_BIBLE_LINK_DEFAULT_WPML_LANGUAGE', apply_filters('wpml_default_language', NULL));
} else {
	define('BLM_BIBLE_LINK_WPML_IS_ACTIVE', false);
}
const BLM_BIBLE_LINK_DEFAULT_OPTIONS = [
	'language' => 'en',
	'translation'  => 'esv16',
	'theme' => 'light',
	'mode' => 'auto',
	'reference_class' => 'bible-link',
	'tooltip_class' => 'bible-tooltip',
	'excluded_class' => null,
	'excluded_elements' => 'h1,h2,h3',
	'reference_color' => '#007bff',
];
const BLM_BIBLE_LINK_DOMAIN = 'https://bible-link.globalrize.org';

// *************************************************************
// Loading plugin in footer of each page.
// *************************************************************

add_action('wp_footer', 'blm_insert_bible_link_script');

function blm_insert_bible_link_script()
{
	$option_values = get_option('blm_options');
	$data = shortcode_atts(BLM_BIBLE_LINK_DEFAULT_OPTIONS, $option_values);

	$language = esc_attr($data['language']);
	$translation = esc_attr($data['translation']);
	$theme = esc_attr($data['theme']);
	$mode = esc_attr($data['mode']);
	$referenceClass = esc_attr($data['reference_class']);
	$tooltipClass = esc_attr($data['tooltip_class']);
	$excludedClass = esc_attr($data['excluded_class']);
	$excludedElements = esc_attr($data['excluded_elements']);
	$referenceColor = esc_attr($data['reference_color']);

	// Adjust language and translation if specified by in plugin WPML config
	$current_language_code = apply_filters('wpml_current_language', null);
	if (BLM_BIBLE_LINK_WPML_IS_ACTIVE && get_option('blm_options_wpml_enabled') === "1" && $current_language_code && $current_language_code !== BLM_BIBLE_LINK_DEFAULT_WPML_LANGUAGE) {
		echo get_option('blm_options_wpml_' . $current_language_code); // output the script as is
	} else {
		$languageAttr = "data-language='$language'";
		$translationAttr = "data-translation='$translation'";
		$themeAttr = ($theme != BLM_BIBLE_LINK_DEFAULT_OPTIONS['theme']) ? "data-theme='$theme'" : '';
		$modeAttr = ($mode != BLM_BIBLE_LINK_DEFAULT_OPTIONS['mode']) ? "data-mode='$mode'" : '';
		$referencClassAttr = ($referenceClass != BLM_BIBLE_LINK_DEFAULT_OPTIONS['reference_class'] && $referenceClass !== '') ? "data-reference-class='$referenceClass'" : '';
		$tooltipClassAttr = ($tooltipClass != BLM_BIBLE_LINK_DEFAULT_OPTIONS['tooltip_class'] && $tooltipClass !== '') ? "data-tooltip-class='$tooltipClass'" : '';
		$excludedElementsAttr = ($excludedElements != BLM_BIBLE_LINK_DEFAULT_OPTIONS['excluded_elements'] && $excludedElements !== '' ? "data-excluded-elements='$excludedElements'" : '');
		$excludedClassAttr = ($excludedClass != BLM_BIBLE_LINK_DEFAULT_OPTIONS['excluded_class'] && $excludedClass !== '' ? "data-excluded-class='$excludedClass'" : '');
		$referencColorAttr = ($referenceColor != BLM_BIBLE_LINK_DEFAULT_OPTIONS['reference_color'] && $referenceColor !== '') ? "data-reference-color='$referenceColor'" : '';

		echo '<script async defer
			' . $translationAttr . $themeAttr . $languageAttr . $modeAttr . $referencClassAttr . $tooltipClassAttr . $excludedElementsAttr . $excludedClassAttr . $referencColorAttr . '
			id="blm-references"
			src="' . BLM_BIBLE_LINK_DOMAIN . '/plugin.js"></script>', "\n";
	}
}

// *************************************************************
// Settings
// *************************************************************

// On plugin activation
register_activation_hook(__FILE__, 'blm_add_bible_link_options');

function blm_add_bible_link_options()
{
	add_option('blm_options', BLM_BIBLE_LINK_DEFAULT_OPTIONS);

	// Add WPML options
	if (BLM_BIBLE_LINK_WPML_IS_ACTIVE) {
		add_option('blm_options_wpml_enabled', 0);
		foreach (apply_filters('wpml_active_languages', null) as $key => $value) {
			// exclude default language since this is configured in the general section
			if ($key != BLM_BIBLE_LINK_DEFAULT_WPML_LANGUAGE) {
				$option_name = 'blm_options_wpml_' . $key;
				add_option($option_name, null);
			}
		}
	}
}

// Add link to options page in plugin overview
add_filter('plugin_action_links', 'blm_bible_link_options_page_link', 10, 2);

function blm_bible_link_options_page_link($links, $file)
{
	if ($file == plugin_basename(__FILE__)) {
		$ltb_links = '<a href="' . get_admin_url() . 'options-general.php?page=blm-plugin">' . __('Options') . '</a>';
		array_unshift($links, $ltb_links);
	}
	return $links;
}

// Add options page
add_action('admin_menu', 'blm_add_bible_link_options_page');

function blm_add_bible_link_options_page()
{
	add_options_page( // improve naming, icon?
		'Globalrize',
		'BibleLink Multilingual',
		'manage_options',
		'blm-plugin',
		'blm_bible_link_options_page_content',
		20
	);
}

function blm_bible_link_options_page_content()
{
	// Check user capabilities
	if (!current_user_can('manage_options')) {
		return;
	}

	echo '<div class="wrap">';
	echo '<h1>BibleLink Multilingual</h1>';
	echo '<div>Powered by <img src="' . BLM_BIBLE_LINK_DOMAIN . '/img/gr-icon.png" width="22" alt="" style="display:inline;"> GlobalRize</div	>
	<br><form method="post" action="options.php">';

	settings_fields('plugin_settings'); // settings group name
	do_settings_sections('blm-plugin'); // page-slug
	submit_button();

	echo '</form></div>';
}

// Call setting registration only when we need it.
if (
	!empty($GLOBALS['pagenow'])
	and ('options-general.php' === $GLOBALS['pagenow']
		or 'options.php' === $GLOBALS['pagenow']
	)
) {
	add_action('admin_init',  'blm_load_latest_options'); // get latest plugin options (languages, translations, etc.)
	add_action('admin_init',  'blm_register_bible_link_general_settings');
	add_action('admin_enqueue_scripts', 'blm_enqueue_bible_link_color_picker');

	if (BLM_BIBLE_LINK_WPML_IS_ACTIVE) {
		add_action('admin_init',  'blm_register_bible_link_wpml_settings');
	}
}

function blm_load_latest_options()
{
	$optionsJson = file_get_contents(BLM_BIBLE_LINK_DOMAIN . '/options.json'); // retrieve json file containing latest options
	$options = json_decode($optionsJson);

	$data = [];
	foreach ($options->languages as $key => $value) {
		$data[$value->name] = $value->code;
	}
	define('BLM_BIBLE_LINK_LANGUAGES', $data);

	$data = []; // empty for next batch
	foreach ($options->translations as $key => $value) {
		$data[$value->name] = $value->code;
	}
	define('BLM_BIBLE_LINK_TRANSLATIONS', $data);
}

function blm_enqueue_bible_link_color_picker($hook_suffix)
{
	wp_enqueue_style('wp-color-picker');
	wp_enqueue_script('handle_blm_script', plugins_url('blm-script.js', __FILE__), ['wp-color-picker'], false, true);
}

function blm_register_bible_link_general_settings()
{
	$themes = ['Light' => 'light', 'Dark' => 'dark'];
	$modes = ['Auto: detect references automatically' => 'auto', 'Specific: limit to HTML elements with the reference class specified below' => 'specific'];

	$option_values = get_option('blm_options');
	$data = shortcode_atts(BLM_BIBLE_LINK_DEFAULT_OPTIONS, $option_values);

	register_setting(
		'plugin_settings', // settings group name
		'blm_options', // option name
		'custom_validator' // sanitization function
	);

	add_settings_section(
		'plugin_settings', // section ID
		'General options', // title (if needed)
		'blm_render_bible_link_general_section', // callback function (if needed)
		'blm-plugin' // page slug
	);


	add_settings_field(
		'blm_options_language',
		'Language',
		'blm_render_bible_link_select_field_html', // callback that renders field
		'blm-plugin', // page slug
		'plugin_settings', // section ID
		[
			'label_for' => 'blm_options[language]',
			'name' => 'blm_options[language]',
			'value' => esc_attr($data['language']),
			'options' => BLM_BIBLE_LINK_LANGUAGES,
			'helper' => 'Language for: bible references detection and tooltip.',
		]
	);
	add_settings_field(
		'blm_options_translation',
		'Bible translation',
		'blm_render_bible_link_select_field_html',
		'blm-plugin',
		'plugin_settings',
		[
			'name' => 'blm_options[translation]',
			'value' => esc_attr($data['translation']),
			'options' => BLM_BIBLE_LINK_TRANSLATIONS,
		]
	);
	add_settings_field(
		'blm_options_theme',
		'Theme',
		'blm_render_bible_link_radio_field_html',
		'blm-plugin',
		'plugin_settings',
		[
			'name' => 'blm_options[theme]',
			'value' => esc_attr($data['theme']),
			'options' => $themes,
		]
	);
	add_settings_field(
		'blm_options_reference_color',
		'BibleLink color',
		'blm_render_bible_link_color_picker_html',
		'blm-plugin',
		'plugin_settings',
		[
			'name' => 'blm_options[reference_color]',
			'value' => esc_attr($data['reference_color']),

		]
	);


	add_settings_section(
		'advanced_settings', // section ID
		'', // title (if needed)
		'blm_render_bible_link_advanced_section', // callback function (if needed)
		'blm-plugin' // page slug
	);
	add_settings_field(
		'blm_options_mode',
		'Mode',
		'blm_render_bible_link_radio_field_html',
		'blm-plugin',
		'advanced_settings',
		[
			'name' => 'blm_options[mode]',
			'value' => esc_attr($data['mode']),
			'options' => $modes,
		]
	);
	$value = esc_attr($data['reference_class']);
	add_settings_field(
		'blm_options_reference_class',
		'Reference class',
		'blm_render_bible_link_text_field_html',
		'blm-plugin',
		'advanced_settings',
		[
			'name' => 'blm_options[reference_class]',
			'value' => ($value != BLM_BIBLE_LINK_DEFAULT_OPTIONS['reference_class']) ? $value : null, // Don't show value when default value; avoids confusion for user.
			'placeholder' => 'bible-link',
			'helper' => 'The HTML class name for identifying references. Preferably only use letters, numbers, hypens and underscores.'
		]
	);
	$value = esc_attr($data['tooltip_class']);
	add_settings_field(
		'blm_options_tooltip_class',
		'Tooltip class',
		'blm_render_bible_link_text_field_html', // function which prints the field
		'blm-plugin', // page slug
		'advanced_settings', // section ID
		[
			'name' => 'blm_options[tooltip_class]',
			'value' => ($value != BLM_BIBLE_LINK_DEFAULT_OPTIONS['tooltip_class']) ? $value : null,
			'placeholder' => 'bible-tooltip',
			'helper' => 'Preferably only use letters, numbers, hypens and underscores.'
		]
	);
	$value = esc_attr($data['excluded_class']);
	add_settings_field(
		'blm_options_excluded_class',
		'Exlude HTML class',
		'blm_render_bible_link_text_field_html', // function which prints the field
		'blm-plugin', // page slug
		'advanced_settings', // section ID
		[
			'name' => 'blm_options[excluded_class]',
			'value' => ($value != BLM_BIBLE_LINK_DEFAULT_OPTIONS['excluded_class']) ? $value : null,
			'placeholder' => 'no-bible-link',
			'helper' => 'The HTML class name to be excluded for auto reference detection. Preferably only use letters, numbers, hypens and underscores'
		]
	);
	$value = esc_attr($data['excluded_elements']);
	add_settings_field(
		'blm_options_excluded_elements',
		'Exlude HTML elements',
		'blm_render_bible_link_text_field_html', // function which prints the field
		'blm-plugin', // page slug
		'advanced_settings', // section ID
		[
			'name' => 'blm_options[excluded_elements]',
			'value' => ($value != BLM_BIBLE_LINK_DEFAULT_OPTIONS['excluded_elements']) ? $value : null,
			'placeholder' => 'h1,h2,h3',
			'helper' => 'The HTML elements to be excluded for auto reference detection. Specify your elements seperated by a comma. No spaces.'
		]
	);
}

function blm_register_bible_link_wpml_settings()
{
	// WPML Settings section
	add_settings_section(
		'wpml_settings', // section ID
		'', // title (if needed)
		'blm_render_bible_link_wpml_section', // callback function (if needed)
		'blm-plugin' // page slug
	);

	// WPML enabled setting
	register_setting(
		'plugin_settings', // settings group name
		'blm_options_wpml_enabled', // option name
		'sanitize_text_field' // sanitization function
	);
	add_settings_field(
		'wpml_enabled',
		'Configure with WPML',
		'blm_render_bible_link_checkbox_field_html',
		'blm-plugin',
		'wpml_settings',
		[
			'name' => 'blm_options_wpml_enabled',
			'value' => get_option('blm_options_wpml_enabled'),
		]
	);

	// Settings for each language
	$wpml_languages = apply_filters('wpml_active_languages', null);
	foreach ($wpml_languages as $key => $value) {
		// exclude default language since this is configured in the general section
		if ($key != BLM_BIBLE_LINK_DEFAULT_WPML_LANGUAGE) {
			$option_name = 'blm_options_wpml_' . $key;
			$option_value = get_option($option_name);

			register_setting(
				'plugin_settings',
				$option_name,
				'custom_validator'
			);

			add_settings_field(
				'blm_options_wpml_' . $key . '_language',
				'Language [' . $key . ']',
				'blm_render_bible_link_text_area_html',
				'blm-plugin',
				'wpml_settings',
				[
					'name' => $option_name,
					'value' => esc_attr($option_value),
					'placeholder' => 'e.g. <script async defer src="https://bible-link.globalrize.org/plugin.js" data-language="en" data-translation="esv16"></script>',
				]
			);
		}
	}
}

function blm_render_bible_link_text_field_html($args)
{
	$name = $args['name'];
	$placeholder = $args['placeholder'];
	$option = $args['value'];

	echo "<input type='text' id='$name' name='$name' value='$option' placeholder='$placeholder' />";

	blm_render_bible_link_helper_html($args);
}

function blm_render_bible_link_text_area_html($args)
{
	$name = $args['name'];
	$placeholder = $args['placeholder'];
	$option = $args['value'];

	echo "<textarea id='$name' name='$name' rows='3' cols='120' placeholder='$placeholder'>$option</textarea>";
}

function blm_render_bible_link_select_field_html($args)
{
	$name = $args['name'];
	$option = $args['value'];
	$options = $args['options'];

	echo "<select name='$name' id='$name'>";
	foreach ($options as $key => $value) {
		if (strpos($key, 'group_start') === 0) echo "<optgroup label='$value'>";
		else if (strpos($key, 'group_end') === 0) echo "</optgroup>";
		else {
			$selected = ($option == $value) ? 'selected' : '';
			echo "<option $selected value='$value'>$key</option>";
		}
	}
	echo '</select>';

	blm_render_bible_link_helper_html($args);
}

function blm_render_bible_link_radio_field_html($args)
{
	$name = $args['name'];
	$option = $args['value'];
	$options = $options = $args['options'];

	foreach ($options as $key => $value) {
		$checked = ($option == $value) ? 'checked' : '';
		echo "<label><input $checked value='$value' name='$name' id='$name' type='radio' /> $key</label> <br> ";
	}

	blm_render_bible_link_helper_html($args);
}

function  blm_render_bible_link_checkbox_field_html($args)
{
	$name = $args['name'];
	$option = $args['value'];

	$checked = ($option == '1') ? 'checked' : '';

	echo "<input type='checkbox' $checked value='1' name='$name' id='$name' />";

	blm_render_bible_link_helper_html($args);
}

function  blm_render_bible_link_color_picker_html($args)
{
	$name = $args['name'];
	$option = $args['value'];

	echo "<input type='text' value='$option' name='$name' id='$name' class='blm_color_picker' data-default-color='#007bff' />";

	blm_render_bible_link_helper_html($args);
}

function blm_render_bible_link_helper_html($args)
{
	if (isset($args['helper'])) {
		printf('<span class="helper"><em> %s</em></span>', $args['helper']);
	}

	// If there is supplemental text
	if (isset($args['supplemental'])) {
		printf('<p class="description">%s</p>', $args['supplemental']);
	}
}

function blm_render_bible_link_general_section()
{
	echo '';
}

function blm_render_bible_link_advanced_section()
{
	echo '<br><h2>Advanced Options</h2>';
}

function blm_render_bible_link_wpml_section()
{
	echo '<br><h2>WPML Options</h2><p>To enable BibleLink with WPML check the box below.</p><p>See this <a href="https://bible-link.globalrize.org">plugin page</a> to generate the code for additional languages. Copy and paste the code in the textfield.</p>';
}
