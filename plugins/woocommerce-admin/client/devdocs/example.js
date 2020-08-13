/**
 * External dependencies
 */
import { Component, createElement } from '@wordpress/element';

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
			exampleComponent = require( `../../packages/components/src/${ this.props.filePath }/docs/example` );
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( e );
		}

		if ( ! exampleComponent ) {
			return;
		}

		this.setState( {
			example: createElement(
				exampleComponent.default || exampleComponent
			),
		} );
	}

	render() {
		const { example } = this.state;

		return <div className="woocommerce-devdocs__example">{ example }</div>;
	}
}

export default Example;
