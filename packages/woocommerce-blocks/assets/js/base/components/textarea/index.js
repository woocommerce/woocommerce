/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

const Textarea = ( {
	className = '',
	disabled = false,
	onTextChange,
	placeholder,
	value = '',
} ) => {
	return (
		<textarea
			className={ classnames(
				'wc-block-components-textarea',
				className
			) }
			disabled={ disabled }
			onChange={ ( event ) => {
				onTextChange( event.target.value );
			} }
			placeholder={ placeholder }
			rows={ 2 }
			value={ value }
		/>
	);
};

Textarea.propTypes = {
	onTextChange: PropTypes.func.isRequired,
	disabled: PropTypes.bool,
	placeholder: PropTypes.string,
	value: PropTypes.string,
};

export default Textarea;
