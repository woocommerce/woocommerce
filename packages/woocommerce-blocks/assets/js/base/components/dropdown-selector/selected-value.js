/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';

const DropdownSelectorSelectedValue = ( { onClick, onRemoveItem, option } ) => {
	const labelRef = useRef( null );

	useEffect( () => {
		labelRef.current.focus();
	}, [ labelRef ] );

	return (
		<div className="wc-block-dropdown-selector__selected-value">
			<button
				ref={ labelRef }
				className="wc-block-dropdown-selector__selected-value__label"
				onClick={ ( e ) => {
					e.stopPropagation();
					onClick( option.value );
				} }
				aria-label={ sprintf(
					/* translators: %s attribute value used in the filter. For example: yellow, green, small, large. */
					__(
						'Replace current %s filter',
						'woocommerce'
					),
					option.name
				) }
			>
				{ option.label }
			</button>
			<button
				className="wc-block-dropdown-selector__selected-value__remove"
				onClick={ () => {
					onRemoveItem( option.value );
				} }
				onKeyDown={ ( e ) => {
					if ( e.key === 'Backspace' || e.key === 'Delete' ) {
						onRemoveItem( option.value );
					}
				} }
				aria-label={ sprintf(
					/* translators: %s attribute value used in the filter. For example: yellow, green, small, large. */
					__( 'Remove %s filter', 'woocommerce' ),
					option.name
				) }
			>
				𝘅
			</button>
		</div>
	);
};

export default DropdownSelectorSelectedValue;
