/**
 * Internal dependencies
 */
import './section-header.scss';

type Props = {
	title: string;
	description: string;
	image: string;
};

const SectionHeader: React.FC< Props > = ( { title, description, image } ) => {
	return (
		<div className="woocommerce-task-header__contents-container woocommerce-task-section-header__container">
			<div className="woocommerce-task-header__contents">
				<h1>{ title }</h1>
				<p>{ description }</p>
			</div>
			<img
				src={ image }
				alt={ title }
				className="illustration-background"
			/>
		</div>
	);
};

export default SectionHeader;
