<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * The AI shopping channels whose public product data specs the readiness
 * checks test. Google's spec feeds Shopping, AI Mode and Gemini; Microsoft's
 * feeds Copilot.
 *
 * @since 11.4.0
 */
final class FeedChannels {

	public const CHATGPT = 'chatgpt';
	public const GOOGLE  = 'google';
	public const COPILOT = 'copilot';

	/**
	 * Channels by id, in display order.
	 *
	 * @return array<string, array{name:string,spec:string,url:string}>
	 */
	public static function all(): array {
		return array(
			self::CHATGPT => array(
				'name' => 'ChatGPT',
				'spec' => 'OpenAI product feed spec',
				'url'  => 'https://developers.openai.com/commerce/specs/feed',
			),
			self::GOOGLE  => array(
				'name' => 'Google',
				'spec' => 'Google Merchant Center product data spec',
				'url'  => 'https://support.google.com/merchants/answer/7052112',
			),
			self::COPILOT => array(
				'name' => 'Microsoft Copilot',
				'spec' => 'Microsoft Merchant Center product data',
				'url'  => 'https://learn.microsoft.com/en-us/advertising/shopping-content/products-resource',
			),
		);
	}
}
