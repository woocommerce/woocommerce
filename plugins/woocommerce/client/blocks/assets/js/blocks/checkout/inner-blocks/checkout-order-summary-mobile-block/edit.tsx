/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { FormStepHeading } from '../../form-step';

export const Edit = (): JSX.Element => {
	return (
		<div { ...useBlockProps() }>
			<FormStepHeading>
				<>{ __( 'Order summary', 'woocommerce' ) }</>
			</FormStepHeading>
			<p>
				{ __(
					'The order summary appears here on smaller screens.',
					'woocommerce'
				) }
			</p>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
