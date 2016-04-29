<?php
/**
 * Admin View: Report by Marketplace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.woocommerce-reports-wide .postbox .chart-with-sidebar
}

?>

<style>
.export_csv_marketplace {
	float: right;
	line-height: 26px;
	border-left: 1px solid #dfdfdf;
	padding: 10px;
	display: block;
	text-decoration: none;
}
.export_csv_marketplace::before {
    font-family: WooCommerce;
    speak: none;
    font-weight: 400;
    font-variant: normal;
    text-transform: none;
    line-height: 1;
    -webkit-font-smoothing: antialiased;
    content: "";
    text-decoration: none;
    margin-right: 4px;
}
.chart-type {
	float: right;
	padding: 10px;
}
.channel-name {
	font-size: 18px;
	line-height: 30px;
	font-weight: bold;
}
</style>
<div id="poststuff" class="woocommerce-reports-wide">
	<div class="postbox">
		<h3 class="stats_range">
			<?php $this->get_export_button(); ?>
			<div class="chart-type">
				<select>
					<option value="bar">Bar</option>
					<option value="line">Line</option>
				</select>
			</div>
			<ul>
				<li id="year" class=""><a href="?page=wc-reports&tab=orders_mp&range=year">Year</a></li>
				<li id="lastmonth" class=""><a href="?page=wc-reports&tab=orders_mp&range=lastmonth">Last Month</a></li>
				<li id="thismonth" class=""><a href="?page=wc-reports&tab=orders_mp&range=thismonth">This Month</a></li>
				<li id="last7day" class=""><a href="?page=wc-reports&tab=orders_mp&range=last7day">Last 7 Days</a></li>				
				<li id="custom" class="custom">
					Custom:					
					<form method="GET">
						<div>
							<input name="start_date" value="" type="hidden">
							<input name="end_date" value="" type="hidden">
							<input name="page" value="wc-reports" type="hidden">
							<input name="tab" value="orders_mp" type="hidden">
							<input name="report" value="sales_by_date" type="hidden">							
							<input name="range" value="custom" type="hidden">

							<input id="from" size="9" placeholder="yyyy-mm-dd" value="<?php if (isset($_GET['from'])) echo $_GET['from']; ?>" name="from" class="range_datepicker from" type="text">
							<input id="to" size="9" placeholder="yyyy-mm-dd" value="<?php if (isset($_GET['to'])) echo $_GET['to']; ?>" name="to" class="range_datepicker to" type="text">
							
							<input class="button" value="Go" type="submit">
						</div>
					</form>
				</li>
			</ul>
		</h3>	
		<div class="inside chart-with-sidebar">
			<div class="chart-sidebar">
				<ul class="chart-legend">

					<!-- Web Store Legend -->
					<li style="border-color: <?php echo $this->chart_colours[0]; ?>" class="highlight_series" data-series="0">
						<strong>
							<?php echo $this->get_mp_total_formatted(0); ?>
						</strong>
						<span class="channel-name">WebStore</span>
						<span class="percent">&nbsp;(&nbsp;<?php echo $this->get_mp_percent(0); ?>&nbsp;)&nbsp;</span>
						<div class="details-wrapper">
							<?php
								$ws_bestseller = $this->get_best_seller_mp(0); 
								echo "<span>" . 'Best Seller : ' . $ws_bestseller['name'] . "(" . $ws_bestseller['qty'] . ")" . "</span><br/>";
								echo "<span>" . 'Products Sold : ' . $this->get_products_count_mp(0) . "</span><br/>";
								echo "<span>" . 'Orders Placed : ' . $this->get_orders_count_mp(0) . "</span><br/>";
							?>
						</div>
					</li>

					<!-- Amazon Legend -->
					<li style="border-color: <?php echo $this->chart_colours[1]; ?>" class="highlight_series" data-series="1">
						<strong>
							<?php echo $this->get_mp_total_formatted(1); ?>
						</strong>
						<span class="channel-name">Amazon</span>
						<span class="percent">&nbsp;(&nbsp;<?php echo $this->get_mp_percent(1); ?>&nbsp;)&nbsp;</span><br/>
						<div class="details-wrapper">
							<?php 
								$ws_bestseller = $this->get_best_seller_mp(1); 
								echo "<span>" . 'Best Seller : ' . $ws_bestseller['name'] . "(" . $ws_bestseller['qty'] . ")" . "</span><br/>";
								echo "<span>" . 'Products Sold : ' . $this->get_products_count_mp(1) . "</span><br/>";
								echo "<span>" . 'Orders Placed : ' . $this->get_orders_count_mp(1) . "</span><br/>";
							?>
						</div>
					</li>

					<!-- eBay Legend -->
					<li style="border-color: <?php echo $this->chart_colours[2]; ?>" class="highlight_series" data-series="2">
						<strong>
							<?php echo $this->get_mp_total_formatted(2); ?>
						</strong>
						<span class="channel-name">eBay</span>
						<span class="percent">&nbsp;(&nbsp;<?php echo $this->get_mp_percent(2); ?>&nbsp;)&nbsp;</span><br/>
						<div class="details-wrapper">
							<?php 
								$ws_bestseller = $this->get_best_seller_mp(2); 
								echo "<span>" . 'Best Seller : ' . $ws_bestseller['name'] . "(" . $ws_bestseller['qty'] . ")" . "</span><br/>";
								echo "<span>" . 'Products Sold : ' . $this->get_products_count_mp(2) . "</span><br/>";
								echo "<span>" . 'Orders Placed : ' . $this->get_orders_count_mp(2) . "</span><br/>";
							?>
						</div>
					</li>

					<!-- Total Legend -->
					<li style="border-color: <?php echo $this->chart_colours[3]; ?>" class="highlight_overall">
						<strong>
							<span class="amount"><?php echo wc_price( $this->get_mp_total(0) + $this->get_mp_total(1) + $this->get_mp_total(2) ); ?></span>
						</strong>
						<span class="channel-name">Total</span>
						<span class="percent">&nbsp;(&nbsp;<?php echo "100%" ; ?>&nbsp;)&nbsp;</span><br/>
						<div class="details-wrapper">
							<?php 
								$ws_bestseller = $this->get_best_seller_mp(3); 
								echo "<span>" . 'Best Seller : ' . $ws_bestseller['name'] . "(" . $ws_bestseller['qty'] . ")" . "</span><br/>";
								echo "<span>" . 'Products Sold : ' . $this->get_products_count_mp(3) . "</span><br/>";
								echo "<span>" . 'Orders Placed : ' . $this->get_orders_count_mp(3) . "</span><br/>";
							?>
						</div>
					</li>

				</ul>
			</div>
			<div class="main">
				<div class="chart-container">
					<div class="chart-placeholder main">
					</div>
				</div>
			</div>
		</div>		
	</div>
</div>

<script type="text/javascript">
	jQuery(document).ready(function (){

		// init chart type
		var chart_type = "bar";
		jQuery(".chart-type > select").val("bar");

		// active current tab
		var range_param = "<?php if (!isset($_GET['range'])) echo 'last7day'; else echo $_GET['range']; ?>";
		jQuery('#'+range_param).addClass("active");

		var series;

		function draw_graph( highlight )
		{
			// Draw Pie
			if ( chart_type == "pie" ) {
				draw_pie( highlight );
				return;
			}

			// Draw Bar/Line
			if ( chart_type == "bar" )	{

				series = [
					<?php
						$name = array("WebStore", "Amazon", "eBay");
						$width  = $this->barwidth / 3;
						$data_backup = $this->data;
						for($index=0; $index<3; $index++) {
							$color  = isset( $this->chart_colours[ $index ] ) ? $this->chart_colours[ $index ] : $this->chart_colours[0];
							$offset = ( $width * $index );
							foreach ( $data_backup[$index] as $key => $series_data ) {
								$data_backup[ $index ][ $key ][0] = $series_data[0] + $offset;
							}
							echo '{
								label: "' . $name[$index] . '",
								data: jQuery.parseJSON( ' . "'". json_encode( $data_backup[$index] ) ."'" .' ),
								color: "' . $color . '",
								bars: {
									fillColor: "' . $color . '",
									fill: true,
									show: true,
									lineWidth: 1,
									align: "center",
									barWidth: ' . $width * 0.75 . ',
									stack: false
								},
								' . $this->get_currency_tooltip() . ',
								enable_tooltip: true,
								prepend_label: true
								},';
						}
					?>
				];

			} else if ( chart_type == "line" ) {

				series = [
					<?php
						$name = array("WebStore", "Amazon", "eBay");
						for($index=0; $index<3; $index++) {
							$color  = isset( $this->chart_colours[ $index ] ) ? $this->chart_colours[ $index ] : $this->chart_colours[0];
							echo '{
								label: "' . $name[$index] . '",
								data: jQuery.parseJSON( ' . "'". json_encode( $this->data[$index] ) ."'" .' ),
								color: "' . $color . '",
								points: { show: true, radius: 5, lineWidth: 4, fillColor: "#fff", fill: true },
								lines: { show: true, lineWidth: 5, fill: false },
								' . $this->get_currency_tooltip() . ',
								enable_tooltip: true,
								prepend_label: true
								},';
						}
					?>
				];

			} 

			// if users mouse over webstore/amazon/ebay legend
			if ( highlight !== 'undefined' && series[ highlight ] ) {
				
				highlight_series = series[ highlight ];

				highlight_series.color = '#9c5d90';

				if ( highlight_series.bars ) {
					highlight_series.bars.fillColor = '#9c5d90';
				}

				if ( highlight_series.lines ) {
					highlight_series.lines.lineWidth = 5;
				}
			}

			// if users mouse over overall legend
			if ( highlight == '3' ) {
				
				for ( i = 0 ; i < 3 ; i++ ) {

					highlight_series = series[ i ];

					highlight_series.color = '#9c5d90';

					if ( highlight_series.bars ) {
						highlight_series.bars.fillColor = '#9c5d90';
					}

					if ( highlight_series.lines ) {
						highlight_series.lines.lineWidth = 5;
					}
				}
			}

			jQuery.plot(
				jQuery('.chart-placeholder.main'),
				series,
				{
					legend: {
						show: false
					},
					grid: {
						color: '#aaa',
						borderColor: 'transparent',
						borderWidth: 0,
						hoverable: true
					},
					xaxes: [ {
						color: '#aaa',
						reserveSpace: true,
						position: "bottom",
						tickColor: '#transparent',
						mode: "time",
						timeformat: "<?php if ( $this->chart_groupby == 'day' ) echo '%d %b'; else echo '%b'; ?>",
						monthNames: <?php global $wp_locale; echo json_encode( array_values( $wp_locale->month_abbrev ) ); ?>,
						tickLength: 1,
						minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
						tickSize: [1, "<?php echo $this->chart_groupby; ?>"],
						font: {	color: "#aaa" }
						} ],
					yaxes: [ {
							//min: 0,
							tickDecimals: 2,
							color: '#d4d9dc',
							font: { color: "#aaa" }
						} ],
					}
			);
		}

		function draw_pie(highlight) {

				dataSet = [
					<?php
						$name = array("WebStore", "Amazon", "eBay");
						for($index=0; $index<3; $index++) {
							$color = isset( $this->chart_colours[ $index ] ) ? $this->chart_colours[ $index ] : $this->chart_colours[0];
							$total = $this->get_mp_total( $index );
							echo '{
								label: "' . $name[$index] . '",
								data: "' . $total . '",
								color: "' . $color . '",
								},';
						}
					?>
				];

				// if users mouse over webstore/amazon/ebay legend
				if ( highlight !== 'undefined' && dataSet[ highlight ] ) {
					highlight_series = dataSet[ highlight ];
					highlight_series.color = '#9c5d90';
				}

				// if users mouse over overall legend
				if ( highlight == '3' ) {
					for ( i = 0 ; i < 3 ; i++ ) {
						highlight_series = dataSet[ i ];
						highlight_series.color = '#9c5d90';
					}
				}

				jQuery.plot('.chart-placeholder.main', dataSet, {
					series: {
						pie: {
								show: true,
								radius: 1,
								label: {
									show: true,
									radius: 3/4,
									formatter: function ( label, series ) { 
									return '<div style="border:1px solid grey;font-size:8pt;text-align:center;padding:5px;color:white;">' +
						                label + ' : ' + Math.round(series.percent) + '%</div>';
									},
									background: {
										opacity: 0.5
									}
								}
							}
						},
						legend: {
							show: false
						},
					    grid: {
					        hoverable: true
					    }
				});

		}

		draw_graph();

		jQuery('.highlight_series').hover(
			function() {
				draw_graph( jQuery(this).data('series') );
			},
			function() {
				draw_graph();
			}
		);

		jQuery('.highlight_overall').hover(
			function () {
				draw_graph( 3 );
			},
			function () {
				draw_graph();
			}
		);

		jQuery('.export_csv_marketplace').click(function () {
			var csv_data = 'data:application/csv;charset=utf-8,';
			var groupby       = jQuery( this ) .data( 'groupby' );

			// CSV Headers
			csv_data += "Date,WebStore,Amazon,eBay\n";

			for (i=0; i<series[0]['data'].length; i++) {
				var new_csv_line = "";

				var date = new Date( parseInt( series[0]['data'][i][0] ) );

				if ( groupby === 'day' ) {
					new_csv_line += date.getUTCFullYear() + '-' + parseInt( date.getUTCMonth() + 1, 10 ) + '-' + date.getUTCDate() + ',';
				} else {
					new_csv_line += date.getUTCFullYear() + '-' + parseInt( date.getUTCMonth() + 1, 10 ) + ',';
				}

				var websto_amount = series[0]['data'][i][1];
				var amazon_amount = series[1]['data'][i][1];
				var ebaymp_amount = series[2]['data'][i][1];

				new_csv_line += websto_amount + "," + amazon_amount + "," + ebaymp_amount + "\n";
				
				csv_data += new_csv_line;
			}

			// Set data as href and return
			jQuery( this ).attr( 'href', encodeURI( csv_data ) );
			return true;
		});

		jQuery(".chart-type > select").change(function () {
			chart_type = jQuery(this).val();
			draw_graph();
		});

	});
</script>