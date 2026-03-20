<?php
/**
 * Template for generating a GraphQL InputObjectType class.
 *
 * @var string $namespace
 * @var string $class_name
 * @var string $graphql_name
 * @var string $description
 * @var array  $use_statements
 * @var array  $fields - each: ['name', 'type_expr', 'description']
 */

$escaped_description = addslashes( $description );
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

<?php foreach ( $use_statements as $use ) : ?>
use <?php echo $use; ?>;
<?php endforeach; ?>
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;

class <?php echo $class_name; ?> {
	private static ?InputObjectType $instance = null;

	public static function get(): InputObjectType {
		if ( null === self::$instance ) {
			self::$instance = new InputObjectType(
				array(
					'name' => '<?php echo $graphql_name; ?>',
<?php if ( $description !== '' ) : ?>
					'description' => __( '<?php echo $escaped_description; ?>', 'woocommerce' ),
<?php endif; ?>
					'fields' => fn() => array(
<?php foreach ( $fields as $field ) : ?>
						'<?php echo $field['name']; ?>' => array(
							'type' => <?php echo $field['type_expr']; ?>,
	<?php if ( ! empty( $field['description'] ) ) : ?>
							'description' => __( '<?php echo addslashes( $field['description'] ); ?>', 'woocommerce' ),
<?php endif; ?>
						),
<?php endforeach; ?>
					),
				)
			);
		}
		return self::$instance;
	}
}
