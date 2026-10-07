<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Tests\Integration\Integrations\Utils;

use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Html_Processing_Helper;

/**
 * Integration test for Html_Processing_Helper.
 *
 * Caption sanitization is only meaningful against the real WP_HTML_Tag_Processor
 * and wp_kses(), so it is covered here rather than in the unit suite, which stubs
 * the HTML API.
 */
class Html_Processing_Helper_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Dangerous caption markup must be neutralized against the real HTML API.
	 *
	 * @dataProvider caption_xss_provider
	 * @param string $input        Raw caption HTML.
	 * @param string $must_not_contain Substring that must not survive.
	 */
	public function test_sanitize_caption_html_strips_dangerous_markup( string $input, string $must_not_contain ): void {
		$result = Html_Processing_Helper::sanitize_caption_html( $input );
		$this->assertStringNotContainsStringIgnoringCase( $must_not_contain, $result );
	}

	/**
	 * Data provider for dangerous caption markup.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function caption_xss_provider(): array {
		return array(
			'javascript href'       => array( '<a href="javascript:alert(1)">x</a>', 'javascript:' ),
			'event handler on link' => array( '<a href="https://x.test" onmouseover="alert(1)">x</a>', 'onmouseover' ),
			'event handler on span' => array( '<span onclick="alert(1)">x</span>', 'onclick' ),
			'void img with onerror' => array( '<img src=x onerror=alert(1)>', 'onerror' ),
			'void svg with onload'  => array( '<svg onload=alert(1)>', 'onload' ),
			'script element'        => array( '<script>alert(1)</script>caption', 'alert' ),
			// Custom-element names that start with an allowed tag name must not
			// slip through the tag-strip pass (e.g. "a-b" masquerading as "a").
			'hyphenated custom tag' => array( '<a-b onmouseover="alert(1)">x</a-b>', 'onmouseover' ),
			'colon custom tag'      => array( '<a:b onmouseover="alert(1)">x</a:b>', 'onmouseover' ),
			'span-prefixed tag'     => array( '<span-x onclick="alert(1)">x</span-x>', 'onclick' ),
		);
	}

	/**
	 * A tag smuggled in through a ">" in an attribute value must not keep its
	 * attributes.
	 *
	 * The ">" in "<img alt="a><em onclick=alert(1)>x">" belongs to the alt value,
	 * but the tag span is still cut there, so an <em> falls out of the split. What
	 * has to hold is that it is rebuilt from the allow-list: the onclick is gone,
	 * the href below is gone, and the leftover text is escaped rather than markup.
	 */
	public function test_sanitize_caption_html_neutralizes_tag_smuggling_via_attribute_values(): void {
		$this->assertSame(
			'<em>x"&gt;',
			Html_Processing_Helper::sanitize_caption_html( '<img alt="a><em onclick=alert(1)>x">' )
		);

		$smuggled_link = Html_Processing_Helper::sanitize_caption_html( '<img alt="a><a href="javascript:alert(1)">click</a>">' );
		$this->assertStringNotContainsString( 'href', $smuggled_link );
		$this->assertStringContainsString( 'click', $smuggled_link );
	}

	/**
	 * A tag without a closing ">" must not survive as markup.
	 *
	 * Callers wrap the sanitized caption in markup (Embed::render_caption appends
	 * "</div>"), and a browser consumes that wrapper as part of any tag the caption
	 * leaves open, so the caption has to be complete markup on its own.
	 *
	 * Which way an unterminated tag goes depends on what is attached to the
	 * pre_kses hook — core escapes it to text, and without that callback kses drops
	 * the tag or supplies the missing ">". The assertions cover what holds either
	 * way, so they do not turn red on a caller that filters differently.
	 */
	public function test_sanitize_caption_html_neutralizes_unterminated_tags(): void {
		$unterminated_img = Html_Processing_Helper::sanitize_caption_html( 'caption<img src=x onerror="alert(1)"' );
		$this->assertStringContainsString( 'caption', $unterminated_img );
		$this->assertStringNotContainsString( '<img', $unterminated_img );
		$this->assertNoEventHandlers( $unterminated_img );

		// An unterminated tag that is on the list may be rebuilt rather than
		// escaped, but never keeps an attribute the allow-list rejects.
		$unterminated_link = Html_Processing_Helper::sanitize_caption_html( 'caption<a href="https://x.test" onmouseover="alert(1)"' );
		$this->assertStringContainsString( 'caption', $unterminated_link );
		$this->assertNoEventHandlers( $unterminated_link );
	}

	/**
	 * Assert that no tag in the given HTML carries an event-handler attribute.
	 *
	 * Checks the parsed attributes rather than the serialized string, so a handler
	 * that only survives as escaped text does not count as a failure.
	 *
	 * @param string $html Sanitized HTML to inspect.
	 */
	private function assertNoEventHandlers( string $html ): void {
		$processor = new \WP_HTML_Tag_Processor( $html );
		while ( $processor->next_tag() ) {
			$handlers = $processor->get_attribute_names_with_prefix( 'on' );
			$this->assertSame(
				array(),
				is_array( $handlers ) ? $handlers : array(),
				sprintf( 'Event handler survived on <%s> in: %s', strtolower( (string) $processor->get_tag() ), $html )
			);
		}
	}

	/**
	 * A caption of never-closed executable tags must stay cheap to sanitize.
	 *
	 * The executable-element pass matches lazily, so with the closing tag left to a
	 * backreference every unclosed opening tag costs a scan to the end of the
	 * caption, and a few hundred kilobytes take seconds. This caption is also large
	 * enough to exhaust PCRE's backtrack limit, which must leave the author's text
	 * alone rather than discard the caption.
	 */
	public function test_sanitize_caption_html_handles_many_unclosed_executable_tags(): void {
		$caption = str_repeat( '<script>', 131072 ) . 'My <strong>caption</strong> text';

		$started = microtime( true );
		$result  = Html_Processing_Helper::sanitize_caption_html( $caption );
		$elapsed = microtime( true ) - $started;

		$this->assertSame( 'My <strong>caption</strong> text', $result );
		$this->assertLessThan( 5, $elapsed, 'Sanitizing a caption of unclosed tags should not take seconds.' );
	}

	/**
	 * Safe caption markup must be preserved.
	 */
	public function test_sanitize_caption_html_preserves_safe_markup(): void {
		$this->assertSame(
			'My <strong>video</strong> caption',
			Html_Processing_Helper::sanitize_caption_html( 'My <strong>video</strong> caption' )
		);

		$this->assertSame(
			'This is plain text',
			Html_Processing_Helper::sanitize_caption_html( 'This is plain text' )
		);

		$this->assertSame( '', Html_Processing_Helper::sanitize_caption_html( '' ) );

		$nested = '<strong><em><a href="https://example.com">Nested <mark>highlighted</mark> link</a></em></strong>';
		$this->assertSame( $nested, Html_Processing_Helper::sanitize_caption_html( $nested ) );

		// A valid link is preserved; target="_blank" gains rel="noopener noreferrer".
		$linked = Html_Processing_Helper::sanitize_caption_html( '<a href="https://example.com" target="_blank">link</a>' );
		$this->assertStringContainsString( 'href="https://example.com"', $linked );
		$this->assertStringContainsString( 'target="_blank"', $linked );
		$this->assertStringContainsString( 'rel="noopener noreferrer"', $linked );
	}

	/**
	 * Disallowed elements are removed but their text content is kept, except for
	 * executable elements, which are dropped together with their content.
	 */
	public function test_sanitize_caption_html_unwraps_disallowed_elements(): void {
		$this->assertSame(
			'Not allowed<strong>Bold</strong><em>italic</em>',
			Html_Processing_Helper::sanitize_caption_html( '<div>Not allowed</div><strong>Bold</strong><script>alert("xss")</script><em>italic</em>' )
		);

		// Void and self-closing elements are covered too; "<br/>" is normalized.
		$this->assertSame(
			'<br /><strong>Bold</strong><em>italic</em>',
			Html_Processing_Helper::sanitize_caption_html( '<br/><strong>Bold</strong><hr/><em>italic</em>' )
		);
	}

	/**
	 * Attribute values on allowed elements are narrowed to the caption rules.
	 */
	public function test_sanitize_caption_html_narrows_attribute_values(): void {
		// Only the safe CSS properties survive.
		$this->assertSame(
			'<span style="font-size: 14px">x</span>',
			Html_Processing_Helper::sanitize_caption_html( '<span style="font-size: 14px; position: absolute">x</span>' )
		);

		// data-* values must be a single token; "a b" is rejected.
		$attributed = Html_Processing_Helper::sanitize_caption_html( '<span class="ok" data-id="5" data-bad="a b">x</span>' );
		$this->assertStringContainsString( 'class="ok"', $attributed );
		$this->assertStringContainsString( 'data-id="5"', $attributed );
		$this->assertStringNotContainsString( 'data-bad', $attributed );
	}

	/**
	 * Background and border classes are dropped, everything else on the element is kept.
	 *
	 * @dataProvider wrapper_handled_class_provider
	 * @param string        $class_attribute Class attribute to clean.
	 * @param array<string> $expected        Class names that must remain, in order.
	 */
	public function test_remove_wrapper_handled_classes( string $class_attribute, array $expected ): void {
		$html = new \WP_HTML_Tag_Processor( '<h3 class="' . $class_attribute . '">Heading</h3>' );
		$this->assertTrue( $html->next_tag() );

		Html_Processing_Helper::remove_wrapper_handled_classes( $html );

		// Read back through the tag processor so the assertion sees the rewritten attribute.
		$class_names = preg_split( '/\s+/', trim( (string) $html->get_attribute( 'class' ) ) );
		$remaining   = array_values(
			array_filter(
				is_array( $class_names ) ? $class_names : array(),
				function ( $class_name ) {
					return '' !== $class_name;
				}
			)
		);

		$this->assertSame( $expected, $remaining );
	}

	/**
	 * Class attributes that are not a plain space-separated list must be handled without warnings.
	 *
	 * The helper reads the attribute rather than the parsed class list, so it has to cope with the
	 * shapes WP_HTML_Tag_Processor can hand back: null when the attribute is absent, and boolean
	 * true for a valueless attribute. Neither is a string, and both would otherwise reach trim().
	 */
	public function test_remove_wrapper_handled_classes_handles_unusual_class_attributes(): void {
		// No class attribute at all: nothing to do, and the tag is left exactly as it was.
		$html = new \WP_HTML_Tag_Processor( '<h3>Heading</h3>' );
		$this->assertTrue( $html->next_tag() );
		Html_Processing_Helper::remove_wrapper_handled_classes( $html );
		$this->assertSame( '<h3>Heading</h3>', $html->get_updated_html() );

		// Valueless attribute: get_attribute() returns boolean true, not a string.
		$html = new \WP_HTML_Tag_Processor( '<h3 class>Heading</h3>' );
		$this->assertTrue( $html->next_tag() );
		Html_Processing_Helper::remove_wrapper_handled_classes( $html );
		$this->assertSame( '<h3 class>Heading</h3>', $html->get_updated_html() );

		// Empty value: no class names to walk.
		$html = new \WP_HTML_Tag_Processor( '<h3 class="">Heading</h3>' );
		$this->assertTrue( $html->next_tag() );
		Html_Processing_Helper::remove_wrapper_handled_classes( $html );
		$this->assertStringContainsString( 'Heading', $html->get_updated_html() );

		// Class names may be separated by any whitespace, not just single spaces.
		$html = new \WP_HTML_Tag_Processor( "<h3 class=\"wp-block-heading\n\thas-background  has-tertiary-background-color\">Heading</h3>" );
		$this->assertTrue( $html->next_tag() );
		Html_Processing_Helper::remove_wrapper_handled_classes( $html );
		$remaining = (string) $html->get_attribute( 'class' );
		$this->assertStringContainsString( 'wp-block-heading', $remaining );
		$this->assertStringNotContainsString( 'has-background', $remaining );
		$this->assertStringNotContainsString( 'has-tertiary-background-color', $remaining );
	}

	/**
	 * Data provider for wrapper-handled class removal.
	 *
	 * @return array<string, array{string, array<string>}>
	 */
	public function wrapper_handled_class_provider(): array {
		return array(
			// The reported bug: the preset background class survived on the inner element because it
			// does not contain the literal "has-background", so the translucent color painted twice.
			'preset background color'     => array(
				'wp-block-heading has-tertiary-background-color has-background',
				array( 'wp-block-heading' ),
			),
			'generic background only'     => array(
				'wp-block-heading has-background',
				array( 'wp-block-heading' ),
			),
			'multi word slug'             => array(
				'has-vivid-red-background-color has-background',
				array(),
			),
			'text color is kept'          => array(
				'has-pale-cyan-blue-color has-text-color has-vivid-red-background-color has-background',
				array( 'has-pale-cyan-blue-color', 'has-text-color' ),
			),
			'alignment class is kept'     => array(
				'has-text-align-center has-tertiary-background-color',
				array( 'has-text-align-center' ),
			),
			'border classes are dropped'  => array(
				'wp-block-heading has-border-color has-accent-border-color',
				array( 'wp-block-heading' ),
			),
			// A whole class name is removed rather than a substring of one, so a class that merely
			// starts with "has-background" is left intact instead of being reduced to a fragment.
			'unrelated background prefix' => array(
				'has-background-dim wp-block-cover',
				array( 'has-background-dim', 'wp-block-cover' ),
			),
			'nothing to remove'           => array(
				'wp-block-heading has-large-font-size',
				array( 'wp-block-heading', 'has-large-font-size' ),
			),
			// Matching is case-sensitive, mirroring how the CSS inliner matches these classes
			// outside quirks mode. WordPress only ever emits them lowercase.
			'uppercase is not matched'    => array(
				'HAS-BACKGROUND wp-block-heading',
				array( 'HAS-BACKGROUND', 'wp-block-heading' ),
			),
		);
	}
}
