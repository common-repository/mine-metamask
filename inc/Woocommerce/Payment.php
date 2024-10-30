<?php
namespace MineMetamask\Woocommerce;
class Payment{
    public function __construct()
    {
        /**
         * add new Gateway
         */
        add_filter('woocommerce_payment_gateways', [$this, 'erc20_add_gateway_class']);
        /**
         * listen request
         */
        add_action('init', [$this, 'thankyour_request']);
    }

    public function erc20_add_gateway_class($gateways) {
        $gateways[] = 'MineMetamask\Woocommerce\WC_Token_Gateway';
        return $gateways;
    }
    public function thankyour_request() {
        /**
         * get request
         */
    
        if ($_POST['request']=='request') {

            $order_id = sanitize_text_field($_POST['orderid']);
            $tx = sanitize_text_field($_POST['tx']);

            if (is_int((int)$order_id)  && strlen($tx) == 66 && !preg_match('/[^A-Za-z0-9]/', $tx) ) {
                if (substr($tx,0,2) != '0x'){
                    return ;
                }
                $order = wc_get_order($order_id);
                $order->payment_complete();
                $order->add_order_note(__("payment completed-", 'mine-metamask') . " Transaction Hash:"  . $tx );
                exit();
            }
        }
    }
}