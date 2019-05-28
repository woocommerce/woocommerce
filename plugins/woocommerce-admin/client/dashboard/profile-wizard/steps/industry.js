/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { SelectControl, TextControl, Button } from 'newspack-components';

class Industry extends Component {
	constructor() {
		super();
		this.state = {
			textInput: '',
			selectValue1: '1st',
			selectValue2: '',
		};
	}
	render() {
		return (
			<Fragment>
				<TextControl
					label={ 'Text Input' }
					value={ this.state.textInput }
					onChange={ value => {
						this.setState( { textInput: value } );
					} }
				/>

				<SelectControl
					label="Select with value"
					value={ this.state.selectValue1 }
					options={ [
						{ value: '1st', label: 'First' },
						{ value: '2nd', label: 'Second' },
						{ value: '3rd', label: 'Third' },
					] }
					onChange={ value => this.setState( { selectValue1: value } ) }
				/>

				<SelectControl
					label="Select empty"
					value={ this.state.selectValue2 }
					options={ [
						{ value: '1st', label: 'First' },
						{ value: '2nd', label: 'Second' },
						{ value: '3rd', label: 'Third' },
					] }
					onChange={ value => this.setState( { selectValue2: value } ) }
				/>

				<Button isPrimary>Button</Button>
			</Fragment>
		);
	}
}

export default Industry;
