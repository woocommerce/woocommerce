<?php
/**
 * Template for generating a GraphQL InterfaceType class.
 *
 * @var string $namespace
 * @var string $class_name
 * @var string $graphql_name
 * @var string $description
 * @var array  $use_statements
 * @var array  $fields - each: ['name', 'type_expr', 'description', 'args' => [], 'deprecation_reason' => ?string]
 * @var array  $type_map - each: ['fqcn' => string, 'alias' => string] mapping PHP FQCN to generated ObjectType alias
 */

$escaped_description = addslashes( $description );
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

<?php
// Drop any caller-supplied import whose effective short name would collide
// with one of the hardcoded imports emitted below, otherwise the generated
// file wouldn't compile ("Cannot use ... because the name is already in use").
$reserved_short_names = array( 'InterfaceType', 'Type' );
// PHP class-name resolution (including `use`) is case-insensitive, so the
// collision check has to be too — a caller-supplied `Foo\type` would
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
use Automattic\WooCommerce\Internal\Api\Schema\InterfaceType;
use Automattic\WooCommerce\Internal\Api\Schema\Type;

class <?php echo $class_name; ?> {
	private static ?InterfaceType $instance = null;

	public static function get(): InterfaceType {
		if ( null === self::$instance ) {
			self::$instance = new InterfaceType(
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
						),
<?php endforeach; ?>
					),
					'resolveType' => function ( $value ) {
						$class = get_class( $value );
						$map = array(
<?php foreach ( $type_map as $entry ) : ?>
							'<?php echo $entry['fqcn']; ?>' => <?php echo $entry['alias']; ?>::get(),
<?php endforeach; ?>
						);
						return $map[ $class ] ?? null;
					},
				)
			);
		}
		return self::$instance;
	}
}
