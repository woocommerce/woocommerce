/**
 * External dependencies
 */
import clsx from 'clsx';

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
	shouldAnimate = false,
}: {
	isEditing: boolean;
	addressCard: JSX.Element;
	addressForm: JSX.Element;
	shouldAnimate?: boolean;
} ): JSX.Element => {
	const wrapperClasses = clsx(
		'wc-block-components-address-address-wrapper',
		{
			'is-editing': isEditing,
			'is-animated': shouldAnimate,
		}
	);

	return (
		<div className={ wrapperClasses }>
			<div className="wc-block-components-address-card-wrapper">
				{ addressCard }
			</div>
			<div className="wc-block-components-address-form-wrapper">
				{ addressForm }
			</div>
		</div>
	);
};

export default AddressWrapper;
