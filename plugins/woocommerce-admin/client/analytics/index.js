/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Header from 'components/header';
import SegmentedSelection from 'components/segmented-selection';

export default class extends Component {
	constructor() {
		super();
		this.state = {
			numbers: [],
		};
		this.onSelect = this.onSelect.bind( this );
	}

	onSelect( key, value ) {
		this.setState( { [ key ]: value } );
	}

	render() {
		return (
			<Fragment>
				<Header sections={ [ __( 'Analytics', 'woo-dash' ) ] } />
				<div
					style={ {
						width: '300px',
						padding: '20px',
						border: '1px solid lightgray',
						backgroundColor: 'white',
					} }
				>
					<h2>Example</h2>
					<SegmentedSelection
						options={ [
							{ value: 'one', label: 'One' },
							{ value: 'two', label: 'Two' },
							{ value: 'three', label: 'Three' },
							{ value: 'four', label: 'Four' },
						] }
						selected={ this.state.numbers }
						onSelect={ this.onSelect }
						legend="Select a number"
						name="numbers"
					/>
				</div>
			</Fragment>
		);
	}
}
