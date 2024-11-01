<?php global $woocommerce; ?>
<div id="wc_ticketrestaurant">
  <div id="wc_ticketrestaurant_settings">
    <h3><?php echo $this->method_title; ?> <span style="font-size: 75%;">v.<?php echo WC_TicketRestaurant::VERSION; ?></span></h3>
    <table class="form-table">
      <?php
      $is_valid = true;

      if (trim(get_woocommerce_currency()) !== 'EUR') {
        $is_valid = false;
        echo '<p><strong>' . __('ERROR!', 'ticket-restaurant-mobile-for-woocommerce') . printf(__('Set WooCommerce currency to <strong>Euros (&euro;)</strong> %1$s', 'ticket-restaurant-mobile-for-woocommerce'), '<a href="admin.php?page=wc-settings&tab=general">' . __('here', 'ticket-restaurant-mobile-for-woocommerce') . '</a>.') . '</strong></p>';
      }

      if ( null == get_option('ticketrestaurant_mobile_callback_received' ) ) {
        $notify_url = (get_option('permalink_structure') == '' ? home_url('/') . '?wc-api=WC_TicketRestaurant' : home_url('/') . 'wc-api/WC_TicketRestaurant/');
        $notify_code = get_option('ticketrestaurant_mobile_auth_code');
        
        echo '<div id="message" class="error inline"><p>Ainda não recebeu comunicações de pagamento do Ticket Restaurant Mobile, configure a comunicação via Webhook por forma a que as suas encomendas passem para "Em Processamento" automaticamente após pagamento.<br />
        Utilize o seguinte endereço <code>' . $notify_url . '</code> com o código de autorização <code>' . $notify_code . '</code></p></div>';
      }

      $notify_url = (get_option('permalink_structure') == '' ? home_url('/') . '?wc-api=WC_TicketRestaurant' : home_url('/') . 'wc-api/WC_TicketRestaurant/');

      if ( null === $this->get_option('apikey') || empty( $this->get_option( 'apikey' ) ) ) {
        echo __('Please configure your authorization token in order to activate Ticket Restaurant Mobile gateway', 'ticket-restaurant-mobile-for-woocommerce');
      }

      if ($is_valid) $this->generate_settings_html();
      ?>
    </table>
  </div>
</div>


<div class="clear"></div>