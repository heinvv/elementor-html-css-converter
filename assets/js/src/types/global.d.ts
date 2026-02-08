declare namespace JSX {
	interface IntrinsicElements {
		[elemName: string]: any;
	}
}

interface Window {
	elementor?: {
		notifications?: {
			showToast: (options: { message: string; type: string }) => void;
		};
	};
}
