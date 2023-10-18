/**
 * External dependencies
 */
import { Popover } from '@wordpress/components';
import { Icon, info } from '@wordpress/icons';
import { useState } from '@wordpress/element';

export default function StatusPopover( props: {
	text: string;
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
			className="woocommerce-marketplace__my-subscriptions__status-warning"
		>
			<Icon icon={ info } size={ 16 } />
			{ props.text }
			{ shouldShowExplanation() && (
				<Popover className="woocommerce-marketplace__my-subscriptions__status-popover">
					{ props.explanation }
				</Popover>
			) }
		</button>
	);
}
