/** @format */
/**
 * Internal dependencies
 */
import { SimpleSelectControl } from '@woocommerce/components';

/**
 * External dependencies
 */
import { Component } from '@wordpress/element';

export default class MySimpleSelectControl extends Component {
	constructor() {
		super();
		this.state = {
			pet: '',
		};
	}

	render() {
		const { pet } = this.state;

		const petOptions = [
			{
				value: 'cat',
				label: 'Cat',
			},
			{
				value: 'dog',
				label: 'Dog',
			},
			{
				value: 'bunny',
				label: 'Bunny',
			},
			{
				value: 'snake',
				label: 'Snake',
			},
			{
				value: 'chinchilla',
				label: 'Chinchilla',
				disabled: true,
			},
		];

		return (
			<SimpleSelectControl
				label="What is your favorite pet?"
				onChange={ value => this.setState( { pet: value } ) }
				options={ petOptions }
				value={ pet }
			/>
		);
	}
}
