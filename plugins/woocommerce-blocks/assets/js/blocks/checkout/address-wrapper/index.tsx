/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Wrapper for address fields which handles the edit/preview transition. Form fields are always rendered so that
 * validation can occur.
 */
export const AddressWrapper = ( {
	isEditing = false,
	addressCard,
	addressForm,
}: {
	isEditing: boolean;
	addressCard: () => JSX.Element;
	addressForm: () => JSX.Element;
} ): JSX.Element | null => {
	const wrapperClasses = classnames(
		'wc-block-components-address-address-wrapper',
		{
			'is-editing': isEditing,
		}
	);

	return (
		<div className={ wrapperClasses }>
			<div className="wc-block-components-address-card-wrapper">
				{ addressCard() }
			</div>
			<div className="wc-block-components-address-form-wrapper">
				{ addressForm() }
			</div>
		</div>
	);
};

export default AddressWrapper;
