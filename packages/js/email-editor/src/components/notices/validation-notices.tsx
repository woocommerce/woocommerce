/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice, Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useValidationNotices } from '../../hooks';

export function ValidationNotices() {
	const { notices } = useValidationNotices();

	if ( notices.length === 0 ) {
		return null;
	}

	return (
		<Notice
			status="error"
			className="woocommerce-email-editor-validation-errors components-editor-notices__pinned"
			isDismissible={ false }
		>
			<>
				<strong>
					{ __( 'Fix errors to continue:', 'woocommerce' ) }
				</strong>
				<ul>
					{ notices.map( ( { id, content, actions } ) => (
						<li key={ id }>
							{ content }
							{ actions.length > 0
								? actions.map( ( { label, onClick } ) => (
										<Button
											key={ label }
											onClick={ onClick }
											variant="link"
										>
											{ label }
										</Button>
								  ) )
								: null }
						</li>
					) ) }
				</ul>
			</>
		</Notice>
	);
}
