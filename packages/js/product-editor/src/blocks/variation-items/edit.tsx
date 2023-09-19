/**
 * External dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	Product,
	useUserPreferences,
} from '@woocommerce/data';
import { useBlockProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { createElement, useMemo, useRef } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId, useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { VariationsTable } from '../../components/variations-table';
import { useValidation } from '../../contexts/validation-context';
import { VariationOptionsBlockAttributes } from './types';
import { VariableProductTour } from './variable-product-tour';

export function Edit( {
	context,
}: BlockEditProps< VariationOptionsBlockAttributes > & {
	context?: {
		isInSelectedTab?: boolean;
	};
} ) {
	const noticeDimissed = useRef( false );
	const { invalidateResolution } = useDispatch(
		EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
	);
	const productId = useEntityId( 'postType', 'product' );
	const blockProps = useBlockProps();
	const [ productStatus ] = useEntityProp< string >(
		'postType',
		'product',
		'status'
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
		[ productId ]
	);

	const {
		updateUserPreferences,
		variable_items_without_price_notice_dismissed:
			itemsWithoutPriceNoticeDismissed,
	} = useUserPreferences();

	const { ref: variationTableRef } = useValidation< Product >(
		`variations`,
		async function regularPriceValidator( defaultValue, additionalData ) {
			if (
				totalCountWithoutPrice > 0 &&
				! noticeDimissed.current &&
				productStatus !== 'publish' &&
				additionalData?.status === 'publish'
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
				onVariationTableChange={ ( type, update ) => {
					if (
						type === 'delete' ||
						( type === 'update' &&
							update &&
							update.find(
								( variation ) =>
									variation.regular_price ||
									variation.sale_price
							) )
					) {
						invalidateResolution(
							'getProductVariationsTotalCount',
							[ totalCountWithoutPriceRequestParams ]
						);
					}
				} }
			/>
			{ context?.isInSelectedTab && <VariableProductTour /> }
		</div>
	);
}
