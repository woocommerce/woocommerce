<?php
/**
 * Template for generating the TypeRegistry class.
 *
 * Lists all concrete types that implement interfaces, so the schema
 * can register them for inline fragment resolution.
 *
 * @var string $namespace
 * @var array  $types - each: ['short_name', 'fqcn']
 */
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

<?php foreach ( $types as $type ) : ?>
use <?php echo $type['fqcn']; ?>;
<?php endforeach; ?>

class TypeRegistry {
	/**
	 * Return all concrete types that implement interfaces.
	 *
	 * Pass this to the Schema 'types' config so that inline fragments
	 * (e.g. `... on VariableProduct`) are resolvable.
	 *
	 * @return array
	 */
	public static function get_interface_implementors(): array {
		return array(
<?php foreach ( $types as $type ) : ?>
			<?php echo $type['short_name']; ?>::get(),
<?php endforeach; ?>
		);
	}
}
