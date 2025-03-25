/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useCallback,
	useContext,
	useEffect,
	useMemo,
} from '@wordpress/element';
import { BlockInstance } from '@wordpress/blocks';
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { __experimentalBlockPatternsList as BlockPatternList } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from '../sidebar-navigation-screen';
import { useEditorBlocks } from '../../hooks/use-editor-blocks';
import { usePatternsByCategory } from '../../hooks/use-patterns';
import { HighlightedBlockContext } from '../../context/highlighted-block-context';
import { useEditorScroll } from '../../hooks/use-editor-scroll';
import { useSelectedPattern } from '../../hooks/use-selected-pattern';
import { findPatternByBlock } from '../utils';
import { CustomizeStoreContext } from '~/customize-store/assembler-hub';
import { FlowType } from '~/customize-store/types';
import { footerTemplateId } from '~/customize-store/data/homepageTemplates';

import './style.scss';
import { PatternWithBlocks } from '~/customize-store/types/pattern';

const SUPPORTED_FOOTER_PATTERNS = [
	'woocommerce-blocks/footer-with-3-menus',
	'woocommerce-blocks/footer-simple-menu',
	'woocommerce-blocks/footer-large',
];

export const SidebarNavigationScreenFooter = ( {
	onNavigateBackClick,
}: {
	onNavigateBackClick: () => void;
} ) => {
	const { scroll } = useEditorScroll( {
		editorSelector: '.woocommerce-customize-store__block-editor iframe',
		scrollDirection: 'bottom',
	} );

	const { isLoading, patterns } = usePatternsByCategory( 'woo-commerce' );

	const currentTemplateId: string | undefined = useSelect(
		( select ) =>
			select( coreStore ).getDefaultTemplateId( { slug: 'home' } ),
		[]
	);

	const [ mainTemplateBlocks ] = useEditorBlocks(
		'wp_template',
		currentTemplateId || ''
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
		( pattern: PatternWithBlocks, selectedBlocks: BlockInstance[] ) => {
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
				"Select a new footer from the options below. Your footer includes your site's secondary navigation and will be added to every page. You can continue customizing this via the Editor.",
				'woocommerce'
		  )
		: __(
				"Select a footer from the options below. Your footer includes your site's secondary navigation and will be added to every page. You can continue customizing this via the Editor later.",
				'woocommerce'
		  );

	return (
		<SidebarNavigationScreen
			title={ title }
			onNavigateBackClick={ () => {
				resetHighlightedBlockClientId();
				onNavigateBackClick();
			} }
			description={ description }
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
