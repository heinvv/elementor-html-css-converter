export type ElementorUIComponents = {
	Dialog: any;
	DialogContent: any;
	DialogActions: any;
	Button: any;
	TextField: any;
	Stack: any;
	Box: any;
	Alert: any;
};

export type ModalManager = {
	container: HTMLDivElement | null;
	root: any;
	isOpen: boolean;
	init(): void;
	open(): void;
	close(): void;
	render(): void;
};
