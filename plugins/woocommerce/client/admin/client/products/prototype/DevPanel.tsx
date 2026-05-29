/**
 * External dependencies
 */
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { usePrototypeFlags } from './usePrototypeFlags';

const panelStyle: React.CSSProperties = {
	position: 'fixed',
	bottom: '16px',
	right: '16px',
	zIndex: 99999,
	fontFamily: 'monospace',
	fontSize: '12px',
};

const buttonStyle: React.CSSProperties = {
	background: 'rgba(0, 0, 0, 0.75)',
	color: '#fff',
	border: 'none',
	borderRadius: '12px',
	padding: '4px 12px',
	cursor: 'pointer',
	display: 'block',
	marginLeft: 'auto',
};

const cardStyle: React.CSSProperties = {
	background: 'rgba(0, 0, 0, 0.85)',
	color: '#fff',
	borderRadius: '8px',
	padding: '12px',
	marginBottom: '8px',
	minWidth: '200px',
};

const rowStyle: React.CSSProperties = {
	display: 'flex',
	justifyContent: 'space-between',
	alignItems: 'center',
	marginBottom: '8px',
};

export function DevPanel() {
	const [ isOpen, setIsOpen ] = useState( false );
	const { flags, flagDefinitions, toggleFlag } = usePrototypeFlags();

	return (
		<div style={ panelStyle }>
			{ isOpen && (
				<div style={ cardStyle }>
					{ flagDefinitions.map( ( { key, label } ) => (
						<div key={ key } style={ rowStyle }>
							<label htmlFor={ `proto-flag-${ key }` }>
								{ label }
							</label>
							<input
								id={ `proto-flag-${ key }` }
								type="checkbox"
								checked={ flags[ key ] ?? false }
								onChange={ () => toggleFlag( key ) }
							/>
						</div>
					) ) }
				</div>
			) }
			<button
				style={ buttonStyle }
				onClick={ () => setIsOpen( ( o ) => ! o ) }
			>
				Dev
			</button>
		</div>
	);
}
