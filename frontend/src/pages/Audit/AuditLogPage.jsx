import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { ProTable } from '@ant-design/pro-components';
import { DatePicker, Select } from 'antd';
import ErrorState from '../../components/shared/ErrorState';
import { auditService } from '../../services/auditService';
import { formatDisplayDateTime } from '../../utils/dateFormat';

function formatDate(d) {
  if (!d) return undefined;
  const date = d.toDate?.() || d;
  return date.toISOString().slice(0, 10);
}

const ACTION_OPTIONS = [
  'cell.override',
  'sheet.generate',
  'sheet.finalize',
  'sheet.reopen',
  'period.finalize',
  'period.reopen',
  'matrix.create',
  'matrix.update',
  'matrix.delete',
  'employee_map.create',
  'employee_map.update',
  'employee_map.delete',
  'user.login',
  'user.logout',
];

const ENTITY_OPTIONS = [
  'AttendanceCell',
  'AttendanceSheet',
  'AttendancePeriod',
  'MatrixRule',
  'EmployeeMap',
  'User',
];

export default function AuditLogPage() {
  const [filters, setFilters] = useState({});

  const { data, isLoading, isError, error, refetch } = useQuery({
    queryKey: ['audit-logs', filters],
    queryFn: () => auditService.list(filters),
  });

  const list = data?.data || [];

  return (
    <div style={{ padding: 24 }}>
      {isError ? (
        <ErrorState
          description={error?.message || 'Log audit tidak dapat dimuat.'}
          onRetry={refetch}
        />
      ) : (
      <ProTable
        headerTitle="Log Audit"
        loading={isLoading}
        dataSource={list}
        rowKey="id"
        search={false}
        pagination={{ total: data?.total, pageSize: filters.per_page || 25 }}
        onChange={(pagination) => setFilters((f) => ({ ...f, page: pagination.current, per_page: pagination.pageSize }))}
        locale={{ emptyText: 'Belum ada aktivitas tercatat.' }}
        toolBarRender={() => [
          <Select
            key="action"
            allowClear
            placeholder="Aksi"
            style={{ width: 180 }}
            options={ACTION_OPTIONS.map((a) => ({ value: a, label: a }))}
            onChange={(v) => setFilters((f) => ({ ...f, action: v, page: 1 }))}
          />,
          <Select
            key="entity"
            allowClear
            placeholder="Entitas"
            style={{ width: 160 }}
            options={ENTITY_OPTIONS.map((e) => ({ value: e, label: e }))}
            onChange={(v) => setFilters((f) => ({ ...f, entity_type: v, page: 1 }))}
          />,
          <DatePicker.RangePicker
            key="dates"
            onChange={(dates) =>
              setFilters((f) => ({
                ...f,
                from: formatDate(dates?.[0]),
                to: formatDate(dates?.[1]),
                page: 1,
              }))
            }
          />,
        ]}
        columns={[
          { title: 'Waktu', dataIndex: 'created_at', width: 170, render: (v) => formatDisplayDateTime(v) },
          { title: 'Pengguna', dataIndex: ['user', 'name'], width: 140 },
          { title: 'Aksi', dataIndex: 'action', width: 160 },
          { title: 'Entitas', dataIndex: 'entity_type', width: 140 },
          { title: 'ID Entitas', dataIndex: 'entity_id', width: 90 },
          { title: 'Catatan', dataIndex: 'note', ellipsis: true },
        ]}
      />
      )}
    </div>
  );
}
