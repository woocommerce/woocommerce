<?php /* phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
<html>
<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php echo get_option( 'blog_charset' ); ?>" />
	<style>
		html { font-family: "Helvetica Neue", sans-serif; font-size: <?php echo $constants['font_size']; ?>pt; }
		header { margin-top: <?php echo $constants['margin']; ?>; }
		h1 { font-size: <?php echo $constants['title_font_size']; ?>pt; font-weight: 500; text-align: center; }
		h3 { color: #707070; margin:0; }
		table {
			background-color:#F5F5F5;
			width:100%;
			color: #707070;
			margin: <?php echo $constants['margin'] / 2; ?>pt 0;
			padding: <?php echo $constants['margin'] / 2; ?>pt;
		}
		table td:last-child { width: 30%; text-align: right; }
		table tr:last-child { color: #000000; font-weight: bold; }
		footer {
			font-size: <?php echo $constants['footer_font_size']; ?>pt;
			border-top: 1px solid #707070;
			margin-top: <?php echo $constants['margin']; ?>pt;
			padding-top: <?php echo $constants['margin']; ?>pt;
		}
		p { line-height: <?php echo $constants['line_height']; ?>pt; margin: 0 0 <?php echo $constants['margin'] / 2; ?> 0; }
		<?php if ( $payment_info ) { ?>
			.card-icon {
				width: <?php echo $constants['icon_width']; ?>pt;
				height: <?php echo $constants['icon_height']; ?>pt;
				vertical-align: top;
				background-repeat: no-repeat;
				background-position-y: center;
				display: inline-block;
				background-image: url("data:image/svg+xml;base64,<?php echo $payment_info['card_icon']; ?>");
			}
		<?php } ?>
	</style>
</head>

<body>
<header>
	<h1><?php echo $texts['receipt_title']; ?></h1>
	<h3><?php echo strtoupper( $texts['amount_paid_section_title'] ); ?></h3>
	<p>
		<?php echo $formatted_amount; ?>
	</p>
	<h3><?php echo strtoupper( $texts['date_paid_section_title'] ); ?></h3>
	<p>
		<?php echo $formatted_date; ?>
	</p>
	<?php if ( $payment_method ) { ?>
		<h3><?php echo strtoupper( $texts['payment_method_section_title'] ); ?></h3>
		<p>
			<?php if ( $payment_info ) { ?>
				<span class="card-icon"></span> - <?php echo $payment_info['card_last4']; ?>
			<?php } else { ?>
				<p><?php echo $payment_method; ?></p>
			<?php } ?>
		</p>
	<?php } ?>
</header>

<h3><?php echo strtoupper( $texts['summary_section_title'] ); ?></h3>
<table>
	<?php
	foreach ( $line_items as $line_item ) {
		if ( isset( $line_item['quantity'] ) ) {
			?>
			<tr><td><?php echo $line_item['title']; ?> × <?php echo $line_item['quantity']; ?></td><td><?php echo $line_item['amount']; ?></td></tr>
		<?php } else { ?>
			<tr><td><?php echo $line_item['title']; ?></td><td><?php echo $line_item['amount']; ?></td></tr>
			<?php
		}
	}
	?>
</table>

<?php if ( ! empty( $notes ) ) { ?>
	<h3><?php echo strtoupper( $texts['order_notes_section_title'] ); ?></h3>
	<?php foreach ( $notes as $note ) { ?>
		<p><?php echo $note; ?></p>
		<?php
	}
}

if ( $payment_info ) {
	?>
	<footer>
		<p>
			<?php
			if ( $payment_info['app_name'] ) {
				echo $texts['app_name'] . ': ' . $payment_info['app_name'] . '<br/>';
			}
			if ( $payment_info['aid'] ) {
				echo $texts['aid'] . ': ' . $payment_info['aid'] . '<br/>';
			}
			if ( $payment_info['account_type'] ) {
				echo $texts['account_type'] . ': ' . $payment_info['account_type'];
			}
			?>
		</p>
	</footer>
<?php } ?>

</body>
</html>
