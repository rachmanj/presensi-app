import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Card, Col, Row, Select, Statistic, Table, Typography } from 'antd';
import { Column } from '@ant-design/charts';
import ErrorState from '../../components/shared/ErrorState';
import {
  getDashboardAttendanceTrend,
  getDashboardOvertime,
  getDashboardSummary,
} from '../../services/adminService';
import { adminService } from '../../services/adminService';

const { Title } = Typography;

export default function DashboardPage() {
  const [siteCode, setSiteCode] = useState(null);

  const { data: sites } = useQuery({
    queryKey: ['sites'],
    queryFn: adminService.sites.list,
  });

  const {
    data: summary,
    isLoading: summaryLoading,
    isError: summaryError,
    error: summaryErrorObj,
    refetch: refetchSummary,
  } = useQuery({
    queryKey: ['dashboard', 'summary', siteCode],
    queryFn: () => getDashboardSummary(siteCode),
  });

  const {
    data: trend,
    isLoading: trendLoading,
    isError: trendError,
    error: trendErrorObj,
    refetch: refetchTrend,
  } = useQuery({
    queryKey: ['dashboard', 'trend', siteCode],
    queryFn: () => getDashboardAttendanceTrend(siteCode),
  });

  const {
    data: overtime,
    isLoading: overtimeLoading,
    isError: overtimeError,
    error: overtimeErrorObj,
    refetch: refetchOvertime,
  } = useQuery({
    queryKey: ['dashboard', 'overtime', siteCode],
    queryFn: () => getDashboardOvertime(siteCode),
  });

  const trendConfig = {
    data: trend || [],
    xField: 'label',
    yField: 'percentage',
    height: 280,
    label: {
      position: 'top',
      formatter: (datum) => `${datum.percentage}%`,
    },
    meta: {
      percentage: { alias: 'Attendance %', min: 0, max: 100 },
    },
  };

  return (
    <div style={{ padding: 24 }}>
      <Row justify="space-between" align="middle" style={{ marginBottom: 24 }}>
        <Title level={3} style={{ margin: 0 }}>Today Dashboard</Title>
        <Select
          allowClear
          placeholder="Filter site"
          style={{ width: 200 }}
          value={siteCode}
          onChange={setSiteCode}
          options={(sites || []).map((s) => ({ value: s.code, label: s.code }))}
        />
      </Row>

      {summaryError ? (
        <Card>
          <ErrorState
            description={summaryErrorObj?.message || 'Failed to load attendance summary.'}
            onRetry={refetchSummary}
          />
        </Card>
      ) : (
        <Row gutter={[16, 16]}>
          <Col xs={12} md={6} lg={6} xl={6}>
            <Card loading={summaryLoading}>
              <Statistic title="Present" value={summary?.present ?? 0} valueStyle={{ color: '#3f8600' }} />
            </Card>
          </Col>
          <Col xs={12} md={6} lg={6} xl={6}>
            <Card loading={summaryLoading}>
              <Statistic title="Late" value={summary?.late ?? 0} valueStyle={{ color: '#faad14' }} />
            </Card>
          </Col>
          <Col xs={12} md={6} lg={6} xl={6}>
            <Card loading={summaryLoading}>
              <Statistic title="On Leave" value={summary?.on_leave ?? 0} valueStyle={{ color: '#1890ff' }} />
            </Card>
          </Col>
          <Col xs={12} md={6} lg={6} xl={6}>
            <Card loading={summaryLoading} style={{ borderLeft: '4px solid #cf1322' }}>
              <Statistic title="Absent" value={summary?.absent ?? 0} valueStyle={{ color: '#cf1322' }} />
            </Card>
          </Col>
        </Row>
      )}

      <Card
        title="Attendance Last 7 Days"
        style={{ marginTop: 24 }}
        loading={trendLoading && !trendError}
      >
        {trendError ? (
          <ErrorState
            description={trendErrorObj?.message || 'Failed to load attendance trend chart.'}
            onRetry={refetchTrend}
          />
        ) : (
          <Column {...trendConfig} />
        )}
      </Card>

      <Card
        title={`Monthly Overtime: ${overtime?.period?.label || 'Active period'}`}
        style={{ marginTop: 24 }}
        loading={overtimeLoading && !overtimeError}
      >
        {overtimeError ? (
          <ErrorState
            description={overtimeErrorObj?.message || 'Failed to load overtime data.'}
            onRetry={refetchOvertime}
          />
        ) : (
          <Table
            size="small"
            rowKey="site_code"
            pagination={false}
            dataSource={overtime?.sites || []}
            columns={[
              { title: 'Site', dataIndex: 'site_code' },
              { title: 'Overtime Hours', dataIndex: 'overtime_hours' },
              { title: 'Overtime Days', dataIndex: 'overtime_days' },
            ]}
          />
        )}
      </Card>
    </div>
  );
}
