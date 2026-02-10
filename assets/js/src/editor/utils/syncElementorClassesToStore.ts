const CONVERTER_API_PATH = 'html-css-converter/v1/';
const ELEMENTOR_GLOBAL_CLASSES_PATH = 'elementor/v1/global-classes';
const GLOBAL_CLASSES_LOAD_ACTION = 'globalClasses/load';

type ElementorStore = {
	dispatch?: (action: any) => void;
	__dispatch?: (action: any) => void;
};

type GlobalClassesData = {
	items: Record<string, unknown>;
	order: string[];
};

const getRestBaseUrl = (converterApiUrl: string): string => {
	return converterApiUrl.replace(CONVERTER_API_PATH, '');
};

const getNonce = (): string => {
	return (window as any).ehccImport?.nonce || '';
};

const getStore = (): ElementorStore | null => {
	return (window as any).elementorV2?.store || null;
};

const dispatchToStore = (store: ElementorStore, action: any): void => {
	const dispatcher = store.dispatch || store.__dispatch;
	if (dispatcher) {
		dispatcher(action);
	}
};

const fetchGlobalClasses = async (baseUrl: string, context: string): Promise<GlobalClassesData> => {
	const url = `${baseUrl}${ELEMENTOR_GLOBAL_CLASSES_PATH}?context=${context}`;

	const response = await fetch(url, {
		headers: {
			'X-WP-Nonce': getNonce(),
		},
	});

	const result = await response.json();

	return {
		items: result.data || {},
		order: result.meta?.order || [],
	};
};

export const syncElementorClassesToStore = async (converterApiUrl: string): Promise<void> => {
	try {
		const store = getStore();
		if (!store) {
			return;
		}

		const restBaseUrl = getRestBaseUrl(converterApiUrl);

		const [preview, frontend] = await Promise.all([
			fetchGlobalClasses(restBaseUrl, 'preview'),
			fetchGlobalClasses(restBaseUrl, 'frontend'),
		]);

		dispatchToStore(store, {
			type: GLOBAL_CLASSES_LOAD_ACTION,
			payload: { preview, frontend },
		});
	} catch {
		return;
	}
};
