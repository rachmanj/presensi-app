import { Tooltip, Tag } from 'antd';

function formatBalance(balance) {
  if (!balance || !Array.isArray(balance)) return null;

  return balance.map((item) => {
    const name = item.leave_type_name || item.type || item.name || 'Cuti';
    const days = item.remaining_days ?? item.remaining ?? item.days ?? 0;

    return `${name}: ${days} hari`;
  }).join(', ');
}

export default function LeaveBalanceBadge({ balance, showInline = false }) {
  const text = formatBalance(balance);

  if (!text) {
    return showInline ? <span style={{ color: '#999' }}>—</span> : null;
  }

  if (showInline) {
    return (
      <Tooltip title={text}>
        <Tag color="blue" style={{ cursor: 'help' }}>Cuti</Tag>
      </Tooltip>
    );
  }

  return (
    <Tooltip title={text}>
      <Tag color="blue" style={{ cursor: 'help', marginLeft: 4 }}>Cuti</Tag>
    </Tooltip>
  );
}
