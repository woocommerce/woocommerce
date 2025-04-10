/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useCallback,
	useMemo,
	useEffect,
	useContext,
} from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { BlockInstance } from '@wordpress/blocks';
import { select, useSelect } from '@wordpress/data';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	// @ts-expect-error No types for this exist yet.
	__experimentalBlockPatternsList as BlockPatternList,
	store as blockEditorStore,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from '../sidebar-navigation-screen';
import { useEditorBlocks } from '../../hooks/use-editor-blocks';
import { useHomeTemplates } from '../../hooks/use-home-templates';
import { useSelectedPattern } from '../../hooks/use-selected-pattern';
import { useEditorScroll } from '../../hooks/use-editor-scroll';
import { FlowType } from '~/customize-store/types';
import { CustomizeStoreContext } from '~/customize-store/assembler-hub';
import {
	PRODUCT_HERO_PATTERN_BUTTON_STYLE,
	findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate,
} from '../../utils/black-background-pattern-update-button';
import { useIsActiveNewNeutralVariation } from '../../hooks/use-is-active-new-neutral-variation';
import './style.scss';
import { PatternWithBlocks } from '~/customize-store/types/pattern';

export const SidebarNavigationScreenHomepage = ( {
	onNavigateBackClick,
}: {
	onNavigateBackClick: () => void;
} ) => {
	const { scroll } = useEditorScroll( {
		editorSelector: '.woocommerce-customize-store__block-editor iframe',
		scrollDirection: 'top',
	} );
	const { isLoading, homeTemplates } = useHomeTemplates();
	// eslint-disable-next-line react-hooks/exhaustive-deps
	const { selectedPattern, setSelectedPattern } = useSelectedPattern();

	const currentTemplateId: string | undefined = useSelect(
		( sel ) => sel( coreStore ).getDefaultTemplateId( { slug: 'home' } ),
		[]
	);

	const [ blocks, , onChange ] = useEditorBlocks(
		'wp_template',
		currentTemplateId || ''
	);

	const onClickPattern = useCallback(
		( pattern: PatternWithBlocks, selectedBlocks: BlockInstance[] ) => {
			if ( pattern === selectedPattern ) {
				return;
			}
			setSelectedPattern( pattern );
			onChange(
				[ blocks[ 0 ], ...selectedBlocks, blocks[ blocks.length - 1 ] ],
				{ selection: {} }
			);
			scroll();
		},
		[ selectedPattern, setSelectedPattern, onChange, blocks, scroll ]
	);

	const isEditorLoading = useIsSiteEditorLoading();

	const isActiveNewNeutralVariation = useIsActiveNewNeutralVariation();

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
									const updatedBlocks =
										findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate(
											parsedPattern.blocks,
											(
												buttonBlocks: BlockInstance[]
											) => {
												buttonBlocks.forEach(
													( buttonBlock ) => {
														buttonBlock.attributes.style =
															{};
													}
												);
											}
										);

									return [ ...acc, ...updatedBlocks ];
								}
								const updatedBlocks =
									findButtonBlockInsideCoverBlockWithBlackBackgroundPatternAndUpdate(
										parsedPattern.blocks,
										( buttonBlocks: BlockInstance[] ) => {
											buttonBlocks.forEach(
												( buttonBlock ) => {
													buttonBlock.attributes.style =
														PRODUCT_HERO_PATTERN_BUTTON_STYLE;
												}
											);
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

							if ( ! parsedPattern ) {
								return acc;
							}

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
				'Based on the most successful stores in your industry and location, our AI tool has recommended this template for your business. Prefer a different layout? Choose from the templates below now, or later via the Editor.',
				'woocommerce'
		  )
		: __(
				'Create an engaging homepage by selecting one of our pre-designed layouts. You can continue customizing this page, including the content, later via the Editor.',
				'woocommerce'
		  );

	return (
		<SidebarNavigationScreen
			title={ title }
			onNavigateBackClick={ onNavigateBackClick }
			description={ sidebarMessage }
			content={
				<div className="woocommerce-customize-store__sidebar-homepage-content">
					<div className="woocommerce-edit-site-sidebar-navigation-screen-patterns__group-homepage">
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
