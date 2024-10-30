<?php
namespace MineMetamask\Woocommerce;

class WC_Token_Gateway extends \WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'metamask_payment_gateway';
        $this->method_title = __('Pay with Metamask', 'mine-metamask');
        $this->order_button_text = __('Pay with Metamask', 'mine-metamask');
        $this->method_description = __('If you want to use this Payment Gateway', 'mine-metamask');

        $this->supports = array(
            'products',
        );

        $this->init_settings();
        $this->init_form_fields();

        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('wp_enqueue_scripts', [$this, 'payment_scripts']);
        add_action('woocommerce_api_compete', [$this, 'webhook']);
        add_action('admin_notices', [$this, 'do_ssl_check']);
        add_action('woocommerce_thankyou', [$this, 'thankyou_page']);

    }

    /**
     * 插件设置项目
     */
    public function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable', 'mine-metamask'),
                'label' => __('Enable this payment method', 'mine-metamask'),
                'type' => 'checkbox',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'mine-metamask'),
                'type' => 'text',
                'description' => __('Title Will Show at Checkout Page', 'mine-metamask'),
                'default' => 'Metamask Payment Gateway',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'mine-metamask'),
                'type' => 'textarea',
                'description' => __('Description  Will Show at Checkout Page', 'mine-metamask'),
                'default' => __('Please make sure you already install Metamask and enable it.', 'mine-metamask'),
            ),
            'icon' => array(
                'title' => __('Payment icon', 'mine-metamask'),
                'type' => 'text',
                'default' => 'https://tvax3.sinaimg.cn/large/6107c3b3gy1h3eg8pzt60j201c01c0sl.jpg',
                'description' => __('Image Height:25px', 'mine-metamask'),
            ),
            'receive_address' => array(
                'title' => __('Receiving wallet address', 'mine-metamask'),
                'type' => 'text',
                'description' => __('Token will transfer into this wallet address', 'mine-metamask'),
            ),
            'chain' => array(
                'title' => __('Chain ID', 'mine-metamask'),
                'type' => 'select',
                'description' => __('Select a chain', 'mine-metamask'),
                'options' => array(
                    '56' => 'Binance Smart Chain (BSC)',
                    '97' => 'Binance Smart Chain Test Net (BSCTest)'
                ),
            ),
            'token_address' => array(
                'title' => __('Token Address', 'mine-metamask'),
                'type' => 'select',
                'description' => __('Select a token.', 'mine-metamask'),
                'options' => array(
                    '0x55d398326f99059fF775485246999027B3197955' => 'USDT',
                    '0xe9e7CEA3DedcA5984780Bafc599bD69ADd087D56' => 'BUSD',
                    '0xeD24FC36d5Ee211Ea25A80239Fb8C4Cfd80f12Ee' => 'BUSD_TestNet',
                ),
            ),

            'notice' => array(
                'title' => __('Notice', 'mine-metamask'),
                'type' => 'textarea',
                'default' => __('You can buy tokens on this website: ', 'mine-metamask'). '<a href="https://p2p.binance.com/en/express/buy?ref=345559879" target="_blank">p2p.binance.com</a>',
                'description' => __('Notice', 'mine-metamask'),
            ),
        );
    }

    public function payment_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('token_web3', MineMetamask_URL.'/static/web3.min.js', array('jquery'), MineMetamask_Version, true);
        wp_register_script(
            'mine-metamask-layer',
            MineMetamask_URL.'/static/layer/layer.js',
            [ 'jquery' ],
            MineMetamask_Version,
            true
        );
        wp_register_script('token_payments', MineMetamask_URL.'/dist/woocommerce.pay.build.js', array('jquery', 'wp-util', 'wp-i18n', 'token_web3'));
        wp_enqueue_script('wp-util');
        wp_enqueue_script('wp-i18n');
        wp_enqueue_script('mine-metamask-layer');
        wp_enqueue_script('token_payments');
    }

    /**
     *  
     */
    public function validate_fields() {
        return true;
    }

    /**
     * 
     */
    public function process_payment($order_id) {
        global $woocommerce;
        $order = wc_get_order($order_id);
        /**
         *  
         */
        $order->add_order_note(__('create order ,wait for payment', 'mine-metamask'));
        /**
         *  
         */
        $order->update_status('unpaid', __('Wait For Payment', 'mine-metamask'));
        /**
         * reduce stock.
         */
        $order->reduce_order_stock();
        /**
         *  
         */
        WC()->cart->empty_cart();
        /**
         * 
         */
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }
    /**
     * check ssl 
     */
    public function do_ssl_check() {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }
    /**
     *  
     *  
     */
    public function thankyou_page($order_id) {
        /**
         *  
         */
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        /**
         * check order if need payment
         */
        //$order->needs_processing()
        //$order->needs_payment()
        if ($order->needs_payment()) {
            /**
             * show pay button if needs payment.
             */
            
            echo __('<h2>Use Metamask Pay this Order</h2>', 'mine-metamask');
            echo __('Click Button Below, Pay this order.<br>', 'mine-metamask');
            echo '<span style="margin:5px 0px;">' . 'We use this Blockchain Network:' .esc_html($this->chain) . "</span><br>";
            echo '<span style="margin:5px 0px;">' . wp_kses_post($this->notice) . "</span><br>";
            echo '<div><button id="metamask_woo_pay" data-currency="'.esc_attr(get_woocommerce_currency()).'" data-total="' . esc_attr((string) $order->get_total()) . '" data-chain="'. esc_attr($this->chain) .'" data-receive="'.esc_attr($this->receive_address).'" data-token="'.esc_attr($this->token_address).'" data-orderid="'.esc_attr($order_id).'">' . __('Pay with Metamask', 'mine-metamask') . '</button></div>';

        } else {
            echo __('<h2>Your Order is already completed.</h2>', 'mine-metamask');
        }
    }
}