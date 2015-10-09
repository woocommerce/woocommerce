<?php echo strtoupper( $heading ); ?> 

<?php foreach ( $fields as $field ) : ?>
<?php if ( isset( $field['label'] ) && isset( $field['value'] ) && $field['value'] ) : ?>
<?php echo $field['label']; ?>: <?php echo $field['value']; ?> 
<?php endif; ?>
<?php endforeach; ?>
