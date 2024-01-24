/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';
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
import { ToolbarButton } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ai from '../../../icons/ai';
import { WOO_AI_PLUGIN_FEATURE_NAME } from '../../../../src/constants';

import type {
	ProductTitleBlockEditProps,
	ProductTitleBlockEditComponent,
} from '../../../types';
import { buildProductSummarySuggestionPromp } from '../../../ai/build-prompt';
import './styles.scss';

const debug = debugFactory( 'woo-ai:product-editor:name-field' );

const productSummaryFieldWithAi =
	createHigherOrderComponent< ProductTitleBlockEditComponent >(
		( BlockEdit: ProductTitleBlockEditComponent ) => {
			return ( props: ProductTitleBlockEditProps ) => {
				if ( props?.name !== 'woocommerce/product-summary-field' ) {
					return <BlockEdit { ...props } />;
				}

				if ( ! props?.isSelected ) {
					return <BlockEdit { ...props } />;
				}

				debug( 'Extending product summary field with AI' );

				const productId = useEntityId( 'postType', 'product' );

				const [ isRequesting, setIsRequesting ] = useState( false );

				const propductProperty =
					props?.attributes?.property || 'short_description';

				const [ , setSummary ] = useEntityProp< string >(
					'postType',
					'product',
					propductProperty
				);

				const { requestCompletion } = useCompletion( {
					feature: WOO_AI_PLUGIN_FEATURE_NAME,
					onStreamMessage: ( message ) => {
						debug( 'Message received', message );
						setIsRequesting( true );
						try {
							// Adding setTimeout to force a re-render in the summary field.
							setTimeout( setSummary, 0, message );
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
						} catch ( e ) {
							setIsRequesting( false );
							throw new Error( 'Unable to parse suggestions' );
						}
					},
				} );

				const requestSuggestedSummary = useCallback( async () => {
					if ( isRequesting ) {
						return;
					}
					setIsRequesting( true );

					const prompt = await buildProductSummarySuggestionPromp(
						productId
					);
					requestCompletion( prompt );
				}, [ isRequesting, productId, requestCompletion ] );

				const blockControlProps = { group: 'other' };

				return (
					<>
						<BlockControls { ...blockControlProps }>
							<ToolbarButton
								icon={ ai }
								onClick={ requestSuggestedSummary }
								label={ __(
									'Get title suggestions with AI Assistant',
									'woocommerce'
								) }
								disabled={ isRequesting }
							>
								{ __( 'Get suggestion', 'woocommerce' ) }
							</ToolbarButton>
						</BlockControls>

						<BlockEdit { ...props } />
					</>
				);
			};
		},
		'productSummaryFieldWithAi'
	);

export default productSummaryFieldWithAi;
