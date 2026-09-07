import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ReloadOutlined } from '@ant-design/icons';
import { Button, Empty, Spin, Table } from 'antd';
import ErrorState from '../../components/shared/ErrorState';
import { attendanceService } from '../../services/attendanceService';
import { useAttendanceGrid } from '../../hooks/useAttendanceGrid';
import CodeBadge from '../../components/shared/CodeBadge';
import LeaveBalanceBadge from '../../components/shared/LeaveBalanceBadge';
import { useAuth } from '../../hooks/useAuth';
import { useTheme } from '../../hooks/useTheme';
import CellEditModal from './CellEditModal';

const CAN_OVERRIDE = ['hr_supervisor', 'admin'];

const DAY_TYPE_BG = {
  saturday: '#fff7e6',
  sunday: '#fff1f0',
  holiday: '#f5f5f5',
};

const DAY_TYPE_BG_DARK = {
  saturday: 'rgba(250,173,20,0.22)',
  sunday: 'rgba(255,77,79,0.22)',
  holiday: 'rgba(255,255,255,0.10)',
};

export default function SheetReviewPage() {
  const { sheetId } = useParams();
  const [editCell, setEditCell] = useState(null);
  const { isDark } = useTheme();
  const { data: user } = useAuth();
  const canOverride = CAN_OVERRIDE.includes(user?.role);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const { data, isLoading, isError, error, refetch: refetchGrid, updateCell } = useAttendanceGrid(sheetId, {
    refetchInterval: (query) => {
      const rows = query.state.data?.rows ?? [];
      return rows.length === 0 ? 5000 : false;
    },
  });

  const { data: sheetInfo, refetch: refetchSheetInfo } = useQuery({
    queryKey: ['sheet', sheetId],
    queryFn: () => attendanceService.sheets.show(sheetId),
  });

  const handleRefresh = async () => {
    setIsRefreshing(true);
    try {
      await Promise.all([refetchGrid(), refetchSheetInfo()]);
    } finally {
      setIsRefreshing(false);
    }
  };

  if (isLoading) return <Spin style={{ display: 'block', margin: 48 }} />;

  if (isError) {
    return (
      <div style={{ padding: 24 }}>
        <ErrorState
          description={error?.message || 'Failed to load attendance grid.'}
          onRetry={refetchGrid}
        />
      </div>
    );
  }

  const daysInMonth = data?.days_in_month || 30;
  const rows = data?.rows || [];
  const isDraftEmpty = rows.length === 0 && sheetInfo?.status === 'draft';

  const frozenCols = [
    { title: 'No', dataIndex: 'no', fixed: 'left', width: 50 },
    { title: 'Name', dataIndex: 'employee_name', fixed: 'left', width: 180, ellipsis: true,
      render: (name, record) => (
        <span>
          {name}
          <LeaveBalanceBadge balance={record.leave_balance} />
        </span>
      ),
    },
    { title: 'NIK', dataIndex: 'nik', fixed: 'left', width: 90 },
    { title: 'Position', dataIndex: 'position', fixed: 'left', width: 140, ellipsis: true },
  ];

  const dateCols = Array.from({ length: daysInMonth }, (_, i) => {
    const day = i + 1;
    return {
      title: String(day),
      width: 52,
      onHeaderCell: () => {
        const sampleCell = rows[0]?.cells?.[day];
        const bgMap = isDark ? DAY_TYPE_BG_DARK : DAY_TYPE_BG;
        const bg = bgMap[sampleCell?.day_type];
        return bg ? { style: { background: bg } } : {};
      },
      render: (_, record) => {
        const cell = record.cells?.[day];
        if (!cell) return null;
        return (
          <div
            style={{ cursor: canOverride ? 'pointer' : 'default', textAlign: 'center' }}
            onClick={() => canOverride && setEditCell({ ...cell, dayOfMonth: day, employeeName: record.employee_name })}
          >
            <CodeBadge
              code={cell.final_code || cell.auto_code}
              isOverridden={cell.is_overridden}
              dayType={cell.day_type}
            />
          </div>
        );
      },
    };
  });

  const summaryCols = (data?.template?.column_layout?.summary_groups || [])
    .flatMap((g) => g.columns || [])
    .map((col) => ({
      title: col,
      width: 60,
      render: (_, record) => record.summary?.[col] ?? '',
    }));

  const columns = [...frozenCols, ...dateCols, ...summaryCols];

  return (
    <div style={{ padding: 24 }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16 }}>
        <h3 style={{ margin: 0, flex: 1 }}>
          Review: {sheetInfo?.site_code} - {sheetInfo?.period?.label}
          <span style={{ marginLeft: 12, fontSize: 14, color: isDark ? 'rgba(255,255,255,0.45)' : '#666' }}>
            ({rows.length} employees, {daysInMonth} days)
          </span>
        </h3>
        <Button
          icon={<ReloadOutlined />}
          loading={isRefreshing}
          onClick={handleRefresh}
        >
          Reload
        </Button>
      </div>
      {isDraftEmpty ? (
        <Empty description="Sheet not generated yet. Open the sheet detail page and click Generate." />
      ) : (
        <Table
          columns={columns}
          dataSource={rows}
          rowKey="id"
          scroll={{ x: 'max-content' }}
          size="small"
          pagination={{ pageSize: 50 }}
          bordered
        />
      )}
      {editCell && (
        <CellEditModal
          cell={editCell}
          open={!!editCell}
          onClose={() => setEditCell(null)}
          onSave={(values) => {
            updateCell.mutate({
              cellId: editCell.id,
              dayOfMonth: editCell.dayOfMonth,
              ...values,
            });
            setEditCell(null);
          }}
        />
      )}
    </div>
  );
}
