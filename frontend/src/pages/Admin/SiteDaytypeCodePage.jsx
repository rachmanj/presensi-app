import { useRef, useState } from 'react';
import { PlusOutlined } from '@ant-design/icons';
import { ModalForm, ProFormSelect, ProFormText, ProTable } from '@ant-design/pro-components';
import { Button, message, Popconfirm } from 'antd';
import { adminService } from '../../services/adminService';

const DAY_TYPES = [
  { label: 'Hari Kerja', value: 'workday' },
  { label: 'Libur', value: 'off' },
  { label: 'Hari 6', value: 'day6' },
  { label: 'Hari 7 / Libur', value: 'day7_holiday' },
  { label: 'Siaga', value: 'standby' },
];

export default function SiteDaytypeCodePage() {
  const actionRef = useRef();
  const [editing, setEditing] = useState(null);

  const columns = [
    { title: 'Lokasi', dataIndex: 'site_code', width: 80 },
    { title: 'Tipe Hari', dataIndex: 'day_type', width: 120 },
    { title: 'Shift', dataIndex: 'shift', width: 80 },
    { title: 'Code', dataIndex: 'code', width: 100 },
    {
      title: 'Aksi',
      valueType: 'option',
      render: (_, record) => [
        <a key="edit" onClick={() => setEditing(record)}>Ubah</a>,
        <Popconfirm
          key="delete"
          title="Hapus kode tipe hari ini?"
          okText="Hapus"
          cancelText="Batal"
          onConfirm={async () => {
            await adminService.siteDaytypeCodes.remove(record.id);
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
        headerTitle="Kode Tipe Hari Lokasi"
        actionRef={actionRef}
        rowKey="id"
        search={false}
        toolBarRender={() => [
          <Button key="add" type="primary" icon={<PlusOutlined />} onClick={() => setEditing({ shift: 'any' })}>
            Tambah Kode
          </Button>,
        ]}
        request={async () => ({ data: await adminService.siteDaytypeCodes.list(), success: true })}
        columns={columns}
      />
      <ModalForm
        title={editing?.id ? 'Ubah Kode Tipe Hari' : 'Tambah Kode Tipe Hari'}
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        initialValues={editing || {}}
        modalProps={{ okText: 'Simpan', cancelText: 'Batal' }}
        onFinish={async (values) => {
          if (editing?.id) {
            await adminService.siteDaytypeCodes.update(editing.id, values);
            message.success('Berhasil diperbarui');
          } else {
            await adminService.siteDaytypeCodes.create(values);
            message.success('Berhasil dibuat');
          }
          setEditing(null);
          actionRef.current?.reload();
          return true;
        }}
      >
        <ProFormText name="site_code" label="Kode Lokasi" rules={[{ required: true, message: 'Wajib diisi' }]} />
        <ProFormSelect name="day_type" label="Tipe Hari" options={DAY_TYPES} rules={[{ required: true, message: 'Wajib diisi' }]} />
        <ProFormSelect
          name="shift"
          label="Shift"
          options={[
            { label: 'Semua', value: 'any' },
            { label: 'Pagi', value: 'pagi' },
            { label: 'Malam', value: 'malam' },
          ]}
        />
        <ProFormText name="code" label="Code" rules={[{ required: true, message: 'Wajib diisi' }]} />
      </ModalForm>
    </div>
  );
}
