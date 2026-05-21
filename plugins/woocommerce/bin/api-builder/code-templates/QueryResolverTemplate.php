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
 * @var array  $args - each: ['name', 'type_expr', 'description', 'has_default', 'default', 'metadata' => array]
 * @var array  $metadata - root-field-level metadata for discovery (`_apiMetadata`); blank when the operation opts out via shows_in_metadata_query().
 * @var array  $metadata_runtime - full root-field-level metadata, published into $context['_query_metadata'] for downstream field gates regardless of discovery opt-out.
 * @var bool   $has_connection_of
 * @var string $connection_type_alias
 * @var bool   $standalone_attribute_check - true when authorize() is absent and the attribute_expr is the sole authorization gate
 * @var string $attribute_expr - PHP expression (referencing local `$principal`) that evaluates to true iff the autodiscovered authorization attributes grant access
 * @var string $compute_preauthorized_param_type - typed parameter declaration for the generated compute_preauthorized() helper (e.g. `object` or `\WP_User`)
 * @var array  $execute_params - each: ['name', 'conversion' => ?string, 'is_infrastructure' => bool, 'unroll' => ?array]
 * @var ?array $execute_principal_arg - if non-null, ['type_name' => string]: execute() declares a $_principal infra param
 * @var bool   $execute_query_info_arg - true when execute() declares a $_query_info infra param
 * @var array  $input_converters - each: ['method_name', 'input_fqcn', 'input_class', 'properties' => [['name', 'conversion']]]
 * @var ?array $authorize_param_names - if non-null, the authorize() method param names (subset of execute params)
 * @var bool   $has_preauthorized - true when authorize() declares a bool $_preauthorized infrastructure param
 * @var string $preauthorized_expr - PHP expression that evaluates to the $_preauthorized bool at runtime
 * @var ?array $authorize_principal_arg - if non-null, ['type_name' => string]: authorize() declares a $_principal infra param
 * @var bool   $authorize_query_info_arg - true when authorize() declares a $_query_info infra param
 * @var bool    $scalar_return - true when execute() returns a scalar (bool, int, float, string)
 * @var ?string $class_resolver_fqcn - FQCN of a user-provided class resolver with static resolve_class(string): object; null for direct `new` instantiation
 */

$escaped_description = addslashes( $description );
$has_authorize       = $authorize_param_names !== null;
$any_query_info_arg  = $execute_query_info_arg || $authorize_query_info_arg;
?>
<?php echo '<?php'; ?>

declare(strict_types=1);

// THIS FILE IS AUTO-GENERATED. DO NOT EDIT MANUALLY.

namespace <?php echo $namespace; ?>;

use <?php echo $command_fqcn; ?> as <?php echo $command_alias; ?>;
use Automattic\WooCommerce\Api\Infrastructure\QueryInfoExtractor;
use Automattic\WooCommerce\Api\Infrastructure\ResolverHelpers;
<?php
// Drop any caller-supplied import whose effective short name would collide
// with one of the imports emitted unconditionally above and below, otherwise
// the generated file would fail to compile ("Cannot use ... because the name
// is already in use").
$reserved_short_names = array( $command_alias, 'QueryInfoExtractor', 'ResolverHelpers', 'ResolveInfo', 'Type' );
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
use Automattic\WooCommerce\Api\Infrastructure\Schema\ResolveInfo;
use Automattic\WooCommerce\Api\Infrastructure\Schema\Type;

class <?php echo $class_name; ?> {
	public static function get_field_definition(): array {
		return array(
<?php if ( $scalar_return ) : ?>
			'type' => Type::nonNull(new \Automattic\WooCommerce\Api\Infrastructure\Schema\ObjectType(array(
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
<?php if ( ! empty( $metadata ) ) : ?>
			'metadata' => array(
<?php foreach ( $metadata as $meta_name => $meta_value ) : ?>
				<?php echo var_export( $meta_name, true ); ?> => <?php echo var_export( $meta_value, true ); ?>,
<?php endforeach; ?>
			),
<?php endif; ?>
<?php if ( ! empty( $authorization_descriptors ) ) : ?>
			'authorization' => array(
<?php foreach ( $authorization_descriptors as $descriptor ) : ?>
				array(
					'attribute' => <?php echo var_export( $descriptor['attribute'], true ); ?>,
					'args'      => <?php echo var_export( $descriptor['args'], true ); ?>,
				),
<?php endforeach; ?>
			),
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
	<?php if ( ! empty( $arg['metadata'] ) ) : ?>
					'metadata' => array(
		<?php foreach ( $arg['metadata'] as $meta_name => $meta_value ) : ?>
						<?php echo var_export( $meta_name, true ); ?> => <?php echo var_export( $meta_value, true ); ?>,
<?php endforeach; ?>
					),
<?php endif; ?>
				),
<?php endforeach; ?>
			),
<?php if ( $has_connection_of ) : ?>
			'complexity' => ResolverHelpers::complexity_from_pagination(...),
<?php endif; ?>
			'resolve' => array( self::class, 'resolve' ),
		);
	}

	public static function resolve( mixed $root, array $args, mixed $context, ResolveInfo $info ): mixed {
<?php if ( $standalone_attribute_check ) : ?>
		// Standalone authorization gate: no authorize() method on the command,
		// so the autodiscovered authorization attributes are the sole guard.
		if ( ! self::compute_preauthorized( $context['principal'] ) ) {
			throw ResolverHelpers::build_authorization_error( $context['principal'] );
		}

<?php endif; ?>
		// Publish the root query's metadata so downstream field-level
		// authorization gates can read it via `$_metadata['query']`.
		// $context is an ArrayObject (see GraphQLController::process_request())
		// so the mutation propagates to nested resolvers.
		$context['_query_metadata'] = <?php echo var_export( $metadata_runtime, true ); ?>;


<?php if ( null !== $class_resolver_fqcn ) : ?>
		$command = \<?php echo $class_resolver_fqcn; ?>::resolve_class( <?php echo $command_alias; ?>::class );
<?php else : ?>
		$command = new <?php echo $command_alias; ?>();
<?php endif; ?>

<?php if ( $any_query_info_arg ) : ?>
		$query_info = QueryInfoExtractor::extract_from_info( $info, $args );
<?php endif; ?>
		$execute_args = array();
<?php
$pagination_fqcn = 'Automattic\\WooCommerce\\Api\\Pagination\\PaginationParams';
foreach ( $execute_params as $param ) :
	if ( ! empty( $param['unroll'] ) && $param['unroll']['fqcn'] === $pagination_fqcn ) :
?>
		$execute_args['<?php echo $param['name']; ?>'] = ResolverHelpers::create_pagination_params( $args );
<?php elseif ( ! empty( $param['unroll'] ) ) : ?>
		$execute_args['<?php echo $param['name']; ?>'] = ResolverHelpers::create_input(
			fn() => new \<?php echo $param['unroll']['fqcn']; ?>(
<?php foreach ( $param['unroll']['properties'] as $uprop ) : ?>
				<?php echo $uprop['name']; ?>: <?php echo $uprop['value_expr']; ?>,
<?php endforeach; ?>
			)
		);
<?php elseif ( $param['is_infrastructure'] && $param['name'] === '_query_info' ) : ?>
		$execute_args['_query_info'] = $query_info;
<?php elseif ( $param['is_infrastructure'] && $param['name'] === '_principal' ) : ?>
		$execute_args['_principal'] = $context['principal'];
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

<?php foreach ( $input_side_gates as $gate_set ) : ?>
		if ( isset( $execute_args['<?php echo $gate_set['exec_arg_name']; ?>'] ) && $execute_args['<?php echo $gate_set['exec_arg_name']; ?>'] instanceof \<?php echo $gate_set['input_fqcn']; ?> ) {
			$_parent = $execute_args['<?php echo $gate_set['exec_arg_name']; ?>'];
	<?php foreach ( $gate_set['fields'] as $field_gate ) : ?>
			if ( $_parent->was_provided( '<?php echo $field_gate['field_name']; ?>' ) ) {
				$principal = $context['principal'];
				$_metadata = array(
					'query' => $context['_query_metadata'] ?? array(),
					'type'  => <?php echo $field_gate['type_metadata_literal']; ?>,
					'field' => <?php echo $field_gate['field_metadata_literal']; ?>,
				);
				$_args     = $args;
				if ( ! ( <?php echo $field_gate['attribute_expr']; ?> ) ) {
					throw ResolverHelpers::build_field_authorization_error( $principal, '<?php echo $gate_set['input_short_name']; ?>', '<?php echo $field_gate['field_name']; ?>', '<?php echo $field_gate['first_attribute_short']; ?>' );
				}
			}
	<?php endforeach; ?>
		}
<?php endforeach; ?>

<?php if ( $has_authorize ) : ?>
		if ( ! ResolverHelpers::authorize_command( $command, array(
<?php foreach ( $authorize_param_names as $name ) : ?>
			'<?php echo $name; ?>' => $execute_args['<?php echo $name; ?>'],
<?php endforeach; ?>
<?php if ( null !== $authorize_principal_arg ) : ?>
			'_principal' => $context['principal'],
<?php endif; ?>
<?php if ( $authorize_query_info_arg ) : ?>
			'_query_info' => $query_info,
<?php endif; ?>
<?php if ( $has_preauthorized ) : ?>
			'_preauthorized' => <?php echo $preauthorized_expr; ?>,
<?php endif; ?>
		) ) ) {
			throw ResolverHelpers::build_authorization_error( $context['principal'] );
		}

<?php endif; ?>
		$result = ResolverHelpers::execute_command( $command, $execute_args );

<?php if ( $scalar_return ) : ?>
		return array( 'result' => $result );
<?php else : ?>
		return $result;
<?php endif; ?>
	}

	/**
	 * Compute the value `_preauthorized` would carry for a given principal —
	 * the AND of the autodiscovered authorization attributes' authorize()
	 * outcomes on this command. Single source of truth for both the resolver's
	 * own gates and external (code-API) callers asking about authorization
	 * without going through GraphQL execution.
	 *
	 * Returns true vacuously when the command has no authorization attributes
	 * (in that case authorize() on the command is the sole guard, and that
	 * method should be consulted instead).
	 */
	public static function compute_preauthorized( <?php echo $compute_preauthorized_param_type; ?> $principal ): bool {
		return <?php echo $attribute_expr; ?>;
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
