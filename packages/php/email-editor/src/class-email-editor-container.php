<?php
/**
 * Email Editor Container class file.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor;

use Automattic\WooCommerce\Blocks\Registry\Container;
use Automattic\WooCommerce\EmailEditor\Engine\Dependency_Check;
use Automattic\WooCommerce\EmailEditor\Engine\Email_Api_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Patterns\Patterns;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Blocks_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Content_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Highlighting_Postprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Variables_Postprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Blocks_Width_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Cleanup_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Spacing_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Typography_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Quote_Preprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Border_Style_Postprocessor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Process_Manager;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Send_Preview_Email;
use Automattic\WooCommerce\EmailEditor\Engine\Settings_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Engine\User_Theme;
use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Initializer;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Email_Editor_Container {
	/**
	 * Init method.
	 *
	 * @return void
	 */
	public static function init() {
		self::container()->get( Bootstrap::class )->init();
	}

	/**
	 * Loads the DI container for the Email editor.
	 *
	 * @internal This uses the Blocks DI container. This container will be replaced
	 * with a different compatible container.
	 *
	 * @param boolean $reset Used to reset the container to a fresh instance. Note: this means all dependencies will be reconstructed.
	 * @return Container
	 */
	public static function container( $reset = false ) {
		static $container;

		if ( $reset ) {
			$container = null;
		}

		if ( $container ) {
			return $container;
		}

		$container = new Container();

		// Start: MailPoet plugin dependencies.
		$container->register(
			Initializer::class,
			function () {
				return new Initializer();
			}
		);
		// End: MailPoet plugin dependencies.
		// Start: Email editor dependencies.
		$container->register(
			Theme_Controller::class,
			function () {
				return new Theme_Controller();
			}
		);
		$container->register(
			User_Theme::class,
			function () {
				return new User_Theme();
			}
		);
		$container->register(
			Settings_Controller::class,
			function ( $container ) {
				return new Settings_Controller( $container->get( Theme_Controller::class ) );
			}
		);
		$container->register(
			Settings_Controller::class,
			function ( $container ) {
				return new Settings_Controller( $container->get( Theme_Controller::class ) );
			}
		);
		$container->register(
			Templates_Registry::class,
			function () {
				return new Templates_Registry();
			}
		);
		$container->register(
			Templates::class,
			function ( $container ) {
				return new Templates( $container->get( Templates_Registry::class ) );
			}
		);
		$container->register(
			Patterns::class,
			function () {
				return new Patterns();
			}
		);
		$container->register(
			Cleanup_Preprocessor::class,
			function () {
				return new Cleanup_Preprocessor();
			}
		);
		$container->register(
			Blocks_Width_Preprocessor::class,
			function () {
				return new Blocks_Width_Preprocessor();
			}
		);
		$container->register(
			Typography_Preprocessor::class,
			function ( $container ) {
				return new Typography_Preprocessor( $container->get( Settings_Controller::class ) );
			}
		);
		$container->register(
			Spacing_Preprocessor::class,
			function () {
				return new Spacing_Preprocessor();
			}
		);
		$container->register(
			Quote_Preprocessor::class,
			function () {
				return new Quote_Preprocessor();
			}
		);
		$container->register(
			Highlighting_Postprocessor::class,
			function () {
				return new Highlighting_Postprocessor();
			}
		);
		$container->register(
			Variables_Postprocessor::class,
			function ( $container ) {
				return new Variables_Postprocessor( $container->get( Theme_Controller::class ) );
			}
		);
		$container->register(
			Border_Style_Postprocessor::class,
			function () {
				return new Border_Style_Postprocessor();
			}
		);
		$container->register(
			Process_Manager::class,
			function ( $container ) {
				return new Process_Manager(
					$container->get( Cleanup_Preprocessor::class ),
					$container->get( Blocks_Width_Preprocessor::class ),
					$container->get( Typography_Preprocessor::class ),
					$container->get( Spacing_Preprocessor::class ),
					$container->get( Quote_Preprocessor::class ),
					$container->get( Highlighting_Postprocessor::class ),
					$container->get( Variables_Postprocessor::class ),
					$container->get( Border_Style_Postprocessor::class )
				);
			}
		);
		$container->register(
			Blocks_Registry::class,
			function () {
				return new Blocks_Registry();
			}
		);
		$container->register(
			Content_Renderer::class,
			function ( $container ) {
				return new Content_Renderer(
					$container->get( Process_Manager::class ),
					$container->get( Blocks_Registry::class ),
					$container->get( Settings_Controller::class ),
					new Email_Css_Inliner(),
					$container->get( Theme_Controller::class ),
				);
			}
		);
		$container->register(
			Renderer::class,
			function ( $container ) {
				return new Renderer(
					$container->get( Content_Renderer::class ),
					$container->get( Templates::class ),
					new Email_Css_Inliner(),
					$container->get( Theme_Controller::class ),
				);
			}
		);
		$container->register(
			Personalization_Tags_Registry::class,
			function ( $container ) {
				return new Personalization_Tags_Registry(
					$container->get( Email_Editor_Logger::class )
				);
			}
		);
		$container->register(
			Personalizer::class,
			function ( $container ) {
				return new Personalizer(
					$container->get( Personalization_Tags_Registry::class ),
				);
			}
		);
		$container->register(
			Send_Preview_Email::class,
			function ( $container ) {
				return new Send_Preview_Email(
					$container->get( Renderer::class ),
					$container->get( Personalizer::class ),
				);
			}
		);
		$container->register(
			Email_Api_Controller::class,
			function ( $container ) {
				return new Email_Api_Controller(
					$container->get( Personalization_Tags_Registry::class ),
				);
			}
		);
		$container->register(
			Dependency_Check::class,
			function () {
				return new Dependency_Check();
			}
		);
		$container->register(
			Email_Editor_Logger::class,
			function () {
				return new Email_Editor_Logger();
			}
		);
		$container->register(
			Email_Editor::class,
			function ( $container ) {
				return new Email_Editor(
					$container->get( Email_Api_Controller::class ),
					$container->get( Templates::class ),
					$container->get( Patterns::class ),
					$container->get( Send_Preview_Email::class ),
					$container->get( Personalization_Tags_Registry::class ),
					$container->get( Email_Editor_Logger::class )
				);
			}
		);
		// End: Email editor dependencies.

		// Start: Woo dependencies.
		$container->register(
			Bootstrap::class,
			function ( $container ) {
				return new Bootstrap(
					$container->get( Email_Editor::class ),
					$container->get( Initializer::class ),
				);
			}
		);

		return $container;
	}
}
