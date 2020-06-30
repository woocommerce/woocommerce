/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

const DropdownSelectorSelectedChip = ( { onRemoveItem, option } ) => {
	return (
		<button
			className="wc-block-dropdown-selector__selected-chip"
			onClick={ () => {
				onRemoveItem( option.value );
			} }
			onKeyDown={ ( e ) => {
				if ( e.key === 'Backspace' || e.key === 'Delete' ) {
					onRemoveItem( option.value );
				}
			} }
			aria-label={ sprintf(
				__( 'Remove %s filter', 'woocommerce' ),
				option.name
			) }
		>
			<span className="wc-block-dropdown-selector__selected-chip__label">
				{ option.label }
			</span>
			<span className="wc-block-dropdown-selector__selected-chip__remove">
				𝘅
			</span>
		</button>
	);
};

export default DropdownSelectorSelectedChip;
