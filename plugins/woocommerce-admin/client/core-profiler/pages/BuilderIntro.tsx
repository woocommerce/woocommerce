/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

export const BuilderIntro = () => (
	<div className="woocommerce-profiler-builder-intro">
		<h1>
			{ __(
				'Upload your Blueprint to provision your site',
				'woocommerce'
			) }{ ' ' }
		</h1>
		<Button variant="primary">{ __( 'Upload Blueprint' ) }</Button>
	</div>
);
