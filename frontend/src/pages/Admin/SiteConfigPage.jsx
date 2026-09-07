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
    { title: 'Name', dataIndex: 'name' },
    { title: 'Profile', dataIndex: 'profile', width: 100 },
    { title: 'Base Present Code', dataIndex: 'base_present_code', width: 140 },
    {
      title: 'Active',
      dataIndex: 'active',
      width: 80,
      render: (v) => (v ? 'Yes' : 'No'),
    },
    {
      title: 'Actions',
      valueType: 'option',
      width: 120,
      render: (_, record) => [
        <a key="edit" onClick={() => setEditing(record)}>Edit</a>,
        <Popconfirm
          key="delete"
          title="Delete this site?"
          okText="Delete"
          cancelText="Cancel"
          onConfirm={async () => {
            await adminService.sites.remove(record.id);
            message.success('Deleted successfully');
            actionRef.current?.reload();
          }}
        >
          <a>Delete</a>
        </Popconfirm>,
      ],
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <ProTable
        headerTitle="Site Configuration"
        actionRef={actionRef}
        rowKey="id"
        search={false}
        toolBarRender={() => [
          <Button key="add" type="primary" icon={<PlusOutlined />} onClick={() => setEditing({})}>
            Add Site
          </Button>,
        ]}
        request={async () => ({ data: await adminService.sites.list(), success: true })}
        columns={columns}
      />
      <ModalForm
        title={editing?.id ? 'Edit Site' : 'Add Site'}
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        initialValues={editing || { active: true, profile: 'office' }}
        modalProps={{ okText: 'Save', cancelText: 'Cancel' }}
        onFinish={async (values) => {
          if (editing?.id) {
            await adminService.sites.update(editing.id, values);
            message.success('Updated successfully');
          } else {
            await adminService.sites.create(values);
            message.success('Created successfully');
          }
          setEditing(null);
          actionRef.current?.reload();
          return true;
        }}
      >
        <ProFormText name="code" label="Code" rules={[{ required: true, message: 'Required' }]} disabled={!!editing?.id} />
        <ProFormText name="name" label="Name" rules={[{ required: true, message: 'Required' }]} />
        <ProFormSelect
          name="profile"
          label="Profile"
          options={[
            { label: 'Coal', value: 'coal' },
            { label: 'Office', value: 'office' },
            { label: 'Support', value: 'support' },
          ]}
          rules={[{ required: true, message: 'Required' }]}
        />
        <ProFormText name="base_present_code" label="Base Present Code" rules={[{ required: true, message: 'Required' }]} />
        <ProFormSwitch name="active" label="Active" />
      </ModalForm>
    </div>
  );
}
