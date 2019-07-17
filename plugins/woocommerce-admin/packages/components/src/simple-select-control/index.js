/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Dropdown, Button, NavigableMenu, withFocusOutside } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { map, find } from 'lodash';
import classNames from 'classnames';
import PropTypes from 'prop-types';

/**
 * A component for displaying a material styled 'simple' select control.
 */
class SimpleSelectControl extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			currentValue: props.value,
			isFocused: false,
		};
	}

	componentDidUpdate( prevProps ) {
		if (
			this.props.value !== prevProps.value &&
			this.state.currentValue !== this.props.value
		) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				currentValue: this.props.value,
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	handleFocusOutside() {
		this.setState( { isFocused: false } );
	}

	handleOnClick( onToggle ) {
		this.setState( { isFocused: true } );
		if ( 'function' === typeof onToggle ) {
			onToggle();
		}
		const { onClick } = this.props;
		if ( 'function' === typeof onClick ) {
			onClick();
		}
	}

	handleOnFocus() {
		this.setState( { isFocused: true } );
		const { onFocus } = this.props;
		if ( 'function' === typeof onFocus ) {
			onFocus();
		}
	}

	onChange( value ) {
		this.props.onChange( value );
		this.setState( { currentValue: value } );
	}

	render() {
		const { options, label, className } = this.props;
		const { currentValue, isFocused } = this.state;
		const onChange = ( value ) => {
			this.onChange( value );
			this.handleFocusOutside();
		};

		const isEmpty = currentValue === '' || currentValue === null;
		const currentOption = find( options, ( { value } ) => value === currentValue );

		return (
			<Dropdown
				className={ classNames(
					'woocommerce-simple-select-control__dropdown',
					'components-base-control',
					className,
					{
						'is-empty': isEmpty,
						'has-value': ! isEmpty,
						'is-active': isFocused,
					}
				) }
				contentClassName="woocommerce-simple-select-control__dropdown-content"
				position="center"
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						className="woocommerce-simple-select-control__selector"
						onClick={ () => this.handleOnClick( onToggle ) }
						onFocus={ () => this.handleOnFocus() }
						aria-expanded={ isOpen }
						aria-label={ ! isEmpty ? sprintf(
							/* translators: Label: Current Value for a Select Dropddown */
							__( '%s: %s' ), label, currentOption && currentOption.label
						) : label }
					>
						<span className="woocommerce-simple-select-control__label">{ label }</span>
						<span className="woocommerce-simple-select-control__value">{ currentOption && currentOption.label }</span>
					</Button>
				) }
				renderContent={ ( { onClose } ) => (
					<NavigableMenu>
						{ map( options, ( option ) => {
							const optionValue = option.value;
							const optionLabel = option.label;
							const optionDisabled = option.disabled || false;
							const isSelected = ( currentValue === optionValue );
							return (
								<Button
									key={ optionValue }
									onClick={ () => {
										onChange( optionValue );
										onClose();
									} }
									className={ classNames( {
										'is-selected': isSelected,
									} ) }
									disabled={ optionDisabled }
									role="menuitemradio"
									aria-checked={ isSelected }
								>
									<span>
										{ optionLabel }
									</span>
								</Button>
							);
						} ) }
					</NavigableMenu>
				) }
			/>
		);
	}
}

SimpleSelectControl.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * A label to use for the main select element.
	 */
	label: PropTypes.string,
	/**
	 * An array of options to use for the dropddown.
	 */
	options: PropTypes.arrayOf(
		PropTypes.shape( {
			/**
			 * Input value for this option.
			 */
			value: PropTypes.string,
			/**
			 * Label for this option.
			 */
			label: PropTypes.string,
			/**
			 * Disable the option.
			 */
			disabled: PropTypes.bool,
		} )
	),
	/**
	 * A function that receives the value of the new option that is being selected as input.
	 */
	onChange: PropTypes.func,
	/**
	 * The currently value of the select element.
	 */
	value: PropTypes.string,
};

export default withFocusOutside( SimpleSelectControl );
