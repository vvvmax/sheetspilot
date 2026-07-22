<?php

/**
 * @package SheetsPilot
 * @author Unlimited Elements
 * @copyright (C) 2026 Unlimited Elements, All Rights Reserved. 
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 **/
/*
Plugin Name: SheetsPilot Pro
Plugin URI: https://sheetspilot.ai
Description: Pro add-on for SheetsPilot. Enables Pro mode and AI assistant features in the core SheetsPilot plugin.
Version: 1.0.5
Update URI: https://api.freemius.com
Requires at least: 6.0
Requires PHP: 7.4
Author: Unlimited Elements
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: sheetspilot
*/
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Show admin notice when SheetsPilot Pro is active without the free plugin.
 */
function sheetspilot_pro_requires_free_notice() {
    echo '<div class="notice notice-error"><p>' .
        esc_html__( 'SheetsPilot Pro requires the free SheetsPilot plugin to be installed and active.', 'sheetspilot' ) .
        '</p></div>';
}

/**
 * Add red inline warning to plugin row actions.
 *
 * @param array $actions Existing plugin action links.
 * @return array
 */
function sheetspilot_pro_requires_free_row_warning( $actions ) {
    $actions['sheetspilot_pro_requires_free'] =
        '<span style="color:#d63638;font-weight:600;">' .
        esc_html__( 'Requires SheetsPilot (free) plugin.', 'sheetspilot' ) .
        '</span>';

    return $actions;
}

if ( !function_exists( 'she_fs' ) ) {
    // Create a helper function for easy SDK access.
    function she_fs() {
        global $she_fs;
        if ( !isset( $she_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
            $she_fs = fs_dynamic_init( array(
                'id'                => '27794',
                'slug'              => 'sheetspilot',
                'type'              => 'plugin',
                'public_key'        => 'pk_c8a223d611488ab3918c6791c058a',
                'is_premium'        => true,
                'premium_suffix'    => 'Pro',
                'has_addons'        => false,
                'has_paid_plans'    => true,
                'is_org_compliant'  => true,
                'wp_org_gatekeeper' => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
                'menu'              => array(
                    'slug'       => 'sheetspilot',
                    'parent'     => array(
                        'slug' => 'sheetspilot',
                    ),
                    'first-path' => 'admin.php?page=sheetspilot',
                    'contact'    => false,
                    'support'    => false,
                ),
                'is_live'           => true,
            ) );
        }
        return $she_fs;
    }
}

/**
 * Bootstrap Pro only after all plugins are loaded.
 * Prevents false negatives when free plugin loads later in plugin order.
 */
function sheetspilot_pro_bootstrap() {
    if ( ! defined( 'SHEETSPILOT_INC' ) ) {
        add_action( 'admin_notices', 'sheetspilot_pro_requires_free_notice' );
        add_action( 'network_admin_notices', 'sheetspilot_pro_requires_free_notice' );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sheetspilot_pro_requires_free_row_warning' );
        return;
    }

    // Init Freemius.
    she_fs();
    // Signal that SDK was initiated.
    do_action( 'she_fs_loaded' );
}

add_action( 'plugins_loaded', 'sheetspilot_pro_bootstrap', 20 );