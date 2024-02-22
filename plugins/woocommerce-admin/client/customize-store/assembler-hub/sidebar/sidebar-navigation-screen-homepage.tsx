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

// @ts-expect-error Missing type in core-data.
import { __experimentalBlockPatternsList as BlockPatternList } from '@wordpress/block-editor';
import { recordEvent } from '@woocommerce/tracks';

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

export const SidebarNavigationScreenHomepage = () => {
	const { scroll } = useEditorScroll( {
		editorSelector: '.woocommerce-customize-store__block-editor iframe',
		scrollDirection: 'top',
	} );
	const { isLoading, homeTemplates } = useHomeTemplates();
	// eslint-disable-next-line react-hooks/exhaustive-deps
	const { selectedPattern, setSelectedPattern } = useSelectedPattern();

	const [ blocks, , onChange ] = useEditorBlocks();
	const onClickPattern = useCallback(
		( pattern, selectedBlocks ) => {
			setSelectedPattern( pattern );
			onChange(
				[ blocks[ 0 ], ...selectedBlocks, blocks[ blocks.length - 1 ] ],
				{ selection: {} }
			);
			scroll();
		},
		[ blocks, onChange, setSelectedPattern, scroll ]
	);

	const homePatterns = useMemo( () => {
		return Object.entries( homeTemplates ).map(
			( [ templateName, patterns ] ) => {
				return {
					name: templateName,
					title: templateName,
					blocks: patterns.reduce(
						( acc: BlockInstance[], pattern ) => [
							...acc,
							...pattern.blocks,
						],
						[]
					),
					blockTypes: [ '' ],
					categories: [ '' ],
					content: '',
					source: '',
				};
			}
		);
	}, [ homeTemplates ] );

	useEffect( () => {
		if ( selectedPattern || ! blocks.length || ! homePatterns.length ) {
			return;
		}

		const homeBlocks = blocks.slice( 1, -1 );
		const _currentSelectedPattern = homePatterns.find( ( pattern ) => {
			if ( homeBlocks.length !== pattern.blocks.length ) {
				return false;
			}
			return homeBlocks.every(
				( block, index ) => block.name === pattern.blocks[ index ].name
			);
		} );

		setSelectedPattern( _currentSelectedPattern );

		// eslint-disable-next-line react-hooks/exhaustive-deps -- we don't want to re-run this effect when currentSelectedPattern changes
	}, [ blocks, homePatterns ] );

	const { context } = useContext( CustomizeStoreContext );
	const sidebarMessage =
		context.flowType === FlowType.AIOnline
			? __(
					'Based on the most successful stores in your industry and location, our AI tool has recommended this template for your business. Prefer a different layout? Choose from the templates below now, or later via the <EditorLink>Editor</EditorLink>.',
					'woocommerce'
			  )
			: __(
					'Based on the most successful stores, we recommend this layout for your business. Prefer something different? Choose from the templates below now, or continue customizing this page, including the content, later via the <EditorLink>Editor</EditorLink>.',
					'woocommerce'
			  );

	return (
		<SidebarNavigationScreen
			title={ __( 'Change your homepage', 'woocommerce' ) }
			description={ createInterpolateElement( sidebarMessage, {
				EditorLink: (
					<Link
						onClick={ () => {
							recordEvent(
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
						{ isLoading ? (
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
