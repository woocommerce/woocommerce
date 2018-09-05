/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Header from 'layout/header';

export default class extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			selected: [],
		};
		this.onChange = this.onChange.bind( this );
	}

	onChange( selected ) {
		this.setState( { selected } );
	}

	render() {
		return (
			<Fragment>
				<Header sections={ [ __( 'Analytics', 'wc-admin' ) ] } />
			</Fragment>
		);
	}
}
