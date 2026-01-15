/**
 * External dependencies
 */
import { useState, useEffect, useMemo, memo } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { store as coreStore } from '@wordpress/core-data';
import type { UserPatternCategory } from '@wordpress/core-data/build-types/selectors';
import { dispatch, useSelect } from '@wordpress/data';
import { Modal, Button, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { usePreviewTemplates } from '../../hooks';
import {
	EmailEditorPostType,
	storeName,
	TemplateCategory,
	TemplatePreview,
} from '../../store';
import { TemplateList } from './template-list';
import { TemplateCategoriesListSidebar } from './template-categories-list-sidebar';
import { recordEvent, recordEventOnce } from '../../events';

function getCategoriesFromTemplates(
	templates: TemplatePreview[],
	patternCategories: UserPatternCategory[]
): Array< { name: TemplateCategory; label: string } > {
	const categoryLabels = new Map< string, string >(
		patternCategories.map( ( cat ) => [ cat.name, cat.label ] )
	);
	// Add localized label for 'recent' category (used by email posts)
	categoryLabels.set( 'recent', __( 'Recent', 'woocommerce' ) );

	const uniqueCategories = new Set< string >();
	for ( const template of templates ) {
		if ( template.category ) {
			uniqueCategories.add( template.category );
		}
	}

	return [ ...uniqueCategories ].map( ( category ) => ( {
		name: category as TemplateCategory,
		label: categoryLabels.get( category ) ?? category,
	} ) );
}

function SelectTemplateBody( {
	templates,
	handleTemplateSelection,
	templateSelectMode,
} ) {
	const patternCategories = useSelect(
		( select ) =>
			select(
				coreStore
			).getBlockPatternCategories() as UserPatternCategory[],
		[]
	);

	const hideRecentCategory = templateSelectMode === 'swap';

	const displayCategories = useMemo( () => {
		const allCategories = getCategoriesFromTemplates(
			templates,
			patternCategories ?? []
		);

		if ( hideRecentCategory ) {
			return allCategories.filter( ( cat ) => cat.name !== 'recent' );
		}

		// Put 'recent' category first
		return allCategories.sort( ( a, b ) => {
			if ( a.name === 'recent' ) return -1;
			if ( b.name === 'recent' ) return 1;
			return 0;
		} );
	}, [ templates, patternCategories, hideRecentCategory ] );

	const [ selectedCategory, setSelectedCategory ] =
		useState< TemplateCategory | null >( null );

	const handleCategorySelection = ( category: TemplateCategory ) => {
		recordEvent( 'template_select_modal_category_change', { category } );
		setSelectedCategory( category );
	};

	useEffect( () => {
		if ( selectedCategory !== null || displayCategories.length === 0 ) {
			return undefined;
		}

		const timeoutId = setTimeout( () => {
			const defaultCategory =
				displayCategories.find( ( cat ) => cat.name !== 'recent' )
					?.name ?? displayCategories[ 0 ]?.name;
			setSelectedCategory( defaultCategory );
		}, 1000 ); // using setTimeout to ensure the template styles are available before block preview

		return () => clearTimeout( timeoutId );
	}, [ displayCategories, selectedCategory ] );

	return (
		<div className="block-editor-block-patterns-explorer">
			<TemplateCategoriesListSidebar
				templateCategories={ displayCategories }
				selectedCategory={ selectedCategory }
				onClickCategory={ handleCategorySelection }
			/>

			<TemplateList
				templates={ templates }
				onTemplateSelection={ handleTemplateSelection }
				selectedCategory={ selectedCategory }
			/>
		</div>
	);
}

const MemorizedSelectTemplateBody = memo( SelectTemplateBody );

export function SelectTemplateModal( {
	onSelectCallback,
	closeCallback = null,
	previewContent = '',
	postType,
} ) {
	const templateSelectMode = previewContent ? 'swap' : 'new';
	recordEventOnce( 'template_select_modal_opened', { templateSelectMode } );

	const [ templates, emailPosts ] = usePreviewTemplates( previewContent );

	const hasTemplates = templates?.length > 0;

	const handleTemplateSelection = ( template: TemplatePreview ) => {
		const templateIsPostContent = template.type === postType;

		const postContent = template.template as unknown as EmailEditorPostType;

		recordEvent( 'template_select_modal_template_selected', {
			templateSlug: template.slug,
			templateSelectMode,
			templateType: template.type,
		} );

		// When we provide previewContent, we don't want to reset the blocks
		if ( ! previewContent ) {
			void dispatch( editorStore ).resetEditorBlocks(
				template.emailParsed
			);
		}

		void dispatch( storeName ).setTemplateToPost(
			templateIsPostContent ? postContent.template : template.slug
		);
		onSelectCallback();
	};

	const handleCloseWithoutSelection = () => {
		const template = templates[ 0 ] ?? null;
		if ( ! template ) {
			return;
		} // Prevent closing when templates are not loaded
		recordEvent(
			'template_select_modal_handle_close_without_template_selected'
		);
		handleTemplateSelection( template );
	};

	return (
		<Modal
			title={
				templateSelectMode === 'new'
					? __( 'Start with an email preset', 'woocommerce' )
					: __( 'Select a template', 'woocommerce' )
			}
			onRequestClose={ () => {
				recordEvent( 'template_select_modal_closed', {
					templateSelectMode,
				} );
				return closeCallback
					? closeCallback()
					: handleCloseWithoutSelection();
			} }
			isFullScreen
		>
			<MemorizedSelectTemplateBody
				templates={ [ ...templates, ...emailPosts ] }
				handleTemplateSelection={ handleTemplateSelection }
				templateSelectMode={ templateSelectMode }
			/>

			<Flex className="email-editor-modal-footer" justify="flex-end">
				<FlexItem>
					<Button
						variant="tertiary"
						className="email-editor-start_from_scratch_button"
						onClick={ () => {
							recordEvent(
								'template_select_modal_start_from_scratch_clicked'
							);
							return handleCloseWithoutSelection();
						} }
						isBusy={ ! hasTemplates }
					>
						{ __( 'Start from scratch', 'woocommerce' ) }
					</Button>
				</FlexItem>
			</Flex>
		</Modal>
	);
}
