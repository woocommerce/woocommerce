/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const Edit = () => {
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: [ 'core/buttons', 'core/button' ],
		template: [
			[
				'core/buttons',
				{
					layout: {
						type: 'flex',
						verticalAlignment: 'stretched',
					},
				},
				[
					[
						'core/button',
						{
							text: __( 'Clear filters', 'woocommerce' ),
							className:
								'wc-block-product-filter-clear-button is-style-outline',
							style: {
								border: {
									width: '1px',
								},
								typography: {
									textDecoration: 'none',
								},
								outline: 'none',
								fontSize: 'medium',
								spacing: {
									padding: {
										left: '8px',
										right: '8px',
										top: '5px',
										bottom: '5px',
									},
								},
							},
							textAlign: 'center',
							width: 100,
						},
					],
				],
			],
		],
	} );

	return <div { ...innerBlocksProps } />;
};

export default Edit;
