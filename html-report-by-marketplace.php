<?php
/**
 * Admin View: Report by Date (with date filters)
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
</style>

<div id="poststuff" class="woocommerce-reports-wide">
	<div class="postbox">
		<h3 class="stats_range">
			<?php $this->get_export_button(); ?>
			<ul>
				<li id="year" class=""><a href="?page=wc-reports&tab=orders&report=sales_by_marketplace&range=year">Year</a></li>
				<li id="lastmonth" class=""><a href="?page=wc-reports&tab=orders&report=sales_by_marketplace&range=lastmonth">Last Month</a></li>
				<li id="thismonth" class=""><a href="?page=wc-reports&tab=orders&report=sales_by_marketplace&range=thismonth">This Month</a></li>
				<li id="last7day" class=""><a href="?page=wc-reports&tab=orders&report=sales_by_marketplace&range=last7day">Last 7 Days</a></li>				
				<li id="custom" class="custom">
					Custom:					
					<form method="GET">
						<div>
							<input name="start_date" value="" type="hidden">
							<input name="end_date" value="" type="hidden">
							<input name="page" value="wc-reports" type="hidden">
							<input name="tab" value="orders" type="hidden">
							<input name="report" value="sales_by_marketplace" type="hidden">							
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
					<li style="border-color: <?php echo $this->chart_colours[0]; ?>" class="highlight_series tips" data-series="0" data-tip="This is the sum of the WebStore order totals">
						<strong><span class="amount"><?php echo $this->get_mp_total_formatted(0); ?></span></strong>WebStore
					</li>
					<li style="border-color: <?php echo $this->chart_colours[1]; ?>" class="highlight_series tips" data-series="1" data-tip="This is the sum of the Amazon order totals">
						<strong><span class="amount"><?php echo $this->get_mp_total_formatted(1); ?></span></strong>Amazon
					</li>
					<li style="border-color: <?php echo $this->chart_colours[2]; ?>" class="highlight_series tips" data-series="2" data-tip="This is the sum of the EBay order totals">
						<strong><span class="amount"><?php echo $this->get_mp_total_formatted(2); ?></span></strong>EBay
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

		// active current tab
		var range_param = "<?php if (!isset($_GET['range'])) echo 'last7day'; else echo $_GET['range']; ?>";
		jQuery('#'+range_param).addClass("active");

		var series;

		function draw_graph(highlight)
		{
			series = [
							<?php
								$name = array("WebStore", "Amazon", "EBay");
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
						tickColor: 'transparent',
						mode: "time",
						timeformat: "<?php if ( $this->chart_groupby == 'day' ) echo '%d %b'; else echo '%b'; ?>",
						monthNames: <?php global $wp_locale; echo json_encode( array_values( $wp_locale->month_abbrev ) ); ?>,
						tickLength: 1,
						minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
						tickSize: [1, "<?php echo $this->chart_groupby; ?>"],
						font: {
							color: "#aaa"
						}
						} ],
						yaxes: [
							{
								min: 0,
								tickDecimals: 2,
								color: 'transparent',
								font: { color: "#aaa" }
							}
							],
					}
			);
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

		jQuery('.export_csv_marketplace').click(function () {
			var csv_data = 'data:application/csv;charset=utf-8,';
			var groupby       = jQuery( this ) .data( 'groupby' );

			// CSV Headers
			csv_data += "Date,WebStore,Amazon,EBay\n";

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
	});
</script>
<!--

-->