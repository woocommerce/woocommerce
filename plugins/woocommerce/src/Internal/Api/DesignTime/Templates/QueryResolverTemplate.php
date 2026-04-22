<?php
/**
 * Template for generating a query/mutation resolver class.
 *
 * @var string $namespace
 * @var string $class_name
 * @var string $graphql_name
 * @var string $description
 * @var string $command_fqcn
 * @var string $command_alias
 * @var string $return_type_expr
 * @var array  $use_statements
 * @var array  $args - each: ['name', 'type_expr', 'description', 'has_default', 'default']
 * @var array  $capabilities
 * @var bool   $public_access
 * @var bool   $has_connection_of
 * @var string $connection_type_alias
 * @var array  $execute_params - each: ['name', 'conversion' => ?string, 'is_infrastructure' => bool, 'unroll' => ?array]
 * @var array  $input_converters - each: ['method_name', 'input_fqcn', 'input_class', 'properties' => [['name', 'conversion']]]
 * @var ?array $authorize_param_names - if non-null, the authorize() method param names (subset of execute params)
 * @var bool   $has_preauthorized - true when authorize() declares a bool $_preauthorized infrastructure param
 * @var string $preauthorized_expr - PHP expression that evaluates to the $_preauthorized bool at runtime
 * @var bool   $scalar_return - true when execute() returns a scalar (bool, int, float, string)
 */

$escaped_description = addslashes( $description );
$has_authorize       = $authorize_param_names !== null;
$has_cap_check       = ! $has_authorize && ! $public_access && ! empty( $capabilities );
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

use <?php echo $command_fqcn; ?> as <?php echo $command_alias; ?>;
use Automattic\WooCommerce\Internal\Api\QueryInfoExtractor;
use Automattic\WooCommerce\Internal\Api\Utils;
<?php foreach ( $use_statements as $use ) : ?>
use <?php echo $use; ?>;
<?php endforeach; ?>
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;

class <?php echo $class_name; ?> {
	public static function get_field_definition(): array {
		return array(
<?php if ( $scalar_return ) : ?>
			'type' => Type::nonNull(new \GraphQL\Type\Definition\ObjectType(array(
				'name' => '<?php echo $class_name; ?>Result',
				'fields' => array(
					'result' => array( 'type' => <?php echo $return_type_expr; ?> ),
				),
			))),
<?php else : ?>
			'type' => <?php echo $return_type_expr; ?>,
<?php endif; ?>
<?php if ( $description !== '' ) : ?>
			'description' => __( '<?php echo $escaped_description; ?>', 'woocommerce' ),
<?php endif; ?>
			'args' => array(
<?php foreach ( $args as $arg ) : ?>
				'<?php echo $arg['name']; ?>' => array(
					'type' => <?php echo $arg['type_expr']; ?>,
	<?php if ( ! empty( $arg['description'] ) ) : ?>
					'description' => __( '<?php echo addslashes( $arg['description'] ); ?>', 'woocommerce' ),
<?php endif; ?>
	<?php if ( $arg['has_default'] ) : ?>
					'defaultValue' => <?php echo var_export( $arg['default'], true ); ?>,
<?php endif; ?>
				),
<?php endforeach; ?>
			),
<?php if ( $has_connection_of ) : ?>
			'complexity' => Utils::complexity_from_pagination(...),
<?php endif; ?>
			'resolve' => array( self::class, 'resolve' ),
		);
	}

	public static function resolve( mixed $root, array $args, mixed $context, ResolveInfo $info ): mixed {
<?php if ( $has_cap_check ) : ?>
<?php foreach ( $capabilities as $cap ) : ?>
		Utils::check_current_user_can( '<?php echo addslashes( $cap ); ?>' );
<?php endforeach; ?>

<?php endif; ?>
		$command = wc_get_container()->get( <?php echo $command_alias; ?>::class );

		$execute_args = array();
<?php
$pagination_fqcn = 'Automattic\\WooCommerce\\Api\\Pagination\\PaginationParams';
foreach ( $execute_params as $param ) :
	if ( ! empty( $param['unroll'] ) && $param['unroll']['fqcn'] === $pagination_fqcn ) :
?>
		$execute_args['<?php echo $param['name']; ?>'] = Utils::create_pagination_params( $args );
<?php elseif ( ! empty( $param['unroll'] ) ) : ?>
		$execute_args['<?php echo $param['name']; ?>'] = Utils::create_input(
			fn() => new \<?php echo $param['unroll']['fqcn']; ?>(
<?php foreach ( $param['unroll']['properties'] as $uprop ) : ?>
				<?php echo $uprop['name']; ?>: <?php echo $uprop['value_expr']; ?>,
<?php endforeach; ?>
			)
		);
<?php elseif ( $param['is_infrastructure'] && $param['name'] === '_query_info' ) : ?>
		$execute_args['_query_info'] = QueryInfoExtractor::extract_from_info( $info, $args );
<?php elseif ( ! empty( $param['conversion'] ) ) : ?>
		if ( array_key_exists( '<?php echo $param['name']; ?>', $args ) ) {
			$execute_args['<?php echo $param['name']; ?>'] = <?php echo $param['conversion']; ?>;
		}
<?php else : ?>
		if ( array_key_exists( '<?php echo $param['name']; ?>', $args ) ) {
			$execute_args['<?php echo $param['name']; ?>'] = $args['<?php echo $param['name']; ?>'];
		}
<?php endif; ?>
<?php endforeach; ?>

<?php if ( $has_authorize ) : ?>
		if ( ! Utils::authorize_command( $command, array(
<?php foreach ( $authorize_param_names as $name ) : ?>
			'<?php echo $name; ?>' => $execute_args['<?php echo $name; ?>'],
<?php endforeach; ?>
<?php if ( $has_preauthorized ) : ?>
			'_preauthorized' => <?php echo $preauthorized_expr; ?>,
<?php endif; ?>
		) ) ) {
			throw new \GraphQL\Error\Error(
				'You do not have permission to perform this action.',
				extensions: array( 'code' => 'UNAUTHORIZED' )
			);
		}

<?php endif; ?>
		$result = Utils::execute_command( $command, $execute_args );

<?php if ( $scalar_return ) : ?>
		return array( 'result' => $result );
<?php else : ?>
		return $result;
<?php endif; ?>
	}
<?php foreach ( $input_converters as $converter ) : ?>

	private static function <?php echo $converter['method_name']; ?>( array $data ): \<?php echo $converter['input_fqcn']; ?> {
		$input = new \<?php echo $converter['input_fqcn']; ?>();

	<?php foreach ( $converter['properties'] as $prop ) : ?>
		if ( array_key_exists( '<?php echo $prop['name']; ?>', $data ) ) {
			$input->mark_provided( '<?php echo $prop['name']; ?>' );
		<?php if ( ! empty( $prop['conversion'] ) ) : ?>
			$input-><?php echo $prop['name']; ?> = <?php echo $prop['conversion']; ?>;
<?php else : ?>
			$input-><?php echo $prop['name']; ?> = $data['<?php echo $prop['name']; ?>'];
<?php endif; ?>
		}
<?php endforeach; ?>

		return $input;
	}
<?php endforeach; ?>
}
