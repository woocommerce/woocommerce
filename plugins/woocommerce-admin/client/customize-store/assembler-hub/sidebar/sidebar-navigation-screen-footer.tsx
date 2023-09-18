/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useCallback,
	useContext,
	useEffect,
	useMemo,
} from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { Spinner } from '@wordpress/components';
// @ts-expect-error Missing type in core-data.
import { __experimentalBlockPatternsList as BlockPatternList } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { useEditorBlocks } from '../hooks/use-editor-blocks';
import { usePatternsByCategory } from '../hooks/use-patterns';
import { HighlightedBlockContext } from '../context/highlighted-block-context';
import { useEditorScroll } from '../hooks/use-editor-scroll';

const SUPPORTED_FOOTER_PATTERNS = [
	'woocommerce-blocks/footer-simple-menu-and-cart',
	'woocommerce-blocks/footer-with-3-menus',
	'woocommerce-blocks/footer-large',
];

export const SidebarNavigationScreenFooter = () => {
	useEditorScroll( {
		editorSelector: '.woocommerce-customize-store__block-editor iframe',
		scrollDirection: 'bottom',
	} );

	const { isLoading, patterns } = usePatternsByCategory( 'woo-commerce' );
	const [ blocks, , onChange ] = useEditorBlocks();
	const { setHighlightedBlockIndex, resetHighlightedBlockIndex } = useContext(
		HighlightedBlockContext
	);

	useEffect( () => {
		if ( blocks && blocks.length ) {
			setHighlightedBlockIndex( blocks.length - 1 );
		}
	}, [ setHighlightedBlockIndex, blocks ] );

	const footerPatterns = useMemo(
		() =>
			patterns
				.filter( ( pattern ) =>
					SUPPORTED_FOOTER_PATTERNS.includes( pattern.name )
				)
				.sort(
					( a, b ) =>
						SUPPORTED_FOOTER_PATTERNS.indexOf( a.name ) -
						SUPPORTED_FOOTER_PATTERNS.indexOf( b.name )
				),
		[ patterns ]
	);

	const onClickFooterPattern = useCallback(
		( _pattern, selectedBlocks ) => {
			onChange( [ ...blocks.slice( 0, -1 ), selectedBlocks[ 0 ] ], {
				selection: {},
			} );
		},
		[ blocks, onChange ]
	);

	return (
		<SidebarNavigationScreen
			title={ __( 'Change your footer', 'woocommerce' ) }
			onNavigateBackClick={ resetHighlightedBlockIndex }
			description={ createInterpolateElement(
				__(
					"Select a new footer from the options below. Your footer includes your site's secondary navigation and will be added to every page. You can continue customizing this via the <EditorLink>Editor</EditorLink>.",
					'woocommerce'
				),
				{
					EditorLink: (
						<Link
							href={ `${ ADMIN_URL }site-editor.php` }
							type="external"
						/>
					),
				}
			) }
			content={
				<>
					<div className="edit-site-sidebar-navigation-screen-patterns__group-footer">
						{ isLoading && (
							<span className="components-placeholder__preview">
								<Spinner />
							</span>
						) }

						{ ! isLoading && (
							<BlockPatternList
								shownPatterns={ footerPatterns }
								blockPatterns={ footerPatterns }
								onClickPattern={ onClickFooterPattern }
								label={ 'Footers' }
								orientation="vertical"
								category={ 'footer' }
								isDraggable={ false }
								showTitlesAsTooltip={ true }
							/>
						) }
					</div>
				</>
			}
		/>
	);
};
