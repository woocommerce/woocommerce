/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

const Edit = () => {
	return (
		<InnerBlocks
			template={ [
				[
					'core/buttons',
					{ layout: { type: 'flex' } },
					[
						[
							'core/button',
							{
								text: __( 'Clear', 'woocommerce' ),
								className: [
									'wc-block-product-filter-clear-button',
									'is-style-outline',
								],
								style: {
									border: {
										width: '0px',
										style: 'none',
									},
									typography: {
										textDecoration: 'underline',
									},
									outline: 'none',
									fontSize: 'medium',
								},
							},
						],
					],
				],
			] }
			templateLock="insert"
		/>
	);
};

export default Edit;
