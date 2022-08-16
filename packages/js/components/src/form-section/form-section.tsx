/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

type FormSectionProps = {
	title: string;
	description: string;
};

export const FormSection: React.FC< FormSectionProps > = ( {
	title,
	description,
	children,
} ) => {
	return (
		<div className="woocommerce-form-section">
			<div className="woocommerce-form-section__header">
				<h3 className="woocommerce-form-section__title">{ title }</h3>
				<div>
					<p>{ description }</p>
				</div>
			</div>
			<div className="woocommerce-form-section__content">
				{ children }
			</div>
		</div>
	);
};
