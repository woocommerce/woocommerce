/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useCallback,
	useContext,
	useEffect,
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
import { usePatternsByCategory } from '../hooks/use-pattern';
import { HighlightedBlockContext } from '../context/highlighted-block-context';

const footerPatternNames = [
	'woocommerce-blocks/footer-simple-menu-and-cart',
	'woocommerce-blocks/footer-with-2-menus',
	'woocommerce-blocks/footer-with-2-menus-dark',
	'woocommerce-blocks/footer-with-3-menus',
];

export const SidebarNavigationScreenFooter = () => {
	const { isLoading, patterns } = usePatternsByCategory( 'woo-commerce' );
	const footerPatterns = patterns.filter( ( pattern ) =>
		footerPatternNames.includes( pattern.name )
	);

	const { setHighlightedBlockIndex, resetHighlightedBlockIndex } = useContext(
		HighlightedBlockContext
	);

	useEffect( () => {
		setHighlightedBlockIndex( 0 );
	}, [ setHighlightedBlockIndex ] );

	const [ blocks, onChange ] = useEditorBlocks();
	const onClickFooterPattern = useCallback(
		( _pattern, selectedBlocks ) => {
			const newFooterBlock = {
				...selectedBlocks[ 0 ],
				attributes: {
					...selectedBlocks[ 0 ].attributes,
					slug: 'footer',
				},
			};

			onChange(
				[
					...blocks.map( ( block ) => {
						if ( block.attributes?.slug === 'footer' ) {
							return newFooterBlock;
						}
						return block;
					} ),
				],
				{ selection: {} }
			);
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
