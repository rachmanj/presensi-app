import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Card, Col, Row, Select, Statistic, Table, Typography } from 'antd';
import { Column } from '@ant-design/charts';
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

  const { data: summary, isLoading: summaryLoading } = useQuery({
    queryKey: ['dashboard', 'summary', siteCode],
    queryFn: () => getDashboardSummary(siteCode),
  });

  const { data: trend, isLoading: trendLoading } = useQuery({
    queryKey: ['dashboard', 'trend', siteCode],
    queryFn: () => getDashboardAttendanceTrend(siteCode),
  });

  const { data: overtime, isLoading: overtimeLoading } = useQuery({
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
      percentage: { alias: 'Kehadiran %', min: 0, max: 100 },
    },
  };

  return (
    <div style={{ padding: 24 }}>
      <Row justify="space-between" align="middle" style={{ marginBottom: 24 }}>
        <Title level={3} style={{ margin: 0 }}>Dashboard Hari Ini</Title>
        <Select
          allowClear
          placeholder="Filter by site"
          style={{ width: 200 }}
          value={siteCode}
          onChange={setSiteCode}
          options={(sites || []).map((s) => ({ value: s.code, label: s.code }))}
        />
      </Row>

      <Row gutter={16}>
        <Col span={6}>
          <Card loading={summaryLoading}>
            <Statistic title="Hadir" value={summary?.present ?? 0} valueStyle={{ color: '#3f8600' }} />
          </Card>
        </Col>
        <Col span={6}>
          <Card loading={summaryLoading}>
            <Statistic title="Terlambat" value={summary?.late ?? 0} valueStyle={{ color: '#faad14' }} />
          </Card>
        </Col>
        <Col span={6}>
          <Card loading={summaryLoading}>
            <Statistic title="Cuti" value={summary?.on_leave ?? 0} valueStyle={{ color: '#1890ff' }} />
          </Card>
        </Col>
        <Col span={6}>
          <Card loading={summaryLoading}>
            <Statistic title="Absen" value={summary?.absent ?? 0} valueStyle={{ color: '#cf1322' }} />
          </Card>
        </Col>
      </Row>

      <Card title="Kehadiran 7 Hari Terakhir" style={{ marginTop: 24 }} loading={trendLoading}>
        <Column {...trendConfig} />
      </Card>

      <Card
        title={`Lembur Bulanan — ${overtime?.period?.label || 'Periode aktif'}`}
        style={{ marginTop: 24 }}
        loading={overtimeLoading}
      >
        <Table
          size="small"
          rowKey="site_code"
          pagination={false}
          dataSource={overtime?.sites || []}
          columns={[
            { title: 'Site', dataIndex: 'site_code' },
            { title: 'Jam Lembur', dataIndex: 'overtime_hours' },
            { title: 'Hari Lembur', dataIndex: 'overtime_days' },
          ]}
        />
      </Card>
    </div>
  );
}
