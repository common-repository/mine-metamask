<?php
/**
 * Plugin Name: Mine Metamask
 * Plugin URI:  https://www.zwtt8.com/metamask4wp/
 * Description: Login and Pay with Metamask, take your website enter the era of web3.0
 * Version: 1.0.0
 * Author: mine27
 * Author URI: https://www.zwtt8.com/
 * Text Domain: mine-metamask
 * Domain Path: /languages/
 */
if(!defined('ABSPATH'))exit;

define('MineMetamask_Version', '1.0.0');
define('MineMetamask_PATH', dirname(__FILE__));
define('MineMetamask_URL', plugins_url('', __FILE__));

require_once MineMetamask_PATH.'/autoload.php';
new MineMetamask\Login();
new MineMetamask\Woocommerce\Payment();