import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Button, Card, Input, Select, Space, Table, Typography } from 'antd';
import { Line } from '@ant-design/charts';
import ErrorState from '../../components/shared/ErrorState';
import { attendanceService } from '../../services/attendanceService';
import { comparisonService } from '../../services/comparisonService';
import { adminService } from '../../services/adminService';

const { Title } = Typography;

function pctColor(pct) {
  if (pct >= 95) return '#3f8600';
  if (pct >= 80) return '#faad14';

  return '#cf1322';
}

export default function ComparisonPage() {
  const [periodIds, setPeriodIds] = useState([]);
  const [siteCode, setSiteCode] = useState('HO');
  const [nik, setNik] = useState('');
  const [searchNik, setSearchNik] = useState('');

  const { data: periods } = useQuery({
    queryKey: ['periods'],
    queryFn: attendanceService.periods.list,
  });

  const { data: sites } = useQuery({
    queryKey: ['sites'],
    queryFn: adminService.sites.list,
  });

  const {
    data: siteComparison,
    isLoading: siteLoading,
    isError: siteError,
    error: siteErrorObj,
    refetch: refetchSite,
  } = useQuery({
    queryKey: ['comparison-site', siteCode, periodIds],
    queryFn: () => comparisonService.site(siteCode, periodIds),
    enabled: periodIds.length >= 2,
  });

  const { data: employeeComparison } = useQuery({
    queryKey: ['comparison-employee', searchNik, periodIds, siteCode],
    queryFn: () => comparisonService.employee(searchNik, periodIds, siteCode),
    enabled: searchNik && periodIds.length >= 2,
  });

  const gridData = useMemo(() => {
    if (!siteComparison?.periods) return [];

    return siteComparison.periods
      .filter((p) => p.found)
      .map((p) => ({
        key: p.period_id,
        period: p.period_label,
        attendance_pct: p.attendance_percentage,
        overtime_hours: p.overtime_hours,
        leave_count: p.leave_count,
        absent_count: p.absent_count,
        employees: p.total_employees,
      }));
  }, [siteComparison]);

  const trendData = useMemo(() => {
    if (!employeeComparison?.periods) return [];

    return employeeComparison.periods
      .filter((p) => p.found)
      .map((p) => ({
        period: p.period_label,
        percentage: p.summary?.attendance_percentage ?? 0,
      }));
  }, [employeeComparison]);

  const trendConfig = {
    data: trendData,
    xField: 'period',
    yField: 'percentage',
    height: 260,
    point: { size: 5 },
    meta: { percentage: { min: 0, max: 100 } },
  };

  const comparisonEmptyText = periodIds.length < 2
    ? 'Select at least 2 periods to compare site data.'
    : 'No comparison data for the selected site and periods.';

  const renderSiteComparison = () => {
    if (periodIds.length < 2) {
      return (
        <Table
          size="small"
          dataSource={[]}
          rowKey="key"
          pagination={false}
          locale={{ emptyText: comparisonEmptyText }}
          columns={[
            { title: 'Period', dataIndex: 'period' },
            { title: 'Attendance %', dataIndex: 'attendance_pct' },
            { title: 'Overtime Hours', dataIndex: 'overtime_hours' },
            { title: 'On Leave', dataIndex: 'leave_count' },
            { title: 'Absent', dataIndex: 'absent_count' },
            { title: 'Employees', dataIndex: 'employees' },
          ]}
        />
      );
    }

    if (siteError) {
      return (
        <ErrorState
          description={siteErrorObj?.message || 'Failed to load site comparison data.'}
          onRetry={refetchSite}
        />
      );
    }

    return (
      <Table
        size="small"
        dataSource={gridData}
        rowKey="key"
        pagination={false}
        locale={{ emptyText: comparisonEmptyText }}
        columns={[
          { title: 'Period', dataIndex: 'period' },
          {
            title: 'Attendance %',
            dataIndex: 'attendance_pct',
            render: (v) => <span style={{ color: pctColor(v), fontWeight: 600 }}>{v}%</span>,
          },
          { title: 'Overtime Hours', dataIndex: 'overtime_hours' },
          { title: 'On Leave', dataIndex: 'leave_count' },
          { title: 'Absent', dataIndex: 'absent_count' },
          { title: 'Employees', dataIndex: 'employees' },
        ]}
      />
    );
  };

  return (
    <div style={{ padding: 24 }}>
      <Title level={3}>Multi-Month Comparison</Title>

      <Space wrap style={{ marginBottom: 24 }}>
        <Select
          mode="multiple"
          placeholder="Select 2-6 periods"
          style={{ minWidth: 320 }}
          value={periodIds}
          onChange={(v) => setPeriodIds(v.slice(0, 6))}
          options={(periods || []).map((p) => ({ value: p.id, label: p.label }))}
        />
        <Select
          style={{ width: 140 }}
          value={siteCode}
          onChange={setSiteCode}
          options={(sites || []).map((s) => ({ value: s.code, label: s.code }))}
        />
        <Input
          placeholder="Employee NIK"
          value={nik}
          onChange={(e) => setNik(e.target.value)}
          style={{ width: 140 }}
        />
        <Button type="primary" onClick={() => setSearchNik(nik)} disabled={!nik || periodIds.length < 2}>
          Search Employee
        </Button>
      </Space>

      <Card title={`Site Comparison: ${siteCode}`} loading={siteLoading && periodIds.length >= 2 && !siteError}>
        {renderSiteComparison()}
      </Card>

      {employeeComparison && (
        <Card
          title={`Trend: ${employeeComparison.employee_name || employeeComparison.nik}`}
          style={{ marginTop: 24 }}
        >
          <Line {...trendConfig} />
        </Card>
      )}
    </div>
  );
}
