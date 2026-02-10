import { getReact } from '../../utils/getReact';

type SyncButtonProps = {
	onClick: () => void;
};

export const SyncButton = ({ onClick }: SyncButtonProps) => {
	const React = getReact();

	if (!React) {
		return null;
	}

	return React.createElement(
		'button',
		{
			type: 'button',
			onClick: onClick,
			'aria-label': 'Import / Export',
			style: {
				padding: '4px',
				borderRadius: '4px',
				border: 'none',
				background: 'none',
				cursor: 'pointer',
				display: 'inline-flex',
				alignItems: 'center',
				justifyContent: 'center',
				color: 'inherit',
			},
			onMouseEnter: (e: any) => {
				e.currentTarget.style.backgroundColor = 'rgba(0, 0, 0, 0.04)';
			},
			onMouseLeave: (e: any) => {
				e.currentTarget.style.backgroundColor = 'transparent';
			},
		},
		React.createElement('i', {
			className: 'eicon-sync',
			style: { fontSize: '16px', display: 'inline-block' },
		})
	);
};
