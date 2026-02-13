import { getReact } from './getReact';
import { SyncButton } from '../components/classes-import-export/SyncButton';
import { ModalManager } from '../types/utils';

const PANEL_TITLE_TEXT = 'Class Manager';
const BUTTON_ID = 'ehcc-classes-import-export-button';

const createFallbackButton = (modalManager: ModalManager): HTMLElement => {
	const button = document.createElement('button');
	button.type = 'button';
	button.setAttribute('aria-label', 'Import / Export Classes');
	button.className = 'MuiButtonBase-root MuiIconButton-root MuiIconButton-sizeSmall';
	button.style.cssText = 'padding: 4px; border-radius: 4px; border: none; background: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; color: inherit;';

	const icon = document.createElement('i');
	icon.className = 'eicon-sync';
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
			ReactLib.createElement(SyncButton, {
				onClick: () => {
					modalManager.open();
				},
			})
		);
		return true;
	} catch (error) {
		const fallbackButton = createFallbackButton(modalManager);
		container.appendChild(fallbackButton);
		return true;
	}
};

const findClassManagerCloseButton = (): { titleElement: Element; closeButton: Element; parentStack: Element } | null => {
	const titleElements = document.body.querySelectorAll('h2, h3, h4, h5, h6');

	for (const el of titleElements) {
		const text = el.textContent?.trim();
		if (!text || !text.includes(PANEL_TITLE_TEXT)) {
			continue;
		}

		let parentStack: Element | null = el.closest('.MuiStack-root');
		if (!parentStack) {
			continue;
		}

		const outerStack = parentStack.parentElement?.closest('.MuiStack-root');
		if (outerStack) {
			parentStack = outerStack;
		}

		const closeButton = parentStack.querySelector('button[aria-label="Close"]');
		if (closeButton) {
			return { titleElement: el, closeButton, parentStack };
		}
	}

	return null;
};

const injectButton = (modalManager: ModalManager): boolean => {
	if (document.getElementById(BUTTON_ID)) {
		return false;
	}

	const result = findClassManagerCloseButton();
	if (!result) {
		return false;
	}

	const { closeButton, parentStack } = result;

	try {
		const buttonContainer = document.createElement('span');
		buttonContainer.id = BUTTON_ID;
		buttonContainer.style.display = 'inline-flex';
		parentStack.insertBefore(buttonContainer, closeButton);

		const rendered = renderButtonWithReact(buttonContainer, modalManager);
		if (!rendered && buttonContainer.parentNode) {
			buttonContainer.parentNode.removeChild(buttonContainer);
			return false;
		}
		return rendered;
	} catch {
		return false;
	}
};

export const initClassesButtonInjector = (modalManager: ModalManager): void => {
	let injected = false;

	const tryInject = () => {
		if (injected) {
			return;
		}

		try {
			if (injectButton(modalManager)) {
				injected = true;
			}
		} catch {
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

	setTimeout(() => { tryInject(); }, 500);
	setTimeout(() => { tryInject(); }, 2000);
	setTimeout(() => { tryInject(); }, 5000);
	setTimeout(() => { tryInject(); }, 10000);
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

						if (currentPanelId === 'global-classes-manager' && previousPanelId !== 'global-classes-manager') {
							injected = false;
							setTimeout(() => { tryInject(); }, 500);
							setTimeout(() => { tryInject(); }, 1000);
							setTimeout(() => { tryInject(); }, 2000);
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
			} catch {
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
