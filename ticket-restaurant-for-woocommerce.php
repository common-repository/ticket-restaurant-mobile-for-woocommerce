<?php
/**
 * Plugin Name: Ticket Restaurant Mobile for WooCommerce
 * Plugin URI: https://ticket.pt/
 * Description: This plugin allows Ticket Restaurant payments in WooCommerce
 * Version: 1.0.0
 * Author: PayCritical
 * Author URI: https://paycritical.com/
 * Text Domain: ticket-restaurant-mobile-for-woocommerce
 * Domain Path: /lang
 * WC tested up to: 4.3.2
 **/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('WC_TicketRestaurant')) :
	class WC_TicketRestaurant
	{
		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		const VERSION = '1.0.0';

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Initialize the plugin public actions.
		 */
		private function __construct()
		{
			// Load plugin text domain
			add_action('init', array($this, 'load_plugin_textdomain'));

			// Checks with WooCommerce is installed.
			if (class_exists('WC_Payment_Gateway')) {
				$this->includes();

				add_filter('woocommerce_payment_gateways', array($this, 'add_gateway'));

				add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));

				add_action('add_meta_boxes', array($this, 'ticketrestaurant_order_add_meta_box'), 10, 2);

				add_filter('woocommerce_order_data_store_cpt_get_orders_query', array($this, 'handle_custom_query_var'), 10, 2);

				// Set Callback.
				new WC_TicketRestaurant_Callback();
			} else {
				add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
			}

			add_action('wp_ajax_resend_payment_notification', array($this, 'resend_payment_notification'));
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance()
		{
			// If the single instance hasn't been set, set it now.
			if (null == self::$instance) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Get templates path.
		 *
		 * @return string
		 */
		public static function get_templates_path()
		{
			return plugin_dir_path(__FILE__) . 'templates/';
		}

		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain()
		{
			load_plugin_textdomain('ticket-restaurant-mobile-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages/');
		}

		/**
		 * Action links.
		 *
		 * @param  array $links
		 *
		 * @return array
		 */
		public function plugin_action_links($links)
		{
			$plugin_links = [
				'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=ticketrestaurant_mobile')) . '">' . __('Settings', 'ticket-restaurant-mobile-for-woocommerce') . '</a>',
			];

			return array_merge($plugin_links, $links);
		}

		/**
		 * Includes.
		 */
		private function includes()
		{
			include_once 'includes/class-wc-ticketrestaurant-api.php';
			include_once 'includes/class-wc-ticketrestaurant-mobile.php';
			include_once 'includes/class-wc-ticketrestaurant-callback.php';
		}

		/**
		 * Add the gateway to WooCommerce.
		 *
		 * @param   array $methods WooCommerce payment methods.
		 *
		 * @return  array          Payment methods with TicketRestaurant.
		 */
		public function add_gateway($methods)
		{
			$methods[] = 'WC_TicketRestaurant_Mobile';

			return $methods;
		}

		/* Order metabox to show Multibanco payment details */
		public function ticketrestaurant_order_add_meta_box($post_type, $post)
		{
			if ($post_type !== 'shop_order') return;
			$order = wc_get_order($post->ID);
			if ($order->get_payment_method() !== 'ticketrestaurant_mobile') return;

			add_meta_box(
				'woocommerce_ticketrestaurant',
				__('Ticket Restaurant Mobile Payment Details', 'ticket-restaurant-mobile-for-woocommerce'),
				array($this, 'mbticketrestaurant_order_meta_box_html'),
				'shop_order',
				'side',
				'core'
			);
		}

		public function mbticketrestaurant_order_meta_box_html($post)
		{
			include 'includes/views/order-meta-box.php';
		}

		/* WooCommerce fallback notice. */
		public function woocommerce_missing_notice()
		{
			echo '<div class="error"><p>' . sprintf(__('Ticket Restaurant Mobile for WooCommerce Gateway depends on the last version of %s to work!', 'ticket-restaurant-mobile-for-woocommerce'), '<a href="https://wordpress.org/plugins/woocommerce/">' . __('WooCommerce', 'ticket-restaurant-mobile-for-woocommerce') . '</a>') . '</p></div>';
		}

		public function woocommerce_payment_complete_reduce_order_stock($reduce, $order, $payment_method, $stock_when)
		{
			if ($reduce) {
				// $order = new WC_Order( $order_id );
				if ($order->get_payment_method() == $payment_method) {
					if (version_compare(WC_VERSION, '3.4.0', '>=')) {
						//After 3.4.0
						if ($order->has_status(array('pending', 'on-hold'))) {
							//Pending payment
							return $stock_when == 'order' ? true : false;
						} else {
							//Payment done
							return $stock_when == '' ? true : false;
						}
					} else {
						//Before 3.4.0 - This only runs for paid orders
						return $stock_when == 'order' ? true : false;
					}
				} else {
					return $reduce;
				}
			} else {
				//Already reduced
				return false;
			}
		}

		function handle_custom_query_var($query, $query_vars)
		{
			if (!empty($query_vars['ticketrestaurant_payment_id'])) {
				$query['meta_query'][] = array(
					'key' => '_ticketrestaurant_payment_id',
					'value' => esc_attr($query_vars['ticketrestaurant_payment_id']),
				);
			}

			return $query;
		}

		function resend_payment_notification()
		{
			$api = new WC_TicketRestaurant_API();
			$result = $api->resendPayment( sanitize_text_field( $_REQUEST['order_id'] ) );
		// echo $result;
			if ( $result !== 200 ) {
				wp_send_json_error();
				wp_die();
			}
			
			wp_send_json_success();
			wp_die();
			// wp_die(); // this is required to terminate immediately and return a proper response
		}

	}

	add_action('plugins_loaded', array('WC_TicketRestaurant', 'get_instance'));

endif;

/**
 * Activate the plugin.
 */
function ticketrestaurant_generate_webhook_auth_code()
{
	$notify_code = get_option('ticketrestaurant_mobile_auth_code');
	if (null == $notify_code) {
		update_option('ticketrestaurant_mobile_auth_code', base64_encode(home_url() . ':' . date('Y-m-d')));
	}
}
register_activation_hook(__FILE__, 'ticketrestaurant_generate_webhook_auth_code');
