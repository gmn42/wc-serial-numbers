<?php

namespace WooCommerceSerialNumbers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin.
 *
 * @since 1.4.2
 * @package WooCommerceSerialNumbers
 */
class Plugin extends Lib\Plugin {

	/**
	 * Plugin constructor.
	 *
	 * @param array $data The plugin data.
	 *
	 * @since 1.0.0
	 */
	protected function __construct( $data ) {
		parent::__construct( $data );
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function includes() {
		require_once __DIR__ . '/functions.php';
		require_once dirname( __FILE__ ) . '/Deprecated/Functions.php';

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			CLI::instantiate();
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_hooks() {
		register_activation_hook( $this->get_file(), array( Installer::class, 'install' ) );
		add_action( 'admin_notices', array( $this, 'dependencies_notices' ) );
		add_action( 'woocommerce_loaded', array( $this, 'init' ), 0 );
	}

	/**
	 * Missing dependencies notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function dependencies_notices() {
		if ( $this->is_plugin_active( 'woocommerce' ) ) {
			return;
		}
		$notice = sprintf(
		/* translators: 1: plugin name 2: WooCommerce */
			__( '%1$s requires %2$s to be installed and active.', 'wc-serial-numbers' ),
			'<strong>' . esc_html( $this->get_name() ) . '</strong>',
			'<strong>' . esc_html__( 'WooCommerce', 'wc-serial-numbers' ) . '</strong>'
		);

		echo '<div class="notice notice-error"><p>' . wp_kses_post( $notice ) . '</p></div>';
	}

	/**
	 * Init the plugin after plugins_loaded so environment variables are set.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		Installer::instantiate();
		Cache::instantiate();
		Scripts::instantiate();
		Ajax::instantiate();
		Orders::instantiate();
		Encryption::instantiate();
		Stocks::instantiate();
		Cron::instantiate();
		Controllers\Keys::instantiate();
		Shortcodes::instantiate();

		if ( wcsn_is_software_support_enabled() ) {
			Controllers\Activations::instantiate();
			API::instantiate();
		}

		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			Admin\Admin::instantiate();
		}

		// Init action.
		do_action( 'wc_serial_numbers_loaded' );
	}

	/**
	 * Determines if the pro version active.
	 *
	 * @since 1.0.0
	 * @return bool
	 * @deprecated 1.4.0
	 */
	public static function is_pro_active() {
		_deprecated_function( __METHOD__, '1.4.0', 'Plugin::is_premium_active()' );

		return self::$instance->is_premium_active();
	}

	/**
	 * Determines if the wc is active.
	 *
	 * @since 1.0.0
	 * @return bool
	 * @deprecated 1.4.0
	 */
	public function is_wc_active() {
		return $this->is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	/**
	 * Plugin URL getter.
	 *
	 * @since 1.2.0
	 * @return string
	 * @deprecated 1.4.0
	 */
	public function plugin_url() {
		_deprecated_function( __METHOD__, '1.4.0', 'Plugin::get_url()' );

		return $this->get_url();
	}

	/**
	 * Plugin path getter.
	 *
	 * @since 1.2.0
	 * @return string
	 * @deprecated 1.4.0
	 */
	public function plugin_path() {
		_deprecated_function( __METHOD__, '1.4.0', 'Plugin::get_path()' );

		return $this->get_path();
	}

	/**
	 * Plugin base path name getter.
	 *
	 * @since 1.2.0
	 * @return string
	 * @deprecated 1.4.2
	 */
	public function plugin_basename() {
		_deprecated_function( __METHOD__, '1.4.2', 'Plugin::get_basename()' );

		return $this->get_basename();
	}
}
