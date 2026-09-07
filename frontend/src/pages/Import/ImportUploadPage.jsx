import { useEffect, useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Upload, Button, Card, Progress, Alert, Descriptions, Spin } from 'antd';
import { InboxOutlined } from '@ant-design/icons';
import ErrorState from '../../components/shared/ErrorState';
import { attendanceService } from '../../services/attendanceService';
import { importService } from '../../services/importService';

const { Dragger } = Upload;

export default function ImportUploadPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const sheetId = searchParams.get('sheet');
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [importRecord, setImportRecord] = useState(null);
  const [parseStatus, setParseStatus] = useState(null);

  const {
    isLoading: sheetLoading,
    isError: sheetError,
    error: sheetErrorObj,
    refetch: refetchSheet,
  } = useQuery({
    queryKey: ['sheet', sheetId],
    queryFn: () => attendanceService.sheets.show(sheetId),
    enabled: !!sheetId,
  });

  useEffect(() => {
    if (!importRecord?.id) return undefined;

    const interval = setInterval(async () => {
      const status = await importService.status(importRecord.id);
      setParseStatus(status);
      if (status.status === 'parsed' || status.status === 'failed') {
        clearInterval(interval);
      }
    }, 2000);

    return () => clearInterval(interval);
  }, [importRecord?.id]);

  const handleUpload = async (file) => {
    if (!sheetId) return false;
    setUploading(true);
    setProgress(0);
    try {
      const result = await importService.upload(sheetId, file, (e) => {
        setProgress(Math.round((e.loaded / e.total) * 100));
      });
      setImportRecord(result);
    } finally {
      setUploading(false);
    }
    return false;
  };

  if (!sheetId) {
    return (
      <div style={{ padding: 24 }}>
        <Alert type="warning" message="No sheet selected. Open the import list and select a sheet first." />
      </div>
    );
  }

  if (sheetLoading) {
    return (
      <div style={{ padding: 24 }}>
        <Spin />
      </div>
    );
  }

  if (sheetError) {
    return (
      <div style={{ padding: 24 }}>
        <Card title="Upload Fingerprint File">
          <ErrorState
            description={sheetErrorObj?.message || 'Failed to load sheet data.'}
            onRetry={refetchSheet}
          />
        </Card>
      </div>
    );
  }

  return (
    <div style={{ padding: 24, maxWidth: 720 }}>
      <Card title="Upload Fingerprint File">
        <Dragger
          accept=".xls,.xlsx"
          showUploadList={false}
          disabled={uploading}
          beforeUpload={handleUpload}
        >
          <p className="ant-upload-drag-icon">
            <InboxOutlined />
          </p>
          <p className="ant-upload-text">Click or drag a fingerprint .xls file here</p>
          <p className="ant-upload-hint">Format 1 (scan log) or Format 2 (pair + DNC)</p>
        </Dragger>

        {uploading && <Progress percent={progress} style={{ marginTop: 16 }} />}

        {importRecord && (
          <Descriptions bordered size="small" style={{ marginTop: 16 }} column={2}>
            <Descriptions.Item label="Import ID">{importRecord.id}</Descriptions.Item>
            <Descriptions.Item label="Format">{importRecord.format}</Descriptions.Item>
            <Descriptions.Item label="Status">{parseStatus?.status || importRecord.status}</Descriptions.Item>
            <Descriptions.Item label="Matched">{parseStatus?.rows_matched ?? 'Pending'}</Descriptions.Item>
          </Descriptions>
        )}

        {parseStatus?.status === 'parsed' && (
          <Alert
            type="success"
            message="Parse complete"
            style={{ marginTop: 16 }}
            action={
              <Button size="small" onClick={() => navigate('/import')}>
                Back to list
              </Button>
            }
          />
        )}

        {parseStatus?.status === 'failed' && (
          <Alert type="error" message="Parse failed, check error log" style={{ marginTop: 16 }} />
        )}
      </Card>
    </div>
  );
}
