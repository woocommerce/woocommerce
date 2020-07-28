/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import marked from 'marked';
import Prism from 'prismjs';
import 'prismjs/components/prism-jsx';

// Alias `javascript` language to `es6`
Prism.languages.es6 = Prism.languages.javascript;

// Configure marked to use Prism for code-block highlighting.
marked.setOptions( {
	highlight( code, language ) {
		const syntax = Prism.languages[ language ];
		return syntax ? Prism.highlight( code, syntax ) : code;
	},
} );

class Docs extends Component {
	state = {
		readme: null,
	};

	componentDidMount() {
		this.getReadme();
	}

	getReadme() {
		const { filePath } = this.props;
		const readme = require( `../../packages/components/src/${ filePath }/README.md` )
			.default;

		if ( ! readme ) {
			return;
		}

		this.setState( { readme } );
	}

	render() {
		const { readme } = this.state;
		if ( ! readme ) {
			return null;
		}
		return (
			<div
				className="woocommerce-devdocs__docs"
				//eslint-disable-next-line react/no-danger
				dangerouslySetInnerHTML={ { __html: marked( readme ) } }
			/>
		);
	}
}

export default Docs;
