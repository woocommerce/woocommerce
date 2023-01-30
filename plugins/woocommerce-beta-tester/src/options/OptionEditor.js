/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Button } from '@wordpress/components';

const OptionEditor = ( props ) => {
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
		<>
			<textarea
				className="wca-test-helper-option-editor"
				value={ value }
				onChange={ handleChange }
			></textarea>
			<Button
				className="wca-test-helper-edit-btn-save"
				isPrimary
				onClick={ handleSave }
				disabled={ props.option.isSaving === true }
			>
				{ props.option.isSaving ? 'Saving...' : 'Save' }
			</Button>
			<div className="clear"></div>
		</>
	);
};

OptionEditor.propTypes = {
	option: PropTypes.object.isRequired,
	onSave: PropTypes.func.isRequired,
};

export default OptionEditor;
