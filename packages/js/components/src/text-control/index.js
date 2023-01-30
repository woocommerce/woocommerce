/**
 * External dependencies
 */
import classnames from 'classnames';
import { createElement, Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import {
	TextControl as BaseComponent,
	withFocusOutside,
} from '@wordpress/components';

/**
 * An input field use for text inputs in forms.
 */
const TextControl = withFocusOutside(
	class extends Component {
		constructor( props ) {
			super( props );
			this.state = {
				isFocused: false,
			};
		}

		handleFocusOutside() {
			this.setState( { isFocused: false } );
		}

		handleOnClick( event, onClick ) {
			this.setState( { isFocused: true } );
			if ( typeof onClick === 'function' ) {
				onClick( event );
			}
		}

		render() {
			const { isFocused } = this.state;
			const { className, onClick, ...otherProps } = this.props;
			const { label, value, disabled } = otherProps;
			const isEmpty = value === '';
			const isActive = isFocused && ! disabled;

			return (
				<BaseComponent
					className={ classnames(
						'muriel-component',
						'muriel-input-text',
						className,
						{
							disabled,
							empty: isEmpty,
							active: isActive,
							'with-value': ! isEmpty,
						}
					) }
					placeholder={ label }
					onClick={ ( event ) =>
						this.handleOnClick( event, onClick )
					}
					onFocus={ () => this.setState( { isFocused: true } ) }
					{ ...otherProps }
				/>
			);
		}
	}
);

TextControl.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * Disables the field.
	 */
	disabled: PropTypes.bool,
	/**
	 * Input label used as a placeholder.
	 */
	label: PropTypes.string,
	/**
	 * On click handler called when the component is clicked, passed the click event.
	 */
	onClick: PropTypes.func,
	/**
	 * The value of the input field.
	 */
	value: PropTypes.string,
};

export default TextControl;
