<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Report_Sales_By_Category
 *
 * @author      Hamilton Nieri
 * @category    Admin
 * @package     WooCommerce/Admin/Reports
 * @version     2.1.0
 */

class WC_Report_Sales_By_Marketplace {

	/**
	 * Chart colours.
	 *
	 * @var array
	 */
	public $chart_colours;

	/**
	 * Range.
	 *
	 * @var string
	 */
	public $range;

	/**
	 * Chart interval.
	 *
	 * @var int
	 */
	public $chart_interval;
	
	/**
	 * Chart group by.
	 *
	 * @var string
	 */
	public $chart_groupby;

	/**
	 * SQL group by.
	 *
	 * @var string
	 */
	public $group_by_query;

	/**
	 * Report start.
	 *
	 * @var int
	 */
	public $start_date;

	/**
	 * Report end.
	 *
	 * @var int
	 */
	public $end_date;

	/**
	 * Report width.
	 *
	 * @var int
	 */
	public $barwidth;

	/**
	 * Chart data.
	 *
	 * @var array
	 */
	public $data;

	/**
	 * Item ID
	 *
	 * @var int
	 */
	public $item_id;

	/**
	 * Category ID
	 *
	 * @var int
	 */
	public $category_id;

	/**
	 * Constructor.
	 */
	public function __construct() {

		if ( isset( $_GET['range'] ) ) 
			$this->range = $_GET['range']; 
		else 
			$this->range = "last7day";

		if ( isset( $_GET['item_id'] ) ) 
			$this->item_id = $_GET['item_id'];
		else
			$this->item_id = "";

		if ( isset( $_GET['cat_id']) )
			$this->category_id = $_GET['cat_id'];
		else
			$this->category_id = "";

		$this->data = array();
		$this->chart_colours = array( '#1abc9c', '#34495e', '#3498db', '#c6d8d1' );
	}

	/**
	 * Return currency tooltip JS based on WooCommerce currency position settings.
	 *
	 * @return string
	 */
	public function get_currency_tooltip() {
		$currency_pos_opt = get_option( 'woocommerce_currency_pos' );
		switch( $currency_pos_opt ) {
			case 'right':
				$currency_tooltip = 'append_tooltip: "' . get_woocommerce_currency_symbol() . '"'; break;
			case 'right_space':
				$currency_tooltip = 'append_tooltip: "&nbsp;' . get_woocommerce_currency_symbol() . '"'; break;
			case 'left':
				$currency_tooltip = 'prepend_tooltip: "' . get_woocommerce_currency_symbol() . '"'; break;
			case 'left_space':
			default:
				$currency_tooltip = 'prepend_tooltip: "' . get_woocommerce_currency_symbol() . '&nbsp;"'; break;
		}
		return $currency_tooltip;
	}

	/**
	 * Init $start_date, $end_date
	 *
	 * @return string
	 */
	public function calculate_current_range( $current_range ) {
		switch ( $current_range ) {

			case 'custom' :

				$this->start_date = strtotime( sanitize_text_field( $_GET['from'] ) );
				$this->end_date   = strtotime( 'midnight', strtotime( sanitize_text_field( $_GET['to'] ) ) );

				if ( ! $this->end_date ) {
					$this->end_date = current_time('timestamp');
				}

				$interval = 0;
				$min_date = $this->start_date;

				while ( ( $min_date = strtotime( "+1 MONTH", $min_date ) ) <= $this->end_date ) {
					$interval ++;
				}
				
				// 3 months max for day view
				if ( $interval > 3 ) {
					$this->chart_groupby = 'month';
				} else {
					$this->chart_groupby = 'day';
				}
				
				break;

			case 'year' :
				
				$this->start_date    = strtotime( date( 'Y-01-01', current_time('timestamp') ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby = 'month';
				
				break;

			case 'lastmonth' :
				
				$first_day_current_month = strtotime( date( 'Y-m-01', current_time( 'timestamp' ) ) );
				$this->start_date        = strtotime( date( 'Y-m-01', strtotime( '-1 DAY', $first_day_current_month ) ) );
				$this->end_date          = strtotime( date( 'Y-m-t', strtotime( '-1 DAY', $first_day_current_month ) ) );
				$this->chart_groupby     = 'day';
				
				break;

			case 'thismonth' :
				
				$this->start_date    = strtotime( date( 'Y-m-01', current_time('timestamp') ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby = 'day';
				
				break;

			case 'last7day' :
				
				$this->start_date    = strtotime( '-6 days', current_time( 'timestamp' ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby = 'day';
			
				break;

			default:
				
				break;
		
		}

		// Group by
		switch ( $this->chart_groupby ) {

			case 'day' :
				
				$this->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)';
				$this->chart_interval = ceil( max( 0, ( $this->end_date - $this->start_date ) / ( 60 * 60 * 24 ) ) );
				$this->barwidth       = 60 * 60 * 24 * 1000;
				
				break;

			case 'month' :
				
				$this->group_by_query = 'YEAR(posts.post_date), MONTH(posts.post_date)';
				$this->chart_interval = 0;
				$min_date             = $this->start_date;

				while ( ( $min_date   = strtotime( "+1 MONTH", $min_date ) ) <= $this->end_date ) {
					$this->chart_interval ++;
				}

				$this->barwidth = 60 * 60 * 24 * 7 * 4 * 1000;
				
				break;

			default:

				break;
				
		}

	}

	/**
	 * Generate chart data
	 *
	 * @return string
	 */
	public function prepare_data( $marketplace_number, $type ) {
		$prepared_data = array();

		// Ensure all days (or months) have values first in this range
		for ( $i = 0; $i <= $this->chart_interval; $i ++ ) {
			switch ( $this->chart_groupby ) {
				case 'day' :
					$time = strtotime( date( 'Ymd', strtotime( "+{$i} DAY", $this->start_date ) ) ) . '000';
					break;
				case 'month' :
				default :
					$time = strtotime( date( 'Ym', strtotime( "+{$i} MONTH", $this->start_date ) ) . '01' ) . '000';
					break;
			}

			if ( ! isset( $prepared_data[ $time ] ) ) {
				$prepared_data[ $time ] = 0;
			}
		}
		foreach ( $this->get_order_amount_data( $marketplace_number, $type ) as $result ) {
			switch ( $this->chart_groupby ) {
				case 'day' :
					$time = strtotime( date( 'Ymd', strtotime( $result->order_date ) ) ) . '000';
					break;
				case 'month' :
				default :
					$time = strtotime( date( 'Ym', strtotime( $result->order_date ) ) . '01' ) . '000';
					break;
			}

			if ( ! isset( $prepared_data[ $time ] ) ) {
				continue;
			}

			$prepared_data[ $time ] += $result->order_total;
		}
		if ( $type == null || ( $marketplace_number == 0 && $type == "disabled" ) ) {
			foreach ( $this->get_refund_total_mp( $marketplace_number, $type ) as $result ) {
				switch ( $this->chart_groupby ) {
					case 'day' :
						$time = strtotime( date( 'Ymd', strtotime( $result->post_date ) ) ) . '000';
						break;
					case 'month' :
					default :
						$time = strtotime( date( 'Ym', strtotime( $result->post_date ) ) . '01' ) . '000';
						break;
				}

				if ( ! isset( $prepared_data[ $time ] ) ) {
					continue;
				}

				$prepared_data[ $time ] -= $result->total_refund;
			}
		}
		return $prepared_data;
	}

	/**
	 * Get formatted total for chart legends
	 *
	 * @return string
	 */
	public function get_mp_total_formatted( $marketplace_id ) {
		$current_mp = $this->data[ $marketplace_id ];
		$total = 0;
		foreach ( $current_mp as $element ) {
			$total += $element[1];
		}
		return wc_price( $total );
	}

	/**
	 * Get total for chart legends
	 *
	 * @return string
	 */
	public function get_mp_total( $marketplace_id ) {
		$current_mp = $this->data[ $marketplace_id ];
		$total = 0;
		foreach ( $current_mp as $element ) {
			$total += $element[1];
		}
		return $total ;
	}

	/**
	 * Get % for chart legends
	 *
	 * @return string
	 */
	public function get_mp_percent( $marketplace_id ) {
		$mp_total_arr = array();
		$mp_total_arr[0] = $this->get_mp_total( 0 );
		$mp_total_arr[1] = $this->get_mp_total( 1 );
		$mp_total_arr[2] = $this->get_mp_total( 2 );

		$mp_overall = $mp_total_arr[0] + $mp_total_arr[1] + $mp_total_arr[2];

		if ( $mp_overall > 0)
			return number_format( $mp_total_arr[ $marketplace_id ] / $mp_overall * 100 , 2 ) . " %" ;

		return "0 %";
	}

	/**
	 * Export marketplace data
	 *
	 * @return string
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
		?>
		<a
			href="#"
			download="report-<?php echo esc_attr( $current_range ); ?>-<?php echo date_i18n( 'Y-m-d', current_time('timestamp') ); ?>.csv"
			class="export_csv_marketplace"
			data-groupby="<?php echo $this->chart_groupby; ?>"
		>
			<?php _e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}

	/**
	 * Get marketplace order amount data
	 *
	 * @return array
	 */
    public function get_order_amount_data( $marketplace_number, $type ) {
        global $wpdb;

        $start_date_forsql = date( 'Y-m-d', $this->start_date );
        $end_date_forsql   = date( 'Y-m-d', strtotime( '+1 day', $this->end_date ) );
        $item_id = $this->item_id;
        $sql = "";
        if ( $type == null ) {
     		// sales by date
	        switch ( $marketplace_number ) {
	        	case '2':
	        		// GET TOTAL AMOUNTS OF ORDERS FROM EBAY 
			        $sql = "SELECT SUM( meta__order_total.meta_value) as order_total, DATE_FORMAT(posts.post_date,'%Y-%m-%d') AS order_date, posts.post_date AS order_day
							FROM {$wpdb->prefix}posts AS posts 
							INNER JOIN {$wpdb->prefix}postmeta AS meta__order_total ON ( posts.ID = meta__order_total.post_id AND meta__order_total.meta_key = '_order_total' )
							WHERE 	posts.post_type 	IN ( 'shop_order' )
							AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded' )
							AND 	posts.post_date >= '$start_date_forsql'
							AND 	posts.post_date < '$end_date_forsql'
							AND 	posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')
							GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date) 
							ORDER BY post_date";
					break;
				case '1':
					// GET TOTAL AMOUNTS OF ORDERS FROM AMAZON
			        $sql = "SELECT SUM( meta__order_total.meta_value) as order_total, DATE_FORMAT(posts.post_date,'%Y-%m-%d') AS order_date, posts.post_date AS order_day
							FROM {$wpdb->prefix}posts AS posts 
							INNER JOIN {$wpdb->prefix}postmeta AS meta__order_total ON ( posts.ID = meta__order_total.post_id AND meta__order_total.meta_key = '_order_total' )
							WHERE 	posts.post_type 	IN ( 'shop_order' )
							AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded' )
							AND 	posts.post_date >= '$start_date_forsql'
							AND 	posts.post_date < '$end_date_forsql'
							AND 	posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')
							GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date) 
							ORDER BY post_date";
					break;
				case '0':
					// GET TOTAL AMOUNTS OF ORDERS FROM WEBSTROE
			        $sql = "SELECT SUM( meta__order_total.meta_value) as order_total, DATE_FORMAT(posts.post_date,'%Y-%m-%d') AS order_date, posts.post_date AS order_day
							FROM {$wpdb->prefix}posts AS posts 
							INNER JOIN {$wpdb->prefix}postmeta AS meta__order_total ON ( posts.ID = meta__order_total.post_id AND meta__order_total.meta_key = '_order_total' )
							WHERE 	posts.post_type 	IN ( 'shop_order' )
							AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded' )
							AND 	posts.post_date >= '$start_date_forsql'
							AND 	posts.post_date < '$end_date_forsql'
							AND 	posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')
							AND 	posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')
							GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date) 
							ORDER BY post_date";
					break;
				default:
					break;
			}
		} else if ( $type == "product" ) {
			// sales by product
			$sql = "SELECT SUM( order_item_meta__line_total.meta_value) AS order_total, posts.post_date AS order_date, order_item_meta__product_id.meta_value AS product_id 
					FROM {$wpdb->prefix}posts AS posts 
					INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id 
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__line_total ON (order_items.order_item_id = order_item_meta__line_total.order_item_id)  AND (order_item_meta__line_total.meta_key = '_line_total') 
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id ON (order_items.order_item_id = order_item_meta__product_id.order_item_id)  AND (order_item_meta__product_id.meta_key = '_product_id') 
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id_array ON order_items.order_item_id = order_item_meta__product_id_array.order_item_id 
					WHERE 	posts.post_type 	IN ( 'shop_order','shop_order_refund' )
					AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold')			
					AND 	posts.post_date >= '$start_date_forsql'
					AND 	posts.post_date < '$end_date_forsql'";

			switch ( $marketplace_number ) {
				case '0':
					// WEB STORE 
					$sql .= "AND posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')
							 AND posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')";		 
					break;
				case '1':
					// AMAZON
					$sql .= "AND posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')";
					break;
				case '2':
					// EBAY
					$sql .= "AND posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')";
					break;
				default:
					// OVERALL
					break;
			}

			$sql .= "AND ( ( order_item_meta__product_id_array.meta_key   IN ('_product_id','_variation_id') AND order_item_meta__product_id_array.meta_value IN ('$item_id') )) 
					GROUP BY product_id, YEAR(posts.post_date), MONTH(posts.post_date)";
		} else if ( $type == "category" ) {
			// type = category, sales by category
			$prod_ids = $this->get_products_in_category($this->category_id);
			$prod_ids_forsql = "'".implode("','", array_values($prod_ids))."'";
			
			$sql = "SELECT  order_item_meta__product_id.meta_value AS product_id, SUM( order_item_meta__line_total.meta_value) AS order_total, posts.post_date AS order_date 
					FROM {$wpdb->prefix}posts AS posts

					INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items 
					ON (posts.ID = order_items.order_id) AND (order_items.order_item_type = 'line_item') 

					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id 
					ON (order_items.order_item_id = order_item_meta__product_id.order_item_id)  AND (order_item_meta__product_id.meta_key = '_product_id')

					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__line_total 
					ON (order_items.order_item_id = order_item_meta__line_total.order_item_id)  AND (order_item_meta__line_total.meta_key = '_line_total')

					WHERE 	order_item_meta__product_id.meta_value IN ($prod_ids_forsql)
					AND 	posts.post_type 	IN ( 'shop_order','shop_order_refund' )
					AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold')
					AND 	posts.post_date >= '$start_date_forsql'
					AND 	posts.post_date < '$end_date_forsql'";
			
			switch ( $marketplace_number ) {
				case '0':
					// WEB STORE 
					$sql .= "AND posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')
							 AND posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')";		 
					break;
				case '1':
					// AMAZON
					$sql .= "AND posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')";
					break;
				case '2':
					// EBAY
					$sql .= "AND posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')";
					break;
				default:
					// OVERALL
					break;
			}
			
			$sql .= "GROUP BY ID, product_id, order_date";
		} else if ( $type == "disabled" ) {
			// sales by date for disabled create order
			$sql = "";
			switch ( $marketplace_number ) {
				case '0':
					// Web Store
					$sql = "SELECT SUM( meta__order_total.meta_value) as order_total, DATE_FORMAT(posts.post_date,'%Y-%m-%d') AS order_date, posts.post_date AS order_day
							FROM {$wpdb->prefix}posts AS posts 
							INNER JOIN {$wpdb->prefix}postmeta AS meta__order_total ON ( posts.ID = meta__order_total.post_id AND meta__order_total.meta_key = '_order_total' )
							WHERE 	posts.post_type 	IN ( 'shop_order' )
							AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded' )
							AND 	posts.post_date >= '$start_date_forsql'
							AND 	posts.post_date < '$end_date_forsql'
							GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date) 
							ORDER BY post_date";
					break;
				case '1':
					// Amazon
					$sql = "SELECT ROUND(SUM(amazon_orders.total),2) AS order_total, DATE_FORMAT(amazon_orders.date_created,'%Y-%m-%d') AS order_date
							FROM {$wpdb->prefix}amazon_orders AS amazon_orders 
							WHERE amazon_orders.date_created >= '$start_date_forsql'
							AND amazon_orders.date_created < '$end_date_forsql'
							AND amazon_orders.status NOT IN ('Canceled', 'Pending')
							GROUP BY YEAR(amazon_orders.date_created), MONTH(amazon_orders.date_created), DAY(amazon_orders.date_created) 
							ORDER BY amazon_orders.date_created";
					break;
				case '2':
					// EBay
					$sql = "SELECT ROUND(SUM(ebay_orders.total),2) AS order_total, DATE_FORMAT(ebay_orders.date_created,'%Y-%m-%d') AS order_date
							FROM {$wpdb->prefix}ebay_orders AS ebay_orders 
							WHERE ebay_orders.date_created >= '$start_date_forsql'
							AND ebay_orders.date_created < '$end_date_forsql'
							AND ebay_orders.CompleteStatus IN ( 'Completed' )
							GROUP BY YEAR(ebay_orders.date_created), MONTH(ebay_orders.date_created), DAY(ebay_orders.date_created) 
							ORDER BY ebay_orders.date_created";
					break;
				default:
					break;
			}
		}
		
		//echo $sql."<br/>";
        
        return $wpdb->get_results( $sql );
    }

	/**
	 * Generate all chart data
	 *
	 * @return bool
	 */
    public function prepare_3marketplace_data($type = null) {
    	for ( $i = 0; $i < 3; $i ++ ) {
    		$data_each = array();
    		$temp_data = $this->prepare_data( $i, $type );
    		foreach ( $temp_data as $key => $value ) {
    			array_push( $data_each, array($key, $value) );
    		}
    		$this->data[$i] = $data_each;
    	}
    	return true;
    }

	/**
	 * Output Date Report
	 *
	 * @return 
	 */
	public function output_report_date() {
		$this->calculate_current_range( $this->range );
		$this->prepare_3marketplace_data();	
		include plugin_dir_path( __FILE__ ) . '../views/html-date-report-by-marketplace.php';
	}

	/**
	 * Output Date Report
	 *
	 * @return 
	 */
	public function output_report_date2() {
		$this->calculate_current_range( $this->range );
		$this->prepare_3marketplace_data("disabled");	
		include plugin_dir_path( __FILE__ ) . '../views/html-disablecreateorder-report-by-marketplace.php';
	}

	/**
	 * Output Product Report
	 *
	 * @return 
	 */
	public function output_report_product() {
		$this->calculate_current_range( $this->range );
		$this->prepare_3marketplace_data("product");	

		// Include select2 js and css
		wp_enqueue_style( 'select2-css', plugin_dir_path( __FILE__ ) . '../assets/css/select2.min.css' );
    	wp_enqueue_script( 'select2-js', plugin_dir_path( __FILE__ ) . '../assets/js/select2.full.min.js', array(), '1.0.0', true );	

		include plugin_dir_path( __FILE__ ) . '../views/html-product-report-by-marketplace.php';
	}

	/**
	 * Output Category Report
	 *
	 * @return 
	 */
	public function output_report_category() {
		$this->calculate_current_range( $this->range );
		$this->prepare_3marketplace_data("category");

		// Include select2 js and css
		wp_enqueue_style( 'select2-css', plugin_dir_path( __FILE__ ) . '../assets/css/select2.min.css' );
    	wp_enqueue_script( 'select2-js', plugin_dir_path( __FILE__ ) . '../assets/js/select2.full.min.js', array(), '1.0.0', true );	
		
		include plugin_dir_path( __FILE__ ) . '../views/html-category-report-by-marketplace.php';
	}

	/**
	 * Get all product ids in a category (and its children).
	 *
	 * @param  int $category_id
	 * @return array
	 */
	public function get_products_in_category( $category_id ) {
		$term_ids    = get_term_children( $category_id, 'product_cat' );
		$term_ids[]  = $category_id;
		$product_ids = get_objects_in_term( $term_ids, 'product_cat' );
		return array_unique($product_ids);
	}

	/**
	 * Get marketplace order amount data
	 *
	 * @return array
	 */
    public function get_best_seller_mp( $marketplace_number ) {
        global $wpdb;

        $start_date_forsql = date( 'Y-m-d', $this->start_date );
        $end_date_forsql   = date( 'Y-m-d', strtotime( '+1 day', $this->end_date ) );

        $sql = "";
        switch ( $marketplace_number ) {
        	case '2':
        		// GET BEST SELLER IN EBAY
		        $sql = "SELECT  order_item_meta__product_id.meta_value AS product_id,SUM( order_item_meta__qty.meta_value) AS order_item_qty FROM {$wpdb->prefix}posts AS posts 
						INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id ON (order_items.order_item_id = order_item_meta__product_id.order_item_id)  AND (order_item_meta__product_id.meta_key = '_product_id') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__qty ON (order_items.order_item_id = order_item_meta__qty.order_item_id)  AND (order_item_meta__qty.meta_key = '_qty') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__line_subtotal ON order_items.order_item_id = order_item_meta__line_subtotal.order_item_id 
						WHERE 	posts.post_type 	IN ( 'shop_order' )
						AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded' )
						AND 	posts.post_date >= '$start_date_forsql'
						AND 	posts.post_date < '$end_date_forsql'
						AND 	posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')
						AND ( ( order_item_meta__line_subtotal.meta_key   = '_line_subtotal' AND order_item_meta__line_subtotal.meta_value > '0' )) GROUP BY product_id ORDER BY order_item_qty DESC LIMIT 1";
				break;
			case '1':
				// GET BEST SELLER IN AMAZON
		        $sql = "SELECT  order_item_meta__product_id.meta_value AS product_id,SUM( order_item_meta__qty.meta_value) AS order_item_qty FROM {$wpdb->prefix}posts AS posts 
						INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id ON (order_items.order_item_id = order_item_meta__product_id.order_item_id)  AND (order_item_meta__product_id.meta_key = '_product_id') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__qty ON (order_items.order_item_id = order_item_meta__qty.order_item_id)  AND (order_item_meta__qty.meta_key = '_qty') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__line_subtotal ON order_items.order_item_id = order_item_meta__line_subtotal.order_item_id 
						WHERE 	posts.post_type 	IN ( 'shop_order' )
						AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded' )
						AND 	posts.post_date >= '$start_date_forsql'
						AND 	posts.post_date < '$end_date_forsql'
						AND 	posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')
						AND ( ( order_item_meta__line_subtotal.meta_key   = '_line_subtotal' AND order_item_meta__line_subtotal.meta_value > '0' )) GROUP BY product_id ORDER BY order_item_qty DESC LIMIT 1";
				break;
			case '0':
				// GET BEST SELLER IN WEB STORE
		        $sql = "SELECT  order_item_meta__product_id.meta_value AS product_id,SUM( order_item_meta__qty.meta_value) AS order_item_qty FROM {$wpdb->prefix}posts AS posts 
						INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id ON (order_items.order_item_id = order_item_meta__product_id.order_item_id)  AND (order_item_meta__product_id.meta_key = '_product_id') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__qty ON (order_items.order_item_id = order_item_meta__qty.order_item_id)  AND (order_item_meta__qty.meta_key = '_qty') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__line_subtotal ON order_items.order_item_id = order_item_meta__line_subtotal.order_item_id 
						WHERE 	posts.post_type 	IN ( 'shop_order' )
						AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded' )
						AND 	posts.post_date >= '$start_date_forsql'
						AND 	posts.post_date < '$end_date_forsql'
						AND 	posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')
						AND 	posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')
						AND ( ( order_item_meta__line_subtotal.meta_key   = '_line_subtotal' AND order_item_meta__line_subtotal.meta_value > '0' )) GROUP BY product_id ORDER BY order_item_qty DESC LIMIT 1";
				break;
			case '3':
				// // GET OVERALL BEST SELLER
		        $sql = "SELECT  order_item_meta__product_id.meta_value AS product_id,SUM( order_item_meta__qty.meta_value) AS order_item_qty FROM {$wpdb->prefix}posts AS posts 
						INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id ON (order_items.order_item_id = order_item_meta__product_id.order_item_id)  AND (order_item_meta__product_id.meta_key = '_product_id') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__qty ON (order_items.order_item_id = order_item_meta__qty.order_item_id)  AND (order_item_meta__qty.meta_key = '_qty') 
						INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__line_subtotal ON order_items.order_item_id = order_item_meta__line_subtotal.order_item_id 
						WHERE 	posts.post_type 	IN ( 'shop_order' )
						AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded' )
						AND 	posts.post_date >= '$start_date_forsql'
						AND 	posts.post_date < '$end_date_forsql'
						AND ( ( order_item_meta__line_subtotal.meta_key   = '_line_subtotal' AND order_item_meta__line_subtotal.meta_value > '0' )) GROUP BY product_id ORDER BY order_item_qty DESC LIMIT 1";
				break;
			default:
				break;
		}
		//echo $sql."<br/>";
        $rows = $wpdb->get_results( $sql );
        //print_r($rows);
        foreach ( $rows as $row ) {
			$best_seller = array();
			$best_seller['name'] = get_the_title( $row->product_id );
			$best_seller['qty']   = $row->order_item_qty;
			return $best_seller;
		}
		return null;
    }
    /**
	 * Get marketplace order count data
	 *
	 * @return array
	 */
    public function get_orders_count_mp( $marketplace_number, $type = null ) {
    	global $wpdb;

    	$start_date_forsql = date( 'Y-m-d', $this->start_date );
        $end_date_forsql   = date( 'Y-m-d', strtotime( '+1 day', $this->end_date ) );

    	$sql = "";

    	switch ( $marketplace_number ) {
    		case '0':
    			// GET THE COUNT OF ORDERS FROM WEBSTORE
    			$sql = "SELECT COUNT(DISTINCT posts.ID) as count
    					FROM {$wpdb->prefix}posts AS posts  
						WHERE posts.post_type IN ( 'shop_order' )		
						AND	posts.post_status IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded')
						AND posts.post_date >= '$start_date_forsql'
						AND posts.post_date < '$end_date_forsql'
						AND posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')
						AND posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')
			 			GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)";
    			break;
    		case '1':
    			// GET THE COUNT OF ORDERS FROM AMAZON
    			$sql = "SELECT COUNT(DISTINCT posts.ID) as count
    					FROM {$wpdb->prefix}posts AS posts  
						WHERE posts.post_type IN ( 'shop_order' )		
						AND	posts.post_status IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded')
						AND posts.post_date >= '$start_date_forsql'
						AND posts.post_date < '$end_date_forsql'
						AND posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')
			 			GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)";
    			break;
    		case '2':
    			// GET THE COUNT OF ORDERS FROM EBAY
    			$sql = "SELECT COUNT(DISTINCT posts.ID) as count
    					FROM {$wpdb->prefix}posts AS posts  
						WHERE posts.post_type IN ( 'shop_order' )		
						AND	posts.post_status IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded')
						AND posts.post_date >= '$start_date_forsql'
						AND posts.post_date < '$end_date_forsql'
						AND posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')
			 			GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)";
    			break;
    		case '3':
    			// GET THE COUNT OF ORDERS FROM OVERALL
    			$sql = "SELECT COUNT(DISTINCT posts.ID) as count
    					FROM {$wpdb->prefix}posts AS posts
						WHERE posts.post_type IN ( 'shop_order' )		
						AND	posts.post_status IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded')
						AND posts.post_date >= '$start_date_forsql'
						AND posts.post_date < '$end_date_forsql'
			 			GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date)";
    			break;
    	}
		//echo $sql."<br/>";
        $rows = $wpdb->get_results( $sql );
        $order_count = 0;
        foreach ( $rows as $row ) {
        	$order_count += $row->count;
		}
		return $order_count;
    }

    /**
     *	Output category search auto complete dropdown
     *
     *	@return html source
     */
    public function get_product_cats_list() {
    	$product_cats = get_terms( 'product_cat' );
    	echo "<option value=''>Select a category</option>";
		foreach( $product_cats as $product_cat ) {
			if ( $product_cat->count == 0 )
				continue;
			echo "<option value='".$product_cat->term_id."'>".$product_cat->name."</option>";
		}
    }

    /**
	 * Get marketplace product count data
	 *
	 * @return array
	 */
    public function get_products_count_mp( $marketplace_number, $type = null ) {
    	global $wpdb;

    	$start_date_forsql = date( 'Y-m-d', $this->start_date );
        $end_date_forsql   = date( 'Y-m-d', strtotime( '+1 day', $this->end_date ) );
        $item_id_forsql    = $this->item_id; 

        if ($type == null ) {
	    	$sql = "SELECT SUM( order_item_meta__qty.meta_value) AS order_item_count, posts.post_date AS post_date 
					FROM {$wpdb->prefix}posts AS posts 
					INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items 
					ON (posts.ID = order_items.order_id) AND (order_items.order_item_type = 'line_item') 
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__qty 
					ON (order_items.order_item_id = order_item_meta__qty.order_item_id)  AND (order_item_meta__qty.meta_key = '_qty') 
					WHERE 	posts.post_type 	IN ( 'shop_order' )
					AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold','wc-refunded')";

			switch ( $marketplace_number ) {
				case '0':
					// WEB STORE 
					$sql .= "AND posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')
							 AND posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')";		 
					break;
				case '1':
					// AMAZON
					$sql .= "AND posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')";
					break;
				case '2':
					// EBAY
					$sql .= "AND posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')";
					break;
				default:
					// OVERALL
					break;
			}

			$sql .= "AND 	posts.post_date >= '$start_date_forsql'
					 AND 	posts.post_date < '$end_date_forsql'
					 AND order_items.order_item_type = 'line_item' 
					 GROUP BY YEAR(posts.post_date), MONTH(posts.post_date)";

		} else if ($type == "product") {

			$sql = "SELECT SUM( order_item_meta__qty.meta_value) AS order_item_count, posts.post_date AS post_date, order_item_meta__product_id.meta_value AS product_id 
					FROM {$wpdb->prefix}posts AS posts 
					INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_items.order_id 
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__qty ON (order_items.order_item_id = order_item_meta__qty.order_item_id)  AND (order_item_meta__qty.meta_key = '_qty') 
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id ON (order_items.order_item_id = order_item_meta__product_id.order_item_id)  AND (order_item_meta__product_id.meta_key = '_product_id') 
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__product_id_array ON order_items.order_item_id = order_item_meta__product_id_array.order_item_id 
					WHERE 	posts.post_type 	IN ( 'shop_order','shop_order_refund' )			
					AND 	posts.post_status 	IN ( 'wc-completed','wc-processing','wc-on-hold')";
			
			switch ( $marketplace_number ) {
				case '0':
					// WEB STORE 
					$sql .= "AND posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')
							 AND posts.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')";		 
					break;
				case '1':
					// AMAZON
					$sql .= "AND posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_wpla_amazon_order_id')";
					break;
				case '2':
					// EBAY
					$sql .= "AND posts.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta pm WHERE pm.meta_key = '_ebay_order_id')";
					break;
				default:
					// OVERALL
					break;
			}

			$sql .= "AND 	posts.post_date >= '$start_date_forsql'
					AND 	posts.post_date < '$end_date_forsql'
					AND ( ( order_item_meta__product_id_array.meta_key   IN ('_product_id','_variation_id') 
					AND order_item_meta__product_id_array.meta_value IN ('$item_id_forsql') )) 
					GROUP BY product_id,YEAR(posts.post_date), MONTH(posts.post_date)";
		
		}

		//echo $sql."<br/>";
        $rows = $wpdb->get_results( $sql );
        $product_count = 0;
        foreach ( $rows as $row ) {
        	$product_count += $row->order_item_count;
		}
		return $product_count;
    }

    /**
	 * Get refund total
	 *
	 * @return array
	 */
    public function get_refund_total_mp( $marketplace_number, $type ) {
    	global $wpdb;

        $start_date_forsql = date( 'Y-m-d', $this->start_date );
        $end_date_forsql   = date( 'Y-m-d', strtotime( '+1 day', $this->end_date ) );

        $sql = "SELECT  SUM(meta__refund_amount.meta_value) AS total_refund, posts.post_date AS post_date  
				FROM {$wpdb->prefix}posts AS posts
				INNER JOIN {$wpdb->prefix}postmeta AS meta__refund_amount ON ( posts.ID = meta__refund_amount.post_id AND meta__refund_amount.meta_key = '_refund_amount' )
				LEFT JOIN {$wpdb->prefix}posts AS parent ON posts.post_parent = parent.ID
				WHERE 	posts.post_type 	IN ( 'shop_order','shop_order_refund' )
				AND parent.post_status IN ('wc-completed','wc-processing','wc-on-hold','wc-refunded')
				AND 	posts.post_date >= '$start_date_forsql'
				AND 	posts.post_date < '$end_date_forsql'";

		if ( $type != 'disabled' ) {
			switch ( $marketplace_number ) {
				case '0':
					// WEB STORE
					$sql .= "AND parent.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta AS pm WHERE pm.meta_key = '_ebay_order_id')
							 AND parent.ID NOT IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta AS pm WHERE pm.meta_key = '_wpla_amazon_order_id')";		 
					break;
				case '1':
					// AMAZON
					$sql .= "AND parent.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta AS pm WHERE pm.meta_key = '_wpla_amazon_order_id')";
					break;
				case '2':
					// EBAY
					$sql .= "AND parent.ID IN (SELECT pm.post_id FROM {$wpdb->prefix}postmeta AS pm WHERE pm.meta_key = '_ebay_order_id')";
					break;
				default:
					// OVERALL
					break;
			}
		}

		$sql .= "GROUP BY YEAR(posts.post_date), MONTH(posts.post_date), DAY(posts.post_date) ORDER BY post_date";

		//echo $sql."<br/>";

        return $wpdb->get_results( $sql );
    }
}