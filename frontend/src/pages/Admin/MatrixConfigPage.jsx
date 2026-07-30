import { useEffect, useState } from 'react';
import { PlusOutlined } from '@ant-design/icons';
import { ModalForm, ProFormDatePicker, ProFormSelect, ProFormText, ProTable } from '@ant-design/pro-components';
import { Button, message, Table, Typography } from 'antd';
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
    { title: 'Home \\ Visit', dataIndex: 'home_site_code', fixed: 'left', width: 100 },
    ...gridData.sites.map((site) => ({
      title: site,
      dataIndex: ['cells', site],
      width: 80,
      render: (cell) => cell?.code || '-',
    })),
  ];

  const listColumns = [
    { title: 'Home', dataIndex: 'home_site_code', width: 80 },
    { title: 'Visit', dataIndex: 'visit_site_code', width: 80 },
    { title: 'Code', dataIndex: 'code', width: 80 },
    { title: 'Effective From', dataIndex: 'effective_from', width: 120 },
    { title: 'Effective To', dataIndex: 'effective_to', width: 120 },
    {
      title: 'Actions',
      valueType: 'option',
      render: (_, record) => [
        <a key="edit" onClick={() => setEditing(record)}>Edit</a>,
        <a
          key="delete"
          onClick={async () => {
            await adminService.matrixRules.remove(record.id);
            message.success('Deleted');
            loadGrid();
          }}
        >
          Delete
        </a>,
      ],
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Typography.Title level={4}>Matrix Grid</Typography.Title>
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
        headerTitle="Matrix Rules"
        rowKey="id"
        search={false}
        toolBarRender={() => [
          <Button key="add" type="primary" icon={<PlusOutlined />} onClick={() => setEditing({ effective_from: '2025-01-01' })}>
            Add Rule
          </Button>,
        ]}
        request={async () => ({ data: await adminService.matrixRules.list(), success: true })}
        columns={listColumns}
      />

      <ModalForm
        title={editing?.id ? 'Edit Matrix Rule' : 'Add Matrix Rule'}
        open={editing !== null}
        onOpenChange={(open) => !open && setEditing(null)}
        initialValues={editing || {}}
        onFinish={async (values) => {
          const payload = {
            ...values,
            effective_from: values.effective_from?.format?.('YYYY-MM-DD') || values.effective_from,
            effective_to: values.effective_to?.format?.('YYYY-MM-DD') || values.effective_to || null,
          };
          if (editing?.id) {
            await adminService.matrixRules.update(editing.id, payload);
            message.success('Updated');
          } else {
            await adminService.matrixRules.create(payload);
            message.success('Created');
          }
          setEditing(null);
          loadGrid();
          return true;
        }}
      >
        <ProFormSelect
          name="home_site_code"
          label="Home Site"
          options={gridData.sites.map((s) => ({ label: s, value: s }))}
          rules={[{ required: true }]}
        />
        <ProFormSelect
          name="visit_site_code"
          label="Visit Site"
          options={gridData.sites.map((s) => ({ label: s, value: s }))}
          rules={[{ required: true }]}
        />
        <ProFormText name="code" label="Code" rules={[{ required: true }]} />
        <ProFormDatePicker name="effective_from" label="Effective From" rules={[{ required: true }]} />
        <ProFormDatePicker name="effective_to" label="Effective To" />
      </ModalForm>
    </div>
  );
}
