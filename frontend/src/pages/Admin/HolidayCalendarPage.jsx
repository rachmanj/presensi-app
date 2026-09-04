import { useRef, useState } from 'react';
import { PlusOutlined } from '@ant-design/icons';
import { ModalForm, ProFormDatePicker, ProFormDigit, ProFormSelect, ProFormText, ProTable } from '@ant-design/pro-components';
import { Button, message, Popconfirm } from 'antd';
import { adminService } from '../../services/adminService';
import { formatDisplayDate } from '../../utils/dateFormat';

export default function HolidayCalendarPage() {
  const actionRef = useRef();
  const [editing, setEditing] = useState(null);
  const [year, setYear] = useState(2026);

  const columns = [
    { title: 'Tanggal', dataIndex: 'date', width: 120, render: (v) => formatDisplayDate(v) },
    { title: 'Tipe', dataIndex: 'type', width: 140 },
    { title: 'Deskripsi', dataIndex: 'description' },
    { title: 'Tahun', dataIndex: 'year', width: 80 },
    {
      title: 'Aksi',
      valueType: 'option',
      render: (_, record) => [
        <a key="edit" onClick={() => setEditing(record)}>Ubah</a>,
        <Popconfirm
          key="delete"
          title="Hapus hari libur ini?"
          okText="Hapus"
          cancelText="Batal"
          onConfirm={async () => {
            await adminService.holidays.remove(record.id);
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
        headerTitle="Kalender Hari Libur"
        actionRef={actionRef}
        rowKey="id"
        params={{ year }}
        toolbar={{
          filter: (
            <ProFormDigit
              fieldProps={{ value: year, onChange: setYear, style: { width: 120 } }}
              noStyle
              placeholder="Tahun"
            />
          ),
        }}
        toolBarRender={() => [
          <Button key="add" type="primary" icon={<PlusOutlined />} onClick={() => setEditing({ year })}>
            Tambah Hari Libur
          </Button>,
        ]}
        request={async (params) => ({
          data: await adminService.holidays.list(params.year || year),
          success: true,
        })}
        columns={columns}
      />
      <ModalForm
        title={editing?.id ? 'Ubah Hari Libur' : 'Tambah Hari Libur'}
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        initialValues={editing || {}}
        modalProps={{ okText: 'Simpan', cancelText: 'Batal' }}
        onFinish={async (values) => {
          const payload = {
            ...values,
            date: values.date?.format?.('YYYY-MM-DD') || values.date,
          };
          if (editing?.id) {
            await adminService.holidays.update(editing.id, payload);
            message.success('Berhasil diperbarui');
          } else {
            await adminService.holidays.create(payload);
            message.success('Berhasil dibuat');
          }
          setEditing(null);
          actionRef.current?.reload();
          return true;
        }}
      >
        <ProFormDatePicker name="date" label="Tanggal" rules={[{ required: true, message: 'Wajib diisi' }]} />
        <ProFormSelect
          name="type"
          label="Tipe"
          options={[
            { label: 'Libur Nasional', value: 'national_holiday' },
            { label: 'Cuti Bersama', value: 'joint_leave' },
            { label: 'Khusus', value: 'special' },
          ]}
          rules={[{ required: true, message: 'Wajib diisi' }]}
        />
        <ProFormText name="description" label="Deskripsi" />
        <ProFormDigit name="year" label="Tahun" rules={[{ required: true, message: 'Wajib diisi' }]} />
      </ModalForm>
    </div>
  );
}
