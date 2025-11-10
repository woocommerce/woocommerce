/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEntityBlockEditor, store as coreStore } from '@wordpress/core-data';
import {
	InnerBlocks,
	useInnerBlocksProps,
	useBlockProps,
} from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { Skeleton } from './skeleton';

const TemplatePartInnerBlocks = ( {
	blockProps,
	productType,
	templatePartId,
}: {
	blockProps: Record< string, unknown >;
	productType: string;
	templatePartId: string | undefined;
} ) => {
	const [ blocks, onInput, onChange ] = useEntityBlockEditor(
		'postType',
		'wp_template_part',
		{ id: templatePartId }
	);

	const { isLoading } = useSelect(
		( select ) => {
			const { hasFinishedResolution } = select( coreStore );

			const hasResolvedEntity = hasFinishedResolution(
				'getEditedEntityRecord',
				[ 'postType', 'wp_template_part', templatePartId ]
			);

			return {
				isLoading: ! hasResolvedEntity,
			};
		},
		[ templatePartId ]
	);

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		value: blocks,
		onInput,
		onChange,
		renderAppender:
			! isLoading && blocks.length === 0
				? InnerBlocks.ButtonBlockAppender
				: null,
	} );

	if ( isLoading ) {
		return (
			<div { ...blockProps }>
				<Skeleton productType={ productType } isLoading={ true } />
			</div>
		);
	}

	return <div { ...innerBlocksProps } />;
};

export const AddToCartWithOptionsEditTemplatePart = ( {
	productType,
}: {
	productType: string;
} ) => {
	const addToCartWithOptionsTemplatePartIds = getSetting(
		'addToCartWithOptionsTemplatePartIds',
		{}
	) as Record< string, string | null >;

	const templatePartId = addToCartWithOptionsTemplatePartIds?.[ productType ];

	const blockProps = useBlockProps();

	const { canEditTemplatePart, isLoading } = useSelect(
		( select ) => {
			if ( ! templatePartId ) {
				return {
					canEditTemplatePart: false,
					isLoading: false,
				};
			}

			const { canUser, hasFinishedResolution } = select( coreStore );

			const canUserUpdate = canUser( 'update', {
				kind: 'postType',
				name: 'wp_template_part',
				id: templatePartId,
			} );

			const isLoadingCanUserUpdate = ! hasFinishedResolution( 'canUser', [
				'update',
				{
					kind: 'postType',
					name: 'wp_template_part',
					id: templatePartId,
				},
			] );

			return {
				canEditTemplatePart: canUserUpdate,
				isLoading: isLoadingCanUserUpdate,
			};
		},
		[ templatePartId ]
	);

	if ( ! templatePartId || ! canEditTemplatePart ) {
		return (
			<div { ...blockProps }>
				<Skeleton productType={ productType } isLoading={ isLoading } />
			</div>
		);
	}

	return (
		<TemplatePartInnerBlocks
			blockProps={ blockProps }
			productType={ productType }
			templatePartId={ templatePartId }
		/>
	);
};
