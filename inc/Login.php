<?php
namespace MineMetamask;
require_once MineMetamask_PATH."/vendor/autoload.php";
class Login
{
    private $login_nonce_key = 'metamask-login';
    public function __construct()
    {
        add_action( 'login_form', [ $this, 'register_scripts' ], 9 );
        
        add_action('wp_ajax_metamask_login_nonce', [ $this, 'metamask_login_nonce' ]);
        add_action('wp_ajax_nopriv_metamask_login_nonce', [ $this, 'metamask_login_nonce' ]);
        
        add_action('wp_ajax_metamask_login_check', [ $this, 'metamask_login_check' ]);
        add_action('wp_ajax_nopriv_metamask_login_check', [ $this, 'metamask_login_check' ]);
    }

    public function register_scripts(){
        echo '<p><a id="metamask-login-button" href="javascript:;"><img src="'.MineMetamask_URL.'/static/img/metamask.jpg" style="width:28px;" /></a></p>';
        
        wp_register_script(
            'mine-metamask-layer',
            MineMetamask_URL.'/static/layer/layer.js',
            [ 'jquery' ],
            MineMetamask_Version,
            true
        );
        wp_register_script(
            'mine-metamask-login',
            MineMetamask_URL.'/dist/login.build.js',
            [ 'jquery', 'mine-metamask-login-layer' ],
            MineMetamask_Version,
            true
        );
        wp_enqueue_script('jquery');
        wp_enqueue_script( 'mine-metamask-layer' );
        wp_enqueue_script( 'mine-metamask-login' );
        wp_add_inline_script('mine-metamask-login', 'const metamask_login_config={ajaxUrl:"'.admin_url("admin-ajax.php").'",nonce:"'.wp_create_nonce($this->login_nonce_key).'"};');
    }

    
    // 返回 授权登录用的 nonce
    function metamask_login_nonce(){
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, $this->login_nonce_key)) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $account = sanitize_user($_POST['account']);
        $is_user = username_exists($account);
        if(!$is_user){
            $tnonce = 1;
        }
        else{
            $tnonce = get_user_meta($is_user, 'login_nonce', true);
            if(!$tnonce) $tnonce = 1;
        }
        echo json_encode(['nonce' => sprintf(__('I am signing my one-time nonce(%s) to login this website.', 'mine-metamask'), $tnonce )]);
        exit;
    }

    //验证授权数据是否正确，验证成功注册并登录
    function metamask_login_check(){
        $redirect_to   = !empty(sanitize_text_field($_GET['redirect_to'])) ? sanitize_text_field($_GET['redirect_to']) : get_site_url('', '/wp-admin/');
        $nonce   = !empty($_POST['nonce']) ? $_POST['nonce'] : null;
        if ($nonce && !wp_verify_nonce($nonce, $this->login_nonce_key)) {
            echo json_encode(array('status' => '0', 'msg' => __('Illegal request', 'mine-cloudvod')));exit;
        }
        $account = sanitize_user($_POST['account']);
        $sign = sanitize_text_field($_POST['sign']);
        $is_user = username_exists($account);
        $nonce = $is_user ? get_user_meta($is_user, 'login_nonce', true) : 1;
        $nonce = $nonce ?: 1;
        $msg = sprintf(__('I am signing my one-time nonce(%s) to login this website.', 'mine-metamask'), $nonce );
        $deAccount = personal_ecRecover($msg, $sign);
        if(strtolower($deAccount) == strtolower($account)){
            if($is_user){
                $this->login_in($is_user);
                update_user_meta($is_user, "login_nonce", $nonce + 1);
                echo json_encode(['login'=>true, 'to' => $redirect_to]);
                exit;
            }else{
                if(!get_option( 'users_can_register' )){
                    echo json_encode(['login'=>false, 'msg' => __('You have not registered on this site.', 'mine-metamask')]);
                    exit;
                }
                $user_id = 0;
                if ( is_multisite() ) {
                    $user_id = wpmu_create_user( $deAccount, wp_generate_password(), '' );
                    if ( ! $user_id ) {
                        echo json_encode(['login'=>false, 'msg' => __('Create user fail', 'mine-metamask')]);
                        exit;
                    }
                }
                else {
                    $user_id = wp_create_user( $deAccount, wp_generate_password() );
                    if ( is_wp_error( $user_id ) ) {
                        echo json_encode(['login'=>false, 'msg' => __('Create user fail', 'mine-metamask')]);
                        exit;
                    }
                }
                $this->login_in($user_id);
                update_user_meta($user_id, "login_nonce", $nonce + 1);
                echo json_encode(['login'=>true, 'to' => $redirect_to]);
                exit;
            }
        }
        else{
            echo json_encode(['login'=>false, 'msg' => __('Signature error', 'mine-metamask')]);
            exit;
        }
        exit;
    }

    function login_in($user_id){
        $user = get_user_by( 'ID', $user_id );
        clean_user_cache( $user_id );
        wp_clear_auth_cookie();

        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, false );
        update_user_caches( $user );
        do_action( 'wp_login', $user->data->user_login, $user );
    }
}
