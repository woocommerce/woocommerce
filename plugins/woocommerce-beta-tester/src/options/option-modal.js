/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Button, Modal } from '@wordpress/components';

export const OptionModal = ( props ) => {
	const [ value, setValue ] = useState( props.option.content );

	useEffect( () => {
		setValue( props.option.content );
	}, [ props.option ] );

	const handleChange = ( event ) => {
		setValue( event.target.value );
	};

	const handleSave = () => {
		props.onSave( props.option.name, value );
	};

	return (
		<Modal title="Edit option" onRequestClose={ props.onRequestClose }>
			<div className="wca-test-helper-option-modal-content">
				<label htmlFor="wca-test-helper-option-modal-name">Name:</label>
				<output id="wca-test-helper-option-modal-name">
					{ props.option.name }
				</output>
				<label htmlFor="wca-test-helper-option-editor">Value:</label>
				<textarea
					id="wca-test-helper-option-editor"
					value={ value }
					onChange={ handleChange }
				></textarea>
				<div className="wca-test-helper-option-modal-buttons">
					<Button
						isPrimary
						onClick={ handleSave }
						disabled={ props.option.isSaving === true }
					>
						{ props.option.isSaving ? 'Saving...' : 'Save' }
					</Button>
					<Button isSecondary onClick={ props.onRequestClose }>
						Cancel
					</Button>
				</div>
			</div>
		</Modal>
	);
};

OptionModal.propTypes = {
	option: PropTypes.object.isRequired,
	onRequestClose: PropTypes.func.isRequired,
	onSave: PropTypes.func.isRequired,
};
