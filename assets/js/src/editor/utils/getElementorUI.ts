import { ElementorUIComponents } from '../types/utils';

export const getElementorUI = (): ElementorUIComponents | null => {
	const ui = (window as any).elementorV2?.ui;
	if (!ui) {
		return null;
	}

	const DialogComponent = ui.Dialog;
	const DialogContentComponent = ui.DialogContent;
	const DialogActionsComponent = ui.DialogActions;
	const ButtonComponent = ui.Button;
	const TextFieldComponent = ui.TextField;
	const StackComponent = ui.Stack;
	const BoxComponent = ui.Box;
	const AlertComponent = ui.Alert;

	if (!DialogComponent || !DialogContentComponent || !DialogActionsComponent || 
		!ButtonComponent || !TextFieldComponent || !StackComponent || 
		!BoxComponent || !AlertComponent) {
		return null;
	}

	return {
		Dialog: DialogComponent,
		DialogContent: DialogContentComponent,
		DialogActions: DialogActionsComponent,
		Button: ButtonComponent,
		TextField: TextFieldComponent,
		Stack: StackComponent,
		Box: BoxComponent,
		Alert: AlertComponent,
	};
};
