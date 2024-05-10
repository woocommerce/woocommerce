/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useCallback,
	useMemo,
	useEffect,
	useContext,
} from '@wordpress/element';
import { Link } from '@woocommerce/components';
import { Spinner } from '@wordpress/components';
// @ts-expect-error Missing type.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-expect-error No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
import {
	privateApis as blockEditorPrivateApis,
	__experimentalBlockPatternsList as BlockPatternList,
	store as blockEditorStore,
	// @ts-expect-error No types for this exist yet.
} from '@wordpress/block-editor';
// @ts-expect-error Missing type in core-data.
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { useEditorBlocks } from '../hooks/use-editor-blocks';
import { useHomeTemplates } from '../hooks/use-home-templates';
import { BlockInstance } from '@wordpress/blocks';
import { useSelectedPattern } from '../hooks/use-selected-pattern';
import { useEditorScroll } from '../hooks/use-editor-scroll';
import { FlowType } from '~/customize-store/types';
import { CustomizeStoreContext } from '~/customize-store/assembler-hub';
import { select, useDispatch, useSelect } from '@wordpress/data';

import { trackEvent } from '~/customize-store/tracking';
import {
	PRODUCT_HERO_PATTERN_BUTTON_STYLE,
	findButtonBlockInsideCoverBlockProductHeroPatternAndUpdate,
} from '../utils/hero-pattern';
import { isEqual } from 'lodash';
import { COLOR_PALETTES } from './global-styles/color-palette-variations/constants';

const { GlobalStylesContext, __experimentalGetParsedPattern } = unlock(
	blockEditorPrivateApis
);

export const SidebarNavigationScreenHomepage = () => {
	const { scroll } = useEditorScroll( {
		editorSelector: '.woocommerce-customize-store__block-editor iframe',
		scrollDirection: 'top',
	} );
	const { isLoading, homeTemplates } = useHomeTemplates();
	// eslint-disable-next-line react-hooks/exhaustive-deps
	const { selectedPattern, setSelectedPattern } = useSelectedPattern();

	const currentTemplate = useSelect(
		( select ) =>
			// @ts-expect-error No types for this exist yet.
			select( coreStore ).__experimentalGetTemplateForLink( '/' ),
		[]
	);

	const [ blocks, , onChange ] = useEditorBlocks(
		'wp_template',
		currentTemplate.id
	);

	// @ts-expect-error No types for this exist yet.
	const { selectBlock } = useDispatch( blockEditorStore );

	const onClickPattern = useCallback(
		( pattern, selectedBlocks ) => {
			if ( pattern === selectedPattern ) {
				return;
			}
			setSelectedPattern( pattern );
			selectBlock( pattern.blocks[ 0 ].clientId );
			onChange(
				[ blocks[ 0 ], ...selectedBlocks, blocks[ blocks.length - 1 ] ],
				{ selection: {} }
			);
			scroll();
		},
		[
			selectedPattern,
			setSelectedPattern,
			selectBlock,
			onChange,
			blocks,
			scroll,
		]
	);

	const isEditorLoading = useIsSiteEditorLoading();

	// @ts-expect-error No types for this exist yet.
	const { user } = useContext( GlobalStylesContext );

	const isActiveNewNeutralVariation = useMemo(
		() =>
			isEqual( COLOR_PALETTES[ 0 ].settings.color, user.settings.color ),
		[ user ]
	);

	const homePatterns = useMemo( () => {
		return Object.entries( homeTemplates ).map(
			( [ templateName, patterns ] ) => {
				if ( templateName === 'template1' ) {
					return {
						name: templateName,
						title: templateName,
						blocks: patterns.reduce(
							( acc: BlockInstance[], pattern ) => {
								const parsedPattern = unlock(
									select( blockEditorStore )
								).__experimentalGetParsedPattern(
									pattern.name
								);

								if ( ! parsedPattern ) {
									return acc;
								}

								if ( ! isActiveNewNeutralVariation ) {
									return [ ...acc, ...parsedPattern.blocks ];
								}
								const updatedBlocks =
									findButtonBlockInsideCoverBlockProductHeroPatternAndUpdate(
										parsedPattern.blocks,
										( buttonBlock: BlockInstance ) => {
											buttonBlock.attributes.style =
												PRODUCT_HERO_PATTERN_BUTTON_STYLE;
										}
									);

								return [ ...acc, ...updatedBlocks ];
							},
							[]
						),
						blockTypes: [ '' ],
						categories: [ '' ],
						content: '',
						source: '',
					};
				}

				return {
					name: templateName,
					title: templateName,
					blocks: patterns.reduce(
						( acc: BlockInstance[], pattern ) => {
							const parsedPattern = unlock(
								select( blockEditorStore )
							).__experimentalGetParsedPattern( pattern.name );

							return [ ...acc, ...parsedPattern.blocks ];
						},
						[]
					),
					blockTypes: [ '' ],
					categories: [ '' ],
					content: '',
					source: '',
				};
			}
		);
	}, [ homeTemplates, isActiveNewNeutralVariation ] );

	useEffect( () => {
		if (
			selectedPattern ||
			! blocks.length ||
			! homePatterns.length ||
			isLoading ||
			isEditorLoading
		) {
			return;
		}

		const currentSelectedPattern = homePatterns.find( ( patterns ) => {
			//'blocks' contains all blocks in the template, including the
			// header and footer blocks, while the 'patterns.blocks' does
			// not. For that reason we are removing the first and last
			// blocks from the 'blocks' to be able to compare then
			const homeBlocks = blocks.slice( 1, blocks.length - 1 );

			if ( patterns.blocks.length !== homeBlocks.length ) {
				return false;
			}

			return homeBlocks.every(
				( block, i ) => block.name === patterns.blocks[ i ].name
			);
		} );

		setSelectedPattern( currentSelectedPattern );
		// eslint-disable-next-line react-hooks/exhaustive-deps -- we don't want to re-run this effect when currentSelectedPattern changes
	}, [ blocks, homePatterns, isLoading, isEditorLoading ] );

	const { context } = useContext( CustomizeStoreContext );
	const aiOnline = context.flowType === FlowType.AIOnline;

	const title = aiOnline
		? __( 'Change your homepage', 'woocommerce' )
		: __( 'Choose your homepage', 'woocommerce' );
	const sidebarMessage = aiOnline
		? __(
				'Based on the most successful stores in your industry and location, our AI tool has recommended this template for your business. Prefer a different layout? Choose from the templates below now, or later via the <EditorLink>Editor</EditorLink>.',
				'woocommerce'
		  )
		: __(
				'Create an engaging homepage by selecting one of our pre-designed layouts. You can continue customizing this page, including the content, later via the <EditorLink>Editor</EditorLink>.',
				'woocommerce'
		  );

	return (
		<SidebarNavigationScreen
			title={ title }
			description={ createInterpolateElement( sidebarMessage, {
				EditorLink: (
					<Link
						onClick={ () => {
							trackEvent(
								'customize_your_store_assembler_hub_editor_link_click',
								{
									source: 'homepage',
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
				<div className="woocommerce-customize-store__sidebar-homepage-content">
					<div className="edit-site-sidebar-navigation-screen-patterns__group-homepage">
						{ /* This is necessary to fix this issue: https://github.com/woocommerce/woocommerce/issues/45711
						  If the user switch the homepage while the editor is loading, header and footer could disappear.
						  For more details check: https://github.com/woocommerce/woocommerce/pull/45735
						  */ }
						{ isLoading || isEditorLoading ? (
							<span className="components-placeholder__preview">
								<Spinner />
							</span>
						) : (
							<BlockPatternList
								shownPatterns={ homePatterns }
								blockPatterns={ homePatterns }
								onClickPattern={ onClickPattern }
								label={ 'Homepage' }
								orientation="vertical"
								category={ 'homepage' }
								isDraggable={ false }
								showTitlesAsTooltip={ false }
							/>
						) }
					</div>
				</div>
			}
		/>
	);
};
