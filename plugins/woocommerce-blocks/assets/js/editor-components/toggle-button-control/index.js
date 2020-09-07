/**
 * External dependencies
 */
import { isFunction } from 'lodash';
import classnames from 'classnames';
import { BaseControl, ButtonGroup, Button } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { withInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import './style.scss';

class ToggleButtonControl extends Component {
	constructor() {
		super( ...arguments );

		this.onClick = this.onClick.bind( this );
	}

	onClick( event ) {
		if ( this.props.onChange ) {
			this.props.onChange( event.target.value );
		}
	}

	render() {
		const {
			label,
			checked,
			instanceId,
			className,
			help,
			options,
			value,
		} = this.props;
		const id = `inspector-toggle-button-control-${ instanceId }`;

		let helpLabel;

		if ( help ) {
			helpLabel = isFunction( help ) ? help( checked ) : help;
		}

		return (
			<BaseControl
				id={ id }
				help={ helpLabel }
				className={ classnames(
					'components-toggle-button-control',
					className
				) }
			>
				<label
					id={ id + '__label' }
					htmlFor={ id }
					className="components-toggle-button-control__label"
				>
					{ label }
				</label>
				<ButtonGroup aria-labelledby={ id + '__label' }>
					{ options.map( ( option, index ) => {
						const buttonArgs = {};

						// Change button depending on pressed state.
						if ( value === option.value ) {
							buttonArgs.isPrimary = true;
							buttonArgs[ 'aria-pressed' ] = true;
						} else {
							buttonArgs.isDefault = true;
							buttonArgs[ 'aria-pressed' ] = false;
						}

						return (
							<Button
								key={ `${ option.label }-${ option.value }-${ index }` }
								value={ option.value }
								onClick={ this.onClick }
								aria-label={ label + ': ' + option.label }
								{ ...buttonArgs }
							>
								{ option.label }
							</Button>
						);
					} ) }
				</ButtonGroup>
			</BaseControl>
		);
	}
}

export default withInstanceId( ToggleButtonControl );
