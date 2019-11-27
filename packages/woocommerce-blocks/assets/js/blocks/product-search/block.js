/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { withInstanceId, compose } from '@wordpress/compose';
import { PlainText } from '@wordpress/editor';
import { HOME_URL } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import './editor.scss';
import './style.scss';

/**
 * Component displaying a product search form.
 */
class ProductSearchBlock extends Component {
	renderView() {
		const { attributes: { label, placeholder, formId, className, hasLabel, align } } = this.props;
		const classes = classnames(
			'wc-block-product-search',
			align ? 'align' + align : '',
			className,
		);

		return (
			<div className={ classes }>
				<form role="search" method="get" action={ HOME_URL }>
					<label
						htmlFor={ formId }
						className={ hasLabel ? 'wc-block-product-search__label' : 'wc-block-product-search__label screen-reader-text' }
					>
						{ label }
					</label>
					<div className="wc-block-product-search__fields">
						<input
							type="search"
							id={ formId }
							className="wc-block-product-search__field"
							placeholder={ placeholder }
							name="s"
						/>
						<input type="hidden" name="post_type" value="product" />
						<button
							type="submit"
							className="wc-block-product-search__button"
							label={ __( 'Search', 'woocommerce' ) }
						>
							<svg aria-hidden="true" role="img" focusable="false" className="dashicon dashicons-arrow-right-alt2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
								<path d="M6 15l5-5-5-5 1-2 7 7-7 7z"></path>
							</svg>
						</button>
					</div>
				</form>
			</div>
		);
	}

	renderEdit() {
		const { attributes, setAttributes, instanceId } = this.props;
		const { label, placeholder, formId, className, hasLabel, align } = attributes;
		const classes = classnames(
			'wc-block-product-search',
			align ? 'align' + align : '',
			className,
		);

		if ( ! formId ) {
			setAttributes( { formId: `wc-block-product-search-${ instanceId }` } );
		}

		return (
			<div className={ classes }>
				{ !! hasLabel && (
					<PlainText
						className="wc-block-product-search__label"
						value={ label }
						onChange={ ( value ) => setAttributes( { label: value } ) }
					/>
				) }
				<div className="wc-block-product-search__fields">
					<PlainText
						className="wc-block-product-search__field input-control"
						value={ placeholder }
						onChange={ ( value ) => setAttributes( { placeholder: value } ) }
					/>
					<button
						type="submit"
						className="wc-block-product-search__button"
						label={ __( 'Search', 'woocommerce' ) }
						onClick={ ( e ) => e.preventDefault() }
						tabIndex="-1"
					>
						<svg aria-hidden="true" role="img" focusable="false" className="dashicon dashicons-arrow-right-alt2" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
							<path d="M6 15l5-5-5-5 1-2 7 7-7 7z"></path>
						</svg>
					</button>
				</div>
			</div>
		);
	}

	render() {
		if ( this.props.isPreview ) {
			return this.renderEdit();
		}

		return this.renderView();
	}
}

ProductSearchBlock.propTypes = {
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
	/**
	 * A callback to update attributes.
	 */
	setAttributes: PropTypes.func,
};

export default compose( [
	withInstanceId,
] )( ProductSearchBlock );
