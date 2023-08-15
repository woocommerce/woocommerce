// Ref: https://github.com/WordPress/gutenberg/blob/release/16.0/packages/edit-site/src/components/block-editor/index.js

/**
 * External dependencies
 */
import classNames from 'classnames';
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore, useEntityRecords } from '@wordpress/core-data';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import BlockPreview from './block-preview';

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

// We only show the edit option when page count is <= MAX_PAGE_COUNT
// Performance of Navigation Links is not good past this value.
const MAX_PAGE_COUNT = 100;

export default function BlockEditor( { blocks, template, onRemoveBlock } ) {
	const history = useHistory();
	const location = useLocation();
	const settings = useSettings( {
		templateType: template?.type,
	} );

	// // See packages/block-library/src/page-list/edit.js.
	const { records: pages } = useEntityRecords( 'postType', 'page', {
		per_page: MAX_PAGE_COUNT,
		_fields: [ 'id', 'link', 'menu_order', 'parent', 'title', 'type' ],
		// TODO: When https://core.trac.wordpress.org/ticket/39037 REST API support for multiple orderby
		// values is resolved, update 'orderby' to [ 'menu_order', 'post_title' ] to provide a consistent
		// sort.
		orderby: 'menu_order',
		order: 'asc',
	} );

	const onClickNavigationItem = ( event ) => {
		const clickedPage =
			pages.find( ( page ) => page.link === event.target.href ) ||
			// Fallback to page title if the link is not found. This is needed for a bug in the block library
			// See https://github.com/woocommerce/team-ghidorah/issues/253#issuecomment-1665106817
			pages.find(
				( page ) => page.title.rendered === event.target.innerText
			);
		if ( clickedPage ) {
			history.push( {
				...location.params,
				postId: clickedPage.id,
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
		let hasActionBar = false;
		switch ( true ) {
			case index === 0:
				// header
				additionalStyles = `
				.editor-styles-wrapper{ padding-top: var(--wp--style--root--padding-top) };'
			`;
				break;

			case index === blocks.length - 1:
				// footer
				additionalStyles = `
				.editor-styles-wrapper{ padding-bottom: var(--wp--style--root--padding-bottom) };
			`;
				break;
			default:
				hasActionBar = true;
		}

		return (
			<div
				key={ block.clientId }
				className={ classNames( 'woocommerce-block-preview-container', {
					'has-action-menu': hasActionBar,
				} ) }
			>
				<BlockPreview
					blocks={ block }
					settings={ settings }
					additionalStyles={ additionalStyles }
					onClickNavigationItem={ onClickNavigationItem }
				/>
				{ hasActionBar && (
					<div className="woocommerce-block-preview-container__action-bar">
						<Button
							className="pattern-action-bar__block pattern-action-bar__action"
							role="menuitem"
							variant="secondary"
							onClick={ () => {
								onRemoveBlock( block.clientId );
							} }
						>
							{ __( 'Remove', 'woocommerce' ) }
						</Button>
					</div>
				) }
			</div>
		);
	} );
}
