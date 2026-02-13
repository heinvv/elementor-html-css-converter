import { getReact } from './getReact';
import { ClassesImportExportModal } from '../components/ClassesImportExportModal';
import { ModalManager } from '../types/utils';

export const createClassesModalManager = (): ModalManager => {
	const ReactDOM = (window as any).ReactDOM || (window as any).elementorV2?.reactDOM;

	const getCreateRoot = () => {
		if (ReactDOM?.createRoot) {
			return ReactDOM.createRoot.bind(ReactDOM);
		}
		if (ReactDOM?.client?.createRoot) {
			return ReactDOM.client.createRoot.bind(ReactDOM.client);
		}
		return null;
	};

	const createRoot = getCreateRoot();

	const manager: ModalManager = {
		container: null,
		root: null,
		isOpen: false,

		init() {
			const container = document.createElement('div');
			container.id = 'ehcc-classes-import-export-container';
			document.body.appendChild(container);
			this.container = container;

			if (createRoot && this.container) {
				try {
					this.root = createRoot(this.container);
				} catch (error) {
				}
			}
		},

		open() {
			if (!this.container) {
				this.init();
			}

			if (!this.root && this.container && createRoot) {
				try {
					this.root = createRoot(this.container);
				} catch (error) {
					return;
				}
			}

			this.isOpen = true;
			this.render();
		},

		close() {
			this.isOpen = false;
			this.render();
		},

		render() {
			if (!this.root) {
				return;
			}

			try {
				const ReactLib = getReact();
				if (!ReactLib) {
					return;
				}

				const apiUrl = (window as any).ehccImport?.apiUrl || '/wp-json/html-css-converter/v1/';

				const ErrorBoundary = class extends ReactLib.Component<
					{ children: any },
					{ hasError: boolean; error: any }
				> {
					constructor(props: any) {
						super(props);
						this.state = { hasError: false, error: null };
					}

					static getDerivedStateFromError(error: any) {
						return { hasError: true, error };
					}

					componentDidCatch() {
					}

					render() {
						if (this.state.hasError) {
							return ReactLib.createElement('div', {
								style: { padding: '20px', color: 'red' },
							}, `Error: ${this.state.error?.message || 'Unknown error'}`);
						}
						return this.props.children;
					}
				};

				this.root.render(
					ReactLib.createElement(
						ErrorBoundary,
						{},
						ReactLib.createElement(ClassesImportExportModal, {
							isOpen: this.isOpen,
							onClose: () => this.close(),
							apiUrl: apiUrl,
						})
					)
				);
			} catch {
			}
		},
	};

	return manager;
};
