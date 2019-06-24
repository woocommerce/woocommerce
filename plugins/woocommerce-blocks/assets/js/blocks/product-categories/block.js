/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { noop } from 'lodash';
import { SelectControl } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { buildTermsTree } from './hierarchy';

function getCategories( { hasEmpty, isDropdown, isHierarchical } ) {
	const categories = wc_product_block_data.productCategories.filter(
		( cat ) => hasEmpty || !! cat.count
	);
	return ! isDropdown && isHierarchical ?
		buildTermsTree( categories ) :
		categories;
}

/**
 * Component displaying the categories as dropdown or list.
 */
const ProductCategoriesBlock = ( { attributes, isPreview = false } ) => {
	const { hasCount, isDropdown } = attributes;
	const categories = getCategories( attributes );
	const parentKey = 'parent-' + categories[ 0 ].term_id;

	const renderList = ( items ) => (
		<ul key={ parentKey }>
			{ items.map( ( cat ) => {
				const count = hasCount ? <span>({ cat.count })</span> : null;
				return [
					<li key={ cat.term_id }>
						<a href={ isPreview ? null : cat.link }>{ cat.name }</a> { count } { /* eslint-disable-line */ }
					</li>,
					!! cat.children && !! cat.children.length && renderList( cat.children ),
				];
			} ) }
		</ul>
	);

	return (
		<div className="wc-block-product-categories">
			{ isDropdown ? (
				<SelectControl
					label={ __( 'Select a category', 'woo-gutenberg-products-block' ) }
					options={ categories.map( ( cat ) => ( {
						label: hasCount ? `${ cat.name } (${ cat.count })` : cat.name,
						value: cat.term_id,
					} ) ) }
					onChange={ noop }
				/>
			) : (
				renderList( categories )
			) }
		</div>
	);
};

ProductCategoriesBlock.propTypes = {
	/**
	 * The attributes for this block
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * Whether this is the block preview or frontend display.
	 */
	isPreview: PropTypes.bool,
};

export default ProductCategoriesBlock;
