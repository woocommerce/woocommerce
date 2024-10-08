/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useQueryStateByKey } from '@woocommerce/base-context/hooks';
import { getSetting, getSettingWithCoercion } from '@woocommerce/settings';
import { useMemo, useEffect, useState } from '@wordpress/element';
import clsx from 'clsx';
import { Label } from '@woocommerce/blocks-components';
import {
	isAttributeQueryCollection,
	isBoolean,
	isRatingQueryCollection,
	isStockStatusQueryCollection,
	isStockStatusOptions,
} from '@woocommerce/types';
import { getUrlParameter } from '@woocommerce/utils';
import FilterTitlePlaceholder from '@woocommerce/base-components/filter-placeholder';
import { useIsMounted } from '@woocommerce/base-hooks';
import type { BlockAttributes } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './style.scss';
import { getAttributeFromTaxonomy } from '../../utils/attributes';
import {
	formatPriceRange,
	renderRemovableListItem,
	removeArgsFromFilterUrl,
	cleanFilterUrl,
	maybeUrlContainsFilters,
	urlContainsAttributeFilter,
	StoreAttributes,
} from './utils';
import ActiveAttributeFilters from './active-attribute-filters';
import FilterPlaceholders from './filter-placeholders';
import { useSetWraperVisibility } from '../filter-wrapper/context';

interface ActiveFiltersBlockProps {
	/**
	 * The attributes for this block.
	 */
	attributes: BlockAttributes;
	/**
	 * Whether it's in the editor or frontend display.
	 */
	isEditor: boolean;
}

/**
 * Component displaying active filters.
 */
const ActiveFiltersBlock = ( {
	attributes: blockAttributes,
	isEditor = false,
}: ActiveFiltersBlockProps ) => {
	const setWrapperVisibility = useSetWraperVisibility();
	const isMounted = useIsMounted();
	const componentHasMounted = isMounted();
	const filteringForPhpTemplate = getSettingWithCoercion(
		'isRenderingPhpTemplate',
		false,
		isBoolean
	);
	const [ isLoading, setIsLoading ] = useState( true );
	/*
		activeAttributeFilters is the only async query in this block. Because of this the rest of the filters will render null
		when in a loading state and activeAttributeFilters renders the placeholders.
	*/
	const shouldShowLoadingPlaceholders =
		maybeUrlContainsFilters() && ! isEditor && isLoading;
	const [ productAttributes, setProductAttributes ] = useQueryStateByKey(
		'attributes',
		[]
	);
	const [ productStockStatus, setProductStockStatus ] = useQueryStateByKey(
		'stock_status',
		[]
	);
	const [ minPrice, setMinPrice ] = useQueryStateByKey( 'min_price' );
	const [ maxPrice, setMaxPrice ] = useQueryStateByKey( 'max_price' );

	const [ productRatings, setProductRatings ] =
		useQueryStateByKey( 'rating' );

	const STOCK_STATUS_OPTIONS = getSetting( 'stockStatusOptions', [] );
	const STORE_ATTRIBUTES: StoreAttributes[] = getSetting( 'attributes', [] );
	const activeStockStatusFilters = useMemo( () => {
		if (
			shouldShowLoadingPlaceholders ||
			productStockStatus.length === 0 ||
			! isStockStatusQueryCollection( productStockStatus ) ||
			! isStockStatusOptions( STOCK_STATUS_OPTIONS )
		) {
			return null;
		}

		const stockStatusLabel = __( 'Stock Status', 'woocommerce' );

		return (
			<li>
				<span className="wc-block-active-filters__list-item-type">
					{ stockStatusLabel }:
				</span>
				<ul>
					{ productStockStatus.map( ( slug ) => {
						return renderRemovableListItem( {
							type: stockStatusLabel,
							name: STOCK_STATUS_OPTIONS[ slug ],
							removeCallback: () => {
								removeArgsFromFilterUrl( {
									filter_stock_status: slug,
								} );
								if ( ! filteringForPhpTemplate ) {
									const newStatuses =
										productStockStatus.filter(
											( status ) => {
												return status !== slug;
											}
										);
									setProductStockStatus( newStatuses );
								}
							},
							showLabel: false,
							displayStyle: blockAttributes.displayStyle,
						} );
					} ) }
				</ul>
			</li>
		);
	}, [
		shouldShowLoadingPlaceholders,
		STOCK_STATUS_OPTIONS,
		productStockStatus,
		setProductStockStatus,
		blockAttributes.displayStyle,
		filteringForPhpTemplate,
	] );

	const activePriceFilters = useMemo( () => {
		if (
			shouldShowLoadingPlaceholders ||
			( ! Number.isFinite( minPrice ) && ! Number.isFinite( maxPrice ) )
		) {
			return null;
		}
		return renderRemovableListItem( {
			type: __( 'Price', 'woocommerce' ),
			name: formatPriceRange( minPrice, maxPrice ),
			removeCallback: () => {
				removeArgsFromFilterUrl( 'max_price', 'min_price' );
				if ( ! filteringForPhpTemplate ) {
					setMinPrice( undefined );
					setMaxPrice( undefined );
				}
			},
			displayStyle: blockAttributes.displayStyle,
		} );
	}, [
		shouldShowLoadingPlaceholders,
		minPrice,
		maxPrice,
		blockAttributes.displayStyle,
		setMinPrice,
		setMaxPrice,
		filteringForPhpTemplate,
	] );

	const activeAttributeFilters = useMemo( () => {
		if (
			( ! isAttributeQueryCollection( productAttributes ) &&
				componentHasMounted ) ||
			( ! productAttributes.length &&
				! urlContainsAttributeFilter( STORE_ATTRIBUTES ) )
		) {
			if ( isLoading ) {
				setIsLoading( false );
			}
			return null;
		}

		return productAttributes.map( ( attribute ) => {
			const attributeObject = getAttributeFromTaxonomy(
				attribute.attribute
			);

			if ( ! attributeObject ) {
				if ( isLoading ) {
					setIsLoading( false );
				}
				return null;
			}

			return (
				<ActiveAttributeFilters
					attributeObject={ attributeObject }
					displayStyle={ blockAttributes.displayStyle }
					slugs={ attribute.slug }
					key={ attribute.attribute }
					operator={ attribute.operator }
					isLoadingCallback={ setIsLoading }
				/>
			);
		} );
	}, [
		productAttributes,
		componentHasMounted,
		STORE_ATTRIBUTES,
		isLoading,
		blockAttributes.displayStyle,
	] );

	/**
	 * Parse the filter URL to set the active rating filters.
	 * This code should be moved to Rating Filter block once it's implemented.
	 */
	useEffect( () => {
		if ( ! filteringForPhpTemplate ) {
			return;
		}

		if ( productRatings.length && productRatings.length > 0 ) {
			return;
		}

		const currentRatings = getUrlParameter( 'rating_filter' )?.toString();

		if ( ! currentRatings ) {
			return;
		}

		setProductRatings( currentRatings.split( ',' ) );
	}, [ filteringForPhpTemplate, productRatings, setProductRatings ] );

	const activeRatingFilters = useMemo( () => {
		if (
			shouldShowLoadingPlaceholders ||
			productRatings.length === 0 ||
			! isRatingQueryCollection( productRatings )
		) {
			return null;
		}

		const ratingLabel = __( 'Rating', 'woocommerce' );

		return (
			<li>
				<span className="wc-block-active-filters__list-item-type">
					{ ratingLabel }:
				</span>
				<ul>
					{ productRatings.map( ( slug ) => {
						return renderRemovableListItem( {
							type: ratingLabel,
							name: sprintf(
								/* translators: %s is referring to the average rating value */
								__( 'Rated %s out of 5', 'woocommerce' ),
								slug
							),
							removeCallback: () => {
								removeArgsFromFilterUrl( {
									rating_filter: slug,
								} );
								if ( ! filteringForPhpTemplate ) {
									const newRatings = productRatings.filter(
										( rating ) => {
											return rating !== slug;
										}
									);
									setProductRatings( newRatings );
								}
							},
							showLabel: false,
							displayStyle: blockAttributes.displayStyle,
						} );
					} ) }
				</ul>
			</li>
		);
	}, [
		shouldShowLoadingPlaceholders,
		productRatings,
		setProductRatings,
		blockAttributes.displayStyle,
		filteringForPhpTemplate,
	] );

	const hasFilters = () => {
		return (
			productAttributes.length > 0 ||
			productStockStatus.length > 0 ||
			productRatings.length > 0 ||
			Number.isFinite( minPrice ) ||
			Number.isFinite( maxPrice )
		);
	};

	if ( ! shouldShowLoadingPlaceholders && ! hasFilters() && ! isEditor ) {
		setWrapperVisibility( false );
		return null;
	}

	const TagName =
		`h${ blockAttributes.headingLevel }` as keyof JSX.IntrinsicElements;

	const heading = (
		<TagName className="wc-block-active-filters__title">
			{ blockAttributes.heading }
		</TagName>
	);

	const filterHeading = shouldShowLoadingPlaceholders ? (
		<FilterTitlePlaceholder>{ heading }</FilterTitlePlaceholder>
	) : (
		heading
	);

	const hasFilterableProducts = getSettingWithCoercion(
		'hasFilterableProducts',
		false,
		isBoolean
	);

	if ( ! hasFilterableProducts ) {
		setWrapperVisibility( false );
		return null;
	}

	setWrapperVisibility( true );

	const listClasses = clsx( 'wc-block-active-filters__list', {
		'wc-block-active-filters__list--chips':
			blockAttributes.displayStyle === 'chips',
		'wc-block-active-filters--loading': shouldShowLoadingPlaceholders,
	} );

	return (
		<>
			{ ! isEditor && blockAttributes.heading && filterHeading }
			<div className="wc-block-active-filters">
				<ul className={ listClasses }>
					{ isEditor ? (
						<>
							{ renderRemovableListItem( {
								type: __( 'Size', 'woocommerce' ),
								name: __( 'Small', 'woocommerce' ),
								displayStyle: blockAttributes.displayStyle,
							} ) }
							{ renderRemovableListItem( {
								type: __( 'Color', 'woocommerce' ),
								name: __( 'Blue', 'woocommerce' ),
								displayStyle: blockAttributes.displayStyle,
							} ) }
						</>
					) : (
						<>
							<FilterPlaceholders
								isLoading={ shouldShowLoadingPlaceholders }
								displayStyle={ blockAttributes.displayStyle }
							/>
							{ activePriceFilters }
							{ activeStockStatusFilters }
							{ activeAttributeFilters }
							{ activeRatingFilters }
						</>
					) }
				</ul>
				{ shouldShowLoadingPlaceholders ? (
					<span className="wc-block-active-filters__clear-all-placeholder" />
				) : (
					<button
						className="wc-block-active-filters__clear-all"
						onClick={ () => {
							cleanFilterUrl();
							if ( ! filteringForPhpTemplate ) {
								setMinPrice( undefined );
								setMaxPrice( undefined );
								setProductAttributes( [] );
								setProductStockStatus( [] );
								setProductRatings( [] );
							}
						} }
					>
						<Label
							label={ __( 'Clear All', 'woocommerce' ) }
							screenReaderLabel={ __(
								'Clear All Filters',
								'woocommerce'
							) }
						/>
					</button>
				) }
			</div>
		</>
	);
};

export default ActiveFiltersBlock;
