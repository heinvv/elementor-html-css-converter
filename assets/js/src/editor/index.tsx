import { createModalManager } from './utils/modalManager';
import { createVariablesModalManager } from './utils/variablesModalManager';
import { initVariablesButtonInjector } from './utils/variablesButtonInjector';
import { createClassesModalManager } from './utils/classesModalManager';
import { initClassesButtonInjector } from './utils/classesButtonInjector';

let retryCount = 0;
const MAX_RETRIES = 200;

function waitForReact() {
	retryCount++;

	if (retryCount > MAX_RETRIES) {
		console.warn('[EHCC] Variables Import/Export: Failed to initialize - React or Elementor UI not available');
		return;
	}

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

	try {
		const ehccImportModalReact = createModalManager();

		if (typeof window !== 'undefined') {
			(window as any).ehccImportModalReact = ehccImportModalReact;
		}

		ehccImportModalReact.init();

		const variablesModalManager = createVariablesModalManager();
		variablesModalManager.init();
		initVariablesButtonInjector(variablesModalManager);

		if (typeof window !== 'undefined') {
			(window as any).ehccVariablesImportExport = variablesModalManager;
		}

		const classesModalManager = createClassesModalManager();
		classesModalManager.init();
		console.log('[EHCC] Classes Import/Export: Initializing button injector');
		initClassesButtonInjector(classesModalManager);

		if (typeof window !== 'undefined') {
			(window as any).ehccClassesImportExport = classesModalManager;
			console.log('[EHCC] Classes Import/Export: Modal manager available at window.ehccClassesImportExport');
		}
	} catch (error) {
		console.error('[EHCC] Variables Import/Export: Initialization error:', error);
	}
}

if (typeof window !== 'undefined') {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', waitForReact);
	} else {
		waitForReact();
	}
}

