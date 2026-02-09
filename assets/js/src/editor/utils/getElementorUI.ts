import { ElementorUIComponents } from '../types/utils';

export const getElementorUI = (): ElementorUIComponents | null => {
	const ui = (window as any).elementorV2?.ui;
	if (!ui) {
		return null;
	}

	const DialogComponent = ui.Dialog;
	const DialogContentComponent = ui.DialogContent;
	const DialogActionsComponent = ui.DialogActions;
	const DialogTitleComponent = ui.DialogTitle;
	const ButtonComponent = ui.Button;
	const TextFieldComponent = ui.TextField;
	const StackComponent = ui.Stack;
	const BoxComponent = ui.Box;
	const AlertComponent = ui.Alert;
	const TabComponent = ui.Tab;
	const TabsComponent = ui.Tabs;
	const TypographyComponent = ui.Typography;
	const IconButtonComponent = ui.IconButton;
	const RadioComponent = ui.Radio;
	const RadioGroupComponent = ui.RadioGroup;
	const FormControlLabelComponent = ui.FormControlLabel;

	if (!DialogComponent || !DialogContentComponent || !DialogActionsComponent ||
		!ButtonComponent || !TextFieldComponent || !StackComponent ||
		!BoxComponent || !AlertComponent) {
		return null;
	}

	return {
		Dialog: DialogComponent,
		DialogContent: DialogContentComponent,
		DialogActions: DialogActionsComponent,
		DialogTitle: DialogTitleComponent,
		Button: ButtonComponent,
		TextField: TextFieldComponent,
		Stack: StackComponent,
		Box: BoxComponent,
		Alert: AlertComponent,
		Tab: TabComponent,
		Tabs: TabsComponent,
		Typography: TypographyComponent,
		IconButton: IconButtonComponent,
		Radio: RadioComponent,
		RadioGroup: RadioGroupComponent,
		FormControlLabel: FormControlLabelComponent,
	};
};
