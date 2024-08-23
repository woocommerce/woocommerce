/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import type { TemplateArray } from '@wordpress/blocks';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';
import { SITE_TITLE } from '../../../settings/shared/default-constants';
import Form from './form';

export const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-create-account',
	} );

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
						content: __( '10% off your next order', 'woocommerce' ),
					},
				],
				[
					'core/list-item',
					{
						content: __(
							'Save info for future checkouts',
							'woocommerce'
						),
					},
				],
				[
					'core/list-item',
					{
						content: __(
							'Order and reward tracking',
							'woocommerce'
						),
					},
				],
			],
		],
	] as TemplateArray;

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
				templateLock="insert"
			/>
			<Disabled>
				<Form isEditor={ true } />
			</Disabled>
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
