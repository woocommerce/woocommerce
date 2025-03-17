/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { Button, Dropdown } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	// @ts-expect-error Our current version of packages doesn't have EntitiesSavedStates export
	EntitiesSavedStates,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import { recordEvent } from '../../events';

const SaveAllContent = ( { onToggle } ) => {
	// Hacky way to change the text in the templates row of the save dropdown
	useEffect( () => {
		const panels = document.querySelectorAll(
			'.woocommerce-email-editor-save-button-dropdown  .components-panel__body'
		);
		panels.forEach( ( panel ) => {
			const titleButton = panel.querySelector(
				'.components-panel__body-title button'
			);
			if (
				titleButton &&
				titleButton.textContent.trim() ===
					__( 'Templates', 'woocommerce' )
			) {
				const rows = panel.querySelectorAll( '.components-panel__row' );
				if ( rows.length ) {
					rows[ 0 ].textContent = __(
						'This change will affect emails that use this template.',
						'woocommerce'
					);
				}
			}
		} );
	}, [] );
	return <EntitiesSavedStates close={ onToggle } />;
};

export function SaveAllButton() {
	const { isSaving } = useSelect(
		( select ) => ( {
			isSaving: select( storeName ).isSaving(),
		} ),
		[]
	);

	const buttonRef = useRef( null );

	let label = __( 'Save', 'woocommerce' );
	if ( isSaving ) {
		label = __( 'Saving', 'woocommerce' );
	}

	return (
		<div ref={ buttonRef }>
			<Dropdown
				popoverProps={ {
					placement: 'bottom',
					anchor: buttonRef.current,
				} }
				contentClassName="woocommerce-email-editor-save-button-dropdown"
				renderToggle={ ( { onToggle } ) => (
					<Button
						onClick={ () => {
							recordEvent(
								'header_save_all_button_save_button_clicked'
							);
							onToggle();
						} }
						variant="primary"
						disabled={ isSaving }
					>
						{ label }
					</Button>
				) }
				renderContent={ ( { onToggle } ) => (
					<SaveAllContent onToggle={ onToggle } />
				) }
			/>
		</div>
	);
}
