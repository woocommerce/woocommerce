/**
 * External dependencies
 */
import { useMemo, memo } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { blockDefault } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { EmptyState, Notice, Stack } from '@wordpress/ui';
// @ts-expect-error No types available for this component
// eslint-disable-next-line
import { BlockPreview } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { Async } from './async';
import { TemplateCategory, TemplatePreview } from '../../store';
import { useEmailCss } from '../../hooks';

type Props = {
	templates: TemplatePreview[];
	onTemplateSelection: ( template: TemplatePreview ) => void;
	selectedCategory?: TemplateCategory;
};

function TemplateNoResults() {
	return (
		<EmptyState.Root className="email-editor-template-select__no-results">
			<EmptyState.Icon icon={ blockDefault } />
			<EmptyState.Title>
				{ __( 'No recent templates.', __i18n_text_domain__ ) }
			</EmptyState.Title>
			<EmptyState.Description>
				{ __(
					'Your recent creations will appear here as soon as you begin.',
					__i18n_text_domain__
				) }
			</EmptyState.Description>
		</EmptyState.Root>
	);
}

function TemplateListBox( {
	templates,
	onTemplateSelection,
	selectedCategory,
}: Props ) {
	const { layout } = useSelect( ( select ) => {
		const { getEditorSettings } = select( editorStore );
		const editorSettings = getEditorSettings();
		return {
			// @ts-expect-error There are no types for the experimental features settings.
			// eslint-disable-next-line no-underscore-dangle
			layout: editorSettings?.__experimentalFeatures?.layout,
		};
	} );

	const [ styles ] = useEmailCss();
	const css =
		styles.reduce( ( acc, style ) => {
			return acc + ( style.css ?? '' );
		}, '' ) +
		`.is-root-container { width: ${
			layout?.contentSize || '660px'
		}; margin: 0 auto; }`;

	if ( selectedCategory === 'recent' && templates.length === 0 ) {
		return <TemplateNoResults />;
	}

	return (
		<div className="email-editor-template-select__templates" role="listbox">
			{ templates.map( ( template ) => (
				<div
					key={ `${ template.slug }_${ template.displayName }_${ template.id }` }
					className="email-editor-template-select__template"
				>
					<div
						className="email-editor-template-select__template-button"
						role="button"
						tabIndex={ 0 }
						onClick={ () => {
							onTemplateSelection( template );
						} }
						onKeyPress={ ( event ) => {
							if ( event.key === 'Enter' || event.key === ' ' ) {
								onTemplateSelection( template );
							}
						} }
					>
						<Async
							placeholder={
								<p>
									{ __(
										'rendering template',
										__i18n_text_domain__
									) }
								</p>
							}
						>
							<BlockPreview
								blocks={ template.previewContentParsed }
								viewportWidth={ 900 }
								minHeight={ 300 }
								additionalStyles={ [
									{
										css,
									},
								] }
							/>

							<Stack
								direction="row"
								align="center"
								className="email-editor-template-select__template-details"
							>
								<h4 className="email-editor-template-select__template-title">
									{ template.displayName }
								</h4>
							</Stack>
						</Async>
					</div>
				</div>
			) ) }
		</div>
	);
}

const compareProps = ( prev, next ) =>
	prev.templates.length === next.templates.length &&
	prev.selectedCategory === next.selectedCategory;

const MemorizedTemplateListBox = memo( TemplateListBox, compareProps );

export function TemplateList( {
	templates,
	onTemplateSelection,
	selectedCategory,
}: Props ) {
	const filteredTemplates = useMemo(
		() =>
			selectedCategory !== null && selectedCategory !== undefined
				? templates.filter(
						( template ) => template.category === selectedCategory
				  )
				: templates,
		[ selectedCategory, templates ]
	);

	return (
		<Stack
			direction="column"
			gap="xl"
			className="email-editor-template-select__list"
		>
			{ selectedCategory === 'recent' && (
				<Notice.Root intent="info">
					<Notice.Description>
						{ __(
							'Templates created on the legacy editor will not appear here.',
							__i18n_text_domain__
						) }
					</Notice.Description>
				</Notice.Root>
			) }

			<MemorizedTemplateListBox
				templates={ filteredTemplates }
				onTemplateSelection={ onTemplateSelection }
				selectedCategory={ selectedCategory }
			/>
		</Stack>
	);
}
