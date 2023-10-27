/**
 * External dependencies
 */
import { Popover } from '@wordpress/components';
import { Icon, info } from '@wordpress/icons';
import { useState } from '@wordpress/element';

export default function StatusPopover( props: {
	text: string;
	level: 'error' | 'warning';
	explanation: string;
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
				<Popover className="woocommerce-marketplace__my-subscriptions__popover">
					{ props.explanation }
				</Popover>
			) }
		</button>
	);
}
