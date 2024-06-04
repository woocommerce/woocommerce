<?php /* phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
<html>
<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php echo get_option( 'blog_charset' ); ?>" />
	<style>
<?php echo $data['css']; ?>
	</style>
</head>

<body>
<header>
	<h1 id="receipt_title"><?php echo $data['texts']['receipt_title']; ?></h1>
	<h3 id="amount_paid_section_title"><?php echo strtoupper( $data['texts']['amount_paid_section_title'] ); ?></h3>
	<p>
		<?php echo $data['formatted_amount']; ?>
	</p>
	<h3 id="date_paid_section_title"><?php echo strtoupper( $data['texts']['date_paid_section_title'] ); ?></h3>
	<p>
		<?php echo $data['formatted_date']; ?>
	</p>
	<?php if ( isset( $data['payment_method'] ) ) { ?>
		<h3 id="payment_method_section_title"><?php echo strtoupper( $data['texts']['payment_method_section_title'] ); ?></h3>
		<p>
			<?php if ( $data['payment_info'] ) { ?>
				<span class="card-icon"></span> - <?php echo $data['payment_info']['card_last4']; ?>
			<?php } else { ?>
				<p><?php echo $data['payment_method']; ?></p>
			<?php } ?>
		</p>
	<?php } ?>
</header>

<h3 id="summary_section_title"><?php echo strtoupper( $data['texts']['summary_section_title'] ); ?></h3>
<table id="line_items">
	<?php
	foreach ( $data['formatted_line_items'] as $formatted_line_item ) {
		echo $formatted_line_item;
	}
	?>
</table>

<?php if ( ! empty( $data['notes'] ) ) { ?>
	<h3 id="order_notes_section_title"><?php echo strtoupper( $data['texts']['order_notes_section_title'] ); ?></h3>
	<?php foreach ( $data['notes'] as $note ) { ?>
		<p><?php echo $note; ?></p>
		<?php
	}
}

if ( isset( $data['payment_info'] ) ) {
	?>
	<footer>
		<p id="payment_info">
			<?php
			if ( $data['payment_info']['app_name'] ) {
				echo $data['texts']['app_name'] . ': ' . $data['payment_info']['app_name'] . '<br/>';
			}
			if ( $data['payment_info']['aid'] ) {
				echo $data['texts']['aid'] . ': ' . $data['payment_info']['aid'] . '<br/>';
			}
			if ( $data['payment_info']['account_type'] ) {
				echo $data['texts']['account_type'] . ': ' . $data['payment_info']['account_type'];
			}
			?>
		</p>
	</footer>
<?php } ?>

</body>
</html>
