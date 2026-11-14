/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useState } from '@wordpress/element';
import { CheckboxControl, Textarea } from '@woocommerce/blocks-components';
import { useToggleCollapse } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import './style.scss';

interface CheckoutOrderNotesProps {
	disabled: boolean;
	onChange: ( orderNotes: string ) => void;
	placeholder: string;
	value: string;
}

const CheckoutOrderNotes = ( {
	disabled,
	onChange,
	placeholder,
	value,
}: CheckoutOrderNotesProps ): JSX.Element => {
	const [ withOrderNotes, setWithOrderNotes ] = useState( value !== '' );
	// Store order notes when the textarea is hidden. This allows us to recover
	// text entered previously by the user when the checkbox is re-enabled
	// while keeping the context clean if the checkbox is disabled.
	const [ hiddenOrderNotesText, setHiddenOrderNotesText ] = useState( '' );
	const containerRef = useRef< HTMLDivElement >( null );
	// The checkbox grows the note in and collapses it out.
	const noteCollapse = useToggleCollapse(
		withOrderNotes,
		() =>
			containerRef.current?.querySelector< HTMLElement >(
				':scope > .wc-block-components-textarea:not([data-wc-collapse-copy])'
			) ?? null
	);

	return (
		<div className="wc-block-checkout__add-note" ref={ containerRef }>
			<CheckboxControl
				ref={ noteCollapse.triggerRef }
				disabled={ disabled }
				label={ __( 'Add a note to your order', 'woocommerce' ) }
				checked={ withOrderNotes }
				onChange={ ( isChecked ) => {
					noteCollapse.onToggle();
					setWithOrderNotes( isChecked );
					if ( isChecked ) {
						// When re-enabling the checkbox, store in context the
						// order notes value previously stored in the component
						// state.
						if ( value !== hiddenOrderNotesText ) {
							onChange( hiddenOrderNotesText );
						}
					} else {
						// When un-checking the checkbox, clear the order notes
						// value in the context but store it in the component
						// state.
						onChange( '' );
						setHiddenOrderNotesText( value );
					}
				} }
			/>
			{ withOrderNotes && (
				<Textarea
					disabled={ disabled }
					onTextChange={ onChange }
					placeholder={ placeholder }
					value={ value }
				/>
			) }
		</div>
	);
};

export default CheckoutOrderNotes;
