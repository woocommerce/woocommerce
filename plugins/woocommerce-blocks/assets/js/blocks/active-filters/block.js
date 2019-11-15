/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useQueryStateByKey } from '@woocommerce/base-hooks';
import { useMemo, Fragment } from '@wordpress/element';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';
import { getAttributeFromTaxonomy } from '../../utils/attributes';
import { formatPriceRange, renderRemovableListItem } from './utils';
import ActiveAttributeFilters from './active-attribute-filters';

/**
 * Component displaying active filters.
 */
const ActiveFiltersBlock = ( {
	attributes: blockAttributes,
	isPreview = false,
} ) => {
	const [ productAttributes, setProductAttributes ] = useQueryStateByKey(
		'attributes',
		[]
	);
	const [ minPrice, setMinPrice ] = useQueryStateByKey( 'min_price' );
	const [ maxPrice, setMaxPrice ] = useQueryStateByKey( 'max_price' );

	const activePriceFilters = useMemo( () => {
		if ( ! Number.isFinite( minPrice ) && ! Number.isFinite( maxPrice ) ) {
			return null;
		}
		return renderRemovableListItem(
			__( 'Price:', 'woo-gutenberg-products-block' ),
			formatPriceRange( minPrice, maxPrice ),
			() => {
				setMinPrice( null );
				setMaxPrice( null );
			}
		);
	}, [ minPrice, maxPrice, formatPriceRange ] );

	const activeAttributeFilters = useMemo( () => {
		return productAttributes.map( ( attribute ) => {
			const attributeObject = getAttributeFromTaxonomy(
				attribute.attribute
			);
			return (
				<ActiveAttributeFilters
					attributeObject={ attributeObject }
					slugs={ attribute.slug }
					key={ attribute.attribute }
				/>
			);
		} );
	}, [ productAttributes ] );

	const hasFilters = () => {
		return (
			productAttributes.length > 0 ||
			Number.isFinite( minPrice ) ||
			Number.isFinite( maxPrice )
		);
	};

	if ( ! hasFilters() && ! isPreview ) {
		return null;
	}

	const TagName = `h${ blockAttributes.headingLevel }`;
	const listClasses = classnames( 'wc-block-active-filters-list', {
		'wc-block-active-filters-list--chips':
			blockAttributes.displayStyle === 'chips',
	} );

	return (
		<Fragment>
			{ ! isPreview && blockAttributes.heading && (
				<TagName>{ blockAttributes.heading }</TagName>
			) }
			<div className="wc-block-active-filters">
				<ul className={ listClasses }>
					{ isPreview ? (
						<Fragment>
							{ renderRemovableListItem(
								__( 'Size', 'woo-gutenberg-products-block' ),
								__( 'Small', 'woo-gutenberg-products-block' )
							) }
							{ renderRemovableListItem(
								__( 'Color', 'woo-gutenberg-products-block' ),
								__( 'Blue', 'woo-gutenberg-products-block' )
							) }
						</Fragment>
					) : (
						<Fragment>
							{ activePriceFilters }
							{ activeAttributeFilters }
						</Fragment>
					) }
				</ul>
				<button
					className="wc-block-active-filters__clear-all"
					onClick={ () => {
						setMinPrice( null );
						setMaxPrice( null );
						setProductAttributes( [] );
					} }
				>
					{ __( 'Clear All', 'woo-gutenberg-products-block' ) }
				</button>
			</div>
		</Fragment>
	);
};

export default ActiveFiltersBlock;
