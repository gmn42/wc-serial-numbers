<?php
// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Disable all expired serial numbers
 *
 * since 1.0.0
 */
function wcsn_check_expired_serial_numbers() {
	global $wpdb;
	$wpdb->query( "update {$wpdb->prefix}wcsn_serial_numbers set status='expired' where expire_date != '0000-00-00 00:00:00' AND expire_date < NOW()" );
	$wpdb->query( "update {$wpdb->prefix}wcsn_serial_numbers set status='expired' where validity !='0' AND (order_date + INTERVAL validity DAY ) < NOW()" );
}

add_action( 'wcsn_hourly_event', 'wcsn_check_expired_serial_numbers' );

/**
 * Show serial number details on order details table
 *
 * @param $order
 *
 * @since 1.0.0
 *
 */

function wcsn_order_table_serial_number_details( $order ) {

	if ( 'completed' != $order->get_status() ) {
		return;
	}

	$serial_numbers = wcsn_get_serial_numbers( [ 'order_id' => $order->get_id() ] );

	if ( empty( $serial_numbers ) ) {
		return;
	}

	wc_get_template( '/html-order-details-table.php', array( 'serial_numbers' => $serial_numbers ), '', WC_SERIAL_NUMBERS_TEMPLATES );
}

add_action( 'woocommerce_order_details_after_order_table', 'wcsn_order_table_serial_number_details' );

/**
 * Auto Complete Order
 *
 * @param $order
 *
 * @since 1.0.0
 *
 */
function wcsn_auto_complete_order( $order_id ) {
	if ( 'yes' !== wcsn_get_settings( 'wsn_auto_complete_order', '', 'wsn_delivery_settings' ) ) {
		return;
	}
	$order          = wc_get_order( $order_id );
	$current_status = $order->get_status();
	// We only want to update the status to 'completed' if it's coming from one of the following statuses:
	//$allowed_current_statuses = array( 'on-hold', 'pending', 'failed' );
	if ( 'processing' == $current_status ) {
		$items = $order->get_items();
		foreach ( $items as $item_data ) {
			$product                  = $item_data->get_product();
			$product_id               = $product->get_id();
			$is_serial_number_enabled = get_post_meta( $product_id, '_is_serial_number', true ); //Check if the serial number enabled for this product.
			if ( 'yes' == $is_serial_number_enabled ) {
				$order->update_status( 'completed' );

				return;
			}
		}
	}

}

add_action( 'woocommerce_thankyou', 'wcsn_auto_complete_order', 99, 1 );

/**
 * Register Post types
 *
 * @since 1.0.0
 */

function wcsn_register_post_types() {
	register_post_type( 'wcsn_notification', array(
		'labels'              => false,
		'hierarchical'        => false,
		'supports'            => false,
		'public'              => false,
		'exclude_from_search' => true,
		'has_archive'         => false,
		'query_var'           => false,
		'can_export'          => false,
		'rewrite'             => false,
		'capability_type'     => 'post',
		'capabilities'        => array(
			'create_posts' => 'do_not_allow',
		),
		'map_meta_cap'        => true,
	) );
}

add_action( 'init', 'wcsn_register_post_types' );


/**
 * Show Admin Bar Notification Label
 *
 * @return bool|string
 * @since 1.0.0
 *
 */

function wcsn_admin_bar_notification_label() {

	global $wpdb;

	$show_notification = wcsn_get_settings( 'wsn_admin_bar_notification', 'on', 'wsn_notification_settings' );

	if ( 'off' == $show_notification ) {
		return false;
	}

	if ( wcsn_get_notifications( array(), true ) > 0 ) {
		return '<span class="wsn_admin_bar_notification"></span>';
	}

	return false;
}

add_filter( 'wcsn_admin_bar_notification_label', 'wcsn_admin_bar_notification_label' );

/**
 * Render admin bar notification list
 *
 * @param $html
 * @param $email_notification
 *
 * @return bool|false|string
 */

function wcsn_render_notification_list( $email_notification = false ) {

	$show_notification = wcsn_get_settings( 'wsn_admin_bar_notification', 'on', 'wsn_notification_settings' );

	if ( 'on' != $show_notification ) {
		return false;
	}

	$ids = wcsn_get_notifications();

	if ( ! empty( $ids ) ) {
		ob_start();
		wc_get_template( 'notification-list.php', array(
			'ids'                => $ids,
			'email_notification' => $email_notification,
		), '', WC_SERIAL_NUMBERS_INCLUDES . '/admin/notification/' );
		$html = ob_get_clean();

		return $html;
	} else {
		return false;
	}

}

add_filter( 'wcsn_admin_bar_notification_list', 'wcsn_render_notification_list' );

/**
 * Update Notification on serial number created and update
 *
 * @param $serial_id
 * @param $product_id
 *
 * @since 1.0.0
 *
 */

function wcsn_update_notification_list( $serial_id = false, $product_id = false ) {
	if ( ! $product_id ) {
		return;
	}

	$is_exists = get_page_by_title( $product_id, OBJECT, 'wcsn_notification' );

	if ( 'yes' != get_post_meta( $product_id, '_is_serial_number', true ) ) {
		if ( $is_exists && 'publish' === $is_exists->post_status ) {
			wp_update_post( array(
				'ID'             => $is_exists->ID,
				'post_content'   => 0,
				'post_status'    => 'draft',
				'comment_status' => 'disable',
			) );
		}

		return;
	}

	$available_numbers = wcsn_get_serial_numbers( array( 'status' => 'new', 'product_id' => $product_id ), true );

	$show_number = wcsn_get_settings( 'wsn_admin_bar_notification_number', 5, 'wsn_notification_settings' );


	$skip_notification = apply_filters( 'wcsn_skip_notification', false, $product_id, $available_numbers, $show_number );

	if ( $skip_notification ) {
		if ( $is_exists ) {
			wp_update_post( array(
				'ID'             => $is_exists->ID,
				'post_content'   => $available_numbers,
				'post_status'    => 'draft',
				'comment_status' => 'disable',
			) );
		}

		return;
	}

	if ( $available_numbers >= $show_number ) {

		if ( $is_exists ) {
			wp_update_post( array(
				'ID'             => $is_exists->ID,
				'post_content'   => $available_numbers,
				'post_status'    => 'draft',
				'comment_status' => 'disable',
			) );
		}

		return;
	}

	if ( $is_exists ) {
		wp_update_post( array(
			'ID'             => $is_exists->ID,
			'post_content'   => $available_numbers,
			'post_status'    => 'publish',
			'comment_status' => 'enable',
		) );

		return;
	}

	wp_insert_post( array(
		'post_type'      => 'wcsn_notification',
		'post_title'     => $product_id,
		'post_content'   => $available_numbers,
		'post_status'    => 'publish',
		'comment_status' => 'enable',
	) );

	return;
}

function wcsn_run_notification_check() {
	//set value true for only get product which have relation with serial number
	$products = wcsn_get_product_list( true );


	if ( ! empty( $products ) ) {
		$product_ids        = array_keys( $products );
		$check_notification = new WCSN_Automatic_Notification();

		foreach ( $product_ids as $product_id ) {
			$check_notification->push_to_queue( $product_id );
		}

		$check_notification->save()->dispatch();
	}
}

add_action( 'wcsn_per_minute_event', 'wcsn_run_notification_check' );

/**
 * Send Serial Numbers stock notification to email
 * this email send daily
 *
 * @since 1.0.0
 */

function wcsn_send_notification_to_email() {

	$send_notification = wcsn_get_settings( 'wsn_admin_bar_notification_send_email', 'on', 'wsn_notification_settings' );
	if ( 'on' !== $send_notification ) {
		return false;
	}
	$message = wcsn_render_notification_list( true );

	if ( $message === false ) {
		exit();
	}

	global $woocommerce;

	$to = wcsn_get_settings( 'wsn_admin_bar_notification_email', get_option( 'admin_email' ), 'wsn_notification_settings' );

	$subject = __( 'Serial Numbers stock running low', 'wc-serial-numbers' );

	$headers = apply_filters( 'woocommerce_email_headers', '', 'rewards_message' );

	$heading = __( 'Please add more serial number for the following items', 'wc-serial-numbers' );

	$mailer = $woocommerce->mailer();

	$message = $mailer->wrap_message( $heading, $message );

	$mailer->send( $to, $subject, $message, $headers, array() );

	exit();

}

add_action( 'wcsn_daily_event', 'wcsn_send_notification_to_email' );

function wcsn_admin_bar_notification_styles() { ?>
	<style>
		#wp-admin-bar-wsn-wc-serial-numbers .wsn_admin_bar_notification {
			padding-right: 25px
		}

		#wp-admin-bar-wsn-wc-serial-numbers .ever-notification {
			position: absolute;
			right: 3px;
			top: 0
		}

		#wp-admin-bar-wsn-wc-serial-numbers .ever-notification > .alert {
			background: #fff;
			padding: 0 5px 0 3px;
			border-radius: 5px;
			color: red;
			cursor: pointer
		}

		#wp-admin-bar-wsn-wc-serial-numbers .ever-notification:hover + .ever-notification-list {
			display: -webkit-box;
			display: -webkit-flex;
			display: flex
		}

		#wp-admin-bar-wsn-wc-serial-numbers .ever-notification-list {
			position: fixed;
			color: #f0fafe;
			background: #333;
			display: none;
			-webkit-box-orient: vertical;
			-webkit-box-direction: normal;
			-webkit-flex-direction: column;
			flex-direction: column;
			z-index: 999999;
			margin: -1px 0 0 -10px;
			max-height: 100%;
			overflow-y: scroll
		}

		#wp-admin-bar-wsn-wc-serial-numbers .ever-notification-list:hover {
			display: -webkit-box;
			display: -webkit-flex;
			display: flex
		}

		#wp-admin-bar-wsn-wc-serial-numbers .ever-notification-list.alert > li {
			border-left: 5px solid red;
			padding: 0 15px 0 10px;
			margin: 5px 0;
			font-size: 14px
		}

		#wp-admin-bar-wsn-wc-serial-numbers .ever-notification-list.alert > li > a {
			display: inline;
			padding: 0
		}
	</style>

<?php }

add_action( 'admin_head', 'wcsn_admin_bar_notification_styles' );
add_action( 'wp_head', 'wcsn_admin_bar_notification_styles' );

function wcsn_set_stock_for_serial_number( $value, $product ) {
	if ( $product->managing_stock() && wcsn_is_serial_number_enabled( $product->get_id() ) && ! wcsn_is_key_source_automatic( $product->get_id() ) ) {
		$total_serials = wcsn_get_serial_numbers( array(
			'product_id' => $product->get_id(),
			'number'     => - 1,
			'status'     => 'new',
		), true );

		$total_serials = intval( $total_serials );

		return $total_serials;
	}

	return $value;
}

add_filter( 'woocommerce_product_get_stock_quantity', 'wcsn_set_stock_for_serial_number', 10, 2 );

/**
 * @param $product_id
 *
 * @since 1.1.1
 *
 */
function wcsn_notification_after_create_number( $product_id ) {
	wcsn_update_notification_list( false, $product_id );
}

add_action( 'wcsn_serial_number_created', 'wcsn_notification_after_create_number' );

/**
 * Support WooCommerce PDF Invoices & Packing Slips plugin
 *
 * @param $type
 * @param $order
 *
 * @return string
 * @since 1.1.1
 *
 */
function wcsn_add_serial_numner_list( $type, $order ) {
	global $post;
	$order_id       = $order->get_id();
	$serial_numbers = wcsn_get_serial_numbers( [ 'order_id' => $order_id, 'number' => - 1 ] );
	if ( empty( $serial_numbers ) ) {
		return '';
	}
	?>
	<table class="order-details">
		<thead>
		<tr>
			<th class="product"><?php _e( 'Product', 'wc-serial-numbers' ); ?></th>
			<th class="quantity"><?php _e( 'Serial Number', 'wc-serial-numbers' ); ?></th>
			<th class="quantity"><?php _e( 'Activation Limit', 'wc-serial-numbers' ); ?></th>
			<th class="quantity"><?php _e( 'Expire Date', 'wc-serial-numbers' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $serial_numbers as $serial_number ): ?>
			<tr>
				<td><?php echo get_the_title( $serial_number->product_id ); ?></td>
				<td><?php echo wcsn_decrypt( $serial_number->serial_key ); ?></td>
				<td><?php echo ( $serial_number->activation_limit ) ? $serial_number->activation_limit : __( 'N/A', 'wc-serial-numbers' ); ?></td>
				<td><?php echo wcsn_get_serial_expiration_date( $serial_number ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

add_action( 'wpo_wcpdf_before_order_details', 'wcsn_add_serial_numner_list', 10, 2 );

/**
 * Support WooCommerce PDF Invoices, Packing Slips, Delivery Notes & Shipping Labels plugin
 *
 * @param $find_replace
 * @param $html
 * @param $template_type
 * @param $order
 * @param $box_packing
 * @param $order_package
 *
 * @return array
 * @since 1.1.1
 *
 */
function wcsn_wf_module_add_serial_numner_list( $find_replace, $html, $template_type, $order, $box_packing, $order_package ) {
	if ( isset( $find_replace['[wfte_product_table_start]'] ) ) {
		global $post;
		$order_id       = $order->id;
		$serial_numbers = wcsn_get_serial_numbers( [ 'order_id' => $order_id, 'number' => - 1 ] );
		if ( empty( $serial_numbers ) ) {
			return $find_replace;
		}
		ob_start();
		?>
		<table class="wfte_product_table wcsn-pdf-table">
			<thead class="wfte_product_table_head wfte_table_head_color wfte_product_table_head_bg wfte_text_center">
			<tr>
				<th class="product"><?php _e( 'Product', 'wc-serial-numbers' ); ?></th>
				<th class="quantity"><?php _e( 'Serial Number', 'wc-serial-numbers' ); ?></th>
				<th class="quantity"><?php _e( 'Activation Limit', 'wc-serial-numbers' ); ?></th>
				<th class="quantity"><?php _e( 'Expire Date', 'wc-serial-numbers' ); ?></th>
			</tr>
			</thead>
			<tbody class="wfte_payment_summary_table_body wfte_table_body_color">
			<?php foreach ( $serial_numbers as $serial_number ): ?>
				<tr>
					<td><?php echo get_the_title( $serial_number->product_id ); ?></td>
					<td><?php echo wcsn_decrypt( $serial_number->serial_key ); ?></td>
					<td><?php echo ( $serial_number->activation_limit ) ? $serial_number->activation_limit : __( 'N/A', 'wc-serial-numbers' ); ?></td>
					<td><?php echo wcsn_get_serial_expiration_date( $serial_number ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<style type="text/css">
			.wfte_product_table.wcsn-pdf-table {
				margin-bottom: 30px;
			}
		</style>
		<?php
		$find_replace['[wfte_product_table_start]'] = ob_get_clean();
	}

	return $find_replace;
}

add_filter( 'wf_module_generate_template_html', 'wcsn_wf_module_add_serial_numner_list', 10, 6 );

/**
 * WooCommerce PDF Invoices
 *
 * @param $headers
 * @param $order_id
 *
 * @return string
 * @since 1.1.1
 *
 */
function wcsn_woocommerce_invoice( $headers, $order_id ) {
	$serial_numbers = wcsn_get_serial_numbers( [ 'order_id' => $order_id, 'number' => - 1 ] );
	if ( empty( $serial_numbers ) ) {
		return $headers;
	}
	ob_start();
	?>
	<table class="shop_table orderdetails" width="100%">
		<thead>
		<tr>
			<th colspan="7" align="left"><h2><?php _e( 'Serial Number', 'wc-serial-numbers' ); ?></h2></th>
		</tr>
		<tr>
			<th class="product"><?php _e( 'Product', 'wc-serial-numbers' ); ?></th>
			<th class="quantity"><?php _e( 'Serial Number', 'wc-serial-numbers' ); ?></th>
			<th class="quantity"><?php _e( 'Activation Limit', 'wc-serial-numbers' ); ?></th>
			<th class="quantity"><?php _e( 'Expire Date', 'wc-serial-numbers' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $serial_numbers as $serial_number ): ?>
			<tr>
				<td><?php echo get_the_title( $serial_number->product_id ); ?></td>
				<td><?php echo wcsn_decrypt( $serial_number->serial_key ); ?></td>
				<td><?php echo ( $serial_number->activation_limit ) ? $serial_number->activation_limit : __( 'N/A', 'wc-serial-numbers' ); ?></td>
				<td><?php echo wcsn_get_serial_expiration_date( $serial_number ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php
	$content = ob_get_clean();

	return $content . $headers;
}

add_filter( 'pdf_template_table_headings', 'wcsn_woocommerce_invoice', 10, 2 );



