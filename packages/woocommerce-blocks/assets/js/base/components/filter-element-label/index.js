/**
 * External dependencies
 */
import { _n, sprintf } from '@wordpress/i18n';
import Label from '@woocommerce/base-components/label';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * The label for an filter elements.
 *
 * @param {Object} props Incoming props for the component.
 * @param {string} props.name The name for the label.
 * @param {number} props.count The count of products this status is attached to.
 */
const FilterElementLabel = ( { name, count } ) => {
	return (
		<>
			{ name }
			{ Number.isFinite( count ) && (
				<Label
					label={ count }
					screenReaderLabel={ sprintf(
						/* translators: %s number of products. */
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
						className: 'wc-filter-element-label-list-count',
					} }
				/>
			) }
		</>
	);
};

export default FilterElementLabel;
