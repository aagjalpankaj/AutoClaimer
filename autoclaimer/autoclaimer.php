<?php

/**
 * Plugin Name: AutoClaimer
 * Plugin URI: http://thinkatat.com/
 * Description: An add-on for WP Job Manager WooCommerce Paid Listings which automatically claims listings once purchased through specified package.
 * Version: 1.0.4
 * Author: thinkatat
 * Author URI: https://thinkatat.com/
 * Text Domain: autoclaimer
 * Domain Path: /languages/
 */

namespace thinkatat\autoclaimer;

defined('ABSPATH') || exit('This is not the way to call me.');

define('ATACR_TD', 'autoclaimer');
define('ATACR_DIR', dirname(__FILE__));
define('ATACR_URL', plugins_url('', __FILE__));

/**
* 
*/
class Main
{
	private static $instance = null;

	public static $version = '1.0.4';
	
	function __construct()
	{
		add_action('plugins_loaded', array($this, 'loadTextDomain'));

		add_action('woocommerce_product_options_general_product_data', array($this, 'displaySettings'));
        add_action('save_post_product', array($this, 'saveSettings'), 111, 1);

        add_action('job_manager_job_submitted', array($this, 'claimListings'), 111, 1);
        add_action('wcpl_process_package_for_job_listing', array($this, 'listingProcessed'), 111, 3);
	}

	public static function getInstance()
	{
        if (null === self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function loadTextDomain()
    {
    	load_plugin_textdomain(ATACR_TD, false, ATACR_DIR . '/languages');
    }

    public function displaySettings()
    {	
		global $post;

        echo '<div class="options_group show_if_job_package" style="display: block;">';
        woocommerce_wp_checkbox(
         	array(
         		'id' 			=> 'atacr_autoclaim',
         		'wrapper_class' => 'show_if_job_package',
         		'label' 		=> __('Claim Listings?', ATACR_TD),
         		'description' 	=> __('Automatically claim listings once purchased through this package.', ATACR_TD),
         		'value' 		=> get_post_meta($post->ID, 'atacr_autoclaim', true),
         		'desc_tip' 		=> false
         	)
         );
        wp_nonce_field('atacr_save_settings', 'atacr_nonce');
        echo '</div>';
	}

	public function saveSettings($post_id)
	{
		global $_POST;

		//Security check 1.
		if (!current_user_can('manage_options')) {
			return;
		}

		//Security check 2.
		if (!isset($_POST['atacr_nonce']) || !wp_verify_nonce($_POST['atacr_nonce'], 'atacr_save_settings')) {
			return;
		}

		if (isset($_POST['product-type']) && $_POST['product-type'] === 'job_package') {
			if ( isset($_POST['atacr_autoclaim']) ) {
				update_post_meta($post_id, 'atacr_autoclaim', sanitize_text_field($_POST['atacr_autoclaim']));
			}
		}
	}

	public function claimListings($listing_id)
	{
		$package_id = get_post_meta($listing_id, '_package_id', true);
		$auto_claim = get_post_meta($package_id, 'atacr_autoclaim', true);

		if ($auto_claim) {
			update_post_meta($listing_id, '_claimed', '1');
		}
	}

	public function listingProcessed($_package_id, $_is_user_package, $_job_id)
    {
        $package_id = get_post_meta($_job_id, '_package_id', true);
        $auto_claim = get_post_meta($package_id, 'atacr_autoclaim', true);

        if ($auto_claim) {
            update_post_meta($_job_id, '_claimed', '1');
        }
    }
}

$GLOBALS['thinkatat_autoclaimer'] = \thinkatat\autoclaimer\Main::getInstance();
