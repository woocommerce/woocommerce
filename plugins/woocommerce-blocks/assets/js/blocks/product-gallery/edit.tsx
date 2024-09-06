/**
 * External dependencies
 */
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import ErrorPlaceholder, {
	ErrorObject,
} from '@woocommerce/editor-components/error-placeholder';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	moveInnerBlocksToPosition,
	getInnerBlocksLockAttributes,
	getClassNameByNextPreviousButtonsPosition,
} from './utils';
import { ProductGalleryBlockSettings } from './block-settings/index';
import type { ProductGalleryAttributes } from './types';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/group',
		{
			layout: {
				type: 'flex',
				flexWrap: 'nowrap',
				verticalAlignment: 'top',
			},
			metadata: {
				name: 'Gallery Area',
			},
		},
		[
			[
				'woocommerce/product-gallery-thumbnails',
				getInnerBlocksLockAttributes( 'lock' ),
			],
			[
				'core/group',
				{
					layout: {
						type: 'flex',
						orientation: 'vertical',
						justifyContent: 'center',
						verticalAlignment: 'top',
					},
					style: {
						layout: { selfStretch: 'fixed', flexSize: '100%' },
					},
					metadata: {
						name: 'Large Image and Navigation',
					},
					...getInnerBlocksLockAttributes( 'lock' ),
				},
				[
					[
						'woocommerce/product-gallery-large-image',
						getInnerBlocksLockAttributes( 'lock' ),
						[
							[
								'woocommerce/product-sale-badge',
								{
									align: 'right',
									style: {
										spacing: {
											margin: {
												top: '4px',
												right: '4px',
												bottom: '4px',
												left: '4px',
											},
										},
									},
									lock: { move: true },
								},
							],
							[
								'woocommerce/product-gallery-large-image-next-previous',
								{
									layout: {
										type: 'flex',
										verticalAlignment: 'bottom',
									},
									lock: { move: true, remove: true },
								},
							],
						],
					],
					[
						'woocommerce/product-gallery-pager',
						{ lock: { move: true, remove: true } },
					],
				],
			],
		],
	],
];

const getMode = ( currentTemplateId: string, templateType: string ) => {
	if (
		templateType === 'wp_template_part' &&
		currentTemplateId.includes( 'product-gallery' )
	) {
		return 'full';
	}
	return 'standard';
};

export const Edit = ( {
	clientId,
	attributes,
	setAttributes,
}: BlockEditProps< ProductGalleryAttributes > ) => {
	const blockProps = useBlockProps( {
		className: getClassNameByNextPreviousButtonsPosition(
			attributes.nextPreviousButtonsPosition
		),
	} );

	const { currentTemplateId, templateType } = useSelect(
		( select ) => ( {
			currentTemplateId: select( 'core/edit-site' ).getEditedPostId(),
			templateType: select( 'core/edit-site' ).getEditedPostType(),
		} ),
		[]
	);

	useEffect( () => {
		const mode = getMode( currentTemplateId, templateType );
		const newProductGalleryClientId =
			attributes.productGalleryClientId || clientId;

		setAttributes( {
			...attributes,
			mode,
			productGalleryClientId: newProductGalleryClientId,
		} );
		// Move the Thumbnails block to the correct above or below the Large Image based on the thumbnailsPosition attribute.
		moveInnerBlocksToPosition( attributes, newProductGalleryClientId );
	}, [
		setAttributes,
		attributes,
		clientId,
		currentTemplateId,
		templateType,
	] );

	if ( attributes.productGalleryClientId !== clientId ) {
		const error = {
			message: __(
				'productGalleryClientId and clientId codes mismatch.',
				'woocommerce'
			),
			type: 'general',
		} as ErrorObject;

		return <ErrorPlaceholder error={ error } isLoading={ false } />;
	}

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<ProductGalleryBlockSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
					context={ {
						productGalleryClientId: clientId,
						pagerDisplayMode: attributes.pagerDisplayMode,
						thumbnailsPosition: attributes.thumbnailsPosition,
						thumbnailsNumberOfThumbnails:
							attributes.thumbnailsNumberOfThumbnails,
						nextPreviousButtonsPosition:
							attributes.nextPreviousButtonsPosition,
					} }
				/>
			</InspectorControls>
			<InnerBlocks
				allowedBlocks={ [
					'woocommerce/product-gallery-large-image',
					'woocommerce/product-gallery-thumbnails',
				] }
				templateLock={ false }
				template={ TEMPLATE }
			/>
		</div>
	);
};

export const Save = () => {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
