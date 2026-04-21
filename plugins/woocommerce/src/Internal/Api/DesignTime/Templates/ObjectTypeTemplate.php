<?php
/**
 * Template for generating a GraphQL ObjectType class.
 *
 * @var string $namespace
 * @var string $class_name
 * @var string $graphql_name
 * @var string $description
 * @var array  $use_statements
 * @var array  $interfaces - each: ['alias' => string]
 * @var array  $fields - each: ['name', 'type_expr', 'description', 'args' => [], 'deprecation_reason' => ?string, 'paginated_connection' => bool]
 */

$escaped_description = addslashes( $description );
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

<?php
$has_paginated_connection = false;
foreach ( $fields as $f ) {
	if ( ! empty( $f['paginated_connection'] ) ) {
		$has_paginated_connection = true;
		break;
	}
}
?>
<?php foreach ( $use_statements as $use ) : ?>
use <?php echo $use; ?>;
<?php endforeach; ?>
<?php if ( $has_paginated_connection ) : ?>
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Internal\Api\Utils;
<?php endif; ?>
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class <?php echo $class_name; ?> {
	private static ?ObjectType $instance = null;

	public static function get(): ObjectType {
		if ( null === self::$instance ) {
			self::$instance = new ObjectType(
				array(
					'name' => '<?php echo $graphql_name; ?>',
<?php if ( $description !== '' ) : ?>
					'description' => __( '<?php echo $escaped_description; ?>', 'woocommerce' ),
<?php endif; ?>
<?php if ( ! empty( $interfaces ) ) : ?>
					'interfaces' => fn() => array(
	<?php foreach ( $interfaces as $iface ) : ?>
						<?php echo $iface['alias']; ?>::get(),
<?php endforeach; ?>
					),
<?php endif; ?>
					'fields' => fn() => array(
<?php foreach ( $fields as $field ) : ?>
						'<?php echo $field['name']; ?>' => array(
							'type' => <?php echo $field['type_expr']; ?>,
	<?php if ( ! empty( $field['description'] ) ) : ?>
							'description' => __( '<?php echo addslashes( $field['description'] ); ?>', 'woocommerce' ),
<?php endif; ?>
	<?php if ( ! empty( $field['args'] ) ) : ?>
							'args' => array(
		<?php foreach ( $field['args'] as $arg ) : ?>
								'<?php echo $arg['name']; ?>' => array(
									'type' => <?php echo $arg['type_expr']; ?>,
			<?php if ( array_key_exists( 'default', $arg ) ) : ?>
									'defaultValue' => <?php echo var_export( $arg['default'], true ); ?>,
<?php endif; ?>
			<?php if ( ! empty( $arg['description'] ) ) : ?>
									'description' => __( '<?php echo addslashes( $arg['description'] ); ?>', 'woocommerce' ),
<?php endif; ?>
								),
<?php endforeach; ?>
							),
<?php endif; ?>
	<?php if ( ! empty( $field['deprecation_reason'] ) ) : ?>
							'deprecationReason' => '<?php echo addslashes( $field['deprecation_reason'] ); ?>',
<?php endif; ?>
	<?php if ( ! empty( $field['paginated_connection'] ) ) : ?>
							'complexity' => Utils::complexity_from_pagination(...),
							'resolve'    => fn( $parent, array $args ): Connection => Utils::translate_exceptions( fn() => $parent-><?php echo $field['name']; ?>->slice( $args ) ),
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
