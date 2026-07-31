import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ProTable } from '@ant-design/pro-components';
import { Button, Card, Form, Input, Modal, Select, Space, Tag, message } from 'antd';
import LeaveBalanceBadge from '../../components/shared/LeaveBalanceBadge';
import { mappingService } from '../../services/mappingService';

export default function EmployeeMappingPage() {
  const queryClient = useQueryClient();
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form] = Form.useForm();

  const { data: mappings, isLoading } = useQuery({
    queryKey: ['employee-maps'],
    queryFn: () => mappingService.list({ per_page: 100 }),
  });

  const { data: unmatched } = useQuery({
    queryKey: ['unmatched-nips'],
    queryFn: () => mappingService.unmatched(),
  });

  const saveMutation = useMutation({
    mutationFn: (values) =>
      editing
        ? mappingService.update(editing.id, values)
        : mappingService.create(values),
    onSuccess: () => {
      message.success('Mapping saved');
      queryClient.invalidateQueries({ queryKey: ['employee-maps'] });
      queryClient.invalidateQueries({ queryKey: ['unmatched-nips'] });
      setModalOpen(false);
      setEditing(null);
      form.resetFields();
    },
  });

  const openCreate = (prefill = {}) => {
    setEditing(null);
    form.setFieldsValue({ active: true, ...prefill });
    setModalOpen(true);
  };

  const openEdit = (record) => {
    setEditing(record);
    form.setFieldsValue(record);
    setModalOpen(true);
  };

  const handleSuggest = async () => {
    const name = form.getFieldValue('suggest_name');
    if (!name) return;
    const suggestions = await mappingService.suggest(name);
    if (suggestions[0]) {
      form.setFieldsValue({ nik: suggestions[0].nik });
      message.info(`Suggested: ${suggestions[0].fullname}`);
    }
  };

  const columns = [
    { title: 'NIP', dataIndex: 'fingerprint_nip', width: 100 },
    { title: 'PIN', dataIndex: 'fingerprint_pin', width: 80 },
    { title: 'NIK', dataIndex: 'nik', width: 100 },
    {
      title: 'Leave Balance',
      dataIndex: 'leave_balance',
      width: 100,
      render: (balance) => <LeaveBalanceBadge balance={balance} showInline />,
    },
    { title: 'Site', dataIndex: 'site_code', width: 80 },
    {
      title: 'Active',
      dataIndex: 'active',
      width: 80,
      render: (v) => <Tag color={v ? 'green' : 'default'}>{v ? 'Yes' : 'No'}</Tag>,
    },
    { title: 'Note', dataIndex: 'note', ellipsis: true },
    {
      title: 'Actions',
      width: 100,
      render: (_, record) => (
        <Button type="link" size="small" onClick={() => openEdit(record)}>
          Edit
        </Button>
      ),
    },
  ];

  const list = mappings?.data || mappings || [];

  return (
    <div style={{ padding: 24 }}>
      <Space style={{ marginBottom: 16 }}>
        <Button type="primary" onClick={() => openCreate()}>
          Add Mapping
        </Button>
      </Space>

      <ProTable
        columns={columns}
        dataSource={list}
        loading={isLoading}
        rowKey="id"
        search={false}
        pagination={{ pageSize: 20 }}
        headerTitle="Employee Mappings (NIP → NIK)"
      />

      <Card title="Unmatched Queue" style={{ marginTop: 24 }}>
        <ProTable
          columns={[
            { title: 'NIP', dataIndex: 'raw_nip' },
            { title: 'Name', dataIndex: 'raw_name' },
            { title: 'Scans', dataIndex: 'scan_count', width: 80 },
            {
              title: 'Action',
              width: 120,
              render: (_, record) => (
                <Button
                  type="link"
                  size="small"
                  onClick={() => openCreate({
                    fingerprint_nip: record.raw_nip,
                    fingerprint_pin: record.raw_pin || record.raw_nip,
                    suggest_name: record.raw_name,
                  })}
                >
                  Map
                </Button>
              ),
            },
          ]}
          dataSource={unmatched || []}
          rowKey="raw_nip"
          search={false}
          pagination={false}
          size="small"
        />
      </Card>

      <Modal
        title={editing ? 'Edit Mapping' : 'New Mapping'}
        open={modalOpen}
        onCancel={() => { setModalOpen(false); setEditing(null); }}
        onOk={() => form.submit()}
        confirmLoading={saveMutation.isPending}
      >
        <Form form={form} layout="vertical" onFinish={(v) => saveMutation.mutate(v)}>
          <Form.Item name="fingerprint_nip" label="Fingerprint NIP" rules={[{ required: true }]}>
            <Input />
          </Form.Item>
          <Form.Item name="fingerprint_pin" label="Fingerprint PIN" rules={[{ required: true }]}>
            <Input />
          </Form.Item>
          <Form.Item name="nik" label="NIK (HERO)">
            <Input />
          </Form.Item>
          <Form.Item name="site_code" label="Site Code">
            <Select allowClear options={['HO', 'APS', 'BO', '017C', '021C', '022C', '023C', '025C'].map((c) => ({ value: c, label: c }))} />
          </Form.Item>
          <Form.Item name="note" label="Note">
            <Input.TextArea rows={2} />
          </Form.Item>
          <Space>
            <Form.Item name="suggest_name" label="Fuzzy match by name" style={{ flex: 1 }}>
              <Input placeholder="Employee name" />
            </Form.Item>
            <Button onClick={handleSuggest} style={{ marginTop: 30 }}>Suggest</Button>
          </Space>
        </Form>
      </Modal>
    </div>
  );
}
