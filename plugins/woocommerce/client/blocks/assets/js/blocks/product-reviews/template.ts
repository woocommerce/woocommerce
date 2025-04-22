/**
 * External dependencies
 */
import { InnerBlockTemplate } from '@wordpress/blocks';

const TEMPLATE: InnerBlockTemplate[] = [
	[ 'woocommerce/product-reviews-title' ],
	[
		'core/comment-template',
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
										'core/comment-author-name',
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
										'core/comment-date',
										{
											fontSize: 'small',
										},
									],
									[
										'core/comment-edit-link',
										{
											fontSize: 'small',
										},
									],
								],
							],
							[ 'core/comment-content' ],
						],
					],
				],
			],
		],
	],
	[ 'core/comments-pagination' ],
	[ 'woocommerce/product-review-form' ],
];

export default TEMPLATE;
