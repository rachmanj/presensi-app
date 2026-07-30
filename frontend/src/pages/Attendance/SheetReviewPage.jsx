import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { Spin, Table } from 'antd';
import { attendanceService } from '../../services/attendanceService';
import { useAttendanceGrid } from '../../hooks/useAttendanceGrid';
import CodeBadge from '../../components/shared/CodeBadge';
import CellEditModal from './CellEditModal';

const DAY_TYPE_BG = {
  saturday: '#fff7e6',
  sunday: '#fff1f0',
  holiday: '#f5f5f5',
};

export default function SheetReviewPage() {
  const { sheetId } = useParams();
  const [editCell, setEditCell] = useState(null);
  const { data, isLoading, updateCell } = useAttendanceGrid(sheetId);

  const { data: sheetInfo } = useQuery({
    queryKey: ['sheet', sheetId],
    queryFn: () => attendanceService.sheets.show(sheetId),
  });

  if (isLoading) return <Spin style={{ display: 'block', margin: 48 }} />;

  const daysInMonth = data?.days_in_month || 30;
  const rows = data?.rows || [];

  const frozenCols = [
    { title: 'No', dataIndex: 'no', fixed: 'left', width: 50 },
    { title: 'Nama', dataIndex: 'employee_name', fixed: 'left', width: 180, ellipsis: true },
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
        const bg = DAY_TYPE_BG[sampleCell?.day_type];
        return bg ? { style: { background: bg } } : {};
      },
      render: (_, record) => {
        const cell = record.cells?.[day];
        if (!cell) return null;
        return (
          <div
            style={{ cursor: 'pointer', textAlign: 'center' }}
            onClick={() => setEditCell({ ...cell, dayOfMonth: day, employeeName: record.employee_name })}
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
      <h3>
        Review: {sheetInfo?.site_code} — {sheetInfo?.period?.label}
        <span style={{ marginLeft: 12, fontSize: 14, color: '#666' }}>
          ({rows.length} employees, {daysInMonth} days)
        </span>
      </h3>
      <Table
        columns={columns}
        dataSource={rows}
        rowKey="id"
        scroll={{ x: 400 + daysInMonth * 52 + summaryCols.length * 60 }}
        size="small"
        pagination={{ pageSize: 50 }}
        bordered
      />
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
