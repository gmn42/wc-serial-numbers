<?php

namespace WooCommerceSerialNumbers\Admin;

use WooCommerceSerialNumbers\Lib;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings.
 *
 * @since   1.0.0
 * @package WooCommerceSerialNumbers\Admin
 */
class Settings extends Lib\Settings {

	/**
	 * Get settings tabs.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_tabs() {
		$tabs = array(
			'general' => __( 'General', 'wc-serial-numbers' ),
		);

		return apply_filters( 'wc_serial_numbers_settings_tabs', $tabs );
	}

	/**
	 * Get settings.
	 *
	 * @param string $tab Current tab.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_settings( $tab ) {
		$settings = array();

		switch ( $tab ) {
			case 'general':
				$settings = array(
					[
						'title' => __( 'General settings', 'wc-serial-numbers' ),
						'type'  => 'title',
						'desc'  => __( 'The following options affect how the serial numbers will work.', 'wc-serial-numbers' ),
						'id'    => 'section_serial_numbers',
					],
					[
						'title'   => __( 'Auto-complete order', 'wc-serial-numbers' ),
						'id'      => 'wc_serial_numbers_autocomplete_order',
						'desc'    => __( 'Automatically completes orders  after successfull payments.', 'wc-serial-numbers' ),
						'type'    => 'checkbox',
						'default' => 'no',
					],
					[
						'title'    => __( 'Reuse serial number', 'wc-serial-numbers' ),
						'id'       => 'wc_serial_numbers_reuse_serial_number',
						'desc'     => __( 'Recover failed, refunded serial numbers for selling again.', 'wc-serial-numbers' ),
						'desc_tip' => __( 'If you enable this option, the serial number will be available for selling again if the order is refunded or failed.', 'wc-serial-numbers' ),
						'type'     => 'checkbox',
						'default'  => 'no',
					],
					[
						'title'           => __( 'Revoke status', 'wc-serial-numbers' ),
						'desc'            => __( 'Cancelled', 'wc-serial-numbers' ),
						'id'              => 'wc_serial_numbers_revoke_status_cancelled',
						'default'         => 'yes',
						'type'            => 'checkbox',
						'checkboxgroup'   => 'start',
						'show_if_checked' => 'option',
					],
					[
						'desc'          => __( 'Refunded', 'wc-serial-numbers' ),
						'id'            => 'wc_serial_numbers_revoke_status_refunded',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => '',

					],
					[
						'desc'          => __( 'Failed', 'wc-serial-numbers' ),
						'id'            => 'wc_serial_numbers_revoke_status_failed',
						'default'       => 'yes',
						'type'          => 'checkbox',
						'checkboxgroup' => 'end',

					],
					[
						'title'   => __( 'Hide serial number', 'wc-serial-numbers' ),
						'id'      => 'wc_serial_numbers_hide_serial_number',
						'desc'    => __( 'All serial numbers will be hidden and only displayed when the "Show" button is clicked.', 'wc-serial-numbers' ),
						'default' => 'yes',
						'type'    => 'checkbox',
					],
					[
						'title'   => __( 'Disable software support', 'wc-serial-numbers' ),
						'id'      => 'wc_serial_numbers_disable_software_support',
						'desc'    => __( 'Disable Software Licensing support & API functionalities.', 'wc-serial-numbers' ),
						'default' => 'no',
						'type'    => 'checkbox',
					],
					[
						'type' => 'sectionend',
						'id'   => 'section_serial_numbers',
					],
					[
						'title' => __( 'Stock notification', 'wc-serial-numbers' ),
						'type'  => 'title',
						'desc'  => __( 'The following options affects how stock notification will work.', 'wc-serial-numbers' ),
						'id'    => 'stock_section',
					],
					[
						'title'             => __( 'Stock notification email', 'wc-serial-numbers' ),
						'id'                => 'wc_serial_numbers_enable_stock_notification',
						'desc'              => __( 'Sends notification emails when product stock is low.', 'wc-serial-numbers' ),
						'type'              => 'checkbox',
						'sanitize_callback' => 'intval',
						'default'           => 'yes',
					],
					array(
						'title'   => __( 'Stock threshold', 'wc-serial-numbers' ),
						'id'      => 'wc_serial_numbers_stock_threshold',
						'desc'    => __( 'When stock goes below the above number, it will send notification email.', 'wc-serial-numbers' ),
						'type'    => 'number',
						'default' => '5',
					),
					array(
						'title'   => __( 'Notification recipient email', 'wc-serial-numbers' ),
						'id'      => 'wc_serial_numbers_notification_recipient',
						'desc'    => __( 'The email address to be used for sending the email notifications.', 'wc-serial-numbers' ),
						'type'    => 'text',
						'default' => get_option( 'admin_email' ),
					),
					[
						'type' => 'sectionend',
						'id'   => 'stock_section',
					],
				);
				break;
		}
		/**
		 * Filter the settings for the plugin.
		 *
		 * @param array $settings The settings.
		 *
		 * @deprecated 1.4.1
		 */
		$settings = apply_filters( 'wc_serial_numbers_' . $tab . '_settings_fields', $settings );

		return apply_filters( 'wc_serial_numbers_get_settings_' . $tab, $settings );
	}

	/**
	 * Output premium widget.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function output_premium_widget() {
		if ( wc_serial_numbers()->is_premium_active() ) {
			return;
		}
		$features = array(
			__( 'Create and assign license keys for WooCommerce variable products.', 'wc-serial-numbers' ),
			__( 'Generate bulk license keys with your custom key generator rule.', 'wc-serial-numbers' ),
			__( 'Random & sequential order for the generator rules.', 'wc-serial-numbers' ),
			__( 'Automatic license key generator to auto-create & assign keys with orders.', 'wc-serial-numbers' ),
			__( 'License key management option from the order page with required actions.', 'wc-serial-numbers' ),
			__( 'Support for bulk import/export of license keys from/to CSV.', 'wc-serial-numbers' ),
			__( 'Option to sell license keys even if there are no available keys in the stock.', 'wc-serial-numbers' ),
			__( 'Custom deliverable quantity to deliver multiple keys with a single product.', 'wc-serial-numbers' ),
			__( 'Manual delivery option to manually deliver license keys instead of automatic.', 'wc-serial-numbers' ),
			__( 'Email Template to easily and quickly customize the order confirmation & low stock alert email.', 'wc-serial-numbers' ),
			__( 'Many more ...', 'wc-serial-numbers' ),
		);
		?>
		<div class="pluginever-settings__widget highlighted">
			<h3><?php esc_html_e( 'Want More?', 'wc-serial-numbers' ); ?></h3>
			<p><?php esc_attr_e( 'This plugin offers a premium version which comes with the following features:', 'wc-serial-numbers' ); ?></p>
			<ul>
				<?php foreach ( $features as $feature ) : ?>
					<li>- <?php echo esc_html( $feature ); ?></li>
				<?php endforeach; ?>
			</ul>
			<a href="https://pluginever.com/plugins/woocommerce-serial-numbers-pro/" class="button" target="_blank"><?php esc_html_e( 'Upgrade to PRO', 'wc-serial-numbers' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Output tabs.
	 *
	 * @param array $tabs Tabs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_tabs( $tabs ) {
		parent::output_tabs( $tabs );
		if ( wc_serial_numbers()->get_docs_url() ) {
			echo sprintf( '<a href="%s" class="nav-tab" target="_blank">%s</a>', wc_serial_numbers()->get_docs_url(), __( 'Documentation', 'wc-serial-numbers' ) );
		}
	}
}