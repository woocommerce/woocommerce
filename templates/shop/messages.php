<?php
/**
 * Messages
 */
if ( ! $messages ) return;
?>

<?php foreach ( $messages as $message ) : ?>
	<div class="woocommerce_message"><?php echo $message; ?></div>
<?php endforeach; ?>
