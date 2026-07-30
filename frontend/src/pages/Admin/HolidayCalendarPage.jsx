import { useRef, useState } from 'react';
import { PlusOutlined } from '@ant-design/icons';
import { ModalForm, ProFormDatePicker, ProFormDigit, ProFormSelect, ProFormText, ProTable } from '@ant-design/pro-components';
import { Button, message } from 'antd';
import { adminService } from '../../services/adminService';

export default function HolidayCalendarPage() {
  const actionRef = useRef();
  const [editing, setEditing] = useState(null);
  const [year, setYear] = useState(2026);

  const columns = [
    { title: 'Date', dataIndex: 'date', width: 120 },
    { title: 'Type', dataIndex: 'type', width: 140 },
    { title: 'Description', dataIndex: 'description' },
    { title: 'Year', dataIndex: 'year', width: 80 },
    {
      title: 'Actions',
      valueType: 'option',
      render: (_, record) => [
        <a key="edit" onClick={() => setEditing(record)}>Edit</a>,
        <a
          key="delete"
          onClick={async () => {
            await adminService.holidays.remove(record.id);
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
        headerTitle="Holiday Calendar"
        actionRef={actionRef}
        rowKey="id"
        params={{ year }}
        toolbar={{
          filter: (
            <ProFormDigit
              fieldProps={{ value: year, onChange: setYear, style: { width: 120 } }}
              noStyle
              placeholder="Year"
            />
          ),
        }}
        toolBarRender={() => [
          <Button key="add" type="primary" icon={<PlusOutlined />} onClick={() => setEditing({ year })}>
            Add Holiday
          </Button>,
        ]}
        request={async (params) => ({
          data: await adminService.holidays.list(params.year || year),
          success: true,
        })}
        columns={columns}
      />
      <ModalForm
        title={editing?.id ? 'Edit Holiday' : 'Add Holiday'}
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        initialValues={editing || {}}
        onFinish={async (values) => {
          const payload = {
            ...values,
            date: values.date?.format?.('YYYY-MM-DD') || values.date,
          };
          if (editing?.id) {
            await adminService.holidays.update(editing.id, payload);
            message.success('Updated');
          } else {
            await adminService.holidays.create(payload);
            message.success('Created');
          }
          setEditing(null);
          actionRef.current?.reload();
          return true;
        }}
      >
        <ProFormDatePicker name="date" label="Date" rules={[{ required: true }]} />
        <ProFormSelect
          name="type"
          label="Type"
          options={[
            { label: 'National Holiday', value: 'national_holiday' },
            { label: 'Joint Leave', value: 'joint_leave' },
            { label: 'Special', value: 'special' },
          ]}
          rules={[{ required: true }]}
        />
        <ProFormText name="description" label="Description" />
        <ProFormDigit name="year" label="Year" rules={[{ required: true }]} />
      </ModalForm>
    </div>
  );
}
