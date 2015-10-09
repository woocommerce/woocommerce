<h2><?php echo $heading; ?></h2>
<?php foreach ( $fields as $field ) : ?>
<?php if ( isset( $field['label'] ) && isset( $field['value'] ) && $field['value'] ) : ?>
<p><strong><?php echo $field['label']; ?></strong><?php echo $field['value']; ?></p>
<?php endif;?>
<?php endforeach; ?>
