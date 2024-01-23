/**
 * External dependencies
 */
import {
	createElement,
	Fragment,
	useCallback,
	useEffect,
	useState,
} from '@wordpress/element';
import { check, title } from '@wordpress/icons';
import { createHigherOrderComponent } from '@wordpress/compose';
import {
	Button,
	DropdownMenu,
	Flex,
	FlexItem,
	HorizontalRule,
} from '@wordpress/components';
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
import { useEntityId } from '@wordpress/core-data';

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
	const [ titleSuggestions, setTitleSuggestions ] = useState< string[] >(
		[]
	);

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
		onCompletionFinished: ( reason, message ) => {
			try {
				const suggestions = parseStringsArray( message );
				setTitleSuggestions( suggestions );
				setIsRequesting( false );
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
				{ titleSuggestions.map( ( suggestedTitle ) => (
					<FlexItem key={ suggestedTitle }>
						<Button
							icon={ check }
							onClick={ () => onSelect( suggestedTitle ) }
							variant="tertiary"
						>
							{ suggestedTitle }
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
										onSelect={ onClose }
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
