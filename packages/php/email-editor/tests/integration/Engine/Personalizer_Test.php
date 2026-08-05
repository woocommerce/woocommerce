<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Logger\Email_Editor_Logger;

/**
 * Integration test for Personalizer class which validate the functionality
 * of Personalizer using Personalization_Tags_Registry.
 */
class Personalizer_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Instance of Personalizer created before each test.
	 *
	 * @var Personalizer
	 */
	private Personalizer $personalizer;
	/**
	 * Instance of Personalization_Tags_Registry created before each test.
	 *
	 * @var Personalization_Tags_Registry
	 */
	private Personalization_Tags_Registry $tags_registry;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->tags_registry = new Personalization_Tags_Registry( $this->di_container->get( Email_Editor_Logger::class ) );
		$this->personalizer  = new Personalizer( $this->tags_registry );
	}

	/**
	 * Test personalizing content with a single tag.
	 */
	public function testPersonalizeContentWithSingleTag(): void {
		// Register a tag in the registry.
		$this->tags_registry->register(
			new Personalization_Tag(
				'first_name',
				'user-firstname',
				'User',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					return $context['subscriber_name'] ?? 'Default Name';
				}
			)
		);

		$this->personalizer->set_context( array( 'subscriber_name' => 'John' ) );
		$html_content = '<p>Hello, <!--[user-firstname]-->!</p>';
		$this->assertSame( '<p>Hello, John!</p>', $this->personalizer->personalize_content( $html_content ) );
	}

	/**
	 * Test personalizing content with multiple tags.
	 */
	public function testPersonalizeContentWithMultipleTags(): void {
		// Register multiple tags in the registry.
		$this->tags_registry->register(
			new Personalization_Tag(
				'first_name',
				'[user/firstname]',
				'Subscriber Info',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					return $context['subscriber_name'] ?? 'Default Name';
				}
			)
		);

		$this->tags_registry->register(
			new Personalization_Tag(
				'email',
				'user/email',
				'Subscriber Info',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					return $context['subscriber_email'] ?? 'unknown@example.com';
				}
			)
		);

		// Set the context for personalization.
		$this->personalizer->set_context(
			array(
				'subscriber_name'  => 'John',
				'subscriber_email' => 'john@example.com',
			)
		);

		$html_content = '
			<div>
				<h1>Hello, <!--[user/firstname]-->!</h1>
				<p>Your email is <!--[user/email]-->.</p>
			</div>
		';

		$personalized_content = $this->personalizer->personalize_content( $html_content );
		$this->assertSame(
			'
			<div>
				<h1>Hello, John!</h1>
				<p>Your email is john@example.com.</p>
			</div>
		',
			$personalized_content
		);
	}

	/**
	 * Test a missing tag in the registry.
	 */
	public function testMissingTagInRegistry(): void {
		$html_content         = '<p>Hello, <!--[mailpoet/unknown-tag]-->!</p>';
		$personalized_content = $this->personalizer->personalize_content( $html_content );
		$this->assertSame( '<p>Hello, <!--[mailpoet/unknown-tag]-->!</p>', $personalized_content );
	}

	/**
	 * Test a callback arguments.
	 */
	public function testTagWithArguments(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'default_name',
				'[user/firstname]',
				'Subscriber Info',
				function ( $context, $args ) {
					return $args['default'] ?? 'Default Name';
				}
			)
		);

		$html_content = '<p>Hello, <!--[user/firstname default="Guest"]-->!</p>';
		$this->assertSame( '<p>Hello, Guest!</p>', $this->personalizer->personalize_content( $html_content ) );
	}

	/**
	 * Test a callback arguments.
	 */
	public function testPersonalizationInTitle(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'default_name',
				'[user/firstname]',
				'Subscriber Info',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					return $context['user_name'] ?? 'Default Name';
				}
			)
		);

		$html_content = '
			<html>
				<head>
					<title>Welcome, <!--[user/firstname default="Guest"]-->!</title>
			</head>
			<body>
				<p>Hello, <!--[user/firstname default="Guest"]-->!</p>
			</html>
		';
		$this->personalizer->set_context( array( 'user_name' => 'John' ) );
		$this->assertSame(
			'
			<html>
				<head>
					<title>Welcome, John!</title>
			</head>
			<body>
				<p>Hello, John!</p>
			</html>
		',
			$this->personalizer->personalize_content( $html_content )
		);
	}

	/**
	 * Test personalizing content with a tag in href attribute.
	 */
	public function testPersonalizeContentWithHrefTag(): void {
		// Register a tag in the registry.
		$this->tags_registry->register(
			new Personalization_Tag(
				'Store URL',
				'woocommerce/store-url',
				'Store',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					return 'https://example.com';
				}
			)
		);

		$html_content = '<a href="http://[woocommerce/store-url]">Click here</a>';
		$this->assertSame( '<a href="https://example.com">Click here</a>', $this->personalizer->personalize_content( $html_content ) );
	}

	/**
	 * Test personalizing content with a tag in href attribute with URL encoding.
	 */
	public function testPersonalizeContentWithEncodedHrefTag(): void {
		// Register a tag in the registry.
		$this->tags_registry->register(
			new Personalization_Tag(
				'Store URL',
				'woocommerce/store-url',
				'Store',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					return 'https://example.com';
				}
			)
		);

		$html_content = '<a href="http://%5Bwoocommerce/store-url%5D">Click here</a>';
		$this->assertSame( '<a href="https://example.com">Click here</a>', $this->personalizer->personalize_content( $html_content ) );
	}

	/**
	 * Test personalizing content with a non-existent tag in href attribute.
	 */
	public function testPersonalizeContentWithNonExistentHrefTag(): void {
		$html_content = '<a href="http://[woocommerce/non-existent-tag]">Click here</a>';
		$this->assertSame( '<a href="http://[woocommerce/non-existent-tag]">Click here</a>', $this->personalizer->personalize_content( $html_content ) );
	}

	/**
	 * Test personalizing content with a tag in href attribute that includes attributes.
	 */
	public function testPersonalizeContentWithHrefTagWithAttributes(): void {
		// Register a tag in the registry.
		$this->tags_registry->register(
			new Personalization_Tag(
				'Trackable Link',
				'trackable-link',
				'Links',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- The $context parameter is not used in this test.
					return 'https://example.com?url=' . ( $args['url'] ?? '' ) . '&desc=' . ( $args['desc'] ?? '' );
				}
			)
		);

		$html_content = '<a href="[trackable-link url=\'wordpress.com\' desc=\'home-page\']">Click here</a>';
		// Note: WordPress encodes & as &#038; in URLs.
		$expected = '<a href="https://example.com?url=wordpress.com&#038;desc=home-page">Click here</a>';
		$this->assertSame( $expected, $this->personalizer->personalize_content( $html_content ) );
	}

	/**
	 * Test that a tag embedded inside a larger URL is replaced in place.
	 */
	public function testPersonalizeContentWithEmbeddedHrefTag(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'email',
				'user/email',
				'Subscriber Info',
				function () {
					return 'john@example.com';
				}
			)
		);

		$this->assertSame(
			'<a href="http://example.com?test=john@example.com">Click here</a>',
			$this->personalizer->personalize_content( '<a href="http://example.com?test=[user/email]">Click here</a>' ),
			'The token should be replaced within the surrounding URL'
		);
		$this->assertSame(
			'<a href="http://example.com/?next=%2Fshop%2F&#038;e=john@example.com">Click here</a>',
			$this->personalizer->personalize_content( '<a href="http://example.com/?next=%2Fshop%2F&e=[user/email]">Click here</a>' ),
			'Percent-encoding elsewhere in the URL should be preserved'
		);
	}

	/**
	 * Test how the editor-forced protocol prefix is handled for whole-URL tags.
	 */
	public function testWholeUrlHrefTagPrefixHandling(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'Store URL',
				'woocommerce/store-url',
				'Store',
				function () {
					return 'https://example.com';
				}
			)
		);
		$this->tags_registry->register(
			new Personalization_Tag(
				'Store domain',
				'woocommerce/store-domain',
				'Store',
				function () {
					return 'example.com';
				}
			)
		);

		$this->assertSame(
			'<a href="https://example.com?utm_source=email">Click here</a>',
			$this->personalizer->personalize_content( '<a href="http://[woocommerce/store-url]?utm_source=email">Click here</a>' ),
			'An appended suffix should be kept while the forced prefix is discarded'
		);
		$this->assertSame(
			'<a href="https://example.com">Click here</a>',
			$this->personalizer->personalize_content( '<a href="HTTP://[woocommerce/store-url]">Click here</a>' ),
			'The forced prefix should be discarded case-insensitively'
		);
		// esc_url() prepends the scheme when the value lacks one.
		$this->assertSame(
			'<a href="http://example.com">Click here</a>',
			$this->personalizer->personalize_content( '<a href="http://[woocommerce/store-domain]">Click here</a>' ),
			'A schemeless value should still produce a usable link'
		);
	}

	/**
	 * Test that multiple different tags in one href are all replaced.
	 */
	public function testMultipleTagsInOneHref(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'email',
				'user/email',
				'Subscriber Info',
				function () {
					return 'john@example.com';
				}
			)
		);
		$this->tags_registry->register(
			new Personalization_Tag(
				'first_name',
				'user/firstname',
				'Subscriber Info',
				function () {
					return 'John';
				}
			)
		);

		$html_content = '<a href="http://example.com/?e=[user/email]&n=[user/firstname]">Click here</a>';
		$this->assertSame(
			'<a href="http://example.com/?e=john@example.com&#038;n=John">Click here</a>',
			$this->personalizer->personalize_content( $html_content )
		);
	}

	/**
	 * Test that the same tag with different arguments produces per-occurrence values.
	 */
	public function testSameTagWithDifferentArgumentsInOneHref(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'echo_arg',
				'test/echo',
				'Test',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- The $context parameter is not used in this test.
					return $args['value'] ?? '';
				}
			)
		);

		$html_content = '<a href="http://example.com/?a=[test/echo value=one]&b=[test/echo value=two]">Click here</a>';
		$this->assertSame(
			'<a href="http://example.com/?a=one&#038;b=two">Click here</a>',
			$this->personalizer->personalize_content( $html_content )
		);
	}

	/**
	 * Test that an unreplaced leading bracket does not cost the URL its protocol.
	 */
	public function testUnregisteredLeadingBracketKeepsProtocol(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'coupon',
				'test/coupon',
				'Test',
				function () {
					return 'SAVE20';
				}
			)
		);

		$html_content = '<a href="http://[2024]/page?ref=[test/coupon]">Click here</a>';
		$this->assertSame(
			'<a href="http://[2024]/page?ref=SAVE20">Click here</a>',
			$this->personalizer->personalize_content( $html_content )
		);
	}

	/**
	 * Test that a token appearing both plain and URL-encoded in one href is replaced in both places.
	 */
	public function testMixedPlainAndEncodedTokenOccurrences(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'coupon',
				'test/coupon',
				'Test',
				function () {
					return 'SAVE20';
				}
			)
		);

		$html_content = '<a href="http://example.com/?a=[test/coupon]&b=%5Btest/coupon%5D">Click here</a>';
		$this->assertSame(
			'<a href="http://example.com/?a=SAVE20&#038;b=SAVE20">Click here</a>',
			$this->personalizer->personalize_content( $html_content )
		);
	}

	/**
	 * Test that a tag value containing another tag's token is not re-scanned for replacements.
	 */
	public function testTagValueContainingAnotherTokenStaysLiteral(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'name',
				'test/name',
				'Test',
				function () {
					return 'x[test/coupon]y';
				}
			)
		);
		$this->tags_registry->register(
			new Personalization_Tag(
				'coupon',
				'test/coupon',
				'Test',
				function () {
					return 'SAVE20';
				}
			)
		);

		$html_content = '<a href="http://example.com/?n=[test/name]&c=[test/coupon]">Click here</a>';
		$this->assertSame(
			'<a href="http://example.com/?n=x%5Btest/coupon%5Dy&#038;c=SAVE20">Click here</a>',
			$this->personalizer->personalize_content( $html_content )
		);
	}

	/**
	 * Test that an unregistered URL-encoded bracket sequence does not cost the URL its encoding.
	 */
	public function testUnregisteredEncodedBracketKeepsSurroundingEncoding(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'coupon',
				'test/coupon',
				'Test',
				function () {
					return 'SAVE20';
				}
			)
		);

		// items%5B0%5D decodes to a token-shaped [0]; it must not force the decoded base.
		$html_content = '<a href="https://shop.example/checkout?items%5B0%5D=2&note=a%26b&c=[test/coupon]">Click here</a>';
		$this->assertSame(
			'<a href="https://shop.example/checkout?items%5B0%5D=2&#038;note=a%26b&#038;c=SAVE20">Click here</a>',
			$this->personalizer->personalize_content( $html_content )
		);
	}

	/**
	 * Test that a tag value of "0" is a valid replacement in links.
	 */
	public function testZeroStringTagValueIsReplacedInHref(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'items_count',
				'test/count',
				'Test',
				function () {
					return '0';
				}
			)
		);

		$html_content = '<a href="http://example.com/?count=[test/count]">Click here</a>';
		$this->assertSame(
			'<a href="http://example.com/?count=0">Click here</a>',
			$this->personalizer->personalize_content( $html_content )
		);

		// The data-link-href site must also treat "0" as a valid value and clean up the editor-only attributes.
		$this->assertSame(
			'<a  href="http://0">Click here</a>',
			$this->personalizer->personalize_content( '<a data-link-href="[test/count]" href="#">Click here</a>' )
		);
	}

	/**
	 * Test that a token present only in URL-encoded form switches the whole href to the decoded base.
	 *
	 * This pins a known tradeoff: the decoded base also decodes unrelated percent-encoding
	 * elsewhere in the URL (the redirect parameter below loses its encoding).
	 */
	public function testEncodedOnlyTokenDecodesWholeHrefBase(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'coupon',
				'test/coupon',
				'Test',
				function () {
					return 'SAVE20';
				}
			)
		);

		$html_content = '<a href="https://example.com/?redirect=%2Fpath%3Fa%3D1%26b%3D2&tag=%5Btest/coupon%5D">Click here</a>';
		$this->assertSame(
			'<a href="https://example.com/?redirect=/path?a=1&#038;b=2&#038;tag=SAVE20">Click here</a>',
			$this->personalizer->personalize_content( $html_content )
		);
	}

	/**
	 * Test that dollar signs and backslashes in tag values are inserted literally into links.
	 */
	public function testHrefTagValueWithRegexSpecialCharacters(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'Tracking URL',
				'test/tracking-url',
				'Test',
				function () {
					return 'https://example.com/?q=$0&r=${1}&b=a\\1b';
				}
			)
		);

		// The data-link-href site goes through the regex-based replacement.
		// Note: esc_url() strips the curly braces from `${1}` and the backslash from `\1`;
		// the important part is that `$0`, `$1`, and `\1` are not interpreted as regex backreferences.
		$this->assertSame(
			'<a  href="https://example.com/?q=$0&#038;r=$1&#038;b=a1b">Click here</a>',
			$this->personalizer->personalize_content( '<a data-link-href="[test/tracking-url]" href="#">Click here</a>' ),
			'The data-link-href site should insert regex metacharacters literally'
		);
		// The plain-href site uses exact string replacement.
		$this->assertSame(
			'<a href="https://r.example/go?to=https://example.com/?q=$0&#038;r=$1&#038;b=a1b&#038;p=1">Click here</a>',
			$this->personalizer->personalize_content( '<a href="https://r.example/go?to=[test/tracking-url]&p=1">Click here</a>' ),
			'The plain-href site should insert regex metacharacters literally'
		);
	}

	/**
	 * Test that the callback receives the rendering context passed to personalize_content.
	 */
	public function testCallbackReceivesRenderingContext(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'context_echo',
				'test/context',
				'Test',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					return $context[ Personalizer::RENDERING_CONTEXT_KEY ];
				}
			)
		);

		$content = '<p><!--[test/context]--></p>';
		$this->assertSame( '<p>html</p>', $this->personalizer->personalize_content( $content ) );
		$this->assertSame( '<p>text</p>', $this->personalizer->personalize_content( $content, Personalizer::RENDERING_CONTEXT_TEXT ) );
		// Unknown rendering context falls back to html.
		$this->assertSame( '<p>html</p>', $this->personalizer->personalize_content( $content, 'bogus' ) );
	}

	/**
	 * Test that the title tag content is personalized in the text rendering context.
	 */
	public function testTitleReceivesTextRenderingContext(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'context_echo',
				'test/context',
				'Test',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					return $context[ Personalizer::RENDERING_CONTEXT_KEY ];
				}
			)
		);

		$content = '<html><head><title><!--[test/context]--></title></head><body><p><!--[test/context]--></p></body></html>';
		$this->assertSame(
			'<html><head><title>text</title></head><body><p>html</p></body></html>',
			$this->personalizer->personalize_content( $content )
		);
	}

	/**
	 * Test that both href replacement sites pass the href rendering context to the callback.
	 */
	public function testHrefReceivesHrefRenderingContext(): void {
		$captured = array();
		$this->tags_registry->register(
			new Personalization_Tag(
				'Store URL',
				'woocommerce/store-url',
				'Store',
				function ( $context, $args ) use ( &$captured ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					$captured[] = $context[ Personalizer::RENDERING_CONTEXT_KEY ];
					return 'https://example.com';
				}
			)
		);

		$html_content = '<a data-link-href="[woocommerce/store-url]" href="#">First</a><a href="http://[woocommerce/store-url]">Second</a>';
		$this->personalizer->personalize_content( $html_content );
		$this->assertSame( array( Personalizer::RENDERING_CONTEXT_HREF, Personalizer::RENDERING_CONTEXT_HREF ), $captured );
	}

	/**
	 * Test that a text value type tag is escaped in the html rendering context only.
	 */
	public function testTextValueTypeIsEscapedInHtmlContext(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'shop_name',
				'test/shop-name',
				'Test',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					return $context['shop_name'] ?? '';
				},
				array(),
				null,
				array(),
				Personalization_Tag::VALUE_TYPE_TEXT
			)
		);

		$this->personalizer->set_context( array( 'shop_name' => "Tom & Jerry's <shop>" ) );
		$content = 'Welcome to <!--[test/shop-name]-->';
		$this->assertSame(
			'Welcome to Tom &amp; Jerry&#039;s &lt;shop&gt;',
			$this->personalizer->personalize_content( $content, Personalizer::RENDERING_CONTEXT_HTML )
		);
		$this->assertSame(
			"Welcome to Tom & Jerry's <shop>",
			$this->personalizer->personalize_content( $content, Personalizer::RENDERING_CONTEXT_TEXT )
		);
	}

	/**
	 * Test that an already escaped value of a text value type tag is not double-encoded.
	 */
	public function testTextValueTypeIsNotDoubleEncoded(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'shop_name',
				'test/shop-name',
				'Test',
				function () {
					return 'Tom &amp; Jerry';
				},
				array(),
				null,
				array(),
				Personalization_Tag::VALUE_TYPE_TEXT
			)
		);

		$this->assertSame(
			'<p>Tom &amp; Jerry</p>',
			$this->personalizer->personalize_content( '<p><!--[test/shop-name]--></p>' )
		);
	}

	/**
	 * Test that an html value type tag (the default) is never touched by the Personalizer.
	 */
	public function testHtmlValueTypeIsNotEscaped(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'price',
				'test/price',
				'Test',
				function () {
					return '<span class="price">10 &euro;</span>';
				}
			)
		);

		$this->tags_registry->register(
			new Personalization_Tag(
				'tracking_pixel',
				'test/tracking-pixel',
				'Test',
				function () {
					return '<img src="https://track.example/open?id=42&amp;c=1" width="1" height="1" alt="" />';
				}
			)
		);

		$content = '<p><!--[test/price]--></p>';
		$this->assertSame(
			'<p><span class="price">10 &euro;</span></p>',
			$this->personalizer->personalize_content( $content, Personalizer::RENDERING_CONTEXT_HTML )
		);
		$this->assertSame(
			'<p><span class="price">10 &euro;</span></p>',
			$this->personalizer->personalize_content( $content, Personalizer::RENDERING_CONTEXT_TEXT )
		);
		// A tag emitting raw markup, such as a tracking pixel, is inserted verbatim.
		$this->assertSame(
			'<div><img src="https://track.example/open?id=42&amp;c=1" width="1" height="1" alt="" /></div>',
			$this->personalizer->personalize_content( '<div><!--[test/tracking-pixel]--></div>' )
		);
	}

	/**
	 * Test that the reserved rendering context key cannot be injected via set_context().
	 */
	public function testRenderingContextKeyIsReserved(): void {
		$this->tags_registry->register(
			new Personalization_Tag(
				'context_echo',
				'test/context',
				'Test',
				function ( $context, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- The $args parameter is not used in this test.
					return $context[ Personalizer::RENDERING_CONTEXT_KEY ] . '|' . ( $context[5] ?? 'missing' );
				}
			)
		);

		/**
		 * The reserved key is overwritten while the rest of the context — including the
		 * integer key, which is deliberately outside the documented contract — survives.
		 *
		 * @var array<string, mixed> $context
		 */
		$context = array(
			Personalizer::RENDERING_CONTEXT_KEY => 'bogus',
			5                                   => 'five',
		);
		$this->personalizer->set_context( $context );
		$this->assertSame( '<p>html|five</p>', $this->personalizer->personalize_content( '<p><!--[test/context]--></p>' ) );
	}

	/**
	 * Test parsing tokens with various formats.
	 */
	public function testParsingPersonalizationTagAttributes(): void {
		// Use reflection to access the private method.
		$reflection = new \ReflectionClass( $this->personalizer );
		$method     = $reflection->getMethod( 'parse_token' );
		$method->setAccessible( true );

		// Test case 1: Simple token without attributes.
		$result = $method->invoke( $this->personalizer, '[user/firstname]' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[user/firstname]', $result['token'] );
		$this->assertEmpty( $result['arguments'] );

		// Test case 2: Token with a single attribute.
		$result = $method->invoke( $this->personalizer, '[user/firstname default="Guest"]' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[user/firstname]', $result['token'] );
		$this->assertSame( array( 'default' => 'Guest' ), $result['arguments'] );

		// Test case 3: Token with multiple attributes.
		$result = $method->invoke( $this->personalizer, '[user/firstname default="Guest" fallback="Unknown" max_length="10"]' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[user/firstname]', $result['token'] );
		$this->assertSame(
			array(
				'default'    => 'Guest',
				'fallback'   => 'Unknown',
				'max_length' => '10',
			),
			$result['arguments']
		);

		// Test case 4: Token with spaces and different quote types.
		$result = $method->invoke( $this->personalizer, '[user/firstname  default="Guest"  fallback=\'Unknown\' ]' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[user/firstname]', $result['token'] );
		$this->assertSame(
			array(
				'default'  => 'Guest',
				'fallback' => 'Unknown',
			),
			$result['arguments']
		);

		// Test case 5: Token with empty attribute value.
		$result = $method->invoke( $this->personalizer, '[user/firstname  default=""]' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[user/firstname]', $result['token'] );
		$this->assertSame(
			array(
				'default' => '',
			),
			$result['arguments']
		);

		// Test case 6: Token with unquoted values (as produced by esc_url stripping quotes).
		$result = $method->invoke( $this->personalizer, '[trackable-link url=wordpress.com desc=home-page]' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[trackable-link]', $result['token'] );
		$this->assertSame(
			array(
				'url'  => 'wordpress.com',
				'desc' => 'home-page',
			),
			$result['arguments']
		);

		// Test case 7: Token with unquoted values containing spaces (last argument).
		$result = $method->invoke( $this->personalizer, '[trackable-link url=uf desc=desc 123 asd]' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[trackable-link]', $result['token'] );
		$this->assertSame(
			array(
				'url'  => 'uf',
				'desc' => 'desc 123 asd',
			),
			$result['arguments']
		);

		// Test case 8: Token with unquoted values containing spaces (first argument, followed by another).
		$result = $method->invoke( $this->personalizer, '[trackable-link desc=desc 123 asd url=example.com]' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[trackable-link]', $result['token'] );
		$this->assertSame(
			array(
				'desc' => 'desc 123 asd',
				'url'  => 'example.com',
			),
			$result['arguments']
		);

		// Test case 9: Token with three unquoted arguments, middle one with spaces.
		$result = $method->invoke( $this->personalizer, '[trackable-link first=one middle=has some spaces last=three]' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[trackable-link]', $result['token'] );
		$this->assertSame(
			array(
				'first'  => 'one',
				'middle' => 'has some spaces',
				'last'   => 'three',
			),
			$result['arguments']
		);

		// Test case 10: Token with embedded single quote in double-quoted value.
		$result = $method->invoke( $this->personalizer, '[user/greeting title="What\'s up"]' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[user/greeting]', $result['token'] );
		$this->assertSame(
			array(
				'title' => "What's up",
			),
			$result['arguments']
		);

		// Test case 11: Token with embedded double quote in single-quoted value.
		$result = $method->invoke( $this->personalizer, "[user/greeting title='Say \"hello\"']" );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '[user/greeting]', $result['token'] );
		$this->assertSame(
			array(
				'title' => 'Say "hello"',
			),
			$result['arguments']
		);

		// Test case 12: Invalid token format.
		$result = $method->invoke( $this->personalizer, 'invalid-token' );
		/**
		 * Typehint needed by PHPStan.
		 *
		 * @var array{token: string, arguments: array<string, string>} $result
		 */
		$this->assertSame( '', $result['token'] );
		$this->assertEmpty( $result['arguments'] );
	}
}
