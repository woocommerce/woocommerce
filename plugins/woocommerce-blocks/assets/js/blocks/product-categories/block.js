/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, createRef, Fragment } from 'react';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import withComponentId from '../../utils/with-component-id';

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
					{ '–'.repeat( depth ) } { cat.name } { count }
				</option>,
				!! cat.children && !! cat.children.length && this.renderOptions( cat.children, depth + 1 ),
			];
		} );
	}

	render() {
		const { attributes, categories, componentId } = this.props;
		const { className, isDropdown } = attributes;
		const classes = classnames(
			'wc-block-product-categories',
			className,
			{
				'is-dropdown': isDropdown,
				'is-list': ! isDropdown,
			}
		);

		const selectId = `prod-categories-${ componentId }`;

		return (
			<Fragment>
				{ categories.length > 0 && (
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
								<button
									type="button"
									className="wc-block-product-categories__button"
									aria-label={ __( 'Go to category', 'woo-gutenberg-products-block' ) }
									icon="arrow-right-alt2"
									onClick={ this.onNavigate }
								>
									<svg aria-hidden="true" role="img" focusable="false" className="dashicon dashicons-arrow-right-alt2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
										<path d="M6 15l5-5-5-5 1-2 7 7-7 7z"></path>
									</svg>
								</button>
							</Fragment>
						) : (
							this.renderList( categories )
						) }
					</div>
				) }
			</Fragment>
		);
	}
}

export default withComponentId( ProductCategoriesBlock );
