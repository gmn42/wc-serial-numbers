<?php
/**
 * WooCommerce Unit Tests Bootstrap
 *
 * @since 1.1.5
 */

/**
 * Class WCSN_Unit_Tests_Bootstrap
 */
class WCSN_Unit_Tests_Bootstrap {

	/** @var WCSN_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/** @var string woocommerce plugin directory */
	public $woocommerce_plugin_dir;

	/**
	 * Setup the unit testing environment.
	 *
	 * @since 1.1.5
	 */
	public function __construct() {

		ini_set( 'display_errors', 'on' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Blacklisted
		error_reporting( E_ALL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting

		// Ensure server variable is set for WP email functions.
		// phpcs:disable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
			$_SERVER['SERVER_NAME'] = 'localhost';
		}
		// phpcs:enable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		$this->tests_dir              = dirname( __FILE__ );
		$this->plugin_dir             = dirname( $this->tests_dir );
		$this->wp_tests_dir           = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : $this->plugin_dir . '/tmp/wordpress-tests-lib';
		$this->woocommerce_plugin_dir = dirname( dirname( $this->tests_dir ) ) . '/woocommerce';

		// load test function so tests_add_filter() is available.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// load WC.
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_plugin' ) );

		// install WC.
		tests_add_filter( 'setup_theme', array( $this, 'install_plugin' ) );

		// load the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// load WC testing framework.
		$this->includes();
	}

	/**
	 * Load WooCommerce.
	 *
	 * @since 1.1.5
	 */
	public function load_plugin() {
		define( 'WC_TAX_ROUNDING_MODE', 'auto' );
		define( 'WC_USE_TRANSACTIONS', false );
		require_once $this->woocommerce_plugin_dir . '/woocommerce.php';
		require_once $this->plugin_dir . '/wc-serial-numbers.php';
	}

	/**
	 * Install WooCommerce after the test environment and WC have been loaded.
	 *
	 * @since 1.1.5
	 */
	public function install_plugin() {

		// Clean existing install first.
		define( 'WP_UNINSTALL_PLUGIN', true );
		define( 'WC_REMOVE_ALL_DATA', true );
		include $this->woocommerce_plugin_dir . '/uninstall.php';

		WC_Install::install();

		// Initialize the WC API extensions.
		\Automattic\WooCommerce\Admin\Install::create_tables();
		\Automattic\WooCommerce\Admin\Install::create_events();

		// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374.
		if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
			$GLOBALS['wp_roles']->reinit();
		} else {
			$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			wp_roles();
		}

		echo esc_html( 'Installing WooCommerce...' . PHP_EOL );
	}

	/**
	 * Load WC-specific test cases and factories.
	 *
	 * @since 1.1.5
	 */
	public function includes() {

		// framework.
		require_once $this->tests_dir . '/framework/class-wc-unit-test-factory.php';
		require_once $this->tests_dir . '/framework/class-wc-mock-session-handler.php';
		require_once $this->tests_dir . '/framework/class-wc-mock-wc-data.php';
		require_once $this->tests_dir . '/framework/class-wc-mock-wc-object-query.php';
		require_once $this->tests_dir . '/framework/class-wc-mock-payment-gateway.php';
		require_once $this->tests_dir . '/framework/class-wc-payment-token-stub.php';
		require_once $this->tests_dir . '/framework/vendor/class-wp-test-spy-rest-server.php';

		// test cases.
		require_once $this->tests_dir . '/includes/wp-http-testcase.php';
		require_once $this->tests_dir . '/framework/class-wc-unit-test-case.php';
		require_once $this->tests_dir . '/framework/class-wc-api-unit-test-case.php';
		require_once $this->tests_dir . '/framework/class-wc-rest-unit-test-case.php';

		// Helpers.
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-product.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-coupon.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-fee.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-shipping.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-customer.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-order.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-shipping-zones.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-payment-token.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-settings.php';


		require_once $this->tests_dir . '/framework/helpers/class-wcsn-helper-serialnumber.php';
	}

	/**
	 * Get the single class instance.
	 *
	 * @return WCSN_Unit_Tests_Bootstrap
	 * @since 1.1.5
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

WCSN_Unit_Tests_Bootstrap::instance();
