/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEntityBlockEditor, store as coreStore } from '@wordpress/core-data';
import {
	InnerBlocks,
	useInnerBlocksProps,
	useBlockProps,
} from '@wordpress/block-editor';
import { Icon, starEmpty } from '@wordpress/icons';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { Skeleton } from './skeleton';

/**
 * Non-editable preview of the Add to Wishlist Button block, shown as the last
 * child of the template part when the merchant enables the toggle. The button
 * isn't a real inner block here (the template part is loaded separately and we
 * don't want to persist it into the shared template part), so this just mirrors
 * the block's editor markup to show where it'll appear on the frontend.
 */
const AddToWishlistPreview = () => (
	<div className="wc-block-add-to-wishlist-button">
		<button
			type="button"
			className="wc-block-add-to-wishlist-button__toggle"
			disabled
		>
			<span className="wc-block-add-to-wishlist-button__icon wc-block-add-to-wishlist-button__icon--empty">
				<Icon icon={ starEmpty } size={ 24 } />
			</span>
			<span className="wc-block-add-to-wishlist-button__label">
				{ __( 'Add to wishlist', 'woocommerce' ) }
			</span>
		</button>
	</div>
);

const TemplatePartInnerBlocks = ( {
	blockProps,
	productType,
	templatePartId,
	showAddToWishlist,
}: {
	blockProps: Record< string, unknown >;
	productType: string;
	templatePartId: string | undefined;
	showAddToWishlist: boolean;
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

	const { children, ...innerBlocksWrapperProps } = innerBlocksProps;

	return (
		<div { ...innerBlocksWrapperProps }>
			{ children }
			{ showAddToWishlist && <AddToWishlistPreview /> }
		</div>
	);
};

export const AddToCartWithOptionsEditTemplatePart = ( {
	productType,
	showAddToWishlist,
}: {
	productType: string;
	showAddToWishlist: boolean;
} ) => {
	const addToCartWithOptionsTemplatePartIds = getSetting(
		'addToCartWithOptionsTemplatePartIds',
		{}
	) as Record< string, string | null >;

	const templatePartId = addToCartWithOptionsTemplatePartIds?.[ productType ];

	const blockProps = useBlockProps( {
		className: 'wc-block-add-to-cart-with-options',
	} );

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
				{ showAddToWishlist && <AddToWishlistPreview /> }
			</div>
		);
	}

	return (
		<TemplatePartInnerBlocks
			blockProps={ blockProps }
			productType={ productType }
			templatePartId={ templatePartId }
			showAddToWishlist={ showAddToWishlist }
		/>
	);
};
