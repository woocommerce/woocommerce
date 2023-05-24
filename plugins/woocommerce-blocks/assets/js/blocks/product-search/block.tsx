/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { HOME_URL } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './editor.scss';
import './style.scss';

/**
 * Component displaying a product search form.
 *
 * @param {Object}  props                        Incoming props for the component.
 * @param {Object}  props.attributes             Incoming block attributes.
 * @param {string}  props.attributes.label
 * @param {string}  props.attributes.placeholder
 * @param {string}  props.attributes.formId
 * @param {string}  props.attributes.className
 * @param {boolean} props.attributes.hasLabel
 * @param {string}  props.attributes.align
 */

interface ProductSearchBlockProps {
	attributes: {
		label: string;
		placeholder: string;
		formId: string;
		className: string;
		hasLabel: boolean;
		align: string;
	};
}
const ProductSearchBlock = ( {
	attributes: { label, placeholder, formId, className, hasLabel, align },
}: ProductSearchBlockProps ) => {
	const classes = classnames(
		'wc-block-product-search',
		align ? 'align' + align : '',
		className
	);

	return (
		<div className={ classes }>
			<form role="search" method="get" action={ HOME_URL }>
				<label
					htmlFor={ formId }
					className={
						hasLabel
							? 'wc-block-product-search__label'
							: 'wc-block-product-search__label screen-reader-text'
					}
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
						aria-label={ __(
							'Search',
							'woo-gutenberg-products-block'
						) }
					>
						<svg
							aria-hidden="true"
							role="img"
							focusable="false"
							className="dashicon dashicons-arrow-right-alt2"
							xmlns="http://www.w3.org/2000/svg"
							width="20"
							height="20"
							viewBox="0 0 20 20"
						>
							<path d="M6 15l5-5-5-5 1-2 7 7-7 7z" />
						</svg>
					</button>
				</div>
			</form>
		</div>
	);
};

export default ProductSearchBlock;
