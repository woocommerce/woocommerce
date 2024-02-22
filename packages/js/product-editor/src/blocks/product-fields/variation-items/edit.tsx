/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	PartialProductVariation,
	Product,
	useUserPreferences,
} from '@woocommerce/data';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { recordEvent } from '@woocommerce/tracks';
import { createElement, useMemo, useRef } from '@wordpress/element';
import { resolveSelect, useDispatch, useSelect } from '@wordpress/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId, useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { VariationsTable } from '../../../components/variations-table';
import { useValidation } from '../../../contexts/validation-context';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { VariationOptionsBlockAttributes } from './types';
import { VariableProductTour } from './variable-product-tour';
import { TRACKS_SOURCE } from '../../../constants';
import { handlePrompt } from '../../../utils/handle-prompt';
import { ProductEditorBlockEditProps } from '../../../types';
import { EmptyState } from './empty-state';

export function Edit( {
	attributes,
	context,
}: ProductEditorBlockEditProps< VariationOptionsBlockAttributes > & {
	context: {
		isInSelectedTab?: boolean;
	};
} ) {
	const noticeDimissed = useRef( false );
	const { invalidateResolution } = useDispatch(
		EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
	);
	const productId = useEntityId( 'postType', 'product' );
	const blockProps = useWooBlockProps( attributes );
	const [ productStatus ] = useEntityProp< string >(
		'postType',
		'product',
		'status'
	);
	const [ productAttributes ] =
		useProductEntityProp< Product[ 'attributes' ] >( 'attributes' );

	const hasVariationOptions = useMemo(
		function hasAttributesUsedForVariations() {
			return productAttributes?.some(
				( productAttribute ) => productAttribute.variation
			);
		},
		[ productAttributes ]
	);

	const totalCountWithoutPriceRequestParams = useMemo(
		() => ( {
			product_id: productId,
			order: 'asc',
			orderby: 'menu_order',
			has_price: false,
		} ),
		[ productId ]
	);

	const { totalCountWithoutPrice } = useSelect(
		( select ) => {
			const { getProductVariationsTotalCount } = select(
				EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
			);

			return {
				totalCountWithoutPrice:
					getProductVariationsTotalCount< number >(
						totalCountWithoutPriceRequestParams
					),
			};
		},
		[ totalCountWithoutPriceRequestParams ]
	);

	const {
		updateUserPreferences,
		variable_items_without_price_notice_dismissed:
			itemsWithoutPriceNoticeDismissed,
	} = useUserPreferences();

	const { ref: variationTableRef } = useValidation< Product >(
		`variations`,
		async function regularPriceValidator( defaultValue, newData ) {
			/**
			 * We cause a validation error if there is:
			 * - more then one variation without a price.
			 * - the notice hasn't been dismissed.
			 * - The product hasn't already been published.
			 * - We are publishing the product.
			 */
			if (
				totalCountWithoutPrice > 0 &&
				! noticeDimissed.current &&
				productStatus !== 'publish' &&
				// New status.
				newData?.status === 'publish'
			) {
				if ( itemsWithoutPriceNoticeDismissed !== 'yes' ) {
					updateUserPreferences( {
						variable_items_without_price_notice_dismissed: {
							...( itemsWithoutPriceNoticeDismissed || {} ),
							[ productId ]: 'no',
						},
					} );
				}
				return __(
					'Set variation prices before adding this product.',
					'woocommerce'
				);
			}
		},
		[ totalCountWithoutPrice ]
	);

	function onSetPrices(
		handleUpdateAll: ( update: PartialProductVariation[] ) => void
	) {
		recordEvent( 'product_variations_set_prices_select', {
			source: TRACKS_SOURCE,
		} );
		const productVariationsListPromise = resolveSelect(
			EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
		).getProductVariations< PartialProductVariation[] >( {
			product_id: productId,
			order: 'asc',
			orderby: 'menu_order',
			has_price: false,
			_fields: [ 'id' ],
			per_page: totalCountWithoutPrice,
		} );
		handlePrompt( {
			onOk( value ) {
				recordEvent( 'product_variations_set_prices_update', {
					source: TRACKS_SOURCE,
				} );
				productVariationsListPromise.then( ( variations ) => {
					handleUpdateAll(
						variations.map( ( { id } ) => ( {
							id,
							regular_price: value,
						} ) )
					);
				} );
			},
		} );
	}

	const hasNotDismissedNotice =
		! itemsWithoutPriceNoticeDismissed ||
		itemsWithoutPriceNoticeDismissed[ productId ] !== 'yes';
	const noticeText =
		totalCountWithoutPrice > 0 && hasNotDismissedNotice
			? sprintf(
					/** Translators: Number of variations without price */
					__(
						'%d variations do not have prices. Variations that do not have prices will not be visible to customers.',
						'woocommerce'
					),
					totalCountWithoutPrice
			  )
			: '';

	if ( ! hasVariationOptions ) {
		return <EmptyState />;
	}

	return (
		<div { ...blockProps }>
			<VariationsTable
				ref={ variationTableRef as React.Ref< HTMLDivElement > }
				noticeText={ noticeText }
				onNoticeDismiss={ () => {
					noticeDimissed.current = true;
					updateUserPreferences( {
						variable_items_without_price_notice_dismissed: {
							...( itemsWithoutPriceNoticeDismissed || {} ),
							[ productId ]: 'yes',
						},
					} );
				} }
				noticeActions={ [
					{
						label: __( 'Set prices', 'woocommerce' ),
						onClick: onSetPrices,
						className: 'is-destructive',
					},
				] }
				onVariationTableChange={ ( type, update ) => {
					if (
						type === 'delete' ||
						( type === 'update' &&
							update &&
							update.find(
								( variation ) =>
									'regular_price' in variation ||
									'sale_price' in variation
							) )
					) {
						invalidateResolution(
							'getProductVariationsTotalCount',
							[ totalCountWithoutPriceRequestParams ]
						);
					}
				} }
			/>
			{ context.isInSelectedTab && <VariableProductTour /> }
		</div>
	);
}
