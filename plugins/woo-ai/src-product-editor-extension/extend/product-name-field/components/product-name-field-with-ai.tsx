/**
 * External dependencies
 */
import {
	createElement,
	Fragment,
	useCallback,
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { __experimentalUseCompletion as useCompletion } from '@woocommerce/ai';
import debugFactory from 'debug';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { BlockControls } from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { chevronRight, title } from '@wordpress/icons';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import {
	Button,
	DropdownMenu,
	Flex,
	FlexItem,
	HorizontalRule,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import ai from '../../../icons/ai';
import { WOO_AI_PLUGIN_FEATURE_NAME } from '../../../../src/constants';

import type {
	ProductTitleBlockEditProps,
	ProductTitleBlockEditComponent,
} from '../../../types';
import { buildProductTitleSuggestionsPromp } from '../../../ai/build-prompt';
import parseStringsArray from '../../../ai/parse/strings-array';
import './styles.scss';
import typeString from '../../../utils/type-string';

const debug = debugFactory( 'woo-ai:product-editor:name-field' );

function TitleSuggestionsMenu( {
	onSelect,
	autoSuggest = true,
}: {
	onSelect: ( title: string ) => void;
	isOpen: boolean;
	autoSuggest?: boolean;
} ): React.ReactElement {
	const productId = useEntityId( 'postType', 'product' );

	const [ isRequesting, setIsRequesting ] = useState( false );
	const [ titleSuggestions, setTitleSuggestions ] = useState< string[] >( [
		'',
		'',
		'',
	] );

	/*
	 * Get a reference to the first option,
	 * so we can focus it when the suggestions are loaded.
	 */
	const firstOption = useRef< HTMLButtonElement | null >( null );

	const { requestCompletion } = useCompletion( {
		feature: WOO_AI_PLUGIN_FEATURE_NAME,
		onStreamMessage: ( message ) => {
			debug( 'Message received', message );
			setIsRequesting( true );
			try {
				const suggestions = parseStringsArray( message );
				setTitleSuggestions( suggestions );
			} catch ( e ) {
				throw new Error( 'Unable to parse suggestions' );
			}
		},
		onStreamError: ( error ) => {
			setIsRequesting( false );
			debug( 'Streaming error encountered', error );
		},
		onCompletionFinished: () => {
			try {
				setIsRequesting( false );
				firstOption.current?.focus();
			} catch ( e ) {
				setIsRequesting( false );
				throw new Error( 'Unable to parse suggestions' );
			}
		},
	} );

	const requestTitleSuggestions = useCallback( async () => {
		if ( isRequesting ) {
			return;
		}
		setIsRequesting( true );

		const prompt = await buildProductTitleSuggestionsPromp( productId );
		requestCompletion( prompt );
	}, [ isRequesting, productId, requestCompletion ] );

	// Automatically request suggestions when the dropdown is opened
	useEffect( () => {
		if ( ! autoSuggest ) {
			return;
		}

		if ( isRequesting ) {
			return;
		}

		requestTitleSuggestions();
	}, [] ); // eslint-disable-line

	return (
		<div className="ai-assistant__title-suggestions-dropdown__content">
			<Flex
				direction="column"
				gap={ 1 }
				className="ai-assistant__title-suggestions-dropdown__content__suggestions"
			>
				{ titleSuggestions.map( ( suggestedTitle, i ) => (
					<FlexItem key={ suggestedTitle }>
						<Button
							className={
								! suggestedTitle?.length ? 'is-loading' : ''
							}
							icon={ chevronRight }
							onClick={ () => onSelect( suggestedTitle ) }
							variant="tertiary"
							ref={ i === 0 ? firstOption : undefined }
							disabled={ ! suggestedTitle?.length }
						>
							{ suggestedTitle || ' ' }
						</Button>
					</FlexItem>
				) ) }
			</Flex>

			<HorizontalRule />

			<Flex justify="flex-end">
				<FlexItem self-align="flex-middle">
					<Button
						onClick={ requestTitleSuggestions }
						disabled={ isRequesting }
						isBusy={ isRequesting }
						variant="secondary"
						icon={ ai }
					>
						{ __( 'Other suggestions', 'woocommerce' ) }
					</Button>
				</FlexItem>
			</Flex>
		</div>
	);
}

const productNameFieldWithAi =
	createHigherOrderComponent< ProductTitleBlockEditComponent >(
		( BlockEdit: ProductTitleBlockEditComponent ) => {
			return ( props: ProductTitleBlockEditProps ) => {
				// Only extend summary field block instances
				if ( props?.name !== 'woocommerce/product-name-field' ) {
					return <BlockEdit { ...props } />;
				}

				// Only add the `Full editor` button when the block is selected
				if ( ! props?.isSelected ) {
					return <BlockEdit { ...props } />;
				}

				debug( 'Extending product name field block' );

				const [ , setName ] = useEntityProp< string >(
					'postType',
					'product',
					'name'
				);

				const blockControlProps = { group: 'other' };

				return (
					<>
						<BlockControls { ...blockControlProps }>
							<DropdownMenu
								icon={ title }
								className="ai-assistant__title-suggestions-dropdown"
								label={ __(
									'Get title suggestions with AI Assistant',
									'woocommerce'
								) }
								toggleProps={ {
									children: (
										<div className="ai-assistant__i18n-dropdown__toggle-label">
											{ __(
												'Get suggestions',
												'woocommerce'
											) }
										</div>
									),
									disabled: false,
								} }
							>
								{ ( { onClose, isOpen } ) => (
									<TitleSuggestionsMenu
										onSelect={ ( newTitle ) => {
											onClose();
											typeString( newTitle, setName );
										} }
										isOpen={ isOpen }
									/>
								) }
							</DropdownMenu>
						</BlockControls>

						<BlockEdit { ...props } />
					</>
				);
			};
		},
		'productNameFieldWithAi'
	);

export default productNameFieldWithAi;
