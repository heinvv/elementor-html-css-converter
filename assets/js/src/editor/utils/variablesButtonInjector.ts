import { getReact } from './getReact';
import { ImportExportButton } from '../components/variables-import-export/ImportExportButton';
import { ModalManager } from '../types/utils';

const PANEL_TITLE_TEXT = 'Variables Manager';
const BUTTON_ID = 'ehcc-variables-import-export-button';

const createFallbackButton = (modalManager: ModalManager): HTMLElement => {
	const button = document.createElement('button');
	button.type = 'button';
	button.setAttribute('aria-label', 'Import / Export');
	button.className = 'MuiButtonBase-root MuiIconButton-root MuiIconButton-sizeSmall';
	button.style.cssText = 'padding: 4px; border-radius: 4px; border: none; background: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; color: inherit;';

	const icon = document.createElement('i');
	icon.className = 'eicon-exchange';
	icon.style.cssText = 'font-size: 16px; display: inline-block;';
	button.appendChild(icon);

	button.addEventListener('mouseenter', () => {
		button.style.backgroundColor = 'rgba(0, 0, 0, 0.04)';
	});

	button.addEventListener('mouseleave', () => {
		button.style.backgroundColor = 'transparent';
	});

	button.addEventListener('click', (e) => {
		e.preventDefault();
		e.stopPropagation();
		modalManager.open();
	});

	return button;
};

const renderButtonWithReact = (container: Element, modalManager: ModalManager): boolean => {
	const ReactLib = getReact();
	const ReactDOM = (window as any).ReactDOM || (window as any).elementorV2?.reactDOM;

	if (!ReactLib || !ReactDOM) {
		const fallbackButton = createFallbackButton(modalManager);
		container.appendChild(fallbackButton);
		return true;
	}

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
	if (!createRoot) {
		const fallbackButton = createFallbackButton(modalManager);
		container.appendChild(fallbackButton);
		return true;
	}

	try {
		const root = createRoot(container);
		root.render(
			ReactLib.createElement(ImportExportButton, {
				onClick: () => {
					modalManager.open();
				},
			})
		);
		return true;
	} catch (error) {
		console.error('[EHCC] Variables Import/Export: React rendering error:', error);
		const fallbackButton = createFallbackButton(modalManager);
		container.appendChild(fallbackButton);
		return true;
	}
};

const findVariablesManagerPanel = (root: Element): Element | null => {
	const titleElements = root.querySelectorAll('h2, h3, h4, h5, h6');

	for (const el of titleElements) {
		const text = el.textContent?.trim();
		if (text && (text === PANEL_TITLE_TEXT || text.includes(PANEL_TITLE_TEXT))) {
			return el;
		}

		const svg = el.querySelector('svg');
		const textNodes = Array.from(el.childNodes).filter(
			(node) => node.nodeType === Node.TEXT_NODE && node.textContent?.trim()
		);
		if (svg && textNodes.length > 0) {
			const textContent = textNodes.map((n) => n.textContent?.trim()).join(' ').trim();
			if (textContent === PANEL_TITLE_TEXT || textContent.includes(PANEL_TITLE_TEXT)) {
				return el;
			}
		}
	}

	return null;
};

const findActionButtonsContainer = (root: Element): Element | null => {
	const addButton = root.querySelector('[aria-label="Add variable"]');
	const closeButton = root.querySelector('[aria-label="Close"]');

	if (!addButton || !closeButton) {
		return null;
	}

	const addParent = addButton.parentElement;
	if (addParent && addParent.classList.contains('MuiStack-root')) {
		if (addParent.contains(closeButton)) {
			return addParent;
		}
	}

	const addParentStack = addButton.closest('.MuiStack-root');
	if (addParentStack && addParentStack.contains(closeButton)) {
		return addParentStack;
	}

	const allStacks = root.querySelectorAll('.MuiStack-root');
	for (const stack of allStacks) {
		if (stack.contains(addButton) && stack.contains(closeButton)) {
			return stack;
		}
	}

	if (addParent && addParent.classList.contains('MuiStack-root')) {
		return addParent;
	}

	return null;
};

const injectButton = (modalManager: ModalManager, root: Element): boolean => {
	if (root.querySelector(`#${BUTTON_ID}`)) {
		return false;
	}

	const titleElement = findVariablesManagerPanel(root);
	if (!titleElement) {
		return false;
	}

	const addButton = root.querySelector('[aria-label="Add variable"]');
	if (!addButton) {
		return false;
	}

	const actionsContainer = findActionButtonsContainer(root);
	if (!actionsContainer) {
		return false;
	}

	const closeButton = actionsContainer.querySelector('[aria-label="Close"]');
	if (!closeButton) {
		return false;
	}

	try {
		const buttonContainer = document.createElement('span');
		buttonContainer.id = BUTTON_ID;
		buttonContainer.style.display = 'inline-flex';
		actionsContainer.insertBefore(buttonContainer, closeButton);

		const rendered = renderButtonWithReact(buttonContainer, modalManager);
		if (!rendered && buttonContainer.parentNode) {
			buttonContainer.parentNode.removeChild(buttonContainer);
			return false;
		}
		return rendered;
	} catch (error) {
		console.error('[EHCC] Variables Import/Export: Error injecting button:', error);
		return false;
	}
};

export const initVariablesButtonInjector = (modalManager: ModalManager): void => {
	let injected = false;
	let attemptCount = 0;

	const tryInject = () => {
		if (injected) {
			return;
		}

		attemptCount++;

		try {
			const titleElement = findVariablesManagerPanel(document.body);
			const actionsContainer = findActionButtonsContainer(document.body);
			
			if (!titleElement || !actionsContainer) {
				return;
			}

			if (injectButton(modalManager, document.body)) {
				injected = true;
			}
		} catch (error) {
			console.error('[EHCC] Variables Import/Export: Error in tryInject:', error);
		}
	};

	const observer = new MutationObserver(() => {
		if (!injected) {
			tryInject();
		}
	});

	const setupObserver = () => {
		if (document.body) {
			observer.observe(document.body, {
				childList: true,
				subtree: true,
			});
		}
	};

	setupObserver();

	setTimeout(() => {
		tryInject();
	}, 500);

	setTimeout(() => {
		tryInject();
	}, 2000);

	setTimeout(() => {
		tryInject();
	}, 5000);

	setTimeout(() => {
		tryInject();
	}, 10000);

	tryInject();

	if (typeof window !== 'undefined') {
		const elementor = (window as any).elementor;
		if (elementor && typeof elementor.on === 'function') {
			elementor.on('panel:open', () => {
				setTimeout(() => {
					injected = false;
					tryInject();
				}, 300);
			});
		}

		window.addEventListener('elementor:loaded', () => {
			setTimeout(() => {
				injected = false;
				tryInject();
			}, 1000);
		});

		window.addEventListener('elementor:init', () => {
			setTimeout(() => {
				injected = false;
				tryInject();
			}, 1000);
		});

		const elementorV2 = (window as any).elementorV2;
		if (elementorV2?.store) {
			try {
				const store = elementorV2.store;
				let previousPanelId: string | null = null;

				const checkPanelState = () => {
					try {
						const state = store.getState?.() || (store as any).__getState?.();
						const panelsState = state?.panels;
						const currentPanelId = panelsState?.openId || null;

						if (currentPanelId === 'variables-manager' && previousPanelId !== 'variables-manager') {
							injected = false;
							attemptCount = 0;
							setTimeout(() => {
								tryInject();
							}, 500);
							setTimeout(() => {
								tryInject();
							}, 1000);
							setTimeout(() => {
								tryInject();
							}, 2000);
						}

						previousPanelId = currentPanelId;
					} catch (error) {
					}
				};

				if (typeof store.subscribe === 'function') {
					store.subscribe(checkPanelState);
				} else if (typeof (store as any).__subscribe === 'function') {
					(store as any).__subscribe(checkPanelState);
				} else {
					const intervalId = setInterval(() => {
						checkPanelState();
					}, 500);

					setTimeout(() => {
						clearInterval(intervalId);
					}, 60000);
				}

				checkPanelState();
			} catch (error) {
				console.error('[EHCC] Variables Import/Export: Error subscribing to panel store:', error);
			}
		}
	}

	if (!document.body) {
		document.addEventListener('DOMContentLoaded', () => {
			setupObserver();
			tryInject();
		});
	}
};
