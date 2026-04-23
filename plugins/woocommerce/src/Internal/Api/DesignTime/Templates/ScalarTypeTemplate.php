<?php
/**
 * Template for generating a GraphQL CustomScalarType class.
 *
 * @var string $namespace
 * @var string $class_name
 * @var string $graphql_name
 * @var string $description
 * @var string $scalar_fqcn
 * @var string $scalar_alias
 */

$escaped_description = addslashes( $description );
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

use <?php echo $scalar_fqcn; ?> as <?php echo $scalar_alias; ?>;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\CustomScalarType;

class <?php echo $class_name; ?> {
	private static ?CustomScalarType $instance = null;

	public static function get(): CustomScalarType {
		if ( null === self::$instance ) {
			self::$instance = new CustomScalarType(
				array(
					'name'         => '<?php echo $graphql_name; ?>',
<?php if ( $description !== '' ) : ?>
					'description'  => __( '<?php echo $escaped_description; ?>', 'woocommerce' ),
<?php endif; ?>
					'serialize'    => fn( $value ) => <?php echo $scalar_alias; ?>::serialize( $value ),
					'parseValue'   => function ( $value ) {
						try {
							return <?php echo $scalar_alias; ?>::parse( $value );
						} catch ( \InvalidArgumentException $e ) {
							throw new \Automattic\WooCommerce\Vendor\GraphQL\Error\Error( $e->getMessage() );
						}
					},
					'parseLiteral' => function ( $value_node, ?array $variables = null ) {
						if ( $value_node instanceof \Automattic\WooCommerce\Vendor\GraphQL\Language\AST\StringValueNode ) {
							try {
								return <?php echo $scalar_alias; ?>::parse( $value_node->value );
							} catch ( \InvalidArgumentException $e ) {
								throw new \Automattic\WooCommerce\Vendor\GraphQL\Error\Error( $e->getMessage() );
							}
						}
						throw new \Automattic\WooCommerce\Vendor\GraphQL\Error\Error(
							'<?php echo $graphql_name; ?> must be a string, got: ' . $value_node->kind
						);
					},
				)
			);
		}
		return self::$instance;
	}
}
