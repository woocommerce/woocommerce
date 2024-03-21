/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
// @ts-expect-error No types for this exist yet.
import { store as blockEditorStore } from '@wordpress/block-editor';
// @ts-expect-error No types for this exist yet.
import { store as coreStore, useEntityRecords } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
// @ts-expect-error No types for this exist yet.
import { privateApis as routerPrivateApis } from '@wordpress/router';
// @ts-expect-error No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import { useQuery } from '@woocommerce/navigation';
// @ts-expect-error No types for this exist yet.
import useSiteEditorSettings from '@wordpress/edit-site/build-module/components/block-editor/use-site-editor-settings';
import {
	useCallback,
	useContext,
	useEffect,
	useMemo,
} from '@wordpress/element';
// @ts-expect-error No types for this exist yet.
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';

/**
 * Internal dependencies
 */
import { CustomizeStoreContext } from './';
import { BlockEditor } from './block-editor';
import { HighlightedBlockContext } from './context/highlighted-block-context';
import { useEditorBlocks } from './hooks/use-editor-blocks';
import { useScrollOpacity } from './hooks/use-scroll-opacity';

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
			// @ts-expect-error No types for this exist yet.
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

export const BlockEditorContainer = () => {
	const history = useHistory();
	const settings = useSiteEditorSettings();

	const currentTemplate:
		| {
				id: string;
		  }
		| undefined = useSelect(
		( select ) =>
			// @ts-expect-error No types for this exist yet.
			select( coreStore ).__experimentalGetTemplateForLink( '/' ),
		[]
	);

	// This is necessary to avoid this issue: https://github.com/woocommerce/woocommerce/issues/45593
	// Related PR: https://github.com/woocommerce/woocommerce/pull/45600
	const { templateType } = useSelect( ( select ) => {
		const { getEditedPostType } = unlock( select( editSiteStore ) );

		return {
			templateType: getEditedPostType(),
		};
	}, [] );

	const [ blocks, , onChange ] = useEditorBlocks(
		templateType,
		currentTemplate?.id ?? ''
	);

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

	const { highlightedBlockClientId } = useContext( HighlightedBlockContext );
	const isHighlighting = highlightedBlockClientId !== null;
	const additionalStyles = isHighlighting
		? `
		.wp-block.preview-opacity {
			opacity: ${ previewOpacity };
		}
	`
		: '';

	const opacityClass = 'preview-opacity';

	const clientIds = blocks.map( ( block ) => block.clientId );

	// @ts-expect-error No types for this exist yet.
	const { updateBlockAttributes } = useDispatch( blockEditorStore );

	useEffect( () => {
		const { blockIdToHighlight, restOfBlockIds } = clientIds.reduce(
			( acc, clientId ) => {
				if (
					! isHighlighting ||
					clientId === highlightedBlockClientId
				) {
					return {
						blockIdToHighlight: clientId,
						restOfBlockIds: acc.restOfBlockIds,
					};
				}

				return {
					blockIdToHighlight: acc.blockIdToHighlight,
					restOfBlockIds: [ ...acc.restOfBlockIds, clientId ],
				};
			},
			{
				blockIdToHighlight: null,
				restOfBlockIds: [],
			} as {
				blockIdToHighlight: string | null;
				restOfBlockIds: string[];
			}
		);

		updateBlockAttributes( blockIdToHighlight, {
			className: '',
		} );

		updateBlockAttributes( restOfBlockIds, {
			className: ` ${ opacityClass }`,
		} );
	}, [
		clientIds,
		highlightedBlockClientId,
		isHighlighting,
		updateBlockAttributes,
	] );

	const isScrollable = useMemo(
		() =>
			// Disable scrollable for transitional screen
			! (
				typeof currentState === 'object' &&
				currentState.transitionalScreen === 'transitional'
			),
		[ currentState ]
	);

	return (
		<BlockEditor
			renderedBlocks={ blocks }
			isScrollable={ isScrollable }
			onChange={ onChange }
			settings={ settings }
			additionalStyles={ additionalStyles }
			onClickNavigationItem={ onClickNavigationItem }
		/>
	);
};
