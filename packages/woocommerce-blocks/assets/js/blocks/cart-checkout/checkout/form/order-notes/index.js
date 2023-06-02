/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';
import Textarea from '@woocommerce/base-components/textarea';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const CheckoutOrderNotes = ( { disabled, onChange, placeholder, value } ) => {
	const [ withOrderNotes, setWithOrderNotes ] = useState( false );
	// Store order notes when the textarea is hidden. This allows us to recover
	// text entered previously by the user when the checkbox is re-enabled
	// while keeping the context clean if the checkbox is disabled.
	const [ hiddenOrderNotesText, setHiddenOrderNotesText ] = useState( '' );

	return (
		<div className="wc-block-checkout__add-note">
			<CheckboxControl
				disabled={ disabled }
				label={ __(
					'Add a note to your order',
					'woocommerce'
				) }
				checked={ withOrderNotes }
				onChange={ ( isChecked ) => {
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

Textarea.propTypes = {
	onTextChange: PropTypes.func.isRequired,
	disabled: PropTypes.bool,
	placeholder: PropTypes.string,
	value: PropTypes.string,
};

export default CheckoutOrderNotes;
