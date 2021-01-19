/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import Label from '@woocommerce/base-components/label';

/**
 * The label for an attribute term filter.
 *
 * @param {Object} props Incoming props for the component.
 * @param {string} props.name The name for the label.
 * @param {number} props.count The count of products this attribute is attached to.
 */
const AttributeFilterLabel = ( { name, count } ) => {
	return (
		<Fragment>
			{ name }
			{ Number.isFinite( count ) && (
				<Label
					label={ count }
					screenReaderLabel={ sprintf(
						// translators: %s number of products.
						_n(
							'%s product',
							'%s products',
							count,
							'woocommerce'
						),
						count
					) }
					wrapperElement="span"
					wrapperProps={ {
						className: 'wc-block-attribute-filter-list-count',
					} }
				/>
			) }
		</Fragment>
	);
};

export default AttributeFilterLabel;
