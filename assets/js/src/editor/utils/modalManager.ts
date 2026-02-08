import { getReact } from './getReact';
import { ImportModal } from '../components/ImportModal';
import { ModalManager } from '../types/utils';

export const createModalManager = (): ModalManager => {
	const React = (window as any).React || (window as any).elementorV2?.react;
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
			this.container = document.createElement('div');
			this.container.id = 'ehcc-import-modal-react-container';
			document.body.appendChild(this.container);

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
				const urlParams = new URLSearchParams(window.location.search);
				const postId = urlParams.get('post') || ((window as any).elementor?.config?.initial_document?.id);

				const modalElement = ReactLib.createElement(ImportModal, {
					isOpen: this.isOpen,
					onClose: () => this.close(),
					apiUrl: apiUrl,
					postId: postId ? parseInt(postId, 10) : null,
				});

				this.root.render(modalElement);
			} catch (error) {
			}
		},
	};

	return manager;
};
