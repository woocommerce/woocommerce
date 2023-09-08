/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import classNames from 'classnames';
import { useSelect } from '@wordpress/data';
// @ts-ignore No types for this exist yet.
import { useEntityRecords, useEntityBlockEditor } from '@wordpress/core-data';
// @ts-ignore No types for this exist yet.
import { privateApis as routerPrivateApis } from '@wordpress/router';
// @ts-ignore No types for this exist yet.
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import useSiteEditorSettings from '@wordpress/edit-site/build-module/components/block-editor/use-site-editor-settings';
import { BlockInstance } from '@wordpress/blocks';
/**
 * Internal dependencies
 */
import BlockPreview from './block-preview';
import { useCallback } from '@wordpress/element';

const { useHistory, useLocation } = unlock( routerPrivateApis );

type Page = {
	link: string;
	title: { rendered: string; raw: string };
	[ key: string ]: unknown;
};

// We only show the edit option when page count is <= MAX_PAGE_COUNT
// Performance of Navigation Links is not good past this value.
const MAX_PAGE_COUNT = 100;

export const BlockEditor = ( {} ) => {
	const history = useHistory();
	const location = useLocation();
	const settings = useSiteEditorSettings();

	const { templateType } = useSelect( ( select ) => {
		const { getEditedPostType } = unlock( select( editSiteStore ) );

		return {
			templateType: getEditedPostType(),
		};
	}, [] );

	const [ blocks ]: [ BlockInstance[] ] = useEntityBlockEditor(
		'postType',
		templateType
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
			const clickedPage =
				pages.find(
					( page: Page ) =>
						page.link === ( event.target as HTMLAnchorElement ).href
				) ||
				// Fallback to page title if the link is not found. This is needed for a bug in the block library
				// See https://github.com/woocommerce/team-ghidorah/issues/253#issuecomment-1665106817
				pages.find(
					( page: Page ) =>
						page.title.rendered ===
						( event.target as HTMLAnchorElement ).innerText
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
		},
		[ history, location.params, pages ]
	);

	if ( location.params.path === '/customize-store/homepage' ) {
		// When assembling the homepage preview, we need to render the blocks in a different way than the rest of the pages.
		// Because we want to show a action bar when hovering over a pattern. This is not needed for the rest of the pages and will cause an issue with logo editing.
		return (
			<div className="woocommerce-customize-store__block-editor">
				{ blocks.map( ( block, index ) => {
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
							className={ classNames(
								'woocommerce-block-preview-container',
								{
									'has-action-menu': hasActionBar,
								}
							) }
						>
							<BlockPreview
								blocks={ block }
								settings={ settings }
								additionalStyles={ additionalStyles }
								onClickNavigationItem={ onClickNavigationItem }
								// Use sub registry because we have multiple previews
								useSubRegistry={ true }
							/>
						</div>
					);
				} ) }
			</div>
		);
	}
	return (
		<div className="woocommerce-customize-store__block-editor">
			<div className={ 'woocommerce-block-preview-container' }>
				<BlockPreview
					blocks={ blocks }
					settings={ settings }
					additionalStyles={ '' }
					onClickNavigationItem={ onClickNavigationItem }
					// Don't use sub registry so that we can get the logo block from the main registry on the logo sidebar navigation screen component.
					useSubRegistry={ false }
				/>
			</div>
		</div>
	);
};
