/**
 * External dependencies
 */
import { Button, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DefaultColors } from './settings-email-color-palette-slotfill';
import {
	setColors,
	getColors,
	areColorsChanged,
	addListeners,
	removeListeners,
} from './settings-email-color-palette-functions';

type ResetStylesControlProps = {
	defaultColors: DefaultColors;
	hasThemeJson: boolean;
	autoSync: boolean;
	autoSyncInput: HTMLInputElement;
};

export const ResetStylesControl: React.FC< ResetStylesControlProps > = ( {
	defaultColors,
	hasThemeJson,
	autoSync,
	autoSyncInput,
} ) => {
	const [ isResetShown, setIsResetShown ] = useState(
		areColorsChanged( defaultColors )
	);
	const [ changed, setChanged ] = useState( false );
	const [ isAutoSyncEnabled, setIsAutoSyncEnabled ] = useState( autoSync );

	const [ initialValue ] = useState( getColors() );

	const handleAutoSyncToggle = ( newValue: boolean ) => {
		setIsAutoSyncEnabled( newValue );
		autoSyncInput.value = newValue ? 'yes' : 'no';
	};

	const handleInputChange = () => {
		const isOutOfSync = areColorsChanged( defaultColors );
		setIsResetShown( isOutOfSync );
		if ( isOutOfSync ) {
			handleAutoSyncToggle( false );
		}
		setChanged( areColorsChanged( initialValue ) );
	};

	const handleReset = () => {
		setColors( defaultColors );
		setIsResetShown( false );
		setChanged( areColorsChanged( initialValue ) );
		handleAutoSyncToggle( true );
	};

	const handleUndo = () => {
		setColors( initialValue );
		setIsResetShown( areColorsChanged( defaultColors ) );
		setChanged( false );
		handleAutoSyncToggle( autoSync );
	};

	useEffect( () => {
		addListeners( handleInputChange );
		return () => {
			removeListeners( handleInputChange );
		};
	} );

	return (
		<>
			{ ! isResetShown && (
				<span className="wc-settings-email-color-palette-message">
					{ hasThemeJson
						? __( 'Synced with theme.', 'woocommerce' )
						: __( 'Using default values.', 'woocommerce' ) }
				</span>
			) }
			{ hasThemeJson && ! isResetShown && (
				<ToggleControl
					label={ __(
						'Auto-sync with theme changes',
						'woocommerce'
					) }
					checked={ isAutoSyncEnabled }
					onChange={ handleAutoSyncToggle }
					className="wc-settings-email-color-palette-auto-sync"
				/>
			) }
			{ isResetShown && (
				<Button variant="secondary" onClick={ handleReset }>
					{ hasThemeJson
						? __( 'Sync with theme', 'woocommerce' )
						: __( 'Reset', 'woocommerce' ) }
				</Button>
			) }
			{ changed && (
				<Button variant="tertiary" onClick={ handleUndo }>
					{ __( 'Undo changes', 'woocommerce' ) }
				</Button>
			) }
		</>
	);
};
