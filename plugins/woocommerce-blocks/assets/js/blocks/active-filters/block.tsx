/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useQueryStateByKey } from '@woocommerce/base-context/hooks';
import { getSetting, getSettingWithCoercion } from '@woocommerce/settings';
import { useMemo, useEffect } from '@wordpress/element';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import Label from '@woocommerce/base-components/label';
import {
	isAttributeQueryCollection,
	isBoolean,
	isRatingQueryCollection,
	isStockStatusQueryCollection,
	isStockStatusOptions,
} from '@woocommerce/types';
import { getUrlParameter } from '@woocommerce/utils';

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
} from './utils';
import ActiveAttributeFilters from './active-attribute-filters';
import { Attributes } from './types';

/**
 * Component displaying active filters.
 *
 * @param {Object}  props            Incoming props for the component.
 * @param {Object}  props.attributes Incoming attributes for the block.
 * @param {boolean} props.isEditor   Whether or not in the editor context.
 */
const ActiveFiltersBlock = ( {
	attributes: blockAttributes,
	isEditor = false,
}: {
	attributes: Attributes;
	isEditor?: boolean;
} ) => {
	const filteringForPhpTemplate = getSettingWithCoercion(
		'is_rendering_php_template',
		false,
		isBoolean
	);
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

	const STOCK_STATUS_OPTIONS = getSetting( 'stockStatusOptions', [] );
	const activeStockStatusFilters = useMemo( () => {
		if (
			productStockStatus.length === 0 ||
			! isStockStatusQueryCollection( productStockStatus ) ||
			! isStockStatusOptions( STOCK_STATUS_OPTIONS )
		) {
			return null;
		}

		return productStockStatus.map( ( slug ) => {
			return renderRemovableListItem( {
				type: __( 'Stock Status', 'woo-gutenberg-products-block' ),
				name: STOCK_STATUS_OPTIONS[ slug ],
				removeCallback: () => {
					removeArgsFromFilterUrl( {
						filter_stock_status: slug,
					} );
					if ( ! filteringForPhpTemplate ) {
						const newStatuses = productStockStatus.filter(
							( status ) => {
								return status !== slug;
							}
						);
						setProductStockStatus( newStatuses );
					}
				},
				displayStyle: blockAttributes.displayStyle,
			} );
		} );
	}, [
		STOCK_STATUS_OPTIONS,
		productStockStatus,
		setProductStockStatus,
		blockAttributes.displayStyle,
		filteringForPhpTemplate,
	] );

	const activePriceFilters = useMemo( () => {
		if ( ! Number.isFinite( minPrice ) && ! Number.isFinite( maxPrice ) ) {
			return null;
		}
		return renderRemovableListItem( {
			type: __( 'Price', 'woo-gutenberg-products-block' ),
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
		minPrice,
		maxPrice,
		blockAttributes.displayStyle,
		setMinPrice,
		setMaxPrice,
		filteringForPhpTemplate,
	] );

	const activeAttributeFilters = useMemo( () => {
		if ( ! isAttributeQueryCollection( productAttributes ) ) {
			return null;
		}

		return productAttributes.map( ( attribute ) => {
			const attributeObject = getAttributeFromTaxonomy(
				attribute.attribute
			);

			if ( ! attributeObject ) {
				return null;
			}

			return (
				<ActiveAttributeFilters
					attributeObject={ attributeObject }
					displayStyle={ blockAttributes.displayStyle }
					slugs={ attribute.slug }
					key={ attribute.attribute }
					operator={ attribute.operator }
				/>
			);
		} );
	}, [ productAttributes, blockAttributes.displayStyle ] );

	const [ productRatings, setProductRatings ] =
		useQueryStateByKey( 'ratings' );

	/**
	 * Parse the filter URL to set the active rating fitlers.
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
			productRatings.length === 0 ||
			! isRatingQueryCollection( productRatings )
		) {
			return null;
		}

		return productRatings.map( ( slug ) => {
			return renderRemovableListItem( {
				type: __( 'Rating', 'woo-gutenberg-products-block' ),
				name: sprintf(
					/* translators: %s is referring to the average rating value */
					__( 'Rated %s out of 5', 'woo-gutenberg-products-block' ),
					slug
				),
				removeCallback: () => {
					if ( filteringForPhpTemplate ) {
						return removeArgsFromFilterUrl( {
							rating_filter: slug,
						} );
					}
					const newRatings = productRatings.filter( ( rating ) => {
						return rating !== slug;
					} );
					setProductRatings( newRatings );
				},
				displayStyle: blockAttributes.displayStyle,
			} );
		} );
	}, [
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

	if ( ! hasFilters() && ! isEditor ) {
		return null;
	}

	const TagName =
		`h${ blockAttributes.headingLevel }` as keyof JSX.IntrinsicElements;
	const hasFilterableProducts = getSettingWithCoercion(
		'has_filterable_products',
		false,
		isBoolean
	);

	if ( ! hasFilterableProducts ) {
		return null;
	}

	const listClasses = classnames( 'wc-block-active-filters__list', {
		'wc-block-active-filters__list--chips':
			blockAttributes.displayStyle === 'chips',
	} );

	return (
		<>
			{ ! isEditor && blockAttributes.heading && (
				<TagName className="wc-block-active-filters__title">
					{ blockAttributes.heading }
				</TagName>
			) }
			<div className="wc-block-active-filters">
				<ul className={ listClasses }>
					{ isEditor ? (
						<>
							{ renderRemovableListItem( {
								type: __(
									'Size',
									'woo-gutenberg-products-block'
								),
								name: __(
									'Small',
									'woo-gutenberg-products-block'
								),
								displayStyle: blockAttributes.displayStyle,
							} ) }
							{ renderRemovableListItem( {
								type: __(
									'Color',
									'woo-gutenberg-products-block'
								),
								name: __(
									'Blue',
									'woo-gutenberg-products-block'
								),
								displayStyle: blockAttributes.displayStyle,
							} ) }
						</>
					) : (
						<>
							{ activePriceFilters }
							{ activeStockStatusFilters }
							{ activeAttributeFilters }
							{ activeRatingFilters }
						</>
					) }
				</ul>
				<button
					className="wc-block-active-filters__clear-all"
					onClick={ () => {
						cleanFilterUrl();
						if ( ! filteringForPhpTemplate ) {
							setMinPrice( undefined );
							setMaxPrice( undefined );
							setProductAttributes( [] );
							setProductStockStatus( [] );
						}
					} }
				>
					<Label
						label={ __(
							'Clear All',
							'woo-gutenberg-products-block'
						) }
						screenReaderLabel={ __(
							'Clear All Filters',
							'woo-gutenberg-products-block'
						) }
					/>
				</button>
			</div>
		</>
	);
};

ActiveFiltersBlock.propTypes = {
	/**
	 * The attributes for this block.
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * Whether it's in the editor or frontend display.
	 */
	isEditor: PropTypes.bool,
};

export default ActiveFiltersBlock;
