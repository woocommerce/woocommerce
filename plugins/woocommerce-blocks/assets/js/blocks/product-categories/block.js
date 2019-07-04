/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component, createRef, Fragment } from '@wordpress/element';
import { IconButton, Placeholder } from '@wordpress/components';
import { repeat } from 'lodash';
import PropTypes from 'prop-types';
import { withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { buildTermsTree } from './hierarchy';
import { IconFolder } from '../../components/icons';

function getCategories( { hasEmpty, isHierarchical } ) {
	const categories = wc_product_block_data.productCategories.filter(
		( cat ) => hasEmpty || !! cat.count
	);
	return isHierarchical ?
		buildTermsTree( categories ) :
		categories;
}

/**
 * Component displaying the categories as dropdown or list.
 */
class ProductCategoriesBlock extends Component {
	constructor() {
		super( ...arguments );
		this.select = createRef();
		this.onNavigate = this.onNavigate.bind( this );
		this.renderList = this.renderList.bind( this );
		this.renderOptions = this.renderOptions.bind( this );
	}

	onNavigate() {
		const { isPreview = false } = this.props;
		const url = this.select.current.value;
		if ( 'false' === url ) {
			return;
		}
		const home = wc_product_block_data.homeUrl;

		if ( ! isPreview && 0 === url.indexOf( home ) ) {
			document.location.href = url;
		}
	}

	renderList( items, depth = 0 ) {
		const { isPreview = false } = this.props;
		const { hasCount } = this.props.attributes;
		const parentKey = 'parent-' + items[ 0 ].term_id;

		return (
			<ul key={ parentKey }>
				{ items.map( ( cat ) => {
					const count = hasCount ? <span>({ cat.count })</span> : null;
					return [
						<li key={ cat.term_id }>
							<a href={ isPreview ? null : cat.link }>{ cat.name }</a> { count } { /* eslint-disable-line */ }
						</li>,
						!! cat.children && !! cat.children.length && this.renderList( cat.children, depth + 1 ),
					];
				} ) }
			</ul>
		);
	}

	renderOptions( items, depth = 0 ) {
		const { hasCount } = this.props.attributes;

		return items.map( ( cat ) => {
			const count = hasCount ? `(${ cat.count })` : null;
			return [
				<option key={ cat.term_id } value={ cat.link }>
					{ repeat( '–', depth ) } { cat.name } { count }
				</option>,
				!! cat.children && !! cat.children.length && this.renderOptions( cat.children, depth + 1 ),
			];
		} );
	}

	render() {
		const { attributes, instanceId } = this.props;
		const { className, isDropdown } = attributes;
		const categories = getCategories( attributes );
		const classes = classnames(
			'wc-block-product-categories',
			className,
			{
				'is-dropdown': isDropdown,
				'is-list': ! isDropdown,
			}
		);

		const selectId = `prod-categories-${ instanceId }`;

		return (
			<Fragment>
				{ categories.length > 0 ? (
					<div className={ classes }>
						{ isDropdown ? (
							<Fragment>
								<div className="wc-block-product-categories__dropdown">
									<label className="screen-reader-text" htmlFor={ selectId }>
										{ __( 'Select a category', 'woo-gutenberg-products-block' ) }
									</label>
									<select id={ selectId } ref={ this.select }>
										<option value="false" hidden>
											{ __( 'Select a category', 'woo-gutenberg-products-block' ) }
										</option>
										{ this.renderOptions( categories ) }
									</select>
								</div>
								<IconButton
									icon="arrow-right-alt2"
									label={ __( 'Go to category', 'woo-gutenberg-products-block' ) }
									onClick={ this.onNavigate }
								/>
							</Fragment>
						) : (
							this.renderList( categories )
						) }
					</div>
				) : (
					<Placeholder
						className="wc-block-product-categories"
						icon={ <IconFolder /> }
						label={ __( 'Product Categories List', 'woo-gutenberg-products-block' ) }
					>
						{ __( "This block shows product categories for your store. In order to preview this you'll first need to create a product and assign it to a category.", 'woo-gutenberg-products-block' ) }
					</Placeholder>
				) }
			</Fragment>
		);
	}
}

ProductCategoriesBlock.propTypes = {
	/**
	 * The attributes for this block.
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * A unique ID for identifying the label for the select dropdown.
	 */
	instanceId: PropTypes.number,
	/**
	 * Whether this is the block preview or frontend display.
	 */
	isPreview: PropTypes.bool,
};

export default withInstanceId( ProductCategoriesBlock );
