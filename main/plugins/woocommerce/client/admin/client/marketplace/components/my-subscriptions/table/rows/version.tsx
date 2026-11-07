interface VersionProps {
	span: string;
}

export default function Version( props: VersionProps ) {
	return (
		<span className="woocommerce-marketplace__my-subscriptions-version">
			{ props.span }
		</span>
	);
}
