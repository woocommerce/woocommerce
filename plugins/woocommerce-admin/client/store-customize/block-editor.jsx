// Ref: https://github.com/WordPress/gutenberg/blob/release/16.0/packages/edit-site/src/components/block-editor/index.js

/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { unlock } from '@wordpress/edit-site/build-module/private-apis';

/**
 * Internal dependencies
 */
import { BlockPreview } from './block-preview';
const { useHistory, useLocation } = unlock( routerPrivateApis );

const useSettings = ( { templateType } ) => {
	const { restBlockPatterns, restBlockPatternCategories } = useSelect(
		( select ) => ( {
			restBlockPatterns: select( coreStore ).getBlockPatterns(),
			restBlockPatternCategories:
				select( coreStore ).getBlockPatternCategories(),
		} ),
		[]
	);

	const storedSettings = window.blockSettings;

	const settingsBlockPatterns =
		storedSettings.__experimentalAdditionalBlockPatterns ?? // WP 6.0
		storedSettings.__experimentalBlockPatterns; // WP 5.9
	const settingsBlockPatternCategories =
		storedSettings.__experimentalAdditionalBlockPatternCategories ?? // WP 6.0
		storedSettings.__experimentalBlockPatternCategories; // WP 5.9

	const blockPatterns = useMemo(
		() =>
			[
				...( settingsBlockPatterns || [] ),
				...( restBlockPatterns || [] ),
			]
				.filter(
					( x, index, arr ) =>
						index === arr.findIndex( ( y ) => x.name === y.name )
				)
				.filter( ( { postTypes } ) => {
					return (
						! postTypes ||
						( Array.isArray( postTypes ) &&
							postTypes.includes( templateType ) )
					);
				} ),
		[ settingsBlockPatterns, restBlockPatterns, templateType ]
	);

	const blockPatternCategories = useMemo(
		() =>
			[
				...( settingsBlockPatternCategories || [] ),
				...( restBlockPatternCategories || [] ),
			].filter(
				( x, index, arr ) =>
					index === arr.findIndex( ( y ) => x.name === y.name )
			),
		[ settingsBlockPatternCategories, restBlockPatternCategories ]
	);

	const settings = useMemo( () => {
		const {
			__experimentalAdditionalBlockPatterns,
			__experimentalAdditionalBlockPatternCategories,
			...restStoredSettings
		} = storedSettings;

		return {
			...restStoredSettings,
			__experimentalBlockPatterns: blockPatterns,
			__experimentalBlockPatternCategories: blockPatternCategories,
		};
	}, [ storedSettings, blockPatterns, blockPatternCategories ] );

	return settings;
};

export default function BlockEditor( { blocks, template } ) {
	const history = useHistory();
	const location = useLocation();
	const settings = useSettings( {
		templateType: template?.type,
	} );

	const onClickNavigationItem = ( event ) => {
		if ( ! event.target.href ) {
			return;
		}

		if ( event.target.href.includes( 'page_id' ) ) {
			// Navigate to a page
			const urlParams = new URLSearchParams(
				new URL( event.target.href ).search
			);
			const pageId = urlParams.get( 'page_id' );

			history.push( {
				...location.params,
				postId: pageId,
				postType: 'page',
			} );
		} else {
			// Home page
			const { postId, postType, ...params } = location.params;
			history.push( {
				...params,
			} );
		}
	};

	return blocks.map( ( block, index ) => {
		// Add padding to the top and bottom of the block preview.
		let additionalStyles = '';
		if ( index === 0 ) {
			additionalStyles = `
				.editor-styles-wrapper{ padding-top: var(--wp--style--root--padding-top) };'
			`;
		} else if ( index === blocks.length - 1 ) {
			additionalStyles = `
				.editor-styles-wrapper{ padding-bottom: var(--wp--style--root--padding-bottom) };
			`;
		}

		return (
			<BlockPreview
				key={ block.clientId }
				blocks={ [ block ] }
				settings={ settings }
				additionalStyles={ additionalStyles }
				onClickNavigationItem={ onClickNavigationItem }
			/>
		);
	} );
}
