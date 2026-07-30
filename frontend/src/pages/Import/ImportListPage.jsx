import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { ProTable } from '@ant-design/pro-components';
import { Button, Select, Space, Tag } from 'antd';
import { Link } from 'react-router-dom';
import { attendanceService } from '../../services/attendanceService';
import { importService } from '../../services/importService';

const statusColors = {
  uploaded: 'default',
  parsing: 'processing',
  parsed: 'success',
  failed: 'error',
};

export default function ImportListPage() {
  const [sheetId, setSheetId] = useState(null);

  const { data: periods } = useQuery({
    queryKey: ['periods'],
    queryFn: attendanceService.periods.list,
  });

  const { data: sheets } = useQuery({
    queryKey: ['sheets', periods?.[0]?.id],
    queryFn: () => attendanceService.periods.sheets(periods[0].id),
    enabled: !!periods?.length,
  });

  const activeSheetId = sheetId || sheets?.[0]?.id;

  const { data: imports, isLoading } = useQuery({
    queryKey: ['imports', activeSheetId],
    queryFn: () => importService.list(activeSheetId),
    enabled: !!activeSheetId,
    refetchInterval: 5000,
  });

  const columns = [
    { title: 'ID', dataIndex: 'id', width: 60 },
    { title: 'Filename', dataIndex: 'original_filename', ellipsis: true },
    { title: 'Format', dataIndex: 'format', width: 140 },
    {
      title: 'Status',
      dataIndex: 'status',
      width: 100,
      render: (s) => <Tag color={statusColors[s]}>{s}</Tag>,
    },
    { title: 'Total', dataIndex: 'rows_total', width: 70 },
    { title: 'Matched', dataIndex: 'rows_matched', width: 80 },
    { title: 'Unmatched', dataIndex: 'rows_unmatched', width: 90 },
    { title: 'Uploaded', dataIndex: 'uploaded_by', width: 120 },
    {
      title: 'Actions',
      width: 120,
      render: (_, record) => (
        <Space>
          <Link to={`/import/upload?sheet=${activeSheetId}`}>Upload</Link>
          <Button type="link" size="small" danger onClick={() => importService.remove(record.id)}>
            Delete
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Space style={{ marginBottom: 16 }}>
        <Select
          placeholder="Select sheet"
          style={{ width: 280 }}
          value={activeSheetId}
          onChange={setSheetId}
          options={sheets?.map((s) => ({
            value: s.id,
            label: `${s.site_code} — Period ${s.period_id}`,
          }))}
        />
        <Link to={`/import/upload?sheet=${activeSheetId || ''}`}>
          <Button type="primary">Upload New File</Button>
        </Link>
      </Space>
      <ProTable
        columns={columns}
        dataSource={imports || []}
        loading={isLoading}
        rowKey="id"
        search={false}
        pagination={{ pageSize: 20 }}
        headerTitle="Fingerprint Imports"
      />
    </div>
  );
}
