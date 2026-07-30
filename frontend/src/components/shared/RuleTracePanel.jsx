const RULE_COLORS = {
  'matrix.': '#1677ff',
  'leave.': '#722ed1',
  'overtime.': '#fa8c16',
  'daytype.': '#8c8c8c',
  'presence.': '#cf1322',
  'fingerprint.': '#13c2c2',
};

function colorForRule(ruleKey) {
  const prefix = Object.keys(RULE_COLORS).find((p) => ruleKey?.startsWith(p));
  return prefix ? RULE_COLORS[prefix] : '#595959';
}

export default function RuleTracePanel({ traces }) {
  if (!traces?.length) {
    return <p style={{ color: '#999' }}>No trace records available.</p>;
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
      {traces.map((trace) => (
        <div
          key={trace.id}
          style={{
            borderLeft: `3px solid ${colorForRule(trace.rule_key)}`,
            paddingLeft: 12,
            paddingBottom: 4,
          }}
        >
          <div style={{ fontWeight: 600, fontSize: 12, color: colorForRule(trace.rule_key) }}>
            {trace.rule_key}
          </div>
          <div style={{ fontSize: 13 }}>{trace.explanation}</div>
          {trace.inputs && (
            <pre style={{ fontSize: 11, color: '#666', margin: '4px 0 0', overflow: 'auto' }}>
              {JSON.stringify(trace.inputs, null, 2)}
            </pre>
          )}
        </div>
      ))}
    </div>
  );
}
