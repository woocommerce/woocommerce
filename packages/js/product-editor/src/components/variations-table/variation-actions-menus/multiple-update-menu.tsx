/**
 * External dependencies
 */
import { Button, Dropdown } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { VariationActionsMenuProps } from './types';
import { VariationActions } from './variation-actions';

export function MultipleUpdateMenu( {
	selection,
	disabled,
	onChange,
	onDelete,
}: VariationActionsMenuProps ) {
	if ( ! selection ) {
		return null;
	}

	return (
		<Dropdown
			// @ts-expect-error missing prop in types.
			popoverProps={ {
				placement: 'bottom-end',
			} }
			renderToggle={ ( { isOpen, onToggle } ) => (
				<Button
					disabled={ disabled }
					aria-expanded={ isOpen }
					icon={ isOpen ? chevronUp : chevronDown }
					variant="secondary"
					onClick={ onToggle }
					className="variations-actions-menu__toogle"
				>
					<span>{ __( 'Quick update', 'woocommerce' ) }</span>
				</Button>
			) }
			renderContent={ ( { onClose }: { onClose: () => void } ) => (
				<VariationActions
					selection={ selection }
					onClose={ onClose }
					onChange={ onChange }
					onDelete={ onDelete }
					supportsMultipleSelection={ true }
				/>
			) }
		/>
	);
}
