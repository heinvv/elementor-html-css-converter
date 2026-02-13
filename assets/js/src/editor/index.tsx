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
		initClassesButtonInjector(classesModalManager);

		if (typeof window !== 'undefined') {
			(window as any).ehccClassesImportExport = classesModalManager;
		}
	} catch {
	}
}

if (typeof window !== 'undefined') {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', waitForReact);
	} else {
		waitForReact();
	}
}

