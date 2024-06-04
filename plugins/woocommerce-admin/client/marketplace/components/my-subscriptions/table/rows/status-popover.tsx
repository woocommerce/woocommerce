/**
 * External dependencies
 */
import { Popover } from '@wordpress/components';
import { Icon, info } from '@wordpress/icons';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { StatusLevel } from '../../types';

export default function StatusPopover( props: {
	text: string;
	level: StatusLevel;
	explanation: string | JSX.Element;
} ) {
	const [ isVisible, setIsVisible ] = useState( false );

	function shouldShowExplanation() {
		if ( props.explanation === '' ) {
			return false;
		}

		return isVisible;
	}

	return (
		<button
			onClick={ () => {
				setIsVisible( ! isVisible );
			} }
			className={
				'woocommerce-marketplace__my-subscriptions__product-status' +
				' ' +
				`woocommerce-marketplace__my-subscriptions__product-status--${ props.level }`
			}
		>
			<Icon icon={ info } size={ 16 } />
			{ props.text }
			{ shouldShowExplanation() && (
				<Popover
					className="woocommerce-marketplace__my-subscriptions__popover"
					position="top center"
					focusOnMount={ false }
				>
					{ props.explanation }
				</Popover>
			) }
		</button>
	);
}
