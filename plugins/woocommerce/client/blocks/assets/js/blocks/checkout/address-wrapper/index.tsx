/**
 * External dependencies
 */
import clsx from 'clsx';
import { useLayoutEffect, useRef } from '@wordpress/element';
import { animateCollapse } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Wrapper for address fields which handles the edit/preview transition. Form fields are always rendered so that
 * validation can occur. With `shouldAnimate`, the card and the form crossfade while the height changes.
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
	const ref = useRef< HTMLDivElement >( null );
	const wasEditingRef = useRef( isEditing );
	useLayoutEffect( () => {
		const card = ref.current?.firstElementChild as HTMLElement | null;
		if ( isEditing && ! wasEditingRef.current && shouldAnimate && card ) {
			// The card is out of the flow now, but still has the height that the wrapper had.
			animateCollapse( ref.current, true, true, {
				height: `${ card.offsetHeight }px`,
			} );
		}
		wasEditingRef.current = isEditing;
	}, [ isEditing, shouldAnimate ] );

	const wrapperClasses = clsx(
		'wc-block-components-address-address-wrapper',
		{
			'is-editing': isEditing,
			'is-animated': shouldAnimate,
		}
	);

	return (
		<div className={ wrapperClasses } ref={ ref }>
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
