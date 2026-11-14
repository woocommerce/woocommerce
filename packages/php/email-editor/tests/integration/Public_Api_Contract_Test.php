<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check;
use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;
use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger_Interface;
use Automattic\WooCommerce\EmailEditor\Engine\Patterns\Abstract_Pattern;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\HTML_Tag_Processor;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Process_Manager;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Css_Inliner;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Html2Text;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Send_Preview_Email;
use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Template;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\User_Theme;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Column;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Columns;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Image;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Initializer as WooCommerce_Initializer;

/**
 * Pins the parts of this package that MailPoet uses: the services it takes from the container,
 * the methods it calls with the argument counts it passes, the interfaces and base classes it
 * builds on, and the hooks it listens to. A failure here means a change would break MailPoet.
 *
 * The list comes from MailPoet's code (free and Premium PHP). Update it when MailPoet starts or
 * stops using a part of the package.
 */
class Public_Api_Contract_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Test MailPoet can take each service it uses from the container.
	 *
	 * @dataProvider service_provider
	 * @param class-string $service_class Service class.
	 */
	public function testContainerProvidesService( string $service_class ): void {
		$this->assertInstanceOf( $service_class, Email_Editor_Container::container()->get( $service_class ) );
	}

	/**
	 * Services MailPoet takes from the container.
	 *
	 * @return array<string, array{class-string}>
	 */
	public function service_provider(): array {
		$services = array(
			Bootstrap::class,
			Email_Editor_Logger::class,
			Personalizer::class,
			Personalization_Tags_Registry::class,
			Dependency_Check::class,
			Settings_Controller::class,
			Theme_Controller::class,
			User_Theme::class,
			Renderer::class,
			Process_Manager::class,
			WooCommerce_Initializer::class,
		);
		return array_combine( $services, array_map( fn( $service ) => array( $service ), $services ) );
	}

	/**
	 * Test each method MailPoet calls exists, is public and accepts the arguments MailPoet passes.
	 *
	 * @dataProvider method_provider
	 * @param string $class_name Class.
	 * @param string $method     Method.
	 * @param int    $arg_count  Number of arguments MailPoet passes.
	 * @param bool   $is_static  Whether MailPoet calls it statically.
	 */
	public function testMethodAcceptsMailPoetCall( string $class_name, string $method, int $arg_count, bool $is_static = false ): void {
		$this->assertTrue( method_exists( $class_name, $method ), "{$class_name}::{$method}() is missing." );
		$reflection = new \ReflectionMethod( $class_name, $method );
		$this->assertTrue( $reflection->isPublic(), "{$class_name}::{$method}() must stay public." );
		$this->assertSame( $is_static, $reflection->isStatic(), "{$class_name}::{$method}() changed between static and instance." );
		$this->assertLessThanOrEqual( $arg_count, $reflection->getNumberOfRequiredParameters(), "{$class_name}::{$method}() requires more arguments than MailPoet passes." );
		$this->assertGreaterThanOrEqual( $arg_count, $reflection->getNumberOfParameters(), "{$class_name}::{$method}() accepts fewer arguments than MailPoet passes." );
	}

	/**
	 * Methods MailPoet calls, with the most arguments it passes.
	 *
	 * @return array<string, array{0: string, 1: string, 2: int, 3?: bool}>
	 */
	public function method_provider(): array {
		$methods = array(
			array( Email_Editor_Container::class, 'init', 0, true ),
			array( Email_Editor_Container::class, 'container', 0, true ),
			array( Bootstrap::class, 'init', 0 ),
			array( Email_Editor_Logger::class, 'set_logger', 1 ),
			array( Dependency_Check::class, 'are_dependencies_met', 0 ),
			array( Personalizer::class, 'set_context', 1 ),
			array( Personalizer::class, 'personalize_content', 2 ),
			array( Personalization_Tags_Registry::class, 'register', 1 ),
			array( Personalization_Tags_Registry::class, 'unregister', 1 ),
			array( Personalization_Tags_Registry::class, 'get_all', 0 ),
			array( Personalization_Tags_Registry::class, 'get_by_token', 1 ),
			array( Personalization_Tag::class, '__construct', 7 ),
			array( Personalization_Tag::class, '__construct', 8 ),
			array( Personalization_Tag::class, 'get_name', 0 ),
			array( Personalization_Tag::class, 'get_token', 0 ),
			array( Personalization_Tag::class, 'get_category', 0 ),
			array( Personalization_Tag::class, 'get_callback', 0 ),
			array( Personalization_Tag::class, 'get_attributes', 0 ),
			array( Personalization_Tag::class, 'get_value_to_insert', 0 ),
			array( Personalization_Tag::class, 'get_post_types', 0 ),
			array( Personalization_Tag::class, 'get_value_type', 0 ),
			array( Personalization_Tag::class, 'execute_callback', 1 ),
			array( HTML_Tag_Processor::class, '__construct', 1 ),
			array( HTML_Tag_Processor::class, 'replace_token', 1 ),
			array( HTML_Tag_Processor::class, 'flush_updates', 0 ),
			array( Html2Text::class, 'convert', 2, true ),
			array( Renderer::class, 'render', 5 ),
			array( Renderer::class, 'render', 6 ),
			array( Rendering_Context::class, '__construct', 1 ),
			array( Rendering_Context::class, 'get_email_context', 0 ),
			array( Rendering_Context::class, 'get_recipient_email', 0 ),
			array( Rendering_Context::class, 'get_theme_styles', 0 ),
			array( Rendering_Context::class, 'get_layout_width_without_padding', 0 ),
			array( Process_Manager::class, 'preprocess', 4 ),
			array( Theme_Controller::class, 'get_base_theme', 0 ),
			array( Theme_Controller::class, 'get_theme', 0 ),
			array( Theme_Controller::class, 'get_styles', 0 ),
			array( Theme_Controller::class, 'get_variables_values_map', 0 ),
			array( Theme_Controller::class, 'get_layout_settings', 0 ),
			array( Settings_Controller::class, 'get_settings', 0 ),
			array( User_Theme::class, 'get_user_theme_post', 0 ),
			array( Template::class, '__construct', 6 ),
			array( Templates_Registry::class, 'register', 1 ),
			array( Abstract_Pattern::class, 'get_name', 0 ),
			array( Abstract_Pattern::class, 'get_namespace', 0 ),
			array( Abstract_Pattern::class, 'get_properties', 0 ),
			array( WooCommerce_Initializer::class, 'render_block', 3 ),
			array( Image::class, 'render', 3 ),
			array( Column::class, 'render', 3 ),
			array( Columns::class, 'render', 3 ),
		);
		$cases   = array();
		foreach ( $methods as $method ) {
			$cases[ "{$method[0]}::{$method[1]}() with {$method[2]} arguments" ] = $method;
		}
		return $cases;
	}

	/**
	 * Test the interfaces MailPoet implements keep exactly their method signatures. A new method,
	 * or a new parameter or return type, would make MailPoet's classes fail to load.
	 *
	 * @dataProvider interface_provider
	 * @param class-string          $interface_name Interface.
	 * @param array<string, string> $signatures     Method name => parameters and return type.
	 */
	public function testInterfaceSignaturesStayTheSame( string $interface_name, array $signatures ): void {
		$actual = array();
		foreach ( ( new \ReflectionClass( $interface_name ) )->getMethods() as $method ) {
			$parameters                   = array_map(
				fn( \ReflectionParameter $parameter ) => trim( ( $parameter->getType() ?? '' ) . ' $' . $parameter->getName() . ( $parameter->isOptional() ? ' = ?' : '' ) ),
				$method->getParameters()
			);
			$actual[ $method->getName() ] = '(' . implode( ', ', $parameters ) . '): ' . $method->getReturnType();
		}
		ksort( $actual );
		ksort( $signatures );

		$this->assertSame( $signatures, $actual );
	}

	/**
	 * Interfaces MailPoet implements.
	 *
	 * @return array<string, array{class-string, array<string, string>}>
	 */
	public function interface_provider(): array {
		$log = '(string $message, array $context = ?): void';
		return array(
			'logger'      => array(
				Email_Editor_Logger_Interface::class,
				array(
					'alert'     => $log,
					'critical'  => $log,
					'debug'     => $log,
					'emergency' => $log,
					'error'     => $log,
					'info'      => $log,
					'log'       => '(string $level, string $message, array $context = ?): void',
					'notice'    => $log,
					'warning'   => $log,
				),
			),
			'CSS inliner' => array(
				Css_Inliner::class,
				array(
					'from_html'  => '(string $unprocessed_html): self',
					'inline_css' => '(string $css = ?): self',
					'render'     => '(): string',
				),
			),
		);
	}

	/**
	 * Test the pattern base class MailPoet extends keeps its abstract methods and the properties
	 * MailPoet's patterns set.
	 */
	public function testPatternBaseClassStaysExtensible(): void {
		$reflection = new \ReflectionClass( Abstract_Pattern::class );
		$abstract   = array_map( fn( \ReflectionMethod $method ) => $method->getName(), $reflection->getMethods( \ReflectionMethod::IS_ABSTRACT ) );
		sort( $abstract );

		$this->assertSame( array( 'get_content', 'get_title' ), $abstract, 'A new abstract method would break MailPoet patterns.' );
		foreach ( array( 'name', 'namespace', 'block_types', 'template_types', 'categories', 'post_types' ) as $property ) {
			$this->assertTrue( $reflection->hasProperty( $property ), "Property \${$property} is missing." );
			$this->assertFalse( $reflection->getProperty( $property )->isPrivate(), "Property \${$property} must stay overridable." );
		}
	}

	/**
	 * Test the constants MailPoet reads keep their values.
	 *
	 * @dataProvider constant_provider
	 * @param string $constant Class constant.
	 * @param string $expected Value MailPoet relies on.
	 */
	public function testConstantKeepsItsValue( string $constant, string $expected ): void {
		$this->assertTrue( defined( $constant ), "{$constant} is missing." );
		$this->assertSame( $expected, constant( $constant ) );
	}

	/**
	 * Constants MailPoet reads.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function constant_provider(): array {
		return array(
			'HTML rendering context' => array( Personalizer::class . '::RENDERING_CONTEXT_HTML', 'html' ),
			'text rendering context' => array( Personalizer::class . '::RENDERING_CONTEXT_TEXT', 'text' ),
			'link rendering context' => array( Personalizer::class . '::RENDERING_CONTEXT_HREF', 'href' ),
			'rendering context key'  => array( Personalizer::class . '::RENDERING_CONTEXT_KEY', 'rendering_context' ),
			'text value type'        => array( Personalization_Tag::class . '::VALUE_TYPE_TEXT', 'text' ),
			'minimum WordPress'      => array( Dependency_Check::class . '::MIN_WP_VERSION', '6.7' ),
		);
	}

	/**
	 * Test the registries pass themselves to the filters MailPoet registers on.
	 */
	public function testRegistriesPassThemselvesToTheirFilters(): void {
		$templates_registry = $this->di_container->get( Templates_Registry::class );
		$tags_registry      = $this->di_container->get( Personalization_Tags_Registry::class );
		$received           = array();
		foreach ( array( 'woocommerce_email_editor_register_templates', 'woocommerce_email_editor_register_personalization_tags' ) as $hook ) {
			add_filter(
				$hook,
				function ( $registry ) use ( &$received, $hook ) {
					$received[ $hook ] = $registry;
					return $registry;
				}
			);
		}

		$templates_registry->initialize();
		$tags_registry->initialize();

		$this->assertSame( $templates_registry, $received['woocommerce_email_editor_register_templates'] ?? null );
		$this->assertSame( $tags_registry, $received['woocommerce_email_editor_register_personalization_tags'] ?? null );
	}

	/**
	 * Test a preview email passes its personalization context through the filter MailPoet uses.
	 */
	public function testPreviewEmailFiltersItsPersonalizerContext(): void {
		$seen = null;
		add_filter(
			'woocommerce_email_editor_send_preview_email_personalizer_context',
			function ( $context ) use ( &$seen ) {
				$seen = $context;
				return array_merge( $context, array( 'mailpoet_value' => 'set by MailPoet' ) );
			}
		);
		$this->di_container->get( Personalization_Tags_Registry::class )->register(
			new Personalization_Tag( 'Value', 'mailpoet/value', 'Test', fn( $context ) => $context['mailpoet_value'] ?? '' )
		);

		$content = $this->di_container->get( Send_Preview_Email::class )->set_personalize_content( '<p><!--[mailpoet/value]--></p>' );

		$this->assertIsArray( $seen );
		$this->assertSame( '<p>set by MailPoet</p>', $content );
	}

	/**
	 * Test initializing the editor fires the hooks MailPoet uses to set itself up.
	 */
	public function testInitializingFiresMailPoetSetupHooks(): void {
		$initialized_count = did_action( 'woocommerce_email_editor_initialized' );
		$filtered          = array();
		foreach ( array( 'woocommerce_email_editor_post_types', 'woocommerce_is_email_editor_page' ) as $hook ) {
			add_filter(
				$hook,
				function ( $value ) use ( &$filtered, $hook ) {
					$filtered[ $hook ] = true;
					return $value;
				}
			);
		}

		$this->di_container->get( Email_Editor::class )->initialize();

		$this->assertSame( $initialized_count + 1, did_action( 'woocommerce_email_editor_initialized' ) );
		$this->assertArrayHasKey( 'woocommerce_email_editor_post_types', $filtered );
		$this->assertArrayHasKey( 'woocommerce_is_email_editor_page', $filtered );
	}

	/**
	 * Test rendering an email fires the rendering hooks MailPoet uses, with the arguments it reads.
	 */
	public function testRenderingFiresMailPoetHooks(): void {
		$this->di_container->get( Email_Editor::class )->initialize();
		$post_id = $this->factory->post->create( array( 'post_content' => '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->' ) );
		$this->assertIsInt( $post_id );
		$post = get_post( $post_id );
		$this->assertInstanceOf( \WP_Post::class, $post );
		$args  = array();
		$hooks = array(
			'woocommerce_email_editor_rendering_email_context' => 3,
			'woocommerce_email_blocks_renderer_parsed_blocks' => 1,
			'woocommerce_email_content_renderer_styles' => 2,
		);
		foreach ( $hooks as $hook => $accepted ) {
			add_filter(
				$hook,
				function ( ...$values ) use ( &$args, $hook ) {
					$args[ $hook ] = $values;
					return $values[0];
				},
				10,
				$accepted
			);
		}
		$render_start_count = did_action( 'woocommerce_email_editor_render_start' );

		$this->di_container->get( Renderer::class )->render( $post, 'Subject', '', 'en_US' );

		$this->assertSame( $render_start_count + 1, did_action( 'woocommerce_email_editor_render_start' ) );
		$this->assertIsArray( $args['woocommerce_email_editor_rendering_email_context'][0] ?? null, 'Context array' );
		$this->assertSame( $post, $args['woocommerce_email_editor_rendering_email_context'][1] ?? null, 'Rendered post' );
		$this->assertInstanceOf( \WP_Block_Template::class, $args['woocommerce_email_editor_rendering_email_context'][2] ?? null, 'Template' );
		$this->assertIsArray( $args['woocommerce_email_blocks_renderer_parsed_blocks'][0] ?? null, 'Parsed blocks' );
		$this->assertIsString( $args['woocommerce_email_content_renderer_styles'][0] ?? null, 'Styles' );
		$this->assertSame( $post, $args['woocommerce_email_content_renderer_styles'][1] ?? null, 'Post for styles' );
	}
}
