import { useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Button, Card, Descriptions, Empty, Select, Space, Spin } from 'antd';
import ErrorState from '../../components/shared/ErrorState';
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

  const { data: sheets, isLoading: sheetsLoading } = useQuery({
    queryKey: ['export-sheets', latestPeriod?.id],
    queryFn: () => attendanceService.periods.sheets(latestPeriod.id),
    enabled: !!latestPeriod?.id,
  });

  const activeSheet = selected || sheets?.[0]?.id;

  const {
    data: preview,
    isLoading: previewLoading,
    isError: previewError,
    error: previewErrorObj,
    refetch: refetchPreview,
  } = useQuery({
    queryKey: ['export-preview', activeSheet],
    queryFn: () => exportService.preview(activeSheet),
    enabled: !!activeSheet,
  });

  const handleDownload = () => {
    window.open(exportService.downloadUrl(activeSheet), '_blank');
  };

  const renderPreviewArea = () => {
    if (!activeSheet) {
      return (
        <Empty description="Belum ada sheet tersedia. Buat periode dan buat sheet terlebih dahulu." />
      );
    }

    if (previewLoading) {
      return <Spin />;
    }

    if (previewError) {
      return (
        <ErrorState
          description={previewErrorObj?.message || 'Pratinjau ekspor tidak dapat dimuat.'}
          onRetry={refetchPreview}
        />
      );
    }

    if (!preview) {
      return (
        <Empty description="Sheet ini belum memiliki data untuk diekspor. Buat sheet terlebih dahulu." />
      );
    }

    return (
      <Descriptions bordered size="small" column={1}>
        <Descriptions.Item label="Lokasi">{preview.sheet?.site_code}</Descriptions.Item>
        <Descriptions.Item label="Periode">{preview.sheet?.period?.label}</Descriptions.Item>
        <Descriptions.Item label="Template">{preview.sheet?.report_template?.name}</Descriptions.Item>
        <Descriptions.Item label="Karyawan">{preview.summary?.total_employees}</Descriptions.Item>
        <Descriptions.Item label="Sel Di-override">{preview.summary?.overridden_cells}</Descriptions.Item>
      </Descriptions>
    );
  };

  return (
    <div style={{ padding: 24, maxWidth: 640 }}>
      <Card title="Ekspor Laporan Kehadiran" loading={sheetsLoading}>
        <Space direction="vertical" style={{ width: '100%' }} size="large">
          <Select
            placeholder="Pilih sheet untuk ekspor"
            style={{ width: '100%' }}
            value={activeSheet ? Number(activeSheet) : undefined}
            onChange={(v) => {
              setSelected(v);
              setSearchParams({ sheet: v });
            }}
            options={sheets?.map((s) => ({
              value: s.id,
              label: `${s.site_code} - ${latestPeriod?.label} (${s.status})`,
            }))}
          />

          {renderPreviewArea()}

          <Button type="primary" size="large" onClick={handleDownload} disabled={!activeSheet || !preview}>
            Unduh Excel
          </Button>
          <Button
            size="large"
            onClick={() => window.open(exportService.downloadPdfUrl(activeSheet), '_blank')}
            disabled={!activeSheet || !preview}
          >
            Unduh PDF
          </Button>
        </Space>
      </Card>
    </div>
  );
}
