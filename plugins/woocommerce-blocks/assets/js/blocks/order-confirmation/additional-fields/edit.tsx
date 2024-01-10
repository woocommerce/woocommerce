/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import {
	ADDITIONAL_FORM_FIELDS,
	CONTACT_FORM_FIELDS,
} from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import './style.scss';

const Edit = (): JSX.Element => {
	const blockProps = useBlockProps( {
		className: 'wc-block-order-confirmation-additional-fields',
	} );

	const additionalFields = {
		...ADDITIONAL_FORM_FIELDS,
		...CONTACT_FORM_FIELDS,
	};

	return (
		<div { ...blockProps }>
			<dl className="wc-block-order-confirmation-additional-fields-list">
				{ Object.entries( additionalFields ).map( ( [ , field ] ) => {
					const { label, type, options } = field;
					let sampleValue = __(
						'Customer provided value',
						'woocommerce'
					);

					if ( type === 'checkbox' ) {
						sampleValue = __( 'Yes', 'woocommerce' );
					}

					if ( type === 'select' ) {
						sampleValue = options[ 0 ].label;
					}

					return (
						<>
							<dt>{ label }</dt>
							<dd>{ sampleValue }</dd>
						</>
					);
				} ) }
			</dl>
		</div>
	);
};

export default Edit;
