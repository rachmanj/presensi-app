import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ProTable } from '@ant-design/pro-components';
import { Button, Card, Form, Input, Modal, Select, Space, Tag, message } from 'antd';
import ErrorState from '../../components/shared/ErrorState';
import LeaveBalanceBadge from '../../components/shared/LeaveBalanceBadge';
import { mappingService } from '../../services/mappingService';

export default function EmployeeMappingPage() {
  const queryClient = useQueryClient();
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form] = Form.useForm();

  const { data: mappings, isLoading, isError, error, refetch } = useQuery({
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
      message.success('Pemetaan berhasil disimpan');
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
      message.info(`Saran: ${suggestions[0].fullname}`);
    }
  };

  const columns = [
    { title: 'NIP', dataIndex: 'fingerprint_nip', width: 100 },
    { title: 'PIN', dataIndex: 'fingerprint_pin', width: 80 },
    { title: 'NIK', dataIndex: 'nik', width: 100 },
    {
      title: 'Saldo Cuti',
      dataIndex: 'leave_balance',
      width: 100,
      render: (balance) => <LeaveBalanceBadge balance={balance} showInline />,
    },
    { title: 'Lokasi', dataIndex: 'site_code', width: 80 },
    {
      title: 'Aktif',
      dataIndex: 'active',
      width: 80,
      render: (v) => <Tag color={v ? 'green' : 'default'}>{v ? 'Ya' : 'No'}</Tag>,
    },
    { title: 'Catatan', dataIndex: 'note', ellipsis: true },
    {
      title: 'Aksi',
      width: 100,
      render: (_, record) => (
        <Button type="link" size="small" onClick={() => openEdit(record)}>
          Ubah
        </Button>
      ),
    },
  ];

  const list = mappings?.data || mappings || [];

  return (
    <div style={{ padding: 24 }}>
      <Space style={{ marginBottom: 16 }}>
        <Button type="primary" onClick={() => openCreate()}>
          Tambah Pemetaan
        </Button>
      </Space>

      {isError ? (
        <ErrorState
          description={error?.message || 'Daftar pemetaan tidak dapat dimuat.'}
          onRetry={refetch}
        />
      ) : (
        <ProTable
          columns={columns}
          dataSource={list}
          loading={isLoading}
          rowKey="id"
          search={false}
          pagination={{ pageSize: 20 }}
          headerTitle="Pemetaan Karyawan (NIP → NIK)"
          locale={{ emptyText: 'Belum ada pemetaan NIP. Tambahkan pemetaan atau pakai saran otomatis.' }}
        />
      )}

      <Card title="Antrian Belum Tercocok" style={{ marginTop: 24 }}>
        <ProTable
          columns={[
            { title: 'NIP', dataIndex: 'raw_nip' },
            { title: 'Nama', dataIndex: 'raw_name' },
            { title: 'Scan', dataIndex: 'scan_count', width: 80 },
            {
              title: 'Aksi',
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
                  Petakan
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
        title={editing ? 'Ubah Pemetaan' : 'Pemetaan Baru'}
        open={modalOpen}
        onCancel={() => { setModalOpen(false); setEditing(null); }}
        onOk={() => form.submit()}
        confirmLoading={saveMutation.isPending}
        okText="Simpan"
        cancelText="Batal"
      >
        <Form form={form} layout="vertical" onFinish={(v) => saveMutation.mutate(v)}>
          <Form.Item name="fingerprint_nip" label="NIP Fingerprint" rules={[{ required: true, message: 'Wajib diisi' }]}>
            <Input />
          </Form.Item>
          <Form.Item name="fingerprint_pin" label="PIN Fingerprint" rules={[{ required: true, message: 'Wajib diisi' }]}>
            <Input />
          </Form.Item>
          <Form.Item name="nik" label="NIK (HERO)">
            <Input />
          </Form.Item>
          <Form.Item name="site_code" label="Kode Lokasi">
            <Select allowClear options={['HO', 'APS', 'BO', '017C', '021C', '022C', '023C', '025C'].map((c) => ({ value: c, label: c }))} />
          </Form.Item>
          <Form.Item name="note" label="Catatan">
            <Input.TextArea rows={2} />
          </Form.Item>
          <Space>
            <Form.Item name="suggest_name" label="Cocokkan berdasarkan nama" style={{ flex: 1 }}>
              <Input placeholder="Nama karyawan" />
            </Form.Item>
            <Button onClick={handleSuggest} style={{ marginTop: 30 }}>Saran</Button>
          </Space>
        </Form>
      </Modal>
    </div>
  );
}
