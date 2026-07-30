import { useRef, useState } from 'react';
import { PlusOutlined } from '@ant-design/icons';
import { ModalForm, ProFormText, ProFormTextArea, ProTable } from '@ant-design/pro-components';
import { Button, message } from 'antd';
import { adminService } from '../../services/adminService';

export default function ReportTemplatePage() {
  const actionRef = useRef();
  const [editing, setEditing] = useState(null);

  const columns = [
    { title: 'Name', dataIndex: 'name', width: 120 },
    { title: 'Site Profile', dataIndex: 'site_profile', width: 120 },
    {
      title: 'Column Layout',
      dataIndex: 'column_layout',
      render: (v) => JSON.stringify(v)?.slice(0, 80) + '...',
    },
    {
      title: 'Actions',
      valueType: 'option',
      width: 120,
      render: (_, record) => [
        <a key="edit" onClick={() => setEditing({
          ...record,
          column_layout: JSON.stringify(record.column_layout, null, 2),
          footer_config: JSON.stringify(record.footer_config || {}, null, 2),
          signature_config: JSON.stringify(record.signature_config || {}, null, 2),
        })}>Edit</a>,
        <a
          key="delete"
          onClick={async () => {
            await adminService.reportTemplates.remove(record.id);
            message.success('Deleted');
            actionRef.current?.reload();
          }}
        >
          Delete
        </a>,
      ],
    },
  ];

  const parseJson = (str) => {
    try {
      return JSON.parse(str);
    } catch {
      return null;
    }
  };

  return (
    <div style={{ padding: 24 }}>
      <ProTable
        headerTitle="Report Templates"
        actionRef={actionRef}
        rowKey="id"
        search={false}
        toolBarRender={() => [
          <Button
            key="add"
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => setEditing({
              column_layout: '{}',
              footer_config: '{}',
              signature_config: '{}',
            })}
          >
            Add Template
          </Button>,
        ]}
        request={async () => ({ data: await adminService.reportTemplates.list(), success: true })}
        columns={columns}
      />
      <ModalForm
        title={editing?.id ? 'Edit Template' : 'Add Template'}
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        initialValues={editing || {}}
        width={600}
        onFinish={async (values) => {
          const payload = {
            name: values.name,
            site_profile: values.site_profile,
            column_layout: parseJson(values.column_layout),
            footer_config: parseJson(values.footer_config),
            signature_config: parseJson(values.signature_config),
          };
          if (!payload.column_layout) {
            message.error('Invalid column_layout JSON');
            return false;
          }
          if (editing?.id) {
            await adminService.reportTemplates.update(editing.id, payload);
            message.success('Updated');
          } else {
            await adminService.reportTemplates.create(payload);
            message.success('Created');
          }
          setEditing(null);
          actionRef.current?.reload();
          return true;
        }}
      >
        <ProFormText name="name" label="Name" rules={[{ required: true }]} />
        <ProFormText name="site_profile" label="Site Profile" rules={[{ required: true }]} />
        <ProFormTextArea name="column_layout" label="Column Layout (JSON)" rules={[{ required: true }]} fieldProps={{ rows: 6 }} />
        <ProFormTextArea name="footer_config" label="Footer Config (JSON)" fieldProps={{ rows: 4 }} />
        <ProFormTextArea name="signature_config" label="Signature Config (JSON)" fieldProps={{ rows: 4 }} />
      </ModalForm>
    </div>
  );
}
