/**
 * External dependencies
 */
import { InnerBlockTemplate } from '@wordpress/blocks';

const TEMPLATE: InnerBlockTemplate[] = [
	[ 'woocommerce/product-reviews-title' ],
	[
		'woocommerce/product-review-template',
		{},
		[
			[
				'core/columns',
				{},
				[
					[
						'core/column',
						{ width: '40px' },
						[
							[
								'core/avatar',
								{
									size: 40,
									style: {
										border: { radius: '20px' },
									},
								},
							],
						],
					],
					[
						'core/column',
						{},
						[
							[
								'core/group',
								{
									tagName: 'div',
									layout: {
										type: 'flex',
										flexWrap: 'nowrap',
										justifyContent: 'space-between',
									},
								},
								[
									[
										'woocommerce/product-review-author-name',
										{
											fontSize: 'small',
										},
									],
									[ 'woocommerce/product-review-rating' ],
								],
							],
							[
								'core/group',
								{
									layout: { type: 'flex' },
									style: {
										spacing: {
											margin: {
												top: '0px',
												bottom: '0px',
											},
										},
									},
								},
								[
									[
										'woocommerce/product-review-date',
										{
											fontSize: 'small',
										},
									],
								],
							],
							[ 'woocommerce/product-review-content' ],
						],
					],
				],
			],
		],
	],
	[ 'woocommerce/product-reviews-pagination' ],
	[ 'woocommerce/product-review-form' ],
];

export default TEMPLATE;
