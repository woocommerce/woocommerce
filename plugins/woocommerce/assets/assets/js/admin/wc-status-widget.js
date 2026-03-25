/*global jQuery */
(function( $ ) {
	$( function() {
		window.wcTracks.recordEvent( 'wcadmin_status_widget_view' );
	});

	var recordEvent = function( link ) {
		window.wcTracks.recordEvent( 'status_widget_click', {
			link: link
		} );
	};

	$( '.sales-this-month a' ).on( 'click', function() {
		recordEvent( 'net-sales' );
	});

	$( '.best-seller-this-month a' ).on( 'click', function() {
		recordEvent( 'best-seller-this-month' );
	});

	$( '.processing-orders a' ).on( 'click', function() {
		recordEvent( 'orders-processing' );
	});

	$( '.on-hold-orders a' ).on( 'click', function() {
		recordEvent( 'orders-on-hold' );
	});

	$( '.low-in-stock a' ).on( 'click', function() {
	   recordEvent( 'low-stock' );
	});

	$( '.out-of-stock a' ).on( 'click', function() {
		recordEvent( 'out-of-stock' );
	});

	$( '.wc_sparkline.bars' ).each( function () {
		const chartData = $( this ).data( 'sparkline' );

		const options = {
			grid: {
				show: false,
			},
		};

		// main series
		const series = [
			{
				data: chartData,
				color: $( this ).data( 'color' ),
				bars: {
					fillColor: $( this ).data( 'color' ),
					fill: true,
					show: true,
					lineWidth: 1,
					barWidth: $( this ).data( 'barwidth' ),
					align: 'center',
				},
				shadowSize: 0,
			},
		];

		// draw the sparkline
		$.plot( $( this ), series, options );
	} );

	$( '.wc_sparkline.lines' ).each( function () {
		const chartData = $( this ).data( 'sparkline' );

		const options = {
			grid: {
				show: false,
			},
		};

		// main series
		const series = [
			{
				data: chartData,
				color: $( this ).data( 'color' ),
				lines: {
					fill: false,
					show: true,
					lineWidth: 1,
					align: 'center',
				},
				shadowSize: 0,
			},
		];

		// draw the sparkline
		$.plot( $( this ), series, options );
	} );
})( jQuery );
