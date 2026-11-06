/**
 * External dependencies
 */
import clsx from 'clsx';
import type { RefObject } from 'react';

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
	wrapperRef,
}: {
	isEditing: boolean;
	addressCard: JSX.Element;
	addressForm: JSX.Element;
	shouldAnimate?: boolean;
	wrapperRef?: RefObject< HTMLDivElement >;
} ): JSX.Element => {
	const wrapperClasses = clsx(
		'wc-block-components-address-address-wrapper',
		{
			'is-editing': isEditing,
			'is-animated': shouldAnimate,
		}
	);

	return (
		<div className={ wrapperClasses } ref={ wrapperRef }>
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
