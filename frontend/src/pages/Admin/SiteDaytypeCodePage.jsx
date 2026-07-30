import { useRef, useState } from 'react';
import { PlusOutlined } from '@ant-design/icons';
import { ModalForm, ProFormSelect, ProFormText, ProTable } from '@ant-design/pro-components';
import { Button, message } from 'antd';
import { adminService } from '../../services/adminService';

const DAY_TYPES = [
  { label: 'Workday', value: 'workday' },
  { label: 'Off', value: 'off' },
  { label: 'Day 6', value: 'day6' },
  { label: 'Day 7 / Holiday', value: 'day7_holiday' },
  { label: 'Standby', value: 'standby' },
];

export default function SiteDaytypeCodePage() {
  const actionRef = useRef();
  const [editing, setEditing] = useState(null);

  const columns = [
    { title: 'Site', dataIndex: 'site_code', width: 80 },
    { title: 'Day Type', dataIndex: 'day_type', width: 120 },
    { title: 'Shift', dataIndex: 'shift', width: 80 },
    { title: 'Code', dataIndex: 'code', width: 100 },
    {
      title: 'Actions',
      valueType: 'option',
      render: (_, record) => [
        <a key="edit" onClick={() => setEditing(record)}>Edit</a>,
        <a
          key="delete"
          onClick={async () => {
            await adminService.siteDaytypeCodes.remove(record.id);
            message.success('Deleted');
            actionRef.current?.reload();
          }}
        >
          Delete
        </a>,
      ],
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <ProTable
        headerTitle="Site Daytype Codes"
        actionRef={actionRef}
        rowKey="id"
        search={false}
        toolBarRender={() => [
          <Button key="add" type="primary" icon={<PlusOutlined />} onClick={() => setEditing({ shift: 'any' })}>
            Add Code
          </Button>,
        ]}
        request={async () => ({ data: await adminService.siteDaytypeCodes.list(), success: true })}
        columns={columns}
      />
      <ModalForm
        title={editing?.id ? 'Edit Daytype Code' : 'Add Daytype Code'}
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        initialValues={editing || {}}
        onFinish={async (values) => {
          if (editing?.id) {
            await adminService.siteDaytypeCodes.update(editing.id, values);
            message.success('Updated');
          } else {
            await adminService.siteDaytypeCodes.create(values);
            message.success('Created');
          }
          setEditing(null);
          actionRef.current?.reload();
          return true;
        }}
      >
        <ProFormText name="site_code" label="Site Code" rules={[{ required: true }]} />
        <ProFormSelect name="day_type" label="Day Type" options={DAY_TYPES} rules={[{ required: true }]} />
        <ProFormSelect
          name="shift"
          label="Shift"
          options={[
            { label: 'Any', value: 'any' },
            { label: 'Pagi', value: 'pagi' },
            { label: 'Malam', value: 'malam' },
          ]}
        />
        <ProFormText name="code" label="Code" rules={[{ required: true }]} />
      </ModalForm>
    </div>
  );
}
