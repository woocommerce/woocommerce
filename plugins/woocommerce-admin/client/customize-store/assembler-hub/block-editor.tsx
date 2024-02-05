/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
// @ts-ignore No types for this exist yet.
import { store as blockEditorStore } from '@wordpress/block-editor';
// @ts-ignore No types for this exist yet.
import { useEntityRecords } from '@wordpress/core-data';
import { select } from '@wordpress/data';
// @ts-ignore No types for this exist yet.
import { privateApis as routerPrivateApis } from '@wordpress/router';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import useSiteEditorSettings from '@wordpress/edit-site/build-module/components/block-editor/use-site-editor-settings';
import { useQuery } from '@woocommerce/navigation';
import { useContext, useCallback, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import BlockPreview from './block-preview';
import { useEditorBlocks } from './hooks/use-editor-blocks';
import { useScrollOpacity } from './hooks/use-scroll-opacity';
import { CustomizeStoreContext } from './';
import { HighlightedBlockContext } from './context/highlighted-block-context';
import Iframe from './iframe';

const { useHistory } = unlock( routerPrivateApis );

type Page = {
	link: string;
	title: { rendered: string; raw: string };
	[ key: string ]: unknown;
};

const findPageIdByLinkOrTitle = ( event: MouseEvent, _pages: Page[] ) => {
	const target = event.target as HTMLAnchorElement;
	const clickedPage =
		_pages.find( ( page ) => page.link === target.href ) ||
		_pages.find( ( page ) => page.title.rendered === target.innerText );
	return clickedPage ? clickedPage.id : null;
};

const findPageIdByBlockClientId = ( event: MouseEvent ) => {
	const navLink = ( event.target as HTMLAnchorElement ).closest(
		'.wp-block-navigation-link'
	);
	if ( navLink ) {
		const blockClientId = navLink.getAttribute( 'data-block' );
		const navLinkBlocks =
			// @ts-ignore No types for this exist yet.
			select( blockEditorStore ).getBlocksByClientId( blockClientId );

		if ( navLinkBlocks && navLinkBlocks.length ) {
			return navLinkBlocks[ 0 ].attributes.id;
		}
	}
	return null;
};

// We only show the edit option when page count is <= MAX_PAGE_COUNT
// Performance of Navigation Links is not good past this value.
const MAX_PAGE_COUNT = 100;

export const BlockEditor = ( {} ) => {
	const history = useHistory();
	const settings = useSiteEditorSettings();
	const [ blocks, , onChange ] = useEditorBlocks();
	const urlParams = useQuery();
	const { currentState } = useContext( CustomizeStoreContext );

	const scrollDirection =
		urlParams.path === '/customize-store/assembler-hub/footer'
			? 'bottomUp'
			: 'topDown';

	const previewOpacity = useScrollOpacity(
		'.woocommerce-customize-store__block-editor iframe',
		scrollDirection
	);

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

	const onClickNavigationItem = useCallback(
		( event: MouseEvent ) => {
			// If the user clicks on a navigation item, we want to update the URL to reflect the page they are on.
			// Because of bug in the block library (See https://github.com/woocommerce/team-ghidorah/issues/253#issuecomment-1665106817), we're not able to use href link to find the page ID. Instead, we'll use the link/title first, and if that doesn't work, we'll use the block client ID. It depends on the header block type to determine which one to use.
			// This is a temporary solution until the block library is fixed.

			const pageId =
				findPageIdByLinkOrTitle( event, pages ) ||
				findPageIdByBlockClientId( event );

			if ( pageId ) {
				history.push( {
					...urlParams,
					postId: pageId,
					postType: 'page',
				} );
				return;
			}

			// Home page
			const { postId, postType, ...params } = urlParams;
			history.push( {
				...params,
			} );
		},
		[ history, urlParams, pages ]
	);

	const { highlightedBlockIndex } = useContext( HighlightedBlockContext );
	const isHighlighting = highlightedBlockIndex !== -1;
	const additionalStyles = isHighlighting
		? `
		.wp-block.preview-opacity {
			opacity: ${ previewOpacity };
		}
	`
		: '';

	const renderedBlocks = useMemo(
		() =>
			blocks.map( ( block, i ) => {
				if ( ! isHighlighting || i === highlightedBlockIndex ) {
					return block;
				}

				return {
					...block,
					attributes: {
						...block.attributes,
						className:
							block.attributes.className + ' preview-opacity',
					},
				};
			} ),
		[ blocks, highlightedBlockIndex, isHighlighting ]
	);

	return (
		<div className="woocommerce-customize-store__block-editor">
			<div className={ 'woocommerce-block-preview-container' }>
				<BlockPreview
					blocks={ renderedBlocks }
					onChange={
						// We need to pass onChange for the logo screen so that logo block can be updated when we change the logo attributes in logo sidebar navigation screen component.
						// We also need to pass onChange for the assembler hub screen so when a block set an attribute during the block initialization, the block editor will be updated.
						// For other screens, we don't need to pass onChange. Otherwise, we'll get a race condition issue where the block editor will be updated twice: once from the onChange in the sidebar component, and once from the onChange in the block editor component.
						[
							'/customize-store/assembler-hub/logo',
							'/customize-store/assembler-hub',
						].includes( urlParams.path )
							? onChange
							: undefined
					}
					settings={ settings }
					additionalStyles={ additionalStyles }
					isNavigable={ false }
					isScrollable={
						// Disable scrollable for transitional screen
						! (
							typeof currentState === 'object' &&
							currentState.transitionalScreen === 'transitional'
						)
					}
					onClickNavigationItem={ onClickNavigationItem }
					// Don't use sub registry so that we can get the logo block from the main registry on the logo sidebar navigation screen component.
					useSubRegistry={ false }
					autoScale={ false }
					setLogoBlockContext={ true }
					CustomIframeComponent={ Iframe }
				/>
			</div>
		</div>
	);
};
