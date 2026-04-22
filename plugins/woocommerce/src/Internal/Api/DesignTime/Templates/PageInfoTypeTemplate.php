<?php
/**
 * Template for generating the shared PageInfo GraphQL type class.
 *
 * @var string $namespace
 */
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PageInfo {
	private static ?ObjectType $instance = null;

	public static function get(): ObjectType {
		if ( null === self::$instance ) {
			self::$instance = new ObjectType(
				array(
					'name'   => 'PageInfo',
					'fields' => array(
						'has_next_page'     => array(
							'type' => Type::nonNull( Type::boolean() ),
						),
						'has_previous_page' => array(
							'type' => Type::nonNull( Type::boolean() ),
						),
						'start_cursor'      => array(
							'type' => Type::string(),
						),
						'end_cursor'        => array(
							'type' => Type::string(),
						),
					),
				)
			);
		}
		return self::$instance;
	}
}
