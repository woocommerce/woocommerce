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
import { recordEvent } from '@woocommerce/tracks';
import { Spinner } from '@wordpress/components';
// @ts-expect-error No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { useEditorBlocks } from '../hooks/use-editor-blocks';
import { usePatternsByCategory } from '../hooks/use-patterns';
import { HighlightedBlockContext } from '../context/highlighted-block-context';
import { useEditorScroll } from '../hooks/use-editor-scroll';
import { useSelectedPattern } from '../hooks/use-selected-pattern';
import { findPatternByBlock } from './utils';
import BlockPatternList from '../block-pattern-list';
import { CustomizeStoreContext } from '~/customize-store/assembler-hub';
import { FlowType } from '~/customize-store/types';
import { footerTemplateId } from '~/customize-store/data/homepageTemplates';
import { useSelect } from '@wordpress/data';

const SUPPORTED_FOOTER_PATTERNS = [
	'woocommerce-blocks/footer-with-3-menus',
	'woocommerce-blocks/footer-simple-menu',
	'woocommerce-blocks/footer-large',
];

export const SidebarNavigationScreenFooter = () => {
	const { scroll } = useEditorScroll( {
		editorSelector: '.woocommerce-customize-store__block-editor iframe',
		scrollDirection: 'bottom',
	} );

	const { isLoading, patterns } = usePatternsByCategory( 'woo-commerce' );

	const currentTemplate = useSelect(
		( select ) =>
			// @ts-expect-error No types for this exist yet.
			select( coreStore ).__experimentalGetTemplateForLink( '/' ),
		[]
	);

	const [ mainTemplateBlocks ] = useEditorBlocks(
		'wp_template',
		currentTemplate.id
	);

	const [ blocks, , onChange ] = useEditorBlocks(
		'wp_template_part',
		footerTemplateId
	);

	const footerTemplatePartBlockClientId = mainTemplateBlocks.find(
		( block ) => block.attributes.slug === 'footer'
	);

	const { setHighlightedBlockClientId, resetHighlightedBlockClientId } =
		useContext( HighlightedBlockContext );
	// eslint-disable-next-line react-hooks/exhaustive-deps
	const { selectedPattern, setSelectedPattern } = useSelectedPattern();

	useEffect( () => {
		setHighlightedBlockClientId(
			footerTemplatePartBlockClientId?.clientId ?? null
		);
	}, [
		footerTemplatePartBlockClientId?.clientId,
		setHighlightedBlockClientId,
	] );

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

	useEffect( () => {
		// Set the selected pattern when the footer screen is loaded.
		if ( selectedPattern || ! blocks.length || ! footerPatterns.length ) {
			return;
		}

		const currentSelectedPattern = findPatternByBlock(
			footerPatterns,
			blocks[ blocks.length - 1 ]
		);
		setSelectedPattern( currentSelectedPattern );
		// eslint-disable-next-line react-hooks/exhaustive-deps -- we don't want to re-run this effect when currentSelectedPattern changes
	}, [ blocks, footerPatterns ] );

	const onClickFooterPattern = useCallback(
		( pattern, selectedBlocks ) => {
			setSelectedPattern( pattern );
			onChange( [ ...blocks.slice( 0, -1 ), selectedBlocks[ 0 ] ], {
				selection: {},
			} );
			scroll();
		},
		[ blocks, onChange, setSelectedPattern, scroll ]
	);

	const { context } = useContext( CustomizeStoreContext );
	const aiOnline = context.flowType === FlowType.AIOnline;

	const title = aiOnline
		? __( 'Change your footer', 'woocommerce' )
		: __( 'Choose your footer', 'woocommerce' );

	const description = aiOnline
		? __(
				"Select a new footer from the options below. Your footer includes your site's secondary navigation and will be added to every page. You can continue customizing this via the <EditorLink>Editor</EditorLink>.",
				'woocommerce'
		  )
		: __(
				"Select a footer from the options below. Your footer includes your site's secondary navigation and will be added to every page. You can continue customizing this via the <EditorLink>Editor</EditorLink> later.",
				'woocommerce'
		  );

	return (
		<SidebarNavigationScreen
			title={ title }
			onNavigateBackClick={ resetHighlightedBlockClientId }
			description={ createInterpolateElement( description, {
				EditorLink: (
					<Link
						onClick={ () => {
							recordEvent(
								'customize_your_store_assembler_hub_editor_link_click',
								{
									source: 'footer',
								}
							);
							window.open(
								`${ ADMIN_URL }site-editor.php`,
								'_blank'
							);
							return false;
						} }
						href=""
					/>
				),
			} ) }
			content={
				<>
					<div className="woocommerce-customize-store__sidebar-footer-content">
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
								isDraggable={ false }
								onHover={ () => {} }
								showTitlesAsTooltip={ true }
							/>
						) }
					</div>
				</>
			}
		/>
	);
};
