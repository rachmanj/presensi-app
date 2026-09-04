import { useEffect, useState } from 'react';
import { PlusOutlined } from '@ant-design/icons';
import { ModalForm, ProFormDatePicker, ProFormSelect, ProFormText, ProTable } from '@ant-design/pro-components';
import { Button, message, Popconfirm, Table, Typography } from 'antd';
import { adminService } from '../../services/adminService';

export default function MatrixConfigPage() {
  const [gridData, setGridData] = useState({ sites: [], grid: [] });
  const [editing, setEditing] = useState(null);
  const [loading, setLoading] = useState(true);

  const loadGrid = async () => {
    setLoading(true);
    try {
      const data = await adminService.matrixRules.grid();
      setGridData(data);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { loadGrid(); }, []);

  const gridColumns = [
    { title: 'Asal \\ Kunjungan', dataIndex: 'home_site_code', fixed: 'left', width: 100 },
    ...gridData.sites.map((site) => ({
      title: site,
      dataIndex: ['cells', site],
      width: 80,
      render: (cell) => cell?.code || '-',
    })),
  ];

  const listColumns = [
    { title: 'Asal', dataIndex: 'home_site_code', width: 80 },
    { title: 'Kunjungan', dataIndex: 'visit_site_code', width: 80 },
    { title: 'Code', dataIndex: 'code', width: 80 },
    { title: 'Berlaku Dari', dataIndex: 'effective_from', width: 120 },
    { title: 'Berlaku Sampai', dataIndex: 'effective_to', width: 120 },
    {
      title: 'Aksi',
      valueType: 'option',
      render: (_, record) => [
        <a key="edit" onClick={() => setEditing(record)}>Ubah</a>,
        <Popconfirm
          key="delete"
          title="Hapus aturan matriks ini?"
          okText="Hapus"
          cancelText="Batal"
          onConfirm={async () => {
            await adminService.matrixRules.remove(record.id);
            message.success('Berhasil dihapus');
            loadGrid();
          }}
        >
          <a>Hapus</a>
        </Popconfirm>,
      ],
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Typography.Title level={4}>Grid Matriks</Typography.Title>
      <Table
        loading={loading}
        dataSource={gridData.grid}
        columns={gridColumns}
        rowKey="home_site_code"
        scroll={{ x: 'max-content' }}
        pagination={false}
        size="small"
        style={{ marginBottom: 32 }}
      />

      <ProTable
        headerTitle="Aturan Matriks"
        rowKey="id"
        search={false}
        toolBarRender={() => [
          <Button key="add" type="primary" icon={<PlusOutlined />} onClick={() => setEditing({ effective_from: '2025-01-01' })}>
            Tambah Aturan
          </Button>,
        ]}
        request={async () => ({ data: await adminService.matrixRules.list(), success: true })}
        columns={listColumns}
      />

      <ModalForm
        title={editing?.id ? 'Ubah Aturan Matriks' : 'Tambah Aturan Matriks'}
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        initialValues={editing || {}}
        modalProps={{ okText: 'Simpan', cancelText: 'Batal' }}
        onFinish={async (values) => {
          const payload = {
            ...values,
            effective_from: values.effective_from?.format?.('YYYY-MM-DD') || values.effective_from,
            effective_to: values.effective_to?.format?.('YYYY-MM-DD') || values.effective_to || null,
          };
          if (editing?.id) {
            await adminService.matrixRules.update(editing.id, payload);
            message.success('Berhasil diperbarui');
          } else {
            await adminService.matrixRules.create(payload);
            message.success('Berhasil dibuat');
          }
          setEditing(null);
          loadGrid();
          return true;
        }}
      >
        <ProFormSelect
          name="home_site_code"
          label="Lokasi Asal"
          options={gridData.sites.map((s) => ({ label: s, value: s }))}
          rules={[{ required: true, message: 'Wajib diisi' }]}
        />
        <ProFormSelect
          name="visit_site_code"
          label="Lokasi Kunjungan"
          options={gridData.sites.map((s) => ({ label: s, value: s }))}
          rules={[{ required: true, message: 'Wajib diisi' }]}
        />
        <ProFormText name="code" label="Code" rules={[{ required: true, message: 'Wajib diisi' }]} />
        <ProFormDatePicker name="effective_from" label="Berlaku Dari" rules={[{ required: true, message: 'Wajib diisi' }]} />
        <ProFormDatePicker name="effective_to" label="Berlaku Sampai" />
      </ModalForm>
    </div>
  );
}
