import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ProTable } from '@ant-design/pro-components';
import { Button, Form, InputNumber, Modal, Select, Space, Tag, message } from 'antd';
import { Link } from 'react-router-dom';
import { attendanceService } from '../../services/attendanceService';

const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

export default function PeriodListPage() {
  const queryClient = useQueryClient();
  const [createOpen, setCreateOpen] = useState(false);
  const [form] = Form.useForm();

  const { data: periods, isLoading } = useQuery({
    queryKey: ['periods'],
    queryFn: attendanceService.periods.list,
  });

  const createMutation = useMutation({
    mutationFn: attendanceService.periods.create,
    onSuccess: () => {
      message.success('Period created');
      queryClient.invalidateQueries({ queryKey: ['periods'] });
      setCreateOpen(false);
      form.resetFields();
    },
  });

  const columns = [
    { title: 'ID', dataIndex: 'id', width: 60 },
    { title: 'Label', dataIndex: 'label' },
    { title: 'Year', dataIndex: 'year', width: 80 },
    { title: 'Month', dataIndex: 'month', width: 80, render: (m) => monthNames[m] },
    {
      title: 'Status',
      dataIndex: 'status',
      width: 100,
      render: (s) => <Tag>{s}</Tag>,
    },
    { title: 'Sheets', dataIndex: 'sheets_count', width: 80 },
    {
      title: 'Actions',
      render: (_, record) => (
        <Link to={`/attendance/${record.id}`}>
          <Button type="link" size="small">View Sheets</Button>
        </Link>
      ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Space style={{ marginBottom: 16 }}>
        <Button type="primary" onClick={() => setCreateOpen(true)}>New Period</Button>
      </Space>
      <ProTable
        columns={columns}
        dataSource={periods || []}
        loading={isLoading}
        rowKey="id"
        search={false}
        headerTitle="Attendance Periods"
      />

      <Modal
        title="Create Period"
        open={createOpen}
        onCancel={() => setCreateOpen(false)}
        onOk={() => form.submit()}
        confirmLoading={createMutation.isPending}
      >
        <Form form={form} layout="vertical" onFinish={(v) => createMutation.mutate(v)} initialValues={{ year: 2026, month: 6 }}>
          <Form.Item name="year" label="Year" rules={[{ required: true }]}>
            <InputNumber min={2020} max={2099} style={{ width: '100%' }} />
          </Form.Item>
          <Form.Item name="month" label="Month" rules={[{ required: true }]}>
            <Select options={monthNames.slice(1).map((m, i) => ({ value: i + 1, label: m }))} />
          </Form.Item>
        </Form>
      </Modal>
    </div>
  );
}
