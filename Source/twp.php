<?php
/**
 * Plugin Name: TweakWP
 * Plugin URI: https://github.com/Grandbrain/TweakWP
 * Description: The plugin allows you to tweak various Wordpress settings.
 * Author: Andrey Lomakin
 * Author URI: https://github.com/Grandbrain
 * Version: 1.0
 * Text Domain: twp
 * Domain Path: /lang/
 */

/**
 * Add hooks for admin panel and main site interface.
 */
$options = get_option( 'twp_options' );

if ( $options['twp-disable-emoji'] === TRUE ) {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', 'twp_disable_emoji_tinymce' );
    add_filter( 'wp_resource_hints', 'twp_disable_emoji_prefetch', 10, 2 );
}

if ( $options['twp-disable-core-updates'] === TRUE ) {
	add_filter( 'pre_site_transient_update_core', 'twp_disable_wordpress_updates' );
}

if ( $options['twp-disable-themes-updates'] === TRUE ) {
	add_filter( 'pre_site_transient_update_themes', 'twp_disable_wordpress_updates' );
}

if ( $options['twp-disable-plugins-updates'] === TRUE ) {
	add_filter( 'pre_site_transient_update_plugins', 'twp_disable_wordpress_updates' );
}

if ( $options['twp-disable-documents-previews'] === TRUE ) {
    add_filter( 'fallback_intermediate_image_sizes', 'twp_disable_documents_previews' );
}

if ( $options['twp-disable-resource-hints'] === TRUE ) {
	remove_action( 'wp_head', 'wp_resource_hints', 2 );
}

if ( $options['twp-disable-generator-meta'] === TRUE ) {
    remove_action( 'wp_head', 'wp_generator' );
}

if ( $options['twp-disable-wlwmanifest-meta'] === TRUE ) {
    remove_action( 'wp_head', 'wlwmanifest_link' );
}

if ( $options['twp-disable-rsd-link'] === TRUE ) {
    remove_action( 'wp_head', 'rsd_link' );
}

if ( $options['twp-disable-jquery-migrate'] === TRUE ) {
	add_action( 'wp_default_scripts', 'twp_disable_jquery_migrate' );
}

if ( $options['twp-move-jquery-footer'] === TRUE ) {
    add_action( 'wp_head', 'twp_move_jquery_footer', 1, 0 );
}

if ( $options['twp-enable-manifest-link'] === TRUE ) {
    add_action( 'wp_head', 'twp_enable_manifest_link' );
}

if ( $options['twp-enable-service-workers'] === TRUE ) {
	if ( ! empty( $options['twp-service-workers-urls'] ) && ! empty( $options['twp-service-workers-scopes'] ) ) {
		add_action( 'wp_enqueue_scripts', twp_enable_service_workers( $options['twp-service-workers-urls'],
			$options['twp-service-workers-scopes'] ) );
	}
}

if ( is_admin() ) {
	add_action( 'plugins_loaded', 'twp_load_text_domain' );
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'twp_add_action_links', 10, 1 );
	add_action( 'admin_menu', 'twp_add_options_page' );
	add_action( 'admin_init', 'twp_add_options_set' );
	add_action( 'admin_enqueue_scripts', 'twp_load_settings_scripts' );
}

/**
 * Disables emoji in TinyMCE editor.
 *
 * @param array $plugins An array of default TinyMCE plugins.
 *
 * @return array An array without emoji plugins.
 */
function twp_disable_emoji_tinymce($plugins) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, [ 'wpemoji' ] );
	} else {
		return [];
	}
}

/**
 * Disables emoji prefetch link.
 *
 * @param array $urls URLs to print for resource hints.
 * @param string $relation The relation type the URLs are printed for.
 *
 * @return mixed A filtered array without emoji links.
 */
function twp_disable_emoji_prefetch($urls, $relation) {
	if ( $relation == 'dns-prefetch' ) {
		$emoji_url = 'https://s.w.org/images/core/emoji/';
		foreach ( $urls as $key => $url ) {
			if ( strpos( $url, $emoji_url ) !== FALSE ) {
				unset( $urls[ $key ] );
			}
		}
	}

	return $urls;
}

/**
 * Disables Wordpress version check.
 *
 * @return object Default version values.
 */
function twp_disable_wordpress_updates() {
	global $wp_version;

	return (object) [
		'last_checked'    => time(),
		'version_checked' => $wp_version,
		'updates'         => []
	];
}

/**
 * Disables PDF thumbnail previews.
 *
 * @return array An array of image size names.
 */
function twp_disable_documents_previews() {
	return [];
}

/**
 * Disables JQuery Migrate.
 *
 * @param \WP_Scripts $scripts WP_Scripts instance, passed by reference.
 */
function twp_disable_jquery_migrate($scripts) {
	if ( ! empty( $scripts->registered['jquery'] ) ) {
		$scripts->registered['jquery']->deps
			= array_diff( $scripts->registered['jquery']->deps,
			[ 'jquery-migrate' ] );
	}
}

/**
 * Moves JQuery to footer.
 */
function twp_move_jquery_footer() {
	global $wp_scripts;
	$wp_scripts->add_data( 'jquery', 'group', 1 );
	$wp_scripts->add_data( 'jquery-core', 'group', 1 );
	$wp_scripts->add_data( 'jquery-migrate', 'group', 1 );
}

/**
 * Adds PWA manifest link and associated meta information.
 */
function twp_enable_manifest_link() {
	if ( ! defined( 'ABSPATH' ) ) {
		return;
	}

	$file_name = 'manifest.json';
	$file_path = ABSPATH . $file_name;

	if ( is_file( $file_path ) ) {
		echo '<link rel="manifest" href="' . $file_name . '">';
		$content = file_get_contents( $file_path );
		if ( $content === FALSE ) {
			return;
		}
		$json = json_decode( $content, TRUE );
		$key  = 'theme_color';
		if ( ! array_key_exists( $key, $json ) || is_null( $json[ $key ] ) ) {
			return;
		}
		echo '<meta name="theme-color" content="' . $json[ $key ] . '">';
	}
}

/**
 * Adds PWA service workers registration script.
 *
 * @param string $urls Concatenated string of URLs.
 * @param string $scopes Concatenated string of scopes.
 *
 * @return \Closure Function that enqueues registration script.
 */
function twp_enable_service_workers($urls, $scopes) {
	return function () use ( $urls, $scopes ) {
		$translations = [ 'urls' => $urls, 'scopes' => $scopes ];
		wp_enqueue_script( 'twp-client-workers', plugin_dir_url( __FILE__ ) . 'js/twp-client-workers.js',
			[ 'jquery' ], FALSE, TRUE );

		wp_localize_script( 'twp-client-workers', 'twp_translations', $translations );
	};
}

/**
 * Load plugin text domain.
 */
function twp_load_text_domain() {
	load_plugin_textdomain( 'twp', FALSE, basename( dirname( __FILE__ ) ) . '/lang' );
}

/**
 * Adds settings action link.
 *
 * @param array $links An array of plugin action links.
 *
 * @return mixed Updated array of plugin action links.
 */
function twp_add_action_links($links) {
	$link = '<a href="options-general.php?page=twp">' .
	        __( 'Settings', 'twp' ) . '</a>';

	array_unshift( $links, $link );

	return $links;
}

/**
 * Adds options page.
 */
function twp_add_options_page() {
	global $twp_settings_page_hook;
	$twp_settings_page_hook = add_options_page(
		__( 'TweakWP Options' ),
		__( 'TweakWP' ),
		'manage_options',
		'twp',
		'twp_options_page'
	);
}

/**
 * Loads admin scripts.
 *
 * @param string $hook The current admin page.
 */
function twp_load_settings_scripts($hook) {
	global $twp_settings_page_hook;
	if ( $hook != $twp_settings_page_hook ) {
		return;
	}
	wp_enqueue_script( 'twp-admin-settings', plugin_dir_url( __FILE__ ) . 'js/twp-admin-settings.js', [ 'jquery' ] );
}

/**
 * Adds options set.
 */
function twp_add_options_set() {
	register_setting(
		'twp_options_group',
		'twp_options',
		'twp_options_validate'
	);
}

/**
 * Validates options.
 *
 * @param mixed $input Option object.
 *
 * @return mixed Updated option object.
 */
function twp_options_validate( $input ) {

	$input['twp-disable-emoji']              = empty( $input['twp-disable-emoji'] ) ? FALSE : TRUE;
	$input['twp-disable-core-updates']       = empty( $input['twp-disable-core-updates'] ) ? FALSE : TRUE;
	$input['twp-disable-themes-updates']     = empty( $input['twp-disable-themes-updates'] ) ? FALSE : TRUE;
	$input['twp-disable-plugins-updates']    = empty( $input['twp-disable-plugins-updates'] ) ? FALSE : TRUE;
	$input['twp-disable-documents-previews'] = empty( $input['twp-disable-documents-previews'] ) ? FALSE : TRUE;
	$input['twp-disable-resource-hints']     = empty( $input['twp-disable-resource-hints'] ) ? FALSE : TRUE;
	$input['twp-disable-generator-meta']     = empty( $input['twp-disable-generator-meta'] ) ? FALSE : TRUE;
	$input['twp-disable-wlwmanifest-meta']   = empty( $input['twp-disable-wlwmanifest-meta'] ) ? FALSE : TRUE;
	$input['twp-disable-rsd-link']           = empty( $input['twp-disable-rsd-link'] ) ? FALSE : TRUE;
	$input['twp-disable-jquery-migrate']     = empty( $input['twp-disable-jquery-migrate'] ) ? FALSE : TRUE;
	$input['twp-move-jquery-footer']         = empty( $input['twp-move-jquery-footer'] ) ? FALSE : TRUE;
	$input['twp-enable-manifest-link']       = empty( $input['twp-enable-manifest-link'] ) ? FALSE : TRUE;
	$input['twp-enable-service-workers']     = empty( $input['twp-enable-service-workers'] ) ? FALSE : TRUE;
	$input['twp-service-workers-urls']       = '';
	$input['twp-service-workers-scopes']     = '';

	$absoluteUrl = get_site_url();

	if ( empty( $absoluteUrl ) || empty( $input['twp-service-workers-options'] ) ) {
		return $input;
	}

	$absoluteUrl .= '/';
	$urls        = explode( "\n", $input['twp-service-workers-options'] );

	if ( empty( $urls ) ) {
		return $input;
	}

	foreach ( $urls as $url ) {
		$url = trim( $url );

		if ( empty( $url ) ) {
			continue;
		}

		$options = explode( '|', $url );
		$count   = count( $options );
		if ( $count < 1 ) {
			continue;
		}

		$relative = trim( $options[0] );
		$scope    = $count > 1 ? trim( $options[1] ) : '';

		if ( empty( $relative ) ) {
			continue;
		}

		$input['twp-service-workers-urls']   .= $absoluteUrl . $relative . '|';
		$input['twp-service-workers-scopes'] .= $scope . '|';
	}

	return $input;
}

/**
 * Renders options page.
 */
function twp_options_page() {
	?>

    <div class="wrap">

        <h2><?php _e( 'TweakWP Options', 'twp' ) ?></h2>

        <form method="post" action="options.php">

			<?php settings_fields( 'twp_options_group' ); ?>
			<?php $options = get_option( 'twp_options' ); ?>

            <!--'Disable' and 'clean' settings section.-->
            <table class="form-table">

                <caption style="text-align: left">
                    <h3><?php _e( 'Turning off unnecessary features', 'twp' ); ?></h3>
					<?php _e( 'Here you can turn off unnecessary features of your website.', 'twp' ); ?>
                </caption>

                <!--Disable emoji-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Disable Emoji?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-disable-emoji]"
                               name="twp_options[twp-disable-emoji]"
                               type="checkbox"
							<?php checked( 1, $options['twp-disable-emoji'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-disable-emoji]">
							<?php _e( 'Disabling emoji will reduce the load time of the site.', 'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Disable Wordpress updates-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Disable Wordpress updates?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-disable-core-updates]"
                               name="twp_options[twp-disable-core-updates]"
                               type="checkbox"
							<?php checked( 1, $options['twp-disable-core-updates'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-disable-core-updates]">
							<?php _e( 'Disabling Wordpress updates allows you to hide annoying reminders.', 'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Disable themes updates-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Disable themes updates?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-disable-themes-updates]"
                               name="twp_options[twp-disable-themes-updates]"
                               type="checkbox"
							<?php checked( 1, $options['twp-disable-themes-updates'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-disable-themes-updates]">
							<?php _e( 'Disabling themes updates allows you to hide annoying reminders.', 'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Disable plugins updates-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Disable plugins updates?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-disable-plugins-updates]"
                               name="twp_options[twp-disable-plugins-updates]"
                               type="checkbox"
							<?php checked( 1, $options['twp-disable-plugins-updates'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-disable-plugins-updates]">
							<?php _e( 'Disabling plugins updates allows you to hide annoying reminders.', 'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Disable documents previews-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Disable documents previews?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-disable-documents-previews]"
                               name="twp_options[twp-disable-documents-previews]"
                               type="checkbox"
							<?php checked( 1, $options['twp-disable-documents-previews'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-disable-documents-previews]">
							<?php _e( 'Disabling documents previews prevents generation of thumbnails images.',
								'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Disable prefetch links-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Disable prefetch links?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-disable-resource-hints]"
                               name="twp_options[twp-disable-resource-hints]"
                               type="checkbox"
							<?php checked( 1, $options['twp-disable-resource-hints'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-disable-resource-hints]">
							<?php _e( 'Disabling prefetch links removes unnecessary tags from website pages.',
								'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Disable generator metadata-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Disable generator metadata?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-disable-generator-meta]"
                               name="twp_options[twp-disable-generator-meta]"
                               type="checkbox"
							<?php checked( 1, $options['twp-disable-generator-meta'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-disable-generator-meta]">
							<?php _e( 'Disabling generator metadata removes unnecessary tags from website pages.',
								'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Disable Windows Live Writer metadata-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Disable Windows Live Writer metadata?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-disable-wlwmanifest-meta]"
                               name="twp_options[twp-disable-wlwmanifest-meta]"
                               type="checkbox"
							<?php checked( 1, $options['twp-disable-wlwmanifest-meta'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-disable-wlwmanifest-meta]">
							<?php _e( 'Disabling WLW metadata removes unnecessary tags from website pages.',
								'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Disable RSD link-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Disable RSD link?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-disable-rsd-link]"
                               name="twp_options[twp-disable-rsd-link]"
                               type="checkbox"
							<?php checked( 1, $options['twp-disable-rsd-link'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-disable-rsd-link]">
							<?php _e( 'Disabling RSD link removes unnecessary tags from website pages.',
								'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Disable JQuery Migrate-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Disable JQuery Migrate?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-disable-jquery-migrate]"
                               name="twp_options[twp-disable-jquery-migrate]"
                               type="checkbox"
							<?php checked( 1, $options['twp-disable-jquery-migrate'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-disable-jquery-migrate]">
							<?php _e( 'Disabling JQuery Migrate removes unnecessary scripts from website pages.',
								'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Move JQuery to footer-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Move JQuery to footer?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-move-jquery-footer]"
                               name="twp_options[twp-move-jquery-footer]"
                               type="checkbox"
							<?php checked( 1, $options['twp-move-jquery-footer'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-move-jquery-footer]">
							<?php _e( 'Moving JQuery to the footer allows to avoid blocking pages when loading.',
								'twp' ) ?>
                        </label>
                    </td>
                </tr>

            </table>

            <!--PWA settings section.-->
            <table class="form-table">

                <caption style="text-align: left">
                    <h3><?php _e( 'Enabling PWA features', 'twp' ); ?></h3>
					<?php _e( 'Here you can enable PWA features to improve website user experience.', 'twp' ); ?>
                </caption>

                <!--Enable web app manifest link-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Enable web app manifest link?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-enable-manifest-link]"
                               name="twp_options[twp-enable-manifest-link]"
                               type="checkbox"
							<?php checked( 1, $options['twp-enable-manifest-link'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-enable-manifest-link]">
							<?php _e( 'Enabling web app manifest allows to control various website settings.',
								'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Enable service workers scripts-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Enable service workers scripts?', 'twp' ); ?>
                    </th>
                    <td>
                        <input id="twp_options[twp-enable-service-workers]"
                               name="twp_options[twp-enable-service-workers]"
                               type="checkbox"
							<?php checked( 1, $options['twp-enable-service-workers'] ) ?>
                        >
                        <label class="description"
                               for="twp_options[twp-enable-service-workers]">
							<?php _e( 'Enabling service workers scripts allows to control web app behavior.', 'twp' ) ?>
                        </label>
                    </td>
                </tr>

                <!--Service workers options-->
                <tr valign="top">
                    <th scope="row">
						<?php _e( 'Choose service workers scripts:', 'twp' ); ?>
                    </th>
                    <td>
                        <label style="display: block; margin: 5px 0;" for="twp_options[twp-service-workers-options]">
							<?php _e( 'This text field displays the scripts you added.', 'twp' ); ?>
                        </label>
                        <label style="display: block; margin: 5px 0;" for="twp_options[twp-service-workers-options]">
							<?php _e( 'You can edit it manually using simple <code>url|scope</code> ' .
							          'notation <strong>(one per line)</strong> ' .
							          'or automatically using controls below.', 'twp' ); ?>
                        </label>
                        <textarea id="twp_options[twp-service-workers-options]"
                                  name="twp_options[twp-service-workers-options]"
                                  class="regular-text"
                                  rows="5"
                                  style="display: block; margin: 5px 0;">
                            <?php echo $options['twp-service-workers-options']; ?>
                        </textarea>
                        <label style="display: block; margin: 5px 0;" for="twp-service-worker-url">
							<?php _e( 'Enter a relative address of the service worker script:', 'twp' ); ?>
                        </label>
                        <input id="twp-service-worker-url"
                               name="twp-service-worker-url"
                               type="text"
                               class="regular-text"
                               style="display: block; margin: 5px 0;"
                        >
                        <label style="display: block; margin: 5px 0;" for="twp-service-worker-scope">
							<?php _e( 'Enter a service worker scope:', 'twp' ); ?>
                        </label>
                        <input id="twp-service-worker-scope"
                               name="twp-service-worker-scope"
                               type="text"
                               class="regular-text"
                               style="display: block; margin: 5px 0;"
                        >
                        <input id="twp-service-worker-add"
                               type="button"
                               class="button-primary"
                               style="margin: 5px 0;"
                               value="<?php _e( 'Add entry', 'twp' ); ?>"
                        >
                        <input id="twp-service-worker-clear"
                               type="button"
                               class="button-primary"
                               style="margin: 5px 0;"
                               value="<?php _e( 'Clear list', 'twp' ); ?>"
                        >
                    </td>
                </tr>

            </table>

            <p class="submit">
                <input type="submit" class="button-primary"
                       value="<?php _e( 'Save Changes', 'twp' ) ?>"
            </p>

        </form>

    </div>

	<?php
}