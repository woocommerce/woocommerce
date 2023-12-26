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

export const Toggle = ( { children, heading, onToggle } ) => {
	const [ isShow, setIsShow ] = useState( false );
	const onClick = () => {
		onToggle( isShow );
		setIsShow( ! isShow );
	};

	return (
		<div className="toggle">
			<Button
				isTertiary
				onClick={ onClick }
				aria-expanded={ isShow }
				frameBorder={ 0 }
				className="toggle-button"
			>
				{ heading }
				{ isShow ? (
					<ChevronUpIcon size={ 18 } />
				) : (
					<ChevronDownIcon size={ 18 } />
				) }
			</Button>
			{ isShow ? children : null }
		</div>
	);
};
