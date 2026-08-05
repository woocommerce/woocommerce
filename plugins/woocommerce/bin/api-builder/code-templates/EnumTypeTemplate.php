<?php
/**
 * Template for generating a GraphQL EnumType class.
 *
 * @var string $namespace
 * @var string $class_name
 * @var string $graphql_name
 * @var string $description
 * @var string $enum_fqcn
 * @var string $enum_alias
 * @var array  $values - each: ['graphql_name', 'case_name', 'description', 'deprecation_reason' => ?string, 'metadata' => array]
 * @var array  $metadata - type-level metadata, name => scalar value.
 */

$escaped_description = addslashes( $description );
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

use <?php echo $enum_fqcn; ?> as <?php echo $enum_alias; ?>;
use Automattic\WooCommerce\Api\Infrastructure\Schema\EnumType;

class <?php echo $class_name; ?> {
	private static ?EnumType $instance = null;

	public static function get(): EnumType {
		if ( null === self::$instance ) {
			self::$instance = new EnumType(
				array(
					'name' => '<?php echo $graphql_name; ?>',
<?php if ( $description !== '' ) : ?>
					'description' => __( '<?php echo $escaped_description; ?>', 'woocommerce' ),
<?php endif; ?>
<?php if ( ! empty( $metadata ) ) : ?>
					'metadata' => array(
<?php foreach ( $metadata as $meta_name => $meta_value ) : ?>
						<?php echo var_export( $meta_name, true ); ?> => <?php echo var_export( $meta_value, true ); ?>,
<?php endforeach; ?>
					),
<?php endif; ?>
					'values' => array(
<?php foreach ( $values as $val ) : ?>
						'<?php echo $val['graphql_name']; ?>' => array(
							'value' => <?php echo $enum_alias; ?>::<?php echo $val['case_name']; ?>,
	<?php if ( ! empty( $val['description'] ) ) : ?>
							'description' => __( '<?php echo addslashes( $val['description'] ); ?>', 'woocommerce' ),
<?php endif; ?>
	<?php if ( ! empty( $val['deprecation_reason'] ) ) : ?>
							'deprecationReason' => '<?php echo addslashes( $val['deprecation_reason'] ); ?>',
<?php endif; ?>
	<?php if ( ! empty( $val['metadata'] ) ) : ?>
							'metadata' => array(
		<?php foreach ( $val['metadata'] as $meta_name => $meta_value ) : ?>
								<?php echo var_export( $meta_name, true ); ?> => <?php echo var_export( $meta_value, true ); ?>,
<?php endforeach; ?>
							),
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
