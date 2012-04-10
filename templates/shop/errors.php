<?php
/**
 * Error messages
 */
if ( ! $errors ) return;
?>
<ul class="woocommerce_error">
	<?php foreach ( $errors as $error ) : ?>
		<li><?php echo $error; ?></li>
	<?php endforeach; ?>
</ul>