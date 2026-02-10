import { getReact } from '../utils/getReact';
import { getElementorUI } from '../utils/getElementorUI';
import { ModalHeaderProps } from '../types/components';

export const ModalHeader = ({ onClose }: ModalHeaderProps) => {
	const React = getReact();
	const ui = getElementorUI();

	if (!React || !ui) {
		return null;
	}

	const { Box: BoxComponent } = ui;

	return (
		<BoxComponent
			sx={{
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'space-between',
				height: '50px',
				minHeight: '50px',
				borderBottom: 'var(--e-a-border, 1px solid #d5dade)',
			}}
		>
			<BoxComponent
				sx={{
					textAlign: 'start',
					paddingInlineStart: '15px',
				}}
			>
				<BoxComponent
					sx={{
						display: 'flex',
						alignItems: 'center',
						lineHeight: 1,
						textTransform: 'uppercase',
						fontWeight: 'bold',
					}}
				>
					<BoxComponent
						sx={{
							marginInlineEnd: '10px',
							fontSize: '12px',
						}}
					>
						<i className="eicon-globe" />
					</BoxComponent>
					<BoxComponent
						sx={{
							color: 'var(--e-a-color-txt-active, #515962)',
							paddingBlockStart: '2px',
						}}
					>
						Import Website
					</BoxComponent>
				</BoxComponent>
			</BoxComponent>
			<BoxComponent
				sx={{
					display: 'flex',
					flexDirection: 'row-reverse',
				}}
			>
				<BoxComponent
					onClick={onClose}
					sx={{
						cursor: 'pointer',
						width: '47px',
						borderInlineStart: 'var(--e-a-border, 1px solid #d5dade)',
						position: 'relative',
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
						boxSizing: 'content-box',
					}}
				>
					<i
						className="eicon-close"
						aria-hidden={true}
						style={{
							fontSize: '18px',
							transition: 'var(--e-a-transition-hover, all 0.3s)',
							cursor: 'pointer',
						}}
						onMouseEnter={(e: any) => {
							e.currentTarget.style.color = 'var(--e-a-color-txt-hover, #5e72e4)';
						}}
						onMouseLeave={(e: any) => {
							e.currentTarget.style.color = '';
						}}
					/>
					<span className="elementor-screen-only">Close</span>
				</BoxComponent>
			</BoxComponent>
		</BoxComponent>
	);
};
