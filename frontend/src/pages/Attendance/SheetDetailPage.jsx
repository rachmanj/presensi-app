import { useParams, Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ProTable } from '@ant-design/pro-components';
import { Button, Space, Tag, message } from 'antd';
import { attendanceService } from '../../services/attendanceService';

export default function SheetDetailPage() {
  const { periodId } = useParams();
  const queryClient = useQueryClient();

  const { data: sheets, isLoading } = useQuery({
    queryKey: ['period-sheets', periodId],
    queryFn: () => attendanceService.periods.sheets(periodId),
  });

  const createSheet = useMutation({
    mutationFn: (siteCode) => attendanceService.periods.createSheet(periodId, { site_code: siteCode }),
    onSuccess: () => {
      message.success('Sheet created');
      queryClient.invalidateQueries({ queryKey: ['period-sheets', periodId] });
    },
  });

  const generate = useMutation({
    mutationFn: (sheetId) => attendanceService.sheets.generate(sheetId),
    onSuccess: () => message.success('Generation queued — refresh in a moment'),
  });

  const columns = [
    { title: 'ID', dataIndex: 'id', width: 60 },
    { title: 'Site', dataIndex: 'site_code', width: 80 },
    {
      title: 'Status',
      dataIndex: 'status',
      width: 100,
      render: (s) => <Tag color={s === 'review' ? 'blue' : s === 'finalized' ? 'green' : 'default'}>{s}</Tag>,
    },
    { title: 'Template', dataIndex: ['report_template', 'name'], ellipsis: true },
    {
      title: 'Actions',
      render: (_, record) => (
        <Space>
          <Link to={`/attendance/sheet/${record.id}/review`}>
            <Button type="link" size="small">Review Grid</Button>
          </Link>
          <Button type="link" size="small" onClick={() => generate.mutate(record.id)}>Generate</Button>
          <Link to={`/export?sheet=${record.id}`}>
            <Button type="link" size="small">Export</Button>
          </Link>
        </Space>
      ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Space style={{ marginBottom: 16 }}>
        <Button onClick={() => createSheet.mutate('HO')}>+ HO Sheet</Button>
        <Button onClick={() => createSheet.mutate('APS')}>+ APS Sheet</Button>
        <Link to="/attendance"><Button>Back to Periods</Button></Link>
      </Space>
      <ProTable
        columns={columns}
        dataSource={sheets || []}
        loading={isLoading}
        rowKey="id"
        search={false}
        headerTitle={`Sheets for Period #${periodId}`}
      />
    </div>
  );
}
