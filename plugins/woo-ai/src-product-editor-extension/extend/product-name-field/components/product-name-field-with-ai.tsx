/**
 * External dependencies
 */
import { createElement, Fragment, useState } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Button, DropdownMenu } from '@wordpress/components';
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

const debug = debugFactory( 'woo-ai:product-editor:name-field' );

function TitleSuggestions( {
	titles,
	isRequesting,
	onSelect,
	onRequest,
}: {
	titles: string[];
	isRequesting: boolean;
	onSelect: ( title: string ) => void;
	onRequest?: () => void;
} ): React.ReactElement {
	return (
		<>
			<Button onClick={ onRequest } disabled={ isRequesting }>
				{ __( 'Get suggestions', 'woocommerce' ) }
			</Button>
			<>
				{ titles.map( ( title ) => (
					<Button
						key={ title }
						onClick={ () => onSelect( title ) }
						variant="secondary"
					>
						{ title }
					</Button>
				) ) }
			</>
		</>
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

				const [ isRequesting, setIsRequesting ] = useState( false );
				const [ titleSuggestions, setTitleSuggestions ] = useState<
					string[]
				>( [] );

				const productId = useEntityId( 'postType', 'product' );

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
						// eslint-disable-next-line no-console
						setIsRequesting( false );
						debug( 'Streaming error encountered', error );
					},
					onCompletionFinished: ( reason, message ) => {
						try {
							const suggestions = parseStringsArray( message );
							setTitleSuggestions( suggestions );
							setIsRequesting( false );
							// console.log( { titleSuggestions } );
						} catch ( e ) {
							setIsRequesting( false );
							throw new Error( 'Unable to parse suggestions' );
						}
					},
				} );

				const blockControlProps = { group: 'other' };

				return (
					<>
						<BlockControls { ...blockControlProps }>
							<DropdownMenu
								className="ai-assistant__title-suggestions-dropdown"
								icon={ ai }
								label={ __(
									'Get title suggestions',
									'woocommerce'
								) }
								toggleProps={ {
									children: (
										<>
											<div className="ai-assistant__i18n-dropdown__toggle-label">
												{ __(
													'Get suggestions',
													'woocommerce'
												) }
											</div>
										</>
									),
									disabled: false,
									onClick: async () => {
										const prompt =
											await buildProductTitleSuggestionsPromp(
												productId
											);
										requestCompletion( prompt );
									},
								} }
							>
								{ ( { onClose } ) => (
									<TitleSuggestions
										titles={ titleSuggestions }
										onSelect={ onClose }
										isRequesting={ isRequesting }
										onRequest={ async () => {
											const prompt =
												await buildProductTitleSuggestionsPromp(
													productId
												);
											requestCompletion( prompt );
										} }
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
