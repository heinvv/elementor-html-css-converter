export const getReact = () => {
	return (window as any).React || (window as any).elementorV2?.react;
};
