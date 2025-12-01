/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { Params } from 'react-router-dom';

export type Page = {
	container?: JSX.Element;
	path?: string;
	breadcrumbs:
		| string[]
		| ( ( {
				match,
		  }: {
				match: {
					params: Params;
					url?: string;
				};
		  } ) => string[] );
	wpOpenMenu?: string;
	navArgs?: {
		id: string;
	};
	capability?: string;
	layout?: {
		header: boolean;
		footer: boolean;
		showNotices: boolean;
		showStoreAlerts: boolean;
		showPluginArea: boolean;
	};
};

export function usePageClasses( page: Page ) {
	function convertCamelCaseToKebabCase( str: string ) {
		return str.replace(
			/[A-Z]/g,
			( letter ) => `-${ letter.toLowerCase() }`
		);
	}

	function getPathClassName( path: string ) {
		const suffix =
			path === '/'
				? '_home'
				: path
						.replace( /:[a-zA-Z?]+/g, function ( match ) {
							return convertCamelCaseToKebabCase( match ).replace(
								':',
								''
							);
						} )
						.replace( /\//g, '_' );

		return `woocommerce-admin-page_${ suffix }`;
	}

	useEffect( () => {
		if ( ! page.path ) {
			return;
		}

		const classes = getPathClassName( page.path );

		document.body.classList.add( classes );
		return () => {
			document.body.classList.remove( classes );
		};
	}, [ page.path ] );
}
