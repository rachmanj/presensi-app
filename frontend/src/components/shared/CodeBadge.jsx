export default function CodeBadge({ code, isOverridden, dayType }) {
  if (!code) {
    const isWeekend = ['saturday', 'sunday', 'holiday'].includes(dayType);
    return (
      <span style={{ color: isWeekend ? '#999' : '#ccc', fontSize: 11 }}>
        {isWeekend ? '' : '—'}
      </span>
    );
  }

  let bg = '#e6f4ff';
  let color = '#1677ff';
  let border = '1px solid #91caff';

  if (isOverridden) {
    bg = '#fffbe6';
    color = '#d48806';
    border = '1px solid #ffe58f';
  }

  if (['1906'].includes(code)) {
    bg = '#fff2f0';
    color = '#cf1322';
    border = '1px solid #ffccc7';
  }

  return (
    <span
      style={{
        display: 'inline-block',
        padding: '1px 4px',
        borderRadius: 3,
        fontSize: 11,
        fontWeight: 500,
        background: bg,
        color,
        border,
        minWidth: 28,
        textAlign: 'center',
      }}
    >
      {code}
    </span>
  );
}
