/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import React from 'react';

class Example extends Component {
	state = {
		example: null,
	};

	componentDidMount() {
		this.getExample();
	}

	async getExample() {
		let exampleComponent;

		try {
			exampleComponent = require( `components/src/${ this.props.filePath }/docs/example` );
		} catch ( e ) {
			console.error( e );
		}

		if ( ! exampleComponent ) {
			return;
		}

		this.setState( {
			example: React.createElement( exampleComponent.default || exampleComponent ),
		} );
	}

	render() {
		const { example } = this.state;

		return <div className="woocommerce-devdocs__example">{ example }</div>;
	}
}

export default Example;
