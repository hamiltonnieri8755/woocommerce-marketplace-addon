<?php
/**
 * Plugin Name: WooCommerce Marketplace Sales Report 
 * Plugin URI: https://www.wplab.com/
 * Description: An e-commerce toolkit that helps you get marketplace sales report
 * Version: 1.0
 * Author: Hamilton Nieri
 * Author URI: https://www.wplab.com/
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * ----------------------------------------------------------------------
 * Copyright (C) 2016  Hamilton Nieri  (Email: hamiltonnieri8755@yahoo.com)
 * ----------------------------------------------------------------------
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * ----------------------------------------------------------------------
 */

// Including WP core file
if ( ! function_exists( 'get_plugins' ) )
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Including base class
if ( ! class_exists( 'WC_Report_Sales_By_Marketplace' ) )
    require_once plugin_dir_path( __FILE__ ) . '/classes/class-wc-report-sales-by-marketplace.php';

// Whether plugin active or not
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) :

    /**
     * WooCommerce hook
     *
     * @param array   $reports
     * @return array
     */
	function marketplace_addon( $reports ) {
        if ( get_option( 'wplister_create_orders' ) != '1' ) {
            $reports['orders_mp'] = array(
                'title'  => 'Orders by Marketplace',
                'reports' => array(
                    "sales_by_date" => array(
                        'title'       => __( 'Sales by date', 'woocommerce' ),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => 'wc_marketplace_date'
                    ),
                    "sales_by_product" => array(
                        'title'       => __( 'Sales by product', 'woocommerce' ),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => 'wc_marketplace_product'
                    ),
                    "sales_by_category" => array(
                        'title'       => __( 'Sales by category', 'woocommerce' ),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => 'wc_marketplace_category'
                    )
                )
            );
        } else {
            $reports['orders_mp'] = array(
                'title'  => 'Orders by Marketplace',
                'reports' => array(
                    "sales_by_date2" => array(
                        'title'       => __( 'Sales by date', 'woocommerce' ),
                        'description' => '',
                        'hide_title'  => true,
                        'callback'    => 'wc_marketplace_date2'
                    )
                )
            );
        }

        return $reports;
	}

	add_filter( 'woocommerce_admin_reports', 'marketplace_addon' );

    // The object
    $wmp = new WC_Report_Sales_By_Marketplace( );

    /**
     * Function to show sales_by_date of Orders by Marketplace
     * 
     * @return string
     */
    function wc_marketplace_date() {
        global $wmp;
        $wmp->output_report_date();
    }

    /**
     * Function to show sales_by_date of Orders by Marketplace
     * 
     * @return string
     */
    function wc_marketplace_date2() {
        global $wmp;
        $wmp->output_report_date2();
    }

    /**
     * Function to show sales_by_product of Orders by Marketplace
     * 
     * @return string
     */
    function wc_marketplace_product() {
        global $wmp;
        $wmp->output_report_product();
    }

    /**
     * Function to show sales_by_category of Orders by Marketplace
     * 
     * @return string
     */
    function wc_marketplace_category() {
        global $wmp;
        $wmp->output_report_category();
    }
    
else :

    /**
     * Getting notice if WooCommerce not active
     * 
     * @return string
     */
    function wmp_notice() {
        global $current_screen;
        if ( $current_screen->parent_base == 'plugins' ) {
            echo '<div class="error"><p>'.__( 'The <strong>WooCommerce Sales by Marketplace</strong> plugin requires the <a href="http://wordpress.org/plugins/woocommerce" target="_blank">WooCommerce</a> plugin to be activated in order to work. Please <a href="'.admin_url( 'plugin-install.php?tab=search&type=term&s=WooCommerce' ).'" target="_blank">install WooCommerce</a> or <a href="'.admin_url( 'plugins.php' ).'">activate</a> first.' ).'</p></div>';
        }
    }
    add_action( 'admin_notices', 'wmp_notice' );

endif;