<?php
/**
 * Product term create and update ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\Abilities\Ability;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductAttributeCreate;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductTermCreate;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductTermsQuery;
use Automattic\WooCommerce\Internal\Abilities\Domain\ProductTermUpdate;
use WC_Unit_Test_Case;

/**
 * Tests product term writes through the registered abilities.
 */
class ProductTermWritesTest extends WC_Unit_Test_Case {

	/**
	 * Extension state restored after each test.
	 *
	 * @var array
	 */
	private array $saved_state = array();

	/**
	 * Taxonomies registered before the test, so the ones it registers can be removed.
	 *
	 * @var string[]
	 */
	private array $taxonomies_before;

	/**
	 * An administrator that every test acts as.
	 *
	 * @var int
	 */
	private static $user_id;

	/**
	 * Create the fixtures that no test changes beyond its own transaction.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$user_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Set up an administrator with extension fields switched off.
	 */
	public function setUp(): void {
		parent::setUp();
		foreach ( array( 'resources', 'fields' ) as $name ) {
			$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
			$property->setAccessible( true );
			$this->saved_state[ $name ] = $property->getValue();
			$property->setValue( null, array() );
		}
		$this->taxonomies_before = get_taxonomies();
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
		wp_set_current_user( self::$user_id );
	}

	/**
	 * Restore extension state and unregister the attribute taxonomies the test registered.
	 */
	public function tearDown(): void {
		global $wc_product_attributes;
		try {
			foreach ( $this->saved_state as $name => $value ) {
				$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
				$property->setAccessible( true );
				$property->setValue( null, $value );
			}
			foreach ( array_diff( get_taxonomies(), $this->taxonomies_before ) as $taxonomy ) {
				unregister_taxonomy( $taxonomy );
				unset( $wc_product_attributes[ $taxonomy ] );
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Should create a term that the term query returns in the same shape.
	 * @testWith ["product_cat"]
	 *           ["product_tag"]
	 *           ["product_brand"]
	 * @param string $taxonomy Taxonomy.
	 */
	public function test_creates_term( string $taxonomy ): void {
		$result = $this->create(
			array(
				'taxonomy'    => $taxonomy,
				'name'        => 'Tom & Jerry',
				'description' => 'Cartoon <em onclick="x()">classics</em>',
			)
		);

		$this->assertIsArray( $result );
		$term = $result['term'];
		$this->assertSame( 'Tom & Jerry', $term['name'] );
		$this->assertSame( 'tom-jerry', $term['slug'] );
		$this->assertSame( 'Cartoon <em>classics</em>', $term['description'] );
		$this->assertSame( 0, $term['count'] );
		$query = ( new Ability( ProductTermsQuery::get_name(), ProductTermsQuery::get_registration_args() ) )->execute(
			array(
				'taxonomy' => $taxonomy,
				'include'  => array( $term['id'] ),
			)
		);
		$this->assertSame( array( $term ), $query['terms'] );
	}

	/**
	 * @testdox Should declare the query's term fields in both write schemas, without their descriptions.
	 */
	public function test_write_schemas_declare_the_query_fields(): void {
		$query = ProductTermsQuery::get_registration_args()['output_schema']['properties']['terms']['items']['properties'];

		foreach ( array( ProductTermCreate::get_registration_args(), ProductTermUpdate::get_registration_args() ) as $args ) {
			$written = $args['output_schema']['properties']['term']['properties'];
			$this->assertSame( array_keys( $query ), array_keys( $written ) );
			$this->assertSame( 'object', $written['image']['type'] );
			$this->assertSame( array(), array_filter( array_column( $written, 'description' ) ), 'No field carries a description.' );
		}
	}

	/**
	 * @testdox Should create a category under a parent with the slug it is given.
	 */
	public function test_creates_child_category(): void {
		$parent = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );

		$result = $this->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Shoes',
				'slug'     => 'Summer Shoes',
				'parent'   => $parent,
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'summer-shoes', $result['term']['slug'] );
		$this->assertSame( $parent, $result['term']['parent'] );
	}

	/**
	 * @testdox Should create a term of a global attribute created earlier in the same request.
	 */
	public function test_creates_attribute_term(): void {
		$attribute = ( new Ability( ProductAttributeCreate::get_name(), ProductAttributeCreate::get_registration_args() ) )->execute( array( 'name' => 'Color' ) );
		$this->assertIsArray( $attribute );

		$result = $this->create(
			array(
				'taxonomy' => 'pa_color',
				'name'     => 'Red',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'red', $result['term']['slug'] );
		$this->assertSame( 0, $result['term']['menu_order'] );
		$this->assertInstanceOf( \WP_Term::class, get_term( $result['term']['id'], 'pa_color' ) );
	}

	/**
	 * Inputs that would collide with the category Summer or be stored differently than asked.
	 *
	 * @return array
	 */
	public function provide_conflicting_creates(): array {
		return array(
			'same name'                   => array( array( 'name' => 'SUMMER' ), 'woocommerce_ability_product_term_name_exists', true ),
			'same name with a new slug'   => array(
				array(
					'name' => 'Summer',
					'slug' => 'summer-two',
				),
				'woocommerce_ability_product_term_name_exists',
				true,
			),
			'slug in use'                 => array(
				array(
					'name' => 'Holidays',
					'slug' => 'summer',
				),
				'woocommerce_ability_product_term_slug_exists',
				true,
			),
			'name empty after sanitizing' => array( array( 'name' => '<b></b>' ), 'woocommerce_ability_product_term_name_empty', false ),
			'slug empty after sanitizing' => array(
				array(
					'name' => 'Holidays',
					'slug' => '!!!',
				),
				'woocommerce_ability_product_term_slug_empty',
				false,
			),
			'slug zero'                   => array(
				array(
					'name' => 'Holidays',
					'slug' => '0',
				),
				'woocommerce_ability_product_term_slug_empty',
				false,
			),
			'parent missing'              => array(
				array(
					'name'   => 'Holidays',
					'parent' => 99999999,
				),
				'woocommerce_ability_product_term_parent_not_found',
				false,
			),
		);
	}

	/**
	 * @testdox Should refuse a create that collides with a category or names a missing parent, and create nothing.
	 * @dataProvider provide_conflicting_creates
	 * @param array  $input     Create input, without the taxonomy.
	 * @param string $code      Expected error code.
	 * @param bool   $has_id    Whether the error carries the ID of the category it collides with.
	 */
	public function test_refuses_conflicting_create( array $input, string $code, bool $has_id ): void {
		$existing = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Summer',
				'slug'     => 'summer',
			)
		);
		$before   = wp_count_terms( array( 'taxonomy' => 'product_cat' ) );

		$result = $this->create( array( 'taxonomy' => 'product_cat' ) + $input );

		$this->assertWPError( $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		if ( $has_id ) {
			$this->assertSame( $existing, $result->get_error_data()['id'], 'The agent can use the existing term.' );
		}
		$this->assertSame( $before, wp_count_terms( array( 'taxonomy' => 'product_cat' ) ), 'Nothing is created.' );
	}

	/**
	 * @testdox Should allow a category name that only repeats one in another branch.
	 */
	public function test_creates_same_name_under_another_parent(): void {
		self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Shoes',
			)
		);
		$parent = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );

		$result = $this->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Shoes',
				'parent'   => $parent,
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( $parent, $result['term']['parent'] );
	}

	/**
	 * @testdox Should refuse a parent on a tag, and a category of another taxonomy as parent.
	 */
	public function test_refuses_invalid_parent(): void {
		$tag      = self::factory()->term->create( array( 'taxonomy' => 'product_tag' ) );
		$category = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );

		$on_tag = $this->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Sale',
				'parent'   => $tag,
			)
		);
		$cross  = $this->create(
			array(
				'taxonomy' => 'product_brand',
				'name'     => 'Acme',
				'parent'   => $category,
			)
		);

		$this->assertWPError( $on_tag );
		$this->assertSame( 'woocommerce_ability_product_term_parent_unsupported', $on_tag->get_error_code() );
		$this->assertWPError( $cross );
		$this->assertSame( 'woocommerce_ability_product_term_parent_not_found', $cross->get_error_code() );
	}

	/**
	 * @testdox Should report an attribute taxonomy that does not exist to a store manager, for both abilities.
	 */
	public function test_refuses_unknown_taxonomy(): void {
		$create = $this->create(
			array(
				'taxonomy' => 'pa_missing',
				'name'     => 'Red',
			)
		);
		$update = $this->update(
			array(
				'taxonomy' => 'pa_missing',
				'id'       => 1,
				'name'     => 'Red',
			)
		);

		foreach ( array( $create, $update ) as $result ) {
			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_product_term_taxonomy_unknown', $result->get_error_code() );
			$this->assertSame( 404, $result->get_error_data()['status'] );
		}
	}

	/**
	 * @testdox Should refuse to write a pa_ taxonomy that is not a global attribute, and leave its terms alone.
	 */
	public function test_refuses_pa_taxonomy_that_is_not_an_attribute(): void {
		register_taxonomy( 'pa_other_plugin', 'product' );
		$term = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'pa_other_plugin',
				'name'     => 'Kept',
			)
		);

		$create = $this->create(
			array(
				'taxonomy' => 'pa_other_plugin',
				'name'     => 'Added',
			)
		);
		$update = $this->update(
			array(
				'taxonomy' => 'pa_other_plugin',
				'id'       => $term->term_id,
				'name'     => 'Renamed',
			)
		);

		foreach ( array( $create, $update ) as $result ) {
			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_product_term_taxonomy_unknown', $result->get_error_code() );
		}
		$this->assertFalse( get_term_by( 'name', 'Added', 'pa_other_plugin' ) );
		$this->assertSame( 'Kept', get_term( $term->term_id, 'pa_other_plugin' )->name );
	}

	/**
	 * @testdox Should rename, re-slug and move a category, and keep the fields it is not sent.
	 */
	public function test_updates_category(): void {
		$parent = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );
		$id     = self::factory()->term->create(
			array(
				'taxonomy'    => 'product_cat',
				'name'        => 'Shoes',
				'description' => 'All shoes',
			)
		);

		$result = $this->update(
			array(
				'taxonomy' => 'product_cat',
				'id'       => $id,
				'name'     => 'Sneakers',
				'slug'     => 'sneakers',
				'parent'   => $parent,
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( $id, $result['term']['id'] );
		$this->assertSame( 'Sneakers', $result['term']['name'] );
		$this->assertSame( 'sneakers', $result['term']['slug'] );
		$this->assertSame( $parent, $result['term']['parent'] );
		$this->assertSame( 'All shoes', $result['term']['description'] );
	}

	/**
	 * @testdox Should keep a name with an ampersand when the update leaves the name out.
	 */
	public function test_update_keeps_encoded_name(): void {
		$id = self::factory()->term->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Tom & Jerry',
			)
		);

		$result = $this->update(
			array(
				'taxonomy' => 'product_tag',
				'id'       => $id,
				'slug'     => 'cartoons',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'Tom & Jerry', $result['term']['name'] );
		$this->assertSame( 'cartoons', $result['term']['slug'] );
	}

	/**
	 * @testdox Should keep backslashes in a term's name and description on create and update.
	 */
	public function test_keeps_backslashes(): void {
		$created = $this->create(
			array(
				'taxonomy'    => 'product_tag',
				'name'        => 'C:\\Temp',
				'description' => 'Path C:\\Temp',
			)
		);
		$this->assertIsArray( $created );
		$this->assertSame( 'C:\\Temp', $created['term']['name'] );
		$this->assertSame( 'Path C:\\Temp', $created['term']['description'] );

		$id      = $created['term']['id'];
		$updated = $this->update(
			array(
				'taxonomy'    => 'product_tag',
				'id'          => $id,
				'description' => 'Path D:\\Data',
			)
		);
		$this->assertIsArray( $updated );
		$this->assertSame( 'C:\\Temp', $updated['term']['name'] );
		$this->assertSame( 'Path D:\\Data', $updated['term']['description'] );

		$renamed = $this->update(
			array(
				'taxonomy' => 'product_tag',
				'id'       => $id,
				'name'     => 'D:\\Data',
			)
		);
		$this->assertIsArray( $renamed );
		$this->assertSame( 'D:\\Data', $renamed['term']['name'] );
		$this->assertSame( 'Path D:\\Data', $renamed['term']['description'] );
	}

	/**
	 * @testdox Should change the description of a tag whose name another tag already uses.
	 */
	public function test_updates_description_of_a_duplicate_name(): void {
		$id = self::factory()->term->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Sale',
				'slug'     => 'sale',
			)
		);
		self::factory()->term->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Sale',
				'slug'     => 'sale-2',
			)
		);

		$result = $this->update(
			array(
				'taxonomy'    => 'product_tag',
				'id'          => $id,
				'description' => 'Discounted',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'Discounted', $result['term']['description'] );
	}

	/**
	 * @testdox Should refuse an update that would collide, loop, or change nothing, and leave the term as it was.
	 */
	public function test_refuses_invalid_update(): void {
		$parent = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Clothing',
			)
		);
		$id     = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Shirts',
				'parent'   => $parent,
			)
		);
		$other  = self::factory()->term->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Pants',
				'slug'     => 'pants',
				'parent'   => $parent,
			)
		);
		$tag    = self::factory()->term->create( array( 'taxonomy' => 'product_tag' ) );
		$cases  = array(
			'woocommerce_ability_product_term_name_exists' => array(
				'id'   => $id,
				'name' => 'pants',
			),
			'woocommerce_ability_product_term_slug_exists' => array(
				'id'   => $id,
				'slug' => 'pants',
			),
			'woocommerce_ability_product_term_slug_empty'  => array(
				'id'   => $id,
				'slug' => '0',
			),
			'woocommerce_ability_product_term_parent_loop' => array(
				'id'     => $parent,
				'parent' => $id,
			),
			'woocommerce_ability_product_term_no_fields'   => array( 'id' => $id ),
			'woocommerce_ability_product_term_not_found'   => array(
				'id'   => $tag,
				'name' => 'Tees',
			),
		);
		$before = get_term( $id, 'product_cat' );

		foreach ( $cases as $code => $input ) {
			$result = $this->update( array( 'taxonomy' => 'product_cat' ) + $input );

			$this->assertWPError( $result, $code );
			$this->assertSame( $code, $result->get_error_code() );
		}
		$collision = $this->update(
			array(
				'taxonomy' => 'product_cat',
				'id'       => $id,
				'name'     => 'Pants',
			)
		);
		$this->assertSame( $other, $collision->get_error_data()['id'], 'The error names the term it collides with.' );
		$this->assertEquals( $before, get_term( $id, 'product_cat' ), 'The term is unchanged.' );
		$this->assertSame( 0, get_term( $parent, 'product_cat' )->parent, 'The parent is unchanged.' );
	}

	/**
	 * @testdox Should refuse customers both abilities.
	 */
	public function test_refuses_customer(): void {
		$id = self::factory()->term->create( array( 'taxonomy' => 'product_cat' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$create = $this->create(
			array(
				'taxonomy' => 'product_cat',
				'name'     => 'Summer',
			)
		);
		$update = $this->update(
			array(
				'taxonomy' => 'product_cat',
				'id'       => $id,
				'name'     => 'Summer',
			)
		);

		$this->assertWPError( $create );
		$this->assertSame( 'ability_invalid_permissions', $create->get_error_code() );
		$this->assertWPError( $update );
		$this->assertSame( 'ability_invalid_permissions', $update->get_error_code() );
	}

	/**
	 * @testdox Should return extension fields from both abilities only while the feature is enabled.
	 */
	public function test_extension_fields(): void {
		$id     = self::factory()->term->create( array( 'taxonomy' => 'product_tag' ) );
		$schema = ProductTermCreate::get_registration_args()['output_schema'];
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'yes' );
		$this->assertSame( $schema, ProductTermCreate::get_registration_args()['output_schema'] );
		add_action( 'woocommerce_extensible_abilities_resources_init', array( AbilitiesLoader::class, 'register_core_resources' ), 0 );
		do_action( 'woocommerce_extensible_abilities_resources_init' );
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'product_term',
						'namespace'     => 'test-ext',
						'field'         => 'taxonomy',
						'output_schema' => array( 'type' => 'string' ),
						'get_callback'  => static fn( \WP_Term $term ) => $term->taxonomy,
					)
				);
			}
		);
		do_action( 'woocommerce_extensible_abilities_init' );

		$created = $this->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Sale',
			)
		);
		$updated = $this->update(
			array(
				'taxonomy' => 'product_tag',
				'id'       => $id,
				'name'     => 'Clearance',
			)
		);

		$this->assertSame( 'product_tag', $created['term']['extensions']['test-ext']['taxonomy'] );
		$this->assertSame( 'product_tag', $updated['term']['extensions']['test-ext']['taxonomy'] );
		$this->assertSame( 'string', ProductTermUpdate::get_registration_args()['output_schema']['properties']['term']['properties']['extensions']['properties']['test-ext']['properties']['taxonomy']['type'] );
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
		$plain = $this->update(
			array(
				'taxonomy' => 'product_tag',
				'id'       => $id,
				'name'     => 'Outlet',
			)
		);
		$this->assertArrayNotHasKey( 'extensions', $plain['term'] );
	}

	/**
	 * @testdox Should name the saved term in the error when a write saves and an extension field value then fails its schema.
	 */
	public function test_write_that_saves_before_a_field_fails_names_the_saved_term(): void {
		$this->setExpectedIncorrectUsage( ExtensibleAbilities::class . '::register_field' );
		$id = self::factory()->term->create( array( 'taxonomy' => 'product_tag' ) );
		update_option( 'woocommerce_feature_extensible_abilities_enabled', 'yes' );
		add_action( 'woocommerce_extensible_abilities_resources_init', array( AbilitiesLoader::class, 'register_core_resources' ), 0 );
		do_action( 'woocommerce_extensible_abilities_resources_init' );
		add_action(
			'woocommerce_extensible_abilities_init',
			static function () {
				ExtensibleAbilities::register_field(
					array(
						'resource'      => 'product_term',
						'namespace'     => 'invalid-ext',
						'field'         => 'bad',
						'output_schema' => array( 'type' => 'integer' ),
						'get_callback'  => static fn() => 'many',
					)
				);
			}
		);
		do_action( 'woocommerce_extensible_abilities_init' );

		$created = $this->create(
			array(
				'taxonomy' => 'product_tag',
				'name'     => 'Sale',
			)
		);
		$updated = $this->update(
			array(
				'taxonomy' => 'product_tag',
				'id'       => $id,
				'name'     => 'Clearance',
			)
		);

		$created_term = get_term_by( 'name', 'Sale', 'product_tag' );
		$this->assertInstanceOf( \WP_Term::class, $created_term );
		$results = array(
			$created_term->term_id => $created,
			$id                    => $updated,
		);
		foreach ( $results as $term_id => $result ) {
			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_field_output_invalid', $result->get_error_code() );
			$this->assertStringContainsString( 'The change is saved on the product_term ' . $term_id . ',', $result->get_error_message() );
			$this->assertTrue( $result->get_error_data()['saved'] );
			$this->assertSame( $term_id, $result->get_error_data()['id'] );
		}
		$this->assertSame( 'Clearance', get_term( $id, 'product_tag' )->name );
	}

	/**
	 * Run the create ability.
	 *
	 * @param array $input Ability input.
	 * @return mixed
	 */
	private function create( array $input ) {
		return ( new Ability( ProductTermCreate::get_name(), ProductTermCreate::get_registration_args() ) )->execute( $input );
	}

	/**
	 * Run the update ability.
	 *
	 * @param array $input Ability input.
	 * @return mixed
	 */
	private function update( array $input ) {
		return ( new Ability( ProductTermUpdate::get_name(), ProductTermUpdate::get_registration_args() ) )->execute( $input );
	}
}
