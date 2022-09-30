/**
 * Internal dependencies
 */
import './style.scss';

const KnowledgebaseCardPostPlaceholder = ( index ) => {
	const classNameBase = 'woocommerce-marketing-knowledgebase-card__post';

	return (
		<div
			className={ `is-loading ${ classNameBase }` }
			key={ index }
			aria-hidden="true"
		>
			<div className={ `${ classNameBase }-img is-placeholder` }></div>
			<div className={ `${ classNameBase }-text` }>
				<h3 className="is-placeholder" aria-hidden="true"></h3>
				<p className={ `${ classNameBase }-meta is-placeholder` }></p>
			</div>
		</div>
	);
};

export default KnowledgebaseCardPostPlaceholder;
