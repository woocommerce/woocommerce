/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import ChevronUpIcon from 'gridicons/dist/chevron-up';
import ChevronDownIcon from 'gridicons/dist/chevron-down';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './Toggle.scss';

export const Toggle = ( { children, heading } ) => {
	const [ isOpen, setIsOpen ] = useState( false );
	const onToggle = () => {
		setIsOpen( ! isOpen );
	};

	return (
		<div className="toggle">
			<Button
				isTertiary
				onClick={ onToggle }
				aria-expanded={ isOpen }
				frameBorder={ 0 }
				className="toggle-button"
			>
				{ heading }
				{ isOpen ? (
					<ChevronUpIcon size={ 18 } />
				) : (
					<ChevronDownIcon size={ 18 } />
				) }
			</Button>
			{ isOpen ? children : null }
		</div>
	);
};

// export default Toggle;
