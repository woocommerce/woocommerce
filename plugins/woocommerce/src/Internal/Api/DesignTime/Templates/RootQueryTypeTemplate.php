<?php
/**
 * Template for generating the RootQueryType class.
 *
 * @var string $namespace
 * @var array  $queries - each: ['class_name', 'fqcn', 'graphql_name']
 */
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

<?php foreach ( $queries as $query ) : ?>
use <?php echo $query['fqcn']; ?>;
<?php endforeach; ?>
use Automattic\WooCommerce\Internal\Api\Schema\ObjectType;

class RootQueryType {
	private static ?ObjectType $instance = null;

	public static function get(): ObjectType {
		if ( null === self::$instance ) {
			self::$instance = new ObjectType(
				array(
					'name'   => 'Query',
					'fields' => fn() => array(
<?php foreach ( $queries as $query ) : ?>
						'<?php echo $query['graphql_name']; ?>' => <?php echo $query['class_name']; ?>::get_field_definition(),
<?php endforeach; ?>
					),
				)
			);
		}
		return self::$instance;
	}
}
