import { useQuery } from '@tanstack/react-query';
import { Card, Col, Row, Statistic, Typography } from 'antd';
import { getDashboardSummary } from '../../services/adminService';

const { Title, Paragraph } = Typography;

export default function DashboardPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['dashboard', 'summary'],
    queryFn: getDashboardSummary,
  });

  return (
    <div style={{ padding: 24 }}>
      <Title level={3}>Dashboard</Title>
      <Paragraph>Welcome to ARKA Presensi — automated attendance processing.</Paragraph>
      <Row gutter={16} style={{ marginTop: 24 }}>
        <Col span={8}>
          <Card loading={isLoading}>
            <Statistic title="Active Sites" value={data?.sites ?? 0} />
          </Card>
        </Col>
        <Col span={8}>
          <Card loading={isLoading}>
            <Statistic title="Active Periods" value={data?.active_periods ?? 0} />
          </Card>
        </Col>
        <Col span={8}>
          <Card loading={isLoading}>
            <Statistic title="Total Periods" value={data?.total_periods ?? 0} />
          </Card>
        </Col>
      </Row>
    </div>
  );
}
