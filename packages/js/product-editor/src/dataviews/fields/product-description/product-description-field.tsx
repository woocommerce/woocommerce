/**
 * External dependencies
 */
import {
	createElement,
	useEffect,
	useState,
	Fragment,
	createInterpolateElement,
} from '@wordpress/element';
import { BlockInstance, parse, serialize } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import classNames from 'classnames';
import { BaseControl, Popover } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { Product } from '@woocommerce/data';
import { DataFormControlProps } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
	BlockControls,
	// @ts-expect-error no exported member.
	BlockPreview,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	AlignmentControl,
	RichText,
	BlockEditorProvider,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import ModalEditorWelcomeGuide from '../../../components/modal-editor-welcome-guide';
import { store } from '../../../store/product-editor-ui';
import FullEditorToolbarButton from '../../../blocks/product-fields/description/components/full-editor-toolbar-button';
import { ALIGNMENT_CONTROLS } from '../../../blocks/product-fields/summary/constants';
import { ParagraphRTLControl } from '../../../blocks/product-fields/summary/paragraph-rtl-control';
import { useClearSelectedBlockOnBlur } from '../../../hooks/use-clear-selected-block-on-blur';

const ALLOWED_FORMATS = [
	'core/bold',
	'core/code',
	'core/italic',
	'core/link',
	'core/strikethrough',
	'core/underline',
	'core/text-color',
	'core/subscript',
	'core/superscript',
	'core/unknown',
];

/**
 * Check whether the parsed blocks become from the summary block.
 *
 * @param {BlockInstance[]} blocks - The block list
 * @return {string|false} The content of the freeform block if it's a freeform block, false otherwise.
 */
export function getContentFromFreeform(
	blocks: BlockInstance[]
): false | string {
	// Check whether the parsed blocks become from the summary block:
	const isCoreFreeformBlock =
		blocks.length === 1 && blocks[ 0 ].name === 'core/freeform';

	if ( isCoreFreeformBlock ) {
		return blocks[ 0 ].attributes.content;
	}

	return false;
}

export function ProductDescriptionField( {
	field,
}: DataFormControlProps< Product > ) {
	const { id } = field;
	const contentId = useInstanceId(
		ProductDescriptionField,
		'wp-block-woocommerce-product-summary-field__content'
	);
	const [ description, setDescription ] = useEntityProp< string >(
		'postType',
		'product',
		id
	);

	// This is a workaround to hide the toolbar when the block is blurred.
	// This is a temporary solution until using Gutenberg 18 with the
	// fix from https://github.com/WordPress/gutenberg/pull/59800
	const { handleBlur: hideToolbar } = useClearSelectedBlockOnBlur();

	const [ descriptionBlocks, setDescriptionBlocks ] = useState<
		BlockInstance[]
	>( [] );
	const [ direction, setDirection ] = useState< 'ltr' | 'rtl' | undefined >();
	const [ align, setAlignment ] = useState< string >( 'left' );

	// Pick Modal editor data from the store.
	const { isModalEditorOpen, modalEditorBlocks, hasChanged } = useSelect(
		( select ) => {
			return {
				isModalEditorOpen: select( store ).isModalEditorOpen(),
				modalEditorBlocks: select( store ).getModalEditorBlocks(),
				hasChanged: select( store ).getModalEditorContentHasChanged(),
			};
		},
		[]
	);

	// Parse the description into blocks.
	useEffect( () => {
		if ( ! description ) {
			setDescriptionBlocks( [] );
			return;
		}

		/*
		 * First quick check to avoid parsing process,
		 * since it's an expensive operation.
		 */
		if ( description.indexOf( '<!-- wp:' ) === -1 ) {
			return;
		}

		const parsedBlocks = parse( description );
		// Check whether the parsed blocks become from the summary block:
		if ( getContentFromFreeform( parsedBlocks ) ) {
			return;
		}

		setDescriptionBlocks( parsedBlocks );
	}, [ description ] );

	/*
	 * From Modal Editor -> Description entity.
	 * Update the description when the modal editor blocks change.
	 */
	useEffect( () => {
		if ( ! hasChanged ) {
			return;
		}

		const html = serialize( modalEditorBlocks );
		setDescription( html );
	}, [ modalEditorBlocks, setDescription, hasChanged ] );

	return (
		<BlockEditorProvider
			value={ descriptionBlocks }
			settings={ {
				bodyPlaceholder: '',
				hasFixedToolbar: true,
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore This property was recently added in the block editor data store.
				__experimentalClearBlockSelection: false,
				// mediaUpload,
			} }
			onInput={ setDescriptionBlocks }
			onChange={ setDescriptionBlocks }
		>
			<BlockTools>
				<div>
					{ !! descriptionBlocks?.length ? (
						<>
							<BlockControls>
								<FullEditorToolbarButton
									text={ __(
										'Edit in full editor',
										'woocommerce'
									) }
								/>
							</BlockControls>

							<BlockPreview
								blocks={ descriptionBlocks }
								viewportWidth={ 800 }
								additionalStyles={ [
									{
										css: 'body { padding: 32px; height: 10000px }',
									}, // hack: setting height to 10000px to ensure the preview is not cut off.
								] }
							/>
						</>
					) : (
						<div
							className={
								'wp-block wp-block-woocommerce-product-summary-field-wrapper'
							}
						>
							{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
							{ /* @ts-ignore No types for this exist yet. */ }
							<BlockControls group="block">
								<AlignmentControl
									alignmentControls={ ALIGNMENT_CONTROLS }
									value={ align }
									onChange={ setAlignment }
								/>

								<ParagraphRTLControl
									direction={ direction || 'ltr' }
									onChange={ setDirection }
								/>
							</BlockControls>

							<BaseControl
								id={ contentId.toString() }
								label={ createInterpolateElement(
									__( 'Summary', 'woocommerce' ),
									{
										optional: (
											<span className="woocommerce-product-form__optional-input">
												{ __(
													'(OPTIONAL)',
													'woocommerce'
												) }
											</span>
										),
									}
								) }
								help={ __(
									"Summarize this product in 1-2 short sentences. We'll show it at the top of the page.",
									'woocommerce'
								) }
							>
								<div>
									<RichText
										id={ contentId.toString() }
										identifier="content"
										tagName="p"
										value={ description }
										onChange={ setDescription }
										data-empty={ Boolean( description ) }
										className={ classNames(
											'components-summary-control',
											{
												[ `has-text-align-${ align }` ]:
													align,
											}
										) }
										dir={ direction }
										allowedFormats={ ALLOWED_FORMATS }
										onBlur={ hideToolbar }
									/>
								</div>
							</BaseControl>
						</div>
					) }

					{ isModalEditorOpen && <ModalEditorWelcomeGuide /> }
				</div>
				<Popover.Slot />
			</BlockTools>
		</BlockEditorProvider>
	);
}
