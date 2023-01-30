/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';

const Form = ( {
	className,
	children,
	onSubmit = ( event ) => void event,
} ) => {
	const formOnSubmit = ( event ) => {
		event.preventDefault();
		onSubmit( event );
	};

	return (
		<form
			className={ classnames( 'wc-block-components-form', className ) }
			onSubmit={ formOnSubmit }
		>
			{ children }
		</form>
	);
};

Form.propTypes = {
	className: PropTypes.string,
	children: PropTypes.node,
	onSubmit: PropTypes.func,
};

export default Form;
