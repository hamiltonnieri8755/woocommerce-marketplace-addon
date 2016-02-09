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
	 * Constructor.
	 */
	public function __construct() {

		if (isset($_GET['range'])) 
			$this->range = $_GET['range']; 
		else 
			$this->range = "last7day";

		$this->data = array();
		$this->chart_colours = array( '#1abc9c', '#34495e', '#3498db' );
	}

	/**
	 * Return currency tooltip JS based on WooCommerce currency position settings.
	 *
	 * @return string
	 */
	public function get_currency_tooltip() {
		switch( get_option( 'woocommerce_currency_pos' ) ) {
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
		}

	}

	public function prepare_data($marketplace_number) {
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
		foreach ( $this->get_order_amount_data($marketplace_number) as $result ) {
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

			$prepared_data[ $time ] = $result->order_total;
		}

		return $prepared_data;
	}

	public function get_mp_total_formatted($marketplace_id) {
		$current_mp = $this->data[$marketplace_id];
		$total = 0;
		foreach ($current_mp as $element) {
			$total += $element[1];
		}
		return wc_price( $total );
	}

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

    public function get_order_amount_data($marketplace_number) {
        global $wpdb;

        $start_date_forsql = date( 'Y-m-d', $this->start_date );
        $end_date_forsql   = date( 'Y-m-d', strtotime( '+1 day', $this->end_date ) );

        $sql = "SELECT DISTINCT oi.order_id, DATE_FORMAT(p.post_date,'%Y-%m-%d') AS order_date,
        		p.post_date AS order_day, 
        		SUM(DISTINCT pm.meta_value) AS order_total
				FROM {$wpdb->prefix}woocommerce_order_items oi

				LEFT JOIN {$wpdb->prefix}postmeta pm 
					ON oi.order_id = pm.post_id AND pm.meta_key = '_order_total'

				LEFT JOIN {$wpdb->prefix}posts p
					ON oi.order_id = p.ID
				LEFT JOIN {$wpdb->prefix}postmeta pm3 
					ON oi.order_id = pm3.post_id AND pm3.meta_key = '_order_type'

				WHERE p.post_date >= '$start_date_forsql' AND p.post_date < '$end_date_forsql' AND pm3.meta_value = '$marketplace_number'
				GROUP BY YEAR(order_day), MONTH(order_day), DAY(order_day)
				ORDER BY order_date";
        return $wpdb->get_results( $sql );
    }

    public function prepare_3marketplace_data() {
    	for ($i=0; $i<3; $i++)
    	{
    		$data_each = array();
    		$temp_data = $this->prepare_data($i);
    		foreach ($temp_data as $key => $value)
    		{
    			array_push($data_each, array($key, $value));
    		}
    		$this->data[$i] = $data_each;
    	}
    	return true;
    }

	public function output_report() {
		$this->calculate_current_range($this->range);
		$this->prepare_3marketplace_data();	
		include( 'html-report-by-marketplace.php' );
	}
}
/*
		echo $this->range;	echo "-range<br/>";
		echo $this->chart_interval;	echo "-chart_interval<br/>";
		echo $this->chart_groupby;	echo "-chart_groupby<br/>";
		echo $this->group_by_query;	echo "-group_by_query<br/>";
		echo $this->start_date."000";	echo "-start_date<br/>";	
		echo $this->end_date."000";	echo "-end_date<br/>";
		echo $this->barwidth;	echo "-barwidth<br/>";
*/