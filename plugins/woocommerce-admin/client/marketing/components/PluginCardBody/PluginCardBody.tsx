/**
 * Internal dependencies
 */
import { CardBody } from '../index';
import './PluginCardBody.scss';

type PluginCardBodyProps = {
	icon: JSX.Element;
	name: string;
	description: string;
	button: JSX.Element | null;
};

/**
 * Renders a CardBody layout component to display plugin info and button.
 */
export const PluginCardBody: React.FC< PluginCardBodyProps > = ( {
	icon,
	name,
	description,
	button,
} ) => {
	return (
		<CardBody className="woocommerce_marketing_plugin_card_body">
			<div className="woocommerce_marketing_plugin_card_body__icon">
				{ icon }
			</div>
			<div className="woocommerce_marketing_plugin_card_body__details">
				<div className="woocommerce_marketing_plugin_card_body__details-name">
					{ name }
				</div>
				<div className="woocommerce_marketing_plugin_card_body__details-description">
					{ description }
				</div>
			</div>
			{ button }
		</CardBody>
	);
};
