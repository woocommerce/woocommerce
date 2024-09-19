/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import type { TemplateArray, BlockAttributes } from '@wordpress/blocks';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import {
	InnerBlocks,
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './style.scss';
import { SITE_TITLE } from '../../../settings/shared/default-constants';
import Form from './form';

const defaultTemplate = [
	[
		'core/heading',
		{
			level: 3,
			content: sprintf(
				/* translators: %s: site name */
				__( 'Create an account with %s', 'woocommerce' ),
				SITE_TITLE
			),
		},
	],
	[
		'core/list',
		{
			className: 'is-style-checkmark-list',
		},
		[
			[
				'core/list-item',
				{
					content: __( 'Faster future purchases', 'woocommerce' ),
				},
			],
			[
				'core/list-item',
				{
					content: __( 'Securely save payment info', 'woocommerce' ),
				},
			],
			[
				'core/list-item',
				{
					content: __(
						'Track orders & view shopping history',
						'woocommerce'
					),
				},
			],
		],
	],
] as TemplateArray;

type EditProps = {
	attributes: {
		hasDarkControls: boolean;
	};
	setAttributes: ( attrs: BlockAttributes ) => void;
};

export const Edit = ( {
	attributes,
	setAttributes,
}: EditProps ): JSX.Element => {
	const className = clsx( 'wc-block-order-confirmation-create-account', {
		'has-dark-controls': attributes.hasDarkControls,
	} );
	const blockProps = useBlockProps( {
		className,
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks
				allowedBlocks={ [
					'core/heading',
					'core/paragraph',
					'core/list',
					'core/list-item',
					'core/image',
				] }
				template={ defaultTemplate }
				templateLock={ false }
			/>
			<Disabled>
				<Form isEditor={ true } />
			</Disabled>
			<InspectorControls>
				<PanelBody title={ __( 'Style', 'woocommerce' ) }>
					<ToggleControl
						label={ __( 'Dark mode inputs', 'woocommerce' ) }
						help={ __(
							'Inputs styled specifically for use on dark background colors.',
							'woocommerce'
						) }
						checked={ attributes.hasDarkControls }
						onChange={ () =>
							setAttributes( {
								hasDarkControls: ! attributes.hasDarkControls,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};

export default Edit;
