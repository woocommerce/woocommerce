/** @format */
/**
 * External dependencies
 */
import codeBlocks from 'gfm-code-blocks';
import { Component } from '@wordpress/element';
// import { LiveError, LivePreview, LiveProvider } from 'react-live';
// Used to provide scope in LivePreview
import { addFilter } from '@wordpress/hooks';
import { withState } from '@wordpress/compose';
import { getSettings } from '@wordpress/date';
import * as wpComponents from '@wordpress/components';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import * as components from '@woocommerce/components';

class Example extends Component {
	state = {
		code: null,
	};

	componentDidMount() {
		this.getCode();
	}

	async getCode() {
		const readme = require( `components/${ this.props.filePath }/example.md` );

		// Example to render is the first jsx code block that appears in the readme
		let code = codeBlocks( readme ).find( block => 'jsx' === block.lang ).code;

		// react-live cannot resolve imports in real time, so we get rid of them
		// (dependencies will be injected via the scope property).
		code = code.replace( /^.*import.*$/gm, '' );

		code = `${ code } render( <${ this.props.render } /> );`;

		this.setState( { code } );
	}

	render() {
		/*eslint-disable */
		const { code } = this.state;
		const scope = {
			...wpComponents,
			...components,
			Component,
			withState,
			getSettings,
			PropTypes,
			addFilter,
			Gridicon,
		};

		return null;

		return code ? (
			<LiveProvider
				code={ code }
				scope={ scope }
				className="woocommerce-devdocs__example"
				noInline={ true }
			>
				<LiveError />
				<LivePreview />
			</LiveProvider>
		) : null;
		/*eslint-enable */
	}
}

export default Example;
