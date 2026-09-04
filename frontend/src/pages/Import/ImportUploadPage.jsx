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
        <Alert type="warning" message="Belum ada sheet dipilih. Buka daftar impor dan pilih sheet terlebih dahulu." />
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
        <Card title="Unggah File Fingerprint">
          <ErrorState
            description={sheetErrorObj?.message || 'Data sheet tidak dapat dimuat.'}
            onRetry={refetchSheet}
          />
        </Card>
      </div>
    );
  }

  return (
    <div style={{ padding: 24, maxWidth: 720 }}>
      <Card title="Unggah File Fingerprint">
        <Dragger
          accept=".xls,.xlsx"
          showUploadList={false}
          disabled={uploading}
          beforeUpload={handleUpload}
        >
          <p className="ant-upload-drag-icon">
            <InboxOutlined />
          </p>
          <p className="ant-upload-text">Klik atau seret file fingerprint .xls ke sini</p>
          <p className="ant-upload-hint">Format 1 (log scan) atau Format 2 (pasangan + DNC)</p>
        </Dragger>

        {uploading && <Progress percent={progress} style={{ marginTop: 16 }} />}

        {importRecord && (
          <Descriptions bordered size="small" style={{ marginTop: 16 }} column={2}>
            <Descriptions.Item label="ID Impor">{importRecord.id}</Descriptions.Item>
            <Descriptions.Item label="Format">{importRecord.format}</Descriptions.Item>
            <Descriptions.Item label="Status">{parseStatus?.status || importRecord.status}</Descriptions.Item>
            <Descriptions.Item label="Tercocok">{parseStatus?.rows_matched ?? 'Belum'}</Descriptions.Item>
          </Descriptions>
        )}

        {parseStatus?.status === 'parsed' && (
          <Alert
            type="success"
            message="Parse selesai"
            style={{ marginTop: 16 }}
            action={
              <Button size="small" onClick={() => navigate('/import')}>
                Kembali ke daftar
              </Button>
            }
          />
        )}

        {parseStatus?.status === 'failed' && (
          <Alert type="error" message="Parse gagal, periksa log error" style={{ marginTop: 16 }} />
        )}
      </Card>
    </div>
  );
}
