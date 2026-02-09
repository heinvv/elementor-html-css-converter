const VARIABLES_STORAGE_KEY = 'elementor-global-variables';
const WATERMARK_STORAGE_KEY = 'elementor-global-variables-watermark';
const CONVERTER_API_PATH = 'html-css-converter/v1/';
const ELEMENTOR_VARIABLES_LIST_PATH = 'elementor/v1/variables/list';

const getRestBaseUrl = (converterApiUrl: string): string => {
	return converterApiUrl.replace(CONVERTER_API_PATH, '');
};

const getNonce = (): string => {
	return (window as any).ehccImport?.nonce || '';
};

export const syncElementorVariablesToLocalStorage = async (converterApiUrl: string): Promise<void> => {
	try {
		const restBaseUrl = getRestBaseUrl(converterApiUrl);
		const elementorVariablesUrl = restBaseUrl + ELEMENTOR_VARIABLES_LIST_PATH;

		const response = await fetch(elementorVariablesUrl, {
			headers: {
				'X-WP-Nonce': getNonce(),
			},
		});

		const result = await response.json();

		if (result.success && result.data) {
			localStorage.setItem(VARIABLES_STORAGE_KEY, JSON.stringify(result.data.variables));
			localStorage.setItem(WATERMARK_STORAGE_KEY, String(result.data.watermark));
		}
	} catch {
		return;
	}
};
