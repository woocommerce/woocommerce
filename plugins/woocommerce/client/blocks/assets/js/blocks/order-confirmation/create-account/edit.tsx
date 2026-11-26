/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import type { TemplateArray } from '@wordpress/blocks';
import {
	InnerBlocks,
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { getSetting, ADMIN_URL } from '@woocommerce/settings';
import { SITE_TITLE } from '@woocommerce/settings/default-constants';
import {
	Disabled,
	PanelBody,
	// `Link` from `@wordpress/ui` isn't available to this package yet.
	// eslint-disable-next-line @wordpress/use-recommended-components
	ExternalLink,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';
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
		{},
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
};

export const Edit = ( { attributes }: EditProps ): JSX.Element | null => {
	const className = clsx( 'wc-block-order-confirmation-create-account', {
		'has-dark-controls': attributes.hasDarkControls,
	} );
	const blockProps = useBlockProps( {
		className,
	} );
	const isEnabled = getSetting( 'delayedAccountCreationEnabled', true );

	if ( ! isEnabled ) {
		return null;
	}

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
				<PanelBody>
					<p>
						{ __(
							'Configure this feature in your store settings.',
							'woocommerce'
						) }
					</p>
					<ExternalLink
						href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=account` }
					>
						{ __( 'Manage account settings', 'woocommerce' ) }
					</ExternalLink>
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
