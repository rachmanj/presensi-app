import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ProTable } from '@ant-design/pro-components';
import { Button, Form, InputNumber, Modal, Select, Space, Tag, message } from 'antd';
import { Link } from 'react-router-dom';
import ErrorState from '../../components/shared/ErrorState';
import { attendanceService } from '../../services/attendanceService';

const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

export default function PeriodListPage() {
  const queryClient = useQueryClient();
  const [createOpen, setCreateOpen] = useState(false);
  const [form] = Form.useForm();

  const { data: periods, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['periods'],
    queryFn: attendanceService.periods.list,
  });

  const createMutation = useMutation({
    mutationFn: attendanceService.periods.create,
    onSuccess: () => {
      message.success('Periode berhasil dibuat');
      queryClient.invalidateQueries({ queryKey: ['periods'] });
      setCreateOpen(false);
      form.resetFields();
    },
  });

  const columns = [
    { title: 'ID', dataIndex: 'id', width: 60 },
    { title: 'Label', dataIndex: 'label' },
    { title: 'Tahun', dataIndex: 'year', width: 80 },
    { title: 'Bulan', dataIndex: 'month', width: 80, render: (m) => monthNames[m] },
    {
      title: 'Status',
      dataIndex: 'status',
      width: 100,
      render: (s) => <Tag>{s}</Tag>,
    },
    { title: 'Sheet', dataIndex: 'sheets_count', width: 80 },
    {
      title: 'Aksi',
      render: (_, record) => (
        <Link to={`/attendance/${record.id}`}>
          <Button type="link" size="small">Lihat Sheet</Button>
        </Link>
      ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Space style={{ marginBottom: 16 }}>
        <Button type="primary" onClick={() => setCreateOpen(true)}>Periode Baru</Button>
      </Space>
      {isError ? (
        <ErrorState
          description={error?.message || 'Daftar periode tidak dapat dimuat.'}
          onRetry={refetch}
        />
      ) : (
        <ProTable
          columns={columns}
          dataSource={periods || []}
          loading={isLoading}
          rowKey="id"
          search={false}
          headerTitle="Periode Kehadiran"
          locale={{ emptyText: 'Belum ada periode. Buat periode baru untuk mulai.' }}
        />
      )}

      <Modal
        title="Buat Periode"
        open={createOpen}
        onCancel={() => setCreateOpen(false)}
        onOk={() => form.submit()}
        confirmLoading={createMutation.isPending}
        okText="Simpan"
        cancelText="Batal"
      >
        <Form form={form} layout="vertical" onFinish={(v) => createMutation.mutate(v)} initialValues={{ year: 2026, month: 6 }}>
          <Form.Item name="year" label="Tahun" rules={[{ required: true, message: 'Wajib diisi' }]}>
            <InputNumber min={2020} max={2099} style={{ width: '100%' }} />
          </Form.Item>
          <Form.Item name="month" label="Bulan" rules={[{ required: true, message: 'Wajib diisi' }]}>
            <Select options={monthNames.slice(1).map((m, i) => ({ value: i + 1, label: m }))} />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
}
