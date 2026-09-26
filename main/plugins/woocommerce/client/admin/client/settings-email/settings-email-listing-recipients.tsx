/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Tooltip, Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Recipients } from './settings-email-listing-slotfill';

export const RecipientsList = ( {
	recipients,
}: {
	recipients: Recipients;
} ) => {
	const { to, cc, bcc } = {
		to: recipients.to ? recipients.to.split( ',' ).filter( Boolean ) : [],
		cc: recipients.cc ? recipients.cc.split( ',' ).filter( Boolean ) : [],
		bcc: recipients.bcc
			? recipients.bcc.split( ',' ).filter( Boolean )
			: [],
	};
	const copyCount = [ ...cc, ...bcc ].length;

	return (
		<div className="woocommerce-email-listing-recipients">
			<div className="woocommerce-email-listing-recipients-to">
				{ to.join( ', ' ) }
			</div>
			{ copyCount > 0 && (
				<Tooltip
					className="woocommerce-email-listing-recipients-tooltip"
					// @ts-expect-error - Text prop accepts also ReactNode
					text={
						<>
							<div>
								{ __( 'To:', 'woocommerce' ) }{ ' ' }
								{ to.join( ', ' ) }
							</div>
							{ cc.length > 0 && (
								<div>
									{ __( 'CC:', 'woocommerce' ) }{ ' ' }
									{ cc.join( ', ' ) }
								</div>
							) }
							{ bcc.length > 0 && (
								<div>
									{ __( 'BCC:', 'woocommerce' ) }{ ' ' }
									{ bcc.join( ', ' ) }
								</div>
							) }
						</>
					}
				>
					<Button variant="link">
						{
							/* Translators: Link to info about count of additional recipients. */
							__( '+%d more', 'woocommerce' ).replace(
								'%d',
								copyCount.toString()
							)
						}{ ' ' }
					</Button>
				</Tooltip>
			) }
		</div>
	);
};
