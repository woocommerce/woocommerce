/**
 * External dependencies
 */
import {
	createElement,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TourKit, TourKitTypes } from '@woocommerce/components';
import {
	EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME,
	OPTIONS_STORE_NAME,
	useUserPreferences,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { DEFAULT_VARIATION_PER_PAGE_OPTION } from '../../../constants';

export const VariableProductTour: React.FC = () => {
	const [ isTourOpen, setIsTourOpen ] = useState( false );
	const productId = useEntityId( 'postType', 'product' );
	const prevTotalCount = useRef< undefined | number >();
	const requestParams = useMemo(
		() => ( {
			product_id: productId,
			page: 1,
			per_page: DEFAULT_VARIATION_PER_PAGE_OPTION,
			order: 'asc',
			orderby: 'menu_order',
		} ),
		[ productId ]
	);

	const { totalCount } = useSelect(
		( select ) => {
			const { getProductVariationsTotalCount } = select(
				EXPERIMENTAL_PRODUCT_VARIATIONS_STORE_NAME
			);
			return {
				totalCount:
					getProductVariationsTotalCount< number >( requestParams ),
			};
		},
		[ productId ]
	);

	const {
		updateUserPreferences,
		variable_product_block_tour_shown: hasShownTour,
	} = useUserPreferences();

	const config: TourKitTypes.WooConfig = {
		placement: 'top',
		steps: [
			{
				referenceElements: {
					desktop:
						'.wp-block-woocommerce-product-variation-items-field',
				},
				focusElement: {
					desktop:
						'.wp-block-woocommerce-product-variation-items-field',
				},
				meta: {
					name: 'product-variations-2',
					heading: __(
						'⚡️ This product now has variations',
						'woocommerce'
					),
					descriptions: {
						desktop: __(
							'From now on, you’ll manage pricing, shipping, and inventory for each variation individually—just like any other product in your store.',
							'woocommerce'
						),
					},
					primaryButton: {
						text: __( 'Got it', 'woocommerce' ),
					},
				},
			},
		],
		options: {
			classNames: [ 'variation-items-product-tour' ],
			// WooTourKit does not handle merging of default options properly,
			// so we need to duplicate the effects options here.
			effects: {
				arrowIndicator: true,
				spotlight: {
					interactivity: {
						enabled: true,
					},
				},
			},
			callbacks: {
				onStepViewOnce: () => {
					recordEvent( 'variable_product_block_tour_shown', {
						variable_count: totalCount,
					} );
				},
			},
			popperModifiers: [
				{
					name: 'offset',
					options: {
						// 24px for additional padding and 8px for arrow.
						offset: [ 0, 32 ],
					},
				},
			],
		},
		closeHandler: () => {
			updateUserPreferences( {
				variable_product_block_tour_shown: 'yes',
			} );
			setIsTourOpen( false );

			recordEvent( 'variable_product_block_tour_dismissed' );
		},
	};

	useEffect( () => {
		const isFirstVariation =
			prevTotalCount.current !== totalCount &&
			totalCount > 0 &&
			prevTotalCount.current === 0;
		prevTotalCount.current = totalCount;
		if ( isFirstVariation && ! isTourOpen ) {
			setIsTourOpen( true );
		}
	}, [ totalCount ] );

	const { hasShownProductEditorTour } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );
		return {
			hasShownProductEditorTour:
				getOption( 'woocommerce_block_product_tour_shown' ) === 'yes',
		};
	} );

	if (
		hasShownTour === 'yes' ||
		! isTourOpen ||
		! hasShownProductEditorTour
	) {
		return null;
	}

	return <TourKit config={ config } />;
};
