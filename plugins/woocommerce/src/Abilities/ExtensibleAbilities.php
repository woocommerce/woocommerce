<?php
/**
 * Extensible abilities class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\Abilities\ExtensibleAbilitiesRegistry;
use Automattic\WooCommerce\Internal\Utilities\StagedMethod;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * The entry point for extensions in WooCommerce's abilities.
 *
 * An extension registers a resource inside the `woocommerce_extensible_abilities_resources_init`
 * action. It registers a field on a resource, such as `product`, inside the
 * `woocommerce_extensible_abilities_init` action, which fires after. An ability that formats an
 * object of that resource returns the field values under
 * `extensions.<namespace>.<field>`, and lists the fields in its output schema.
 * An ActionableAbility writes the same `extensions` input to the fields that
 * have a `set_callback`, runs the validators of the resource, and saves
 * the object one time.
 * An extension also registers a query filter, which the list ability of the
 * resource accepts under `filters.extensions.<namespace>.<filter>`.
 *
 * The API is experimental while the `extensible_abilities` feature exists.
 *
 * @since 11.3.0
 */
class ExtensibleAbilities {

	public const FEATURE_ID = 'extensible_abilities';

	/**
	 * Whether the store enabled the experimental feature.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}

	/**
	 * Register a resource that abilities format, so extensions can register fields on it.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_resources_init` action. A call after that action is refused.
	 * Core registers `product`, `order` and `order_item` there. An
	 * extension registers a resource that its own abilities format, and then calls `add_fields_to_object()`
	 * and `add_fields_schema()` from those abilities, exactly as Core's abilities do.
	 * Registering the same resource again does nothing.
	 *
	 * @since 11.3.0
	 *
	 * @param string $resource_name Slug of the resource, such as `ticket`.
	 */
	public static function register_resource( string $resource_name ): void {
		ExtensibleAbilitiesRegistry::register_resource( $resource_name );
	}

	/**
	 * Register a field that the abilities of a resource return under `extensions.<namespace>.<field>`.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_init` action. A call after that action is refused.
	 * The resource must be registered with `register_resource()` in the earlier `woocommerce_extensible_abilities_resources_init` action.
	 * A field declares `get_callback` and `output_schema`, `set_callback` and `input_schema`, or both pairs. The
	 * first pair makes it readable and the second makes it writable. An incomplete pair is refused.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Field arguments.
	 *
	 *     @type string   $resource     Resource that an ability formats, for example `product`, `order` or `order_item`.
	 *     @type string   $namespace    Slug of the extension that owns the field, such as `subscriptions`.
	 *     @type string   $field        Field name inside the namespace, such as `trial_length`.
	 *     @type array    $output_schema Optional. JSON schema of the value that `get_callback` returns. Required
	 *                                  with `get_callback`. It needs one `type` other than null, because clients
	 *                                  expect one type for each field. Core publishes it as declared. Its
	 *                                  `title` is the label clients show. Leave a nested property out when it has
	 *                                  no value. Where a nested value must be null, such as an item of a list,
	 *                                  declare it with an `anyOf` that has a `{"type":"null"}` branch. A list of
	 *                                  types is refused in every nested schema under `properties`,
	 *                                  `patternProperties`, `additionalProperties`, `items`, `anyOf` and `oneOf`,
	 *                                  which are the keywords with a nested schema that WordPress supports (see
	 *                                  `rest_get_allowed_schema_keywords()`), and under `allOf`, which
	 *                                  WooCommerce also reads, because some clients refuse it.
	 *     @type string[] $object_types Optional. The object types the field applies to, as `get_type()` of the
	 *                                  object returns them, such as `simple` and `subscription` for a product or
	 *                                  `line_item` for an order item. The object must have a `get_type()` method.
	 *                                  The abilities leave the field out of the other objects and refuse a write
	 *                                  to them. Omit it for every type.
	 *     @type callable $get_callback Optional. Receives the object and returns the value. A field without it
	 *                                  is not in the output. Required with `output_schema`. Return null when the
	 *                                  object has no value, and the field is left out with no error. Any other
	 *                                  value that `output_schema` does not allow fails the call with
	 *                                  `woocommerce_ability_field_output_invalid`. A callback that throws fails
	 *                                  the call with `woocommerce_ability_extension_failed`. It reads the object
	 *                                  it is given, not the database, because an ability can format an object
	 *                                  before it is saved. An empty array for an `object` is sent as `{}`. The
	 *                                  callback must not return a list for an `object` or a `date-time` without
	 *                                  a UTC offset: the schema check accepts them, and a strict client refuses
	 *                                  the whole result.
	 *     @type array    $input_schema Optional. JSON schema of the value that `set_callback` receives. It needs
	 *                                  one `type` name, or an `anyOf` where each branch has one `type` name. A
	 *                                  list of types is refused, at the top level and in every nested schema under
	 *                                  the keywords listed for `output_schema`, because some clients refuse it: a
	 *                                  nullable value uses `[{"type":"string"},{"type":"null"}]` as its `anyOf`.
	 *                                  Core publishes it as declared. A value can be null only where it declares
	 *                                  null. Required with `set_callback`.
	 *     @type callable $set_callback Optional. Receives the object and the value, and changes the object
	 *                                  in memory, when an ability creates or updates the object. It returns a
	 *                                  WP_Error to reject the value, and then nothing is saved. It also runs
	 *                                  after Core refuses another value of the primary object. A field without
	 *                                  it cannot be written. Required with `input_schema`. Rules:
	 *                                  1. It only sets values in memory. It does not save, send email
	 *                                     or make HTTP requests.
	 *                                  2. It receives the value already cast to `input_schema`.
	 *                                  3. A null value, where `input_schema` declares null, deletes the value
	 *                                     of the field.
	 *                                  4. It writes one object: the one it receives. Data that a
	 *                                     write must keep on another object, such as a price range
	 *                                     that a variable parent derives from its variations, is
	 *                                     written by a sync that register_sync() adds. A sync runs
	 *                                     once per call after every object is saved and inside the
	 *                                     transaction of the ability.
	 * }
	 */
	public static function register_field( array $args ): void {
		ExtensibleAbilitiesRegistry::register_field( $args );
	}

	/**
	 * Register a filter that the list ability of a resource accepts under `filters.extensions.<namespace>.<filter>`.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_init` action. A call after that action is refused.
	 * The resource must be registered with `register_resource()`.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Filter arguments.
	 *
	 *     @type string   $resource  Resource whose list ability applies the filters. Core's `products-query`,
	 *                               `orders-query` and `customers-query` apply the filters of `product`, `order`
	 *                               and `customer`. A `customer` filter returns only `include_ids`, which are
	 *                               user IDs, so it matches registered customers only. The list ability
	 *                               of another resource must call `get_query_args()` and `add_query_filters_schema()` itself.
	 *     @type string   $namespace Slug of the extension that owns the filter, such as `subscriptions`.
	 *     @type string   $filter    Filter name inside the namespace, such as `subscribable`.
	 *     @type array    $schema    JSON schema of the value. Its `description` tells agents how to use the filter.
	 *     @type callable $callback  Receives the value, sanitized with the schema, and returns query arguments:
	 *                               a `meta_query`, for products also a `tax_query`, `include_ids` or
	 *                               `exclude_ids`. Core adds each one to the query with AND. Return a
	 *                               WP_Error to refuse the value. A `tax_query` is accepted only for
	 *                               `product`. Any other key, a `tax_query` for another resource, or a key other
	 *                               than `include_ids` for `customer`, fails the call.
	 *                               `include_ids` and `exclude_ids` are lists of object IDs. Use them for
	 *                               data that is not meta, such as order items or a table of the extension:
	 *                               query that data and return the IDs that match. Core keeps the IDs that
	 *                               every `include_ids` filter returns, then drops the IDs that any
	 *                               `exclude_ids` filter returns. An empty `include_ids` matches nothing.
	 * }
	 */
	public static function register_query_filter( array $args ): void {
		ExtensibleAbilitiesRegistry::register_query_filter( $args );
	}

	/**
	 * Add the `filters.extensions` property to the input schema of a list
	 * ability, when the feature is on and the resource has filters. It does
	 * not allow other filters, because a filter that the query ignores
	 * returns wrong results.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $schema        Input schema.
	 * @param string               $resource_name Resource.
	 * @return array<string, mixed>
	 */
	public static function add_query_filters_schema( array $schema, string $resource_name ): array {
		return ExtensibleAbilitiesRegistry::add_query_filters_schema( $schema, $resource_name );
	}

	/**
	 * Get the query arguments of the filters in the input of a list ability.
	 * Each key holds one clause for each filter, so `array_merge_recursive()`
	 * adds them to WP_Query, wc_get_orders() or WP_User_Query arguments with AND. `post__in`
	 * holds the IDs that every `include_ids` filter returned, without the IDs
	 * that any `exclude_ids` filter returned, or `0` when none remain, because
	 * an empty `post__in` does not filter. Without `include_ids`, the excluded
	 * IDs go to `post__not_in`, which WP_Query ignores next to `post__in`.
	 *
	 * @since 11.3.0
	 *
	 * @param string               $resource_name Resource.
	 * @param array<string, mixed> $input         Ability input.
	 * @return array<string, array>|\WP_Error The error of a value that fails its schema, or of a filter that refused its value or failed.
	 */
	public static function get_query_args( string $resource_name, array $input ) {
		return ExtensibleAbilitiesRegistry::get_query_args( $resource_name, $input );
	}

	/**
	 * Refuse a direct call of a stage method when a class overrides its saving
	 * method without overriding the stage method too, because staging would skip
	 * the rules of that override. WC_Data classes use
	 * WC_Data::refuse_unstaged_override(). Another class calls this method at the
	 * start of its stage method, unless its saving method runs the stage method.
	 *
	 * @since 11.4.0
	 *
	 * @param object $subject      Object that gets the change.
	 * @param string $method       Saving method, for example add_tracking_item.
	 * @param string $stage_method Stage method, for example stage_tracking_item.
	 * @throws \WC_Data_Exception When a class has not adopted the stage method.
	 */
	public static function refuse_unstaged_override( object $subject, string $method, string $stage_method ): void {
		StagedMethod::refuse_unstaged( $subject, $method, $stage_method );
	}

	/**
	 * Register a validator that can refuse a change to an object of a resource.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_init` action. Validators
	 * run only when the `extensible_abilities` feature is on.
	 *
	 * Use a field `set_callback` to refuse a bad value of one field. It runs
	 * only when the input sends that field. Use a validator to refuse a change as
	 * a whole. It runs on every change of the resource, also when the input sends
	 * no extension fields. For example, a validator refuses a product change that
	 * would break a subscription plan.
	 *
	 * Core's product abilities and order-update-status run validators. An order
	 * validator sees the order with its new status staged, including the paid
	 * and completed dates that the status sets. A stage_*() method refuses a class
	 * that overrides its saving method without overriding the stage method too, so
	 * the ability refuses that class while the feature is on. See
	 * WC_Data::refuse_unstaged_override().
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Validator arguments.
	 *
	 *     @type string   $resource  Resource that an ActionableAbility changes, such as `product`.
	 *     @type string   $namespace Slug of the extension that owns the validator, such as `subscriptions`.
	 *     @type callable $callback  Receives the changed object, the ability and `array $context`, after Core's
	 *                               changes and the field callbacks, before the one save. A callback that only
	 *                               needs the object or the ability can take fewer arguments.
	 *                               `$context['primary']` is the primary object of the call. It differs from the
	 *                               changed object when the object is a variation that the call saves through
	 *                               its parent. On product-create that parent is not saved yet, so the variation
	 *                               has no parent ID and a rule reads the parent from here.
	 *                               `$context['deleting']` is true when the call deletes or trashes the object,
	 *                               such as a variation of a deleted parent, or an item with `delete` in the
	 *                               `variations` input. `$context['objects']` is every object the call saves,
	 *                               the primary object first, with the field writes of all of them applied, so
	 *                               a rule can compare a variation with the other variations of the same call.
	 *                               `$context['objects_to_delete']` is every object the call deletes or
	 *                               trashes. It returns a WP_Error to refuse the change, and then nothing is
	 *                               saved. The other validators still run, and the client gets every refusal in
	 *                               one error. The client sees the code prefixed with the namespace, as
	 *                               `<namespace>:<code>`. A code that another validator in the same namespace
	 *                               already used gets a numeric suffix, as `<namespace>:<code>:2`. The error
	 *                               gets the namespace in its data, and the 400 status when it has no status.
	 *                               Its message can name the ability or screen to use instead. Validators also
	 *                               run after a field callback or Core refuses a value, so the client gets every
	 *                               refusal together, with all messages in the first one. After a Core refusal
	 *                               they run on the primary object only, which keeps the values Core refused at
	 *                               their old values. Rules:
	 *                               1. It only reads. It does not change the object, save, send email or
	 *                                  make HTTP requests.
	 *                               2. It checks the ability name when the extension's own abilities
	 *                                  change the same resource.
	 * }
	 */
	public static function register_validator( array $args ): void {
		ExtensibleAbilitiesRegistry::register_validator( $args );
	}

	/**
	 * Register a sync on a resource. A sync runs once per ability call, after
	 * every object of the call is saved, and writes what the saved objects imply
	 * elsewhere, such as the price range a variable parent derives from its
	 * variations, or a row in a table of the extension.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_init` action. Syncs
	 * run only when the `extensible_abilities` feature is on, inside the
	 * transaction of the ActionableAbility.
	 *
	 * A sync registered for a resource runs only when an object of that resource
	 * was saved, deleted or trashed in the call. It runs once per call, not once per
	 * object. The saved objects can belong to more than one resource, so a sync
	 * reads the objects it owns and ignores the rest.
	 *
	 * @since 11.4.0
	 *
	 * @param array $args {
	 *     Sync arguments.
	 *
	 *     @type string   $resource  Resource that an ActionableAbility saves, such as `product`.
	 *     @type string   $namespace Slug of the extension that owns the sync, such as `subscriptions`.
	 *     @type callable $callback  Receives `\WC_Data[] $saved`, the ActionableAbility and `array $context`.
	 *                               `$saved` is every saved object of the call, the primary object first
	 *                               unless the call deleted it.
	 *                               `$context['deleted']` is every object the call deleted for good, as it
	 *                               was before the delete, with its ID. `$context['trashed']` is every object
	 *                               the call moved to the trash; it can be restored, so a sync keeps what a
	 *                               restore needs. And
	 *                               `$context['input']` is the input of the call, so a sync can skip work
	 *                               when the call did not write its fields. It returns a WP_Error to
	 *                               refuse, and then every save of the call is undone. A thrown exception
	 *                               refuses it too. The error gets the namespace in its data, and the 400
	 *                               status when it has no status, as a validator's error does. A sync must not
	 *                               send email or make HTTP requests, because an undo does not reach outside
	 *                               the database.
	 * }
	 */
	public static function register_sync( array $args ): void {
		ExtensibleAbilitiesRegistry::register_sync( $args );
	}

	/**
	 * Register a prime callback on a resource. Before an ability formats a list
	 * of objects of the resource, Core calls every prime callback of the
	 * resource once, with the full list. An extension loads the data of its
	 * fields for the whole list in one query, keeps it in its own cache, and
	 * its get_callback reads from there.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_init` action. Prime
	 * callbacks run only when the `extensible_abilities` feature is on, in the
	 * order they were registered.
	 *
	 * @since 11.4.0
	 *
	 * @param array $args {
	 *     Prime arguments.
	 *
	 *     @type string   $resource  Resource that an ability formats, such as `product`.
	 *     @type string   $namespace Slug of the extension that owns the callback, such as `google`.
	 *     @type callable $callback  Receives `object[] $objects`, every object the ability formats. The
	 *                               callback skips the objects it does not need. Core ignores the
	 *                               return value. When it throws, Core logs the error and formats
	 *                               the objects anyway, so the get_callback must still load its
	 *                               data for an object that is not in the cache. Core calls prime
	 *                               callbacks only on reads, not on writes. Key the cache by object ID,
	 *                               and remove the entry of an object when the object is saved, for
	 *                               example on `woocommerce_update_product`. Otherwise a write after a
	 *                               read in the same request returns the old value. Empty the cache at the start of
	 *                               every call, so a failed load leaves no old entries.
	 * }
	 */
	public static function register_prime( array $args ): void {
		ExtensibleAbilitiesRegistry::register_prime( $args );
	}

	/**
	 * Call every prime callback of a resource with the objects that an ability
	 * is about to format. A callback that throws is logged, and the next one runs.
	 * An extension that registers its own resource calls it from its list
	 * abilities before add_fields_to_object(), as Core's abilities do.
	 *
	 * @since 11.4.0
	 *
	 * @param string   $resource_name Resource.
	 * @param object[] $objects       Objects to format.
	 */
	public static function prime( string $resource_name, array $objects ): void {
		ExtensibleAbilitiesRegistry::prime( $resource_name, $objects );
	}

	/**
	 * Add the field values of an object to its formatted output, under
	 * `extensions.<namespace>.<field>`. A value is cast to its schema type, so
	 * `'12'` becomes `12` for an integer field. A field that returns null is
	 * left out. Any other value that its output schema
	 * does not allow returns a `woocommerce_ability_field_output_invalid` error,
	 * and a get_callback that throws returns a `woocommerce_ability_extension_failed`
	 * error. The ability returns the error. A namespace with no
	 * values is left out, and `extensions` is left out when no namespace has
	 * values or the feature is off.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $output   Formatted object.
	 * @param string               $resource_name Resource.
	 * @param object               $subject  Object to read.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function add_fields_to_object( array $output, string $resource_name, $subject ) {
		return ExtensibleAbilitiesRegistry::add_fields_to_object( $output, $resource_name, $subject );
	}

	/**
	 * Add the `extensions` property to an object schema when the feature is on.
	 * It lists each namespace with its fields. Call it after the
	 * `woocommerce_extensible_abilities_init` action, when the ability builds its
	 * schema. The schema stays unchanged when the resource has no readable field.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $schema        Object schema.
	 * @param string               $resource_name Resource.
	 * @param callable|null        $field_filter  Optional. Gets the arguments of a field and returns whether the schema lists it.
	 * @return array<string, mixed>
	 */
	public static function add_fields_schema( array $schema, string $resource_name, ?callable $field_filter = null ): array {
		return ExtensibleAbilitiesRegistry::add_fields_schema( $schema, $resource_name, $field_filter );
	}

	/**
	 * The schema of the `extensions` input of a resource. It lists the fields
	 * that have a set_callback, each with its input schema as declared, and says
	 * which object types a field with `object_types` applies to. ActionableAbility adds it to the input schema of every
	 * ability. An ability that writes other objects of the resource in the same
	 * call, such as the variations of a product, adds it to their input too.
	 *
	 * @since 11.4.0
	 *
	 * @param string        $resource_name Resource.
	 * @param callable|null $field_filter  Optional. Gets the arguments of a field and returns whether the schema lists it.
	 * @return array<string, mixed>
	 */
	public static function get_input_schema( string $resource_name, ?callable $field_filter = null ): array {
		return ExtensibleAbilitiesRegistry::get_input_schema( $resource_name, $field_filter );
	}
}
