/**
 * External dependencies
 */
import { Popover } from '@wordpress/components';
import { useState, useRef, useEffect } from '@wordpress/element';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { StatusLevel } from '../../types';

export default function StatusPopover( props: {
	text: string;
	level: StatusLevel;
	explanation: string | JSX.Element;
	explanationOnHover?: boolean;
} ) {
	const [ isHovered, setIsHovered ] = useState( false );
	const [ isClicked, setIsClicked ] = useState( false );
	const hoverTimeoutId = useRef< null | NodeJS.Timeout >( null );

	useEffect( () => {
		return () => {
			if ( hoverTimeoutId.current ) {
				clearTimeout( hoverTimeoutId.current );
			}
		};
	}, [] );

	const startHover = () => {
		if ( ! props.explanationOnHover ) {
			return;
		}

		if ( hoverTimeoutId.current ) {
			clearTimeout( hoverTimeoutId.current );
		}

		setIsHovered( true );
	};

	const endHover = () => {
		if ( ! props.explanationOnHover ) {
			return;
		}

		if ( hoverTimeoutId.current ) {
			clearTimeout( hoverTimeoutId.current );
		}

		// Add a small delay in case user hovers from the button to the popover.
		// In such a case we don't want to hide the popover.
		hoverTimeoutId.current = setTimeout( () => {
			setIsHovered( false );
		}, 350 );
	};

	function shouldShowExplanation() {
		if ( props.explanation === '' ) {
			return false;
		}

		return isClicked || ( props.explanationOnHover && isHovered );
	}

	return (
		<button
			onClick={ () => setIsClicked( ! isClicked ) }
			onMouseOver={ startHover }
			onFocus={ startHover }
			onMouseOut={ endHover }
			onBlur={ endHover }
			className={ clsx(
				'woocommerce-marketplace__my-subscriptions__product-status',
				`woocommerce-marketplace__my-subscriptions__product-status--${ props.level }`
			) }
		>
			{ props.text }
			{ shouldShowExplanation() && (
				<Popover
					className="woocommerce-marketplace__my-subscriptions__popover"
					position="top center"
					focusOnMount={ false }
					onMouseOver={ startHover }
					onMouseOut={ endHover }
					onFocus={ startHover }
					onBlur={ endHover }
				>
					{ props.explanation }
				</Popover>
			) }
		</button>
	);
}
