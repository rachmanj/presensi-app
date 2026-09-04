import { useRef, useState } from 'react';
import { PlusOutlined } from '@ant-design/icons';
import { ModalForm, ProFormSelect, ProFormSwitch, ProFormText, ProTable } from '@ant-design/pro-components';
import { Button, message, Popconfirm } from 'antd';
import { adminService } from '../../services/adminService';

export default function SiteConfigPage() {
  const actionRef = useRef();
  const [editing, setEditing] = useState(null);

  const columns = [
    { title: 'Code', dataIndex: 'code', width: 80 },
    { title: 'Nama', dataIndex: 'name' },
    { title: 'Profil', dataIndex: 'profile', width: 100 },
    { title: 'Kode Hadir Dasar', dataIndex: 'base_present_code', width: 140 },
    {
      title: 'Aktif',
      dataIndex: 'active',
      width: 80,
      render: (v) => (v ? 'Ya' : 'No'),
    },
    {
      title: 'Aksi',
      valueType: 'option',
      width: 120,
      render: (_, record) => [
        <a key="edit" onClick={() => setEditing(record)}>Ubah</a>,
        <Popconfirm
          key="delete"
          title="Hapus lokasi ini?"
          okText="Hapus"
          cancelText="Batal"
          onConfirm={async () => {
            await adminService.sites.remove(record.id);
            message.success('Berhasil dihapus');
            actionRef.current?.reload();
          }}
        >
          <a>Hapus</a>
        </Popconfirm>,
      ],
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <ProTable
        headerTitle="Konfigurasi Lokasi"
        actionRef={actionRef}
        rowKey="id"
        search={false}
        toolBarRender={() => [
          <Button key="add" type="primary" icon={<PlusOutlined />} onClick={() => setEditing({})}>
            Tambah Lokasi
          </Button>,
        ]}
        request={async () => ({ data: await adminService.sites.list(), success: true })}
        columns={columns}
      />
      <ModalForm
        title={editing?.id ? 'Ubah Lokasi' : 'Tambah Lokasi'}
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        initialValues={editing || { active: true, profile: 'office' }}
        modalProps={{ okText: 'Simpan', cancelText: 'Batal' }}
        onFinish={async (values) => {
          if (editing?.id) {
            await adminService.sites.update(editing.id, values);
            message.success('Berhasil diperbarui');
          } else {
            await adminService.sites.create(values);
            message.success('Berhasil dibuat');
          }
          setEditing(null);
          actionRef.current?.reload();
          return true;
        }}
      >
        <ProFormText name="code" label="Code" rules={[{ required: true, message: 'Wajib diisi' }]} disabled={!!editing?.id} />
        <ProFormText name="name" label="Nama" rules={[{ required: true, message: 'Wajib diisi' }]} />
        <ProFormSelect
          name="profile"
          label="Profil"
          options={[
            { label: 'Batubara', value: 'coal' },
            { label: 'Kantor', value: 'office' },
            { label: 'Dukungan', value: 'support' },
          ]}
          rules={[{ required: true, message: 'Wajib diisi' }]}
        />
        <ProFormText name="base_present_code" label="Kode Hadir Dasar" rules={[{ required: true, message: 'Wajib diisi' }]} />
        <ProFormSwitch name="active" label="Aktif" />
      </ModalForm>
    </div>
  );
}
