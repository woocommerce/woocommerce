<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine;

use Automattic\WooCommerce\EmailEditor\Validator\Builder;

/**
 * Class for email styles schema.
 */
class Email_Styles_Schema {
	/**
	 * Returns the schema for email styles.
	 *
	 * @return array
	 */
	public function get_schema(): array {
		$typography_props = Builder::object(
			array(
				'fontFamily'     => Builder::string()->nullable(),
				'fontSize'       => Builder::string()->nullable(),
				'fontStyle'      => Builder::string()->nullable(),
				'fontWeight'     => Builder::string()->nullable(),
				'letterSpacing'  => Builder::string()->nullable(),
				'lineHeight'     => Builder::string()->nullable(),
				'textTransform'  => Builder::string()->nullable(),
				'textDecoration' => Builder::string()->nullable(),
			)
		)->nullable();
		return Builder::object(
			array(
				'version' => Builder::integer(),
				'styles'  => Builder::object(
					array(
						'spacing'    => Builder::object(
							array(
								'padding'  => Builder::object(
									array(
										'top'    => Builder::string(),
										'right'  => Builder::string(),
										'bottom' => Builder::string(),
										'left'   => Builder::string(),
									)
								)->nullable(),
								'blockGap' => Builder::string()->nullable(),
							)
						)->nullable(),
						'color'      => Builder::object(
							array(
								'background' => Builder::string()->nullable(),
								'text'       => Builder::string()->nullable(),
							)
						)->nullable(),
						'typography' => $typography_props,
						'elements'   => Builder::object(
							array(
								'heading' => Builder::object(
									array(
										'typography' => $typography_props,
									)
								)->nullable(),
								'button'  => Builder::object(
									array(
										'typography' => $typography_props,
									)
								)->nullable(),
								'link'    => Builder::object(
									array(
										'typography' => $typography_props,
									)
								)->nullable(),
								'h1'      => Builder::object(
									array(
										'typography' => $typography_props,
									)
								)->nullable(),
								'h2'      => Builder::object(
									array(
										'typography' => $typography_props,
									)
								)->nullable(),
								'h3'      => Builder::object(
									array(
										'typography' => $typography_props,
									)
								)->nullable(),
								'h4'      => Builder::object(
									array(
										'typography' => $typography_props,
									)
								)->nullable(),
								'h5'      => Builder::object(
									array(
										'typography' => $typography_props,
									)
								)->nullable(),
								'h6'      => Builder::object(
									array(
										'typography' => $typography_props,
									)
								)->nullable(),
							)
						)->nullable(),
					)
				)->nullable(),
			)
		)->to_array();
	}
}
