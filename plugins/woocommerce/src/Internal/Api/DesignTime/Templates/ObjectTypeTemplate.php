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
// Drop any caller-supplied import whose effective short name would collide
// with one of the hardcoded imports emitted below, otherwise the generated
// file wouldn't compile ("Cannot use ... because the name is already in use").
$reserved_short_names = array( 'ObjectType', 'Type' );
if ( $has_paginated_connection ) {
	$reserved_short_names[] = 'Connection';
	$reserved_short_names[] = 'Utils';
}
// PHP class-name resolution (including `use`) is case-insensitive, so the
// collision check has to be too — a caller-supplied `Foo\resolveinfo` would
// otherwise slip past and fail at compile time of the generated file.
$reserved_short_names_lower = array_map( 'strtolower', $reserved_short_names );
$use_statements             = array_values(
	array_filter(
		$use_statements,
		static function ( $use ) use ( $reserved_short_names_lower ) {
			$as_pos = stripos( $use, ' as ' );
			if ( false !== $as_pos ) {
				$short = trim( substr( $use, $as_pos + 4 ) );
			} else {
				$sep_pos = strrpos( $use, '\\' );
				$short   = false !== $sep_pos ? substr( $use, $sep_pos + 1 ) : $use;
			}
			return ! in_array( strtolower( $short ), $reserved_short_names_lower, true );
		}
	)
);
?>
<?php foreach ( $use_statements as $use ) : ?>
use <?php echo $use; ?>;
<?php endforeach; ?>
<?php if ( $has_paginated_connection ) : ?>
use Automattic\WooCommerce\Api\Pagination\Connection;
use Automattic\WooCommerce\Internal\Api\Utils;
<?php endif; ?>
use Automattic\WooCommerce\Internal\Api\Schema\ObjectType;
use Automattic\WooCommerce\Internal\Api\Schema\Type;

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
