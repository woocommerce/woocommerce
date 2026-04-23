<?php
/**
 * Template for generating the RootMutationType class.
 *
 * @var string $namespace
 * @var array  $mutations - each: ['class_name', 'fqcn', 'graphql_name']
 */
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

<?php foreach ( $mutations as $mutation ) : ?>
use <?php echo $mutation['fqcn']; ?>;
<?php endforeach; ?>
use Automattic\WooCommerce\Vendor\GraphQL\Type\Definition\ObjectType;

class RootMutationType {
	private static ?ObjectType $instance = null;

	public static function get(): ObjectType {
		if ( null === self::$instance ) {
			self::$instance = new ObjectType(
				array(
					'name'   => 'Mutation',
					'fields' => fn() => array(
<?php foreach ( $mutations as $mutation ) : ?>
						'<?php echo $mutation['graphql_name']; ?>' => <?php echo $mutation['class_name']; ?>::get_field_definition(),
<?php endforeach; ?>
					),
				)
			);
		}
		return self::$instance;
	}
}
