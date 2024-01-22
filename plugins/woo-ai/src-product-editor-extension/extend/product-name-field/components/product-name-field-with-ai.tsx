/**
 * External dependencies
 */
import { createElement, Fragment, useState } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';
import { ToolbarButton } from '@wordpress/components';
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

const debug = debugFactory( 'woo-ai:product-editor:name-field' );

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

				const productId = useEntityId( 'postType', 'product' );

				const { requestCompletion } = useCompletion( {
					feature: WOO_AI_PLUGIN_FEATURE_NAME,
					onStreamMessage: ( message ) => {
						debug( 'Message received', message );
						setIsRequesting( true );
					},
					onStreamError: ( error ) => {
						// eslint-disable-next-line no-console
						setIsRequesting( false );
						debug( 'Streaming error encountered', error );
					},
					onCompletionFinished: ( reason, content ) => {
						try {
							const parsed = JSON.parse( content );
							setIsRequesting( false );
							debug( 'Parsed suggestions', parsed );
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
							<ToolbarButton
								icon={ ai }
								disabled={ isRequesting }
								label={ __(
									'Get AI suggestions for product name',
									'woocommerce'
								) }
								onClick={ async () => {
									setIsRequesting( true );
									const prompt =
										await buildProductTitleSuggestionsPromp(
											productId
										);

									await requestCompletion( prompt );
								} }
							>
								{ __( 'Get title suggestions', 'woocommerce' ) }
							</ToolbarButton>
						</BlockControls>

						<BlockEdit { ...props } />
					</>
				);
			};
		},
		'productNameFieldWithAi'
	);

export default productNameFieldWithAi;
