/**
 * External dependencies
 */
import clsx from 'clsx';
import { useLayoutEffect, useRef } from '@wordpress/element';
import { animateHeightChange } from '@woocommerce/base-utils/collapse';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Wrapper for address fields which handles the edit/preview transition. Form fields are always rendered so that
 * validation can occur. With `shouldAnimate`, the card and form crossfade as the wrapper's height changes, so the
 * checkout below moves smoothly.
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
	const lastHeight = useRef( 0 );
	const wasEditing = useRef( isEditing );
	useLayoutEffect( () => {
		if ( isEditing !== wasEditing.current && shouldAnimate ) {
			animateHeightChange( ref.current, lastHeight.current );
		}
		wasEditing.current = isEditing;
		lastHeight.current = ref.current?.getBoundingClientRect().height ?? 0;
	} );

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
