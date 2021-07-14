/**
 * External dependencies
 */
import { createElement, Component } from '@wordpress/element';
import { compose, withInstanceId } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { BaseControl, withFocusOutside } from '@wordpress/components';
import classnames from 'classnames';

/**
 * This component is essentially a wrapper (really a reimplementation) around the
 * TextControl component that adds support for affixes, i.e. the ability to display
 * a fixed part either at the beginning or at the end of the text input.
 */
class TextControlWithAffixes extends Component {
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
		const {
			label,
			value,
			help,
			className,
			instanceId,
			onChange,
			onClick,
			prefix,
			suffix,
			type,
			disabled,
			...props
		} = this.props;
		const { isFocused } = this.state;

		const id = `inspector-text-control-with-affixes-${ instanceId }`;
		const onChangeValue = ( event ) => onChange( event.target.value );
		const describedby = [];
		if ( help ) {
			describedby.push( `${ id }__help` );
		}
		if ( prefix ) {
			describedby.push( `${ id }__prefix` );
		}
		if ( suffix ) {
			describedby.push( `${ id }__suffix` );
		}

		const baseControlClasses = classnames( className, {
			'with-value': value !== '',
			empty: value === '',
			active: isFocused && ! disabled,
		} );

		const affixesClasses = classnames( 'text-control-with-affixes', {
			'text-control-with-prefix': prefix,
			'text-control-with-suffix': suffix,
			disabled,
		} );

		return (
			<BaseControl
				label={ label }
				id={ id }
				help={ help }
				className={ baseControlClasses }
				onClick={ ( event ) => this.handleOnClick( event, onClick ) }
			>
				<div className={ affixesClasses }>
					{ prefix && (
						<span
							id={ `${ id }__prefix` }
							className="text-control-with-affixes__prefix"
						>
							{ prefix }
						</span>
					) }

					<input
						className="components-text-control__input"
						type={ type }
						id={ id }
						value={ value }
						onChange={ onChangeValue }
						aria-describedby={ describedby.join( ' ' ) }
						disabled={ disabled }
						onFocus={ () => this.setState( { isFocused: true } ) }
						{ ...props }
					/>

					{ suffix && (
						<span
							id={ `${ id }__suffix` }
							className="text-control-with-affixes__suffix"
						>
							{ suffix }
						</span>
					) }
				</div>
			</BaseControl>
		);
	}
}

TextControlWithAffixes.defaultProps = {
	type: 'text',
};

TextControlWithAffixes.propTypes = {
	/**
	 * If this property is added, a label will be generated using label property as the content.
	 */
	label: PropTypes.string,
	/**
	 * If this property is added, a help text will be generated using help property as the content.
	 */
	help: PropTypes.string,
	/**
	 * Type of the input element to render. Defaults to "text".
	 */
	type: PropTypes.string,
	/**
	 * The current value of the input.
	 */
	value: PropTypes.string.isRequired,
	/**
	 * The class that will be added with "components-base-control" to the classes of the wrapper div.
	 * If no className is passed only components-base-control is used.
	 */
	className: PropTypes.string,
	/**
	 * A function that receives the value of the input.
	 */
	onChange: PropTypes.func.isRequired,
	/**
	 * Markup to be inserted at the beginning of the input.
	 */
	prefix: PropTypes.node,
	/**
	 * Markup to be appended at the end of the input.
	 */
	suffix: PropTypes.node,
	/**
	 * Whether or not the input is disabled.
	 */
	disabled: PropTypes.bool,
};

export default compose( [
	withInstanceId,
	withFocusOutside, // this MUST be the innermost HOC as it calls handleFocusOutside
] )( TextControlWithAffixes );
