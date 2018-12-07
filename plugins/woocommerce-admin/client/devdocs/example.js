/** @format */
/**
 * External dependencies
 */
import codeBlocks from 'gfm-code-blocks';
import { Component } from '@wordpress/element';
import { LiveError, LivePreview, LiveProvider } from 'react-live';
// Used to provide scope in LivePreview
import { addFilter } from '@wordpress/hooks';
import { withState } from '@wordpress/compose';
import { __experimentalGetSettings } from '@wordpress/date';
import * as wpComponents from '@wordpress/components';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import * as components from 'components';
import * as pkgComponents from '@woocommerce/components';

class Example extends Component {
	state = {
		code: null,
	};

	componentDidMount() {
		this.getCode();
	}

	async getCode() {
		let readme;
		try {
			readme = require( `components/src/${ this.props.filePath }/example.md` );
		} catch ( e ) {
			readme = require( `components/${ this.props.filePath }/example.md` );
		}
		if ( ! readme ) {
			return;
		}

		// Example to render is the first jsx code block that appears in the readme
		let code = codeBlocks( readme ).find( block => 'jsx' === block.lang ).code;

		// react-live cannot resolve imports in real time, so we get rid of them
		// (dependencies will be injected via the scope property).
		code = code.replace( /^.*import.*$/gm, '' );

		code = `${ code } render( <${ this.props.render } /> );`;

		this.setState( { code } );
	}

	render() {
		const { code } = this.state;
		const scope = {
			...wpComponents,
			...components,
			...pkgComponents,
			Component,
			withState,
			getSettings: __experimentalGetSettings,
			PropTypes,
			addFilter,
			Gridicon,
		};

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
	}
}

export default Example;
