const VARIABLES_MANAGER_PANEL_ID = 'variables-manager';
const PANEL_REMOUNT_DELAY_MS = 150;

type ElementorStore = {
	getState?: () => any;
	__getState?: () => any;
	dispatch?: (action: any) => void;
	__dispatch?: (action: any) => void;
};

const getStore = (): ElementorStore | null => {
	return (window as any).elementorV2?.store || null;
};

const getState = (store: ElementorStore): any => {
	const getter = store.getState || store.__getState;
	return getter ? getter() : null;
};

const dispatch = (store: ElementorStore, action: any): void => {
	const dispatcher = store.dispatch || store.__dispatch;
	if (dispatcher) {
		dispatcher(action);
	}
};

const isVariablesPanelOpen = (store: ElementorStore): boolean => {
	const state = getState(store);
	return state?.panels?.openId === VARIABLES_MANAGER_PANEL_ID;
};

export const refreshVariablesPanel = (): void => {
	const store = getStore();
	if (!store || !isVariablesPanelOpen(store)) {
		return;
	}

	dispatch(store, { type: 'panels/close', payload: VARIABLES_MANAGER_PANEL_ID });

	setTimeout(() => {
		dispatch(store, { type: 'panels/open', payload: VARIABLES_MANAGER_PANEL_ID });
	}, PANEL_REMOUNT_DELAY_MS);
};
