import { createModalManager } from './utils/modalManager';

function waitForReact() {
	const ui = (window as any).elementorV2?.ui;
	if (!ui) {
		setTimeout(waitForReact, 100);
		return;
	}

	const React = (window as any).React || (window as any).elementorV2?.react;
	const ReactDOM = (window as any).ReactDOM || (window as any).elementorV2?.reactDOM;

	if (!React || !ReactDOM) {
		setTimeout(waitForReact, 100);
		return;
	}

	const ehccImportModalReact = createModalManager();

	if (typeof window !== 'undefined') {
		(window as any).ehccImportModalReact = ehccImportModalReact;
	}

	ehccImportModalReact.init();
}

waitForReact();
