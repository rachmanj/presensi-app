import { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Button, Card, Descriptions, Select, Space } from 'antd';
import { attendanceService } from '../../services/attendanceService';
import { exportService } from '../../services/exportService';

export default function ExportPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const sheetId = searchParams.get('sheet');
  const [selected, setSelected] = useState(sheetId);

  const { data: periods } = useQuery({
    queryKey: ['periods'],
    queryFn: attendanceService.periods.list,
  });

  const latestPeriod = periods?.[0];

  const { data: sheets } = useQuery({
    queryKey: ['export-sheets', latestPeriod?.id],
    queryFn: () => attendanceService.periods.sheets(latestPeriod.id),
    enabled: !!latestPeriod?.id,
  });

  const activeSheet = selected || sheets?.[0]?.id;

  const { data: preview } = useQuery({
    queryKey: ['export-preview', activeSheet],
    queryFn: () => exportService.preview(activeSheet),
    enabled: !!activeSheet,
  });

  const handleDownload = () => {
    window.open(exportService.downloadUrl(activeSheet), '_blank');
  };

  return (
    <div style={{ padding: 24, maxWidth: 640 }}>
      <Card title="Export Attendance Report">
        <Space direction="vertical" style={{ width: '100%' }} size="large">
          <Select
            placeholder="Select sheet to export"
            style={{ width: '100%' }}
            value={activeSheet ? Number(activeSheet) : undefined}
            onChange={(v) => {
              setSelected(v);
              setSearchParams({ sheet: v });
            }}
            options={sheets?.map((s) => ({
              value: s.id,
              label: `${s.site_code} — ${latestPeriod?.label} (${s.status})`,
            }))}
          />

          {preview && (
            <Descriptions bordered size="small" column={1}>
              <Descriptions.Item label="Site">{preview.sheet?.site_code}</Descriptions.Item>
              <Descriptions.Item label="Period">{preview.sheet?.period?.label}</Descriptions.Item>
              <Descriptions.Item label="Template">{preview.sheet?.report_template?.name}</Descriptions.Item>
              <Descriptions.Item label="Employees">{preview.summary?.total_employees}</Descriptions.Item>
              <Descriptions.Item label="Overridden Cells">{preview.summary?.overridden_cells}</Descriptions.Item>
            </Descriptions>
          )}

          <Button type="primary" size="large" onClick={handleDownload} disabled={!activeSheet}>
            Download Excel
          </Button>
        </Space>
      </Card>
    </div>
  );
}
