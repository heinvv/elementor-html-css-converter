type ElementorVariablesService = {
	load: () => Promise<unknown>;
};

const getVariablesService = (): ElementorVariablesService | null => {
	const service = (window as any).elementorV2?.editorVariables?.service;
	if (service && typeof service.load === 'function') {
		return service as ElementorVariablesService;
	}
	return null;
};

export const reloadElementorVariablesService = async (): Promise<void> => {
	try {
		const service = getVariablesService();
		if (!service) {
			return;
		}
		await service.load();
	} catch {
		return;
	}
};
