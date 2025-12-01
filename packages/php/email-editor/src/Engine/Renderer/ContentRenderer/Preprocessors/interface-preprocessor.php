<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors;

/**
 * Interface Preprocessor
 */
interface Preprocessor {
	/**
	 * Method to preprocess the content before rendering
	 *
	 * @param array                                                                                                             $parsed_blocks Parsed blocks of the email.
	 * @param array{contentSize: string, wideSize?: string, allowEditing?: bool, allowCustomContentAndWideSize?: bool}          $layout Layout of the email.
	 * @param array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles Styles of the email.
	 * @return array
	 */
	public function preprocess( array $parsed_blocks, array $layout, array $styles ): array;
}
