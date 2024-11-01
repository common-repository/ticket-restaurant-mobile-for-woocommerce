<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('WC_TicketRestaurant_Mobile')) {
  class WC_TicketRestaurant_Mobile extends WC_Payment_Gateway
  {

    public function __construct()
    {
      global $woocommerce;
      $this->id = 'ticketrestaurant_mobile';

      load_plugin_textdomain('ticket-restaurant-mobile-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages/');

      $this->icon = plugins_url('images/ticket-restaurant-mobile-icon.png', dirname(__FILE__));
      $this->has_fields = false;
      $this->method_title = __('Ticket Restaurant Mobile', 'ticket-restaurant-mobile-for-woocommerce');

      $this->apikey = $this->get_option('apikey');

      //Plugin options and settings
      $this->init_form_fields();
      $this->init_settings();

      //User settings
      $this->title = $this->get_option('title');
      $this->description = $this->get_option('description');
      $this->instructions = $this->get_option('instructions');
      $this->only_portugal = $this->get_option('only_portugal');
      $this->only_above = $this->get_option('only_above');
      $this->only_below = $this->get_option('only_below');
      $this->stock_when = $this->get_option('stock_when');

      // Set the API.
      $this->api = new WC_TicketRestaurant_API($this);

      // Actions and filters
      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
      if (function_exists('icl_object_id') && function_exists('icl_register_string')) add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'register_wpml_strings'));
      add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
      add_action('woocommerce_order_details_after_order_table', array($this, 'order_details_after_order_table'), 20);

      add_filter('woocommerce_available_payment_gateways', array($this, 'disable_if_no_api'));
      add_filter('woocommerce_available_payment_gateways', array($this, 'disable_unless_portugal'));
      add_filter('woocommerce_available_payment_gateways', array($this, 'disable_only_above_or_below'));

      // Customer Emails
      add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 2);

      // Filter to decide if payment_complete reduces stock, or not
      add_filter('woocommerce_payment_complete_reduce_order_stock', array($this, 'woocommerce_payment_complete_reduce_order_stock'), 10, 2);
    }

    function register_wpml_strings()
    {
      //These are already registered by WooCommerce Multilingual
      /*$to_register=array('title','description',);*/
      $to_register = array();
      foreach ($to_register as $string) {
        icl_register_string($this->id, $this->id . '_' . $string, $this->settings[$string]);
      }
    }

    function init_form_fields()
    {
      if (!isset($this->apikey) || empty($this->apikey)) {
        $this->form_fields = array(
          'apikey' => array(
            'title' => __('Authorization Token', 'ticket-restaurant-mobile-for-woocommerce'),
            'type' => 'text',
            'description' => __('Your authorization token on Ticket Restaurant Mobile', 'ticket-restaurant-mobile-for-woocommerce'),
          ),
        );
      } else {
        $this->form_fields = array(
          'enabled' => array(
            'title' => __('Enable/Disable', 'woocommerce'),
            'type' => 'checkbox',
            'label' => __('Enable Ticket Restaurant Mobile', 'ticket-restaurant-mobile-for-woocommerce'),
            'default' => 'no'
          ),
          'apikey' => array(
            'title' => __('Authorization Token', 'ticket-restaurant-mobile-for-woocommerce'),
            'type' => 'text',
            'description' => __('Your authorization token on Ticket Restaurant Mobile', 'ticket-restaurant-mobile-for-woocommerce'),
          ),
          'title' => array(
            'title' => __('Title', 'woocommerce'),
            'type' => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
            'default' => __('Ticket Restaurant Mobile', 'ticket-restaurant-mobile-for-woocommerce')
          ),
          'description' => array(
            'title' => __('Description', 'woocommerce'),
            'type' => 'textarea',
            'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
            'default' => __('Pay with Ticket Restaurant Mobile', 'ticket-restaurant-mobile-for-woocommerce')
          ),
          'instructions' => array(
            'title'       => __('Instructions', 'ticket-restaurant-mobile-for-woocommerce'),
            'type'        => 'textarea',
            'description' => __('Instructions that will be added to the thank you page and email sent to customer.', 'ticket-restaurant-mobile-for-woocommerce'),
            'default'     => __('Authorize payment on your Ticket Restaurant Mobile app', 'ticket-restaurant-mobile-for-woocommerce')
          ),
          'only_portugal' => array(
            'title' => __('Only for Portuguese customers?', 'ticket-restaurant-mobile-for-woocommerce'),
            'type' => 'checkbox',
            'label' => __('Enable only for customers whose address is in Portugal', 'ticket-restaurant-mobile-for-woocommerce'),
            'default' => 'no'
          ),
          'only_above' => array(
            'title' => __('Only for orders above', 'ticket-restaurant-mobile-for-woocommerce'),
            'type' => 'number',
            'description' => __('Enable only for orders above x &euro; (exclusive). Leave blank (or zero) to allow for any order value.', 'ticket-restaurant-mobile-for-woocommerce'),
            'default' => ''
          ),
          'only_below' => array(
            'title' => __('Only for orders below', 'ticket-restaurant-mobile-for-woocommerce'),
            'type' => 'number',
            'description' => __('Enable only for orders below x &euro; (exclusive). Leave blank (or zero) to allow for any order value.', 'ticket-restaurant-mobile-for-woocommerce'),
            'default' => ''
          ),
          'stock_when' => array(
            'title' => __('Reduce stock', 'ticket-restaurant-mobile-for-woocommerce'),
            'type' => 'select',
            'description' => __('Choose when to reduce stock.', 'ticket-restaurant-mobile-for-woocommerce'),
            'default' => '',
            'options'  => array(
              ''    => __('when order is paid (requires active callback)', 'ticket-restaurant-mobile-for-woocommerce'),
              'order'  => __('when order is placed (before payment)', 'ticket-restaurant-mobile-for-woocommerce'),
            ),
          )
        );
      }
    }

    public function admin_options()
    {
      include 'views/html-admin-page.php';
    }

    public function get_icon()
    {
      $alt = (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title);
      $icon_html = '<img src="' . esc_attr($this->icon) . '" alt="' . esc_attr($alt) . '" />';
      return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    function check_order_errors($order_id)
    {
      $order = new WC_Order($order_id);
      $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;

      // A loja não está em Euros
      if (trim(get_woocommerce_currency()) != 'EUR') {
        return __('Configuration error. This store currency is not Euros (&euro;).', 'ticket-restaurant-mobile-for-woocommerce');
      }

      //O valor da encomenda não é aceite
      if (($order_total < 0.1) || ($order_total >= 2000)) {
        return __('It\'s not possible to use Ticket Restaurant Mobile to pay values under 0.10&euro; or above 2000&euro;.', 'ticket-restaurant-mobile-for-woocommerce');
      }
      return false;
    }

    public function thankyou_page($order_id)
    {
      $order = new WC_Order($order_id);
      $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
      $payment_method = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method() : $order->payment_method;

      if ($payment_method == $this->id) {

        wc_get_template('payment-instructions.php', array(
          'method' => $payment_method,
          'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
          'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
          'reference' => get_post_meta($order_id, '_ticketrestaurant_reference', true),
          'phone_number' => get_post_meta($order_id, '_ticketrestaurant_phone_number', true),
          'order_total' => $order_total,
          'order_id' => $order_id,
        ), 'woocommerce/ticket-restaurant/', WC_TicketRestaurant::get_templates_path());
      }
    }

    function order_details_after_order_table($order)
    {
      if (is_wc_endpoint_url('view-order')) {
        $this->thankyou_page($order->get_id());
      }
    }

    function email_instructions($order, $sent_to_admin, $plain_text = false)
    {
      $order_id = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_id() : $order->id;
      $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
      $payment_method = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method() : $order->payment_method;

      if ($sent_to_admin || !$order->has_status('on-hold') || $this->id !== $payment_method) {
        return;
      }

      if ($plain_text) {
        wc_get_template('emails/plain-instructions.php', array(
          'method' => $payment_method,
          'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
          'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
          'reference' => get_post_meta($order_id, '_ticketrestaurant_reference', true),
          'phone_number' => get_post_meta($order_id, '_ticketrestaurant_phone_number', true),
          'order_total' => $order_total,
        ), 'woocommerce/ticket-restaurant/', WC_TicketRestaurant::get_templates_path());
      } else {
        wc_get_template('emails/html-instructions.php', array(
          'method' => $payment_method,
          'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
          'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
          'reference' => get_post_meta($order_id, '_ticketrestaurant_reference', true),
          'phone_number' => get_post_meta($order_id, '_ticketrestaurant_phone_number', true),
          'order_total' => $order_total,
        ), 'woocommerce/ticket-restaurant/', WC_TicketRestaurant::get_templates_path());
      }
    }

    function payment_fields()
    {
      if ($description = $this->get_description()) {
        echo wpautop(wptexturize($description));
      }

      $this->ticketrestaurant_mobile_form();
    }

    function ticketrestaurant_mobile_form()
    {
      $user = wp_get_current_user();
      if ($user->ID) {
        $user_phone = get_user_meta($user->ID, 'billing_phone', true);
      }
?>
      <fieldset id="wc-<?php echo esc_attr($this->id); ?>-ticketrestaurant-mobile-form" class="wc-ticketrestaurant-mobile-form wc-payment-form" style="background:transparent;">
        <p class="form-row form-row-wide">
          <label for="ticketrestaurant_phone"><?php esc_html_e('Phone number registered on Ticket Restaurant Mobile', 'ticket-restaurant-mobile-for-woocommerce'); ?></label>
          <input type="tel" id="ticketrestaurant_phone" autocorrect="off" spellcheck="false" name="ticketrestaurant_phone" class="input-text" aria-label="<?php _e('Phone number registered on Ticket Restaurant Mobile', 'ticket-restaurant-mobile-for-woocommerce'); ?>" placeholder="<?php _e('If different of billing phone', 'ticket-restaurant-mobile-for-woocommerce'); ?>" aria-placeholder="" aria-invalid="false" value="<?php echo $user_phone; ?>" />
          <span class="help-text"><small><?php _e('Fill in, if different from the billing phone.', 'ticket-restaurant-mobile-for-woocommerce'); ?></small></span>
        </p>
        <div class="clear"></div>
      </fieldset>
<?php
    }

    function process_payment($order_id)
    {
      global $woocommerce;
      $order = new WC_Order($order_id);
      $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
      $billing_phone = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_billing_phone() : $order->billing_phone;
      $ticketrestaurant_phone = isset($_POST['ticketrestaurant_phone']) && !empty($_POST['ticketrestaurant_phone']) ? $this->parsePhone($_POST['ticketrestaurant_phone']) : $billing_phone;

      if ($error_message = $this->check_order_errors($order_id)) {
        wc_add_notice(__('Payment error:', 'ticket-restaurant-mobile-for-woocommerce') . $error_message, 'error');
        return;
      }

      if (!isset($ticketrestaurant_phone) || empty($ticketrestaurant_phone)) {
        $error_message = __('Invalid phone number', 'ticket-restaurant-mobile-for-woocommerce');
        wc_add_notice(__('Payment error:', 'ticket-restaurant-mobile-for-woocommerce') . ' ' . $error_message, 'error');
        return;
      }

      $api_request = $this->api->requestPayment($order_id, $order_total, $ticketrestaurant_phone);
      if (isset($api_request->code)) {
        $error_message = $api_request->code . ' - ' . $api_request->description;
        wc_add_notice(__('Payment error:', 'ticket-restaurant-mobile-for-woocommerce') . ' ' . $error_message, 'error');
        return;
      }

      update_post_meta($order_id, '_ticketrestaurant_payment_id', $api_request->paymentId);
      update_post_meta($order_id, '_ticketrestaurant_phone_number', $ticketrestaurant_phone);
      update_post_meta($order_id, '_ticketrestaurant_reference', $api_request->paymentHumanId);

      // Mark as on-hold
      $order->update_status('on-hold', __('Awaiting Ticket Restaurant Mobile payment.', 'ticket-restaurant-mobile-for-woocommerce'));

      // Remove cart
      $woocommerce->cart->empty_cart();

      // Empty awaiting payment session
      if (isset($_SESSION['order_awaiting_payment'])) unset($_SESSION['order_awaiting_payment']);

      // Return thankyou redirect
      return array(
        'result' => 'success',
        'redirect' => $this->get_return_url($order)
      );
    }

    function parsePhone($phone)
    {
      // Remove Spaces, Indicative, Letters    
      $phone = str_replace(" ", "", $phone);
      // Check indicative options
      $phone = str_replace("+351", "", $phone);
      if (strpos($phone, "351") === 0) {
        $phone = substr($phone, 3);
      }
      if (strpos($phone, "00351") === 0) {
        $phone = substr($phone, 5);
      }
      // Remove letters
      $phone = preg_replace("/[^0-9]/", "", $phone);

      // Check if it has 9 digits
      // Check if it starts with 9
      if (strlen($phone) != 9 || $phone[0] != "9") {
        return false;
      }

      // Add country indicate +351
      $phone = "+351" . $phone;
      return $phone;
    }

    function disable_unless_portugal($available_gateways)
    {
      if (!is_admin()) {
        $country = version_compare(WC_VERSION, '3.0', '>=') ? WC()->customer->get_billing_country() : WC()->customer->get_country();
        if (isset($available_gateways[$this->id])) {
          if ($available_gateways[$this->id]->only_portugal == 'yes' && trim($country) != 'PT') {
            unset($available_gateways[$this->id]);
          }
        }
      }
      return $available_gateways;
    }

    function disable_if_no_api($available_gateways)
    {
      if (!isset($this->apikey) || empty($this->apikey)) {
        if (isset($available_gateways[$this->id])) {
          unset($available_gateways[$this->id]);
        }
      }

      return $available_gateways;
    }

    function disable_only_above_or_below($available_gateways)
    {
      global $woocommerce;
      if (isset($available_gateways[$this->id])) {
        if (@floatval($available_gateways[$this->id]->only_above) > 0) {
          if ($woocommerce->cart->total < floatval($available_gateways[$this->id]->only_above)) {
            unset($available_gateways[$this->id]);
          }
        }
        if (@floatval($available_gateways[$this->id]->only_below) > 0) {
          if ($woocommerce->cart->total > floatval($available_gateways[$this->id]->only_below)) {
            unset($available_gateways[$this->id]);
          }
        }
      }
      return $available_gateways;
    }

    function payment_complete($order, $txn_id = '', $note = '')
    {
      $order->add_order_note($note);
      $order->payment_complete($txn_id);
    }

    /* Reduce stock on 'wc_maybe_reduce_stock_levels'? */
    function woocommerce_payment_complete_reduce_order_stock($bool, $order_id)
    {
      $order = new WC_Order($order_id);
      if ($order->get_payment_method() == $this->id) {
        return (WC_TicketRestaurant::woocommerce_payment_complete_reduce_order_stock($bool, $order, $this->id, $this->stock_when));
      } else {
        return $bool;
      }
    }
  }
} // class_exists()
