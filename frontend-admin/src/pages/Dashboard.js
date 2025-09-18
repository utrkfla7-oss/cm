import React from 'react';
import { Row, Col, Card, Statistic, Progress, Typography, Space, Button, Spin } from 'antd';
import {
  VideoCameraOutlined,
  PlaySquareOutlined,
  UserOutlined,
  CloudUploadOutlined,
  TrendingUpOutlined,
  ClockCircleOutlined,
} from '@ant-design/icons';
import { useQuery } from 'react-query';
import { Line, Column } from '@ant-design/plots';
import apiService from '../services/api';

const { Title, Text } = Typography;

const Dashboard = () => {
  // Fetch dashboard stats
  const { data: stats, isLoading: statsLoading } = useQuery(
    'dashboard-stats',
    () => apiService.getDashboardStats(),
    {
      refetchInterval: 30000, // Refresh every 30 seconds
    }
  );

  // Fetch recent import jobs
  const { data: importJobs, isLoading: jobsLoading } = useQuery(
    'recent-import-jobs',
    () => apiService.getImportJobs({ page: 1, limit: 5 }),
    {
      refetchInterval: 10000, // Refresh every 10 seconds
    }
  );

  if (statsLoading) {
    return (
      <div className="netflix-loading">
        <Spin size="large" />
      </div>
    );
  }

  const overview = stats?.data?.overview || {};
  const subscriptions = stats?.data?.subscriptions || [];
  const userGrowth = stats?.data?.user_growth || [];

  // Process user growth data for chart
  const growthData = userGrowth.map(item => ({
    date: item.date,
    users: item.count,
  }));

  // Process subscription data for chart
  const subscriptionData = subscriptions.map(sub => ({
    type: sub.subscription_type,
    count: sub.count,
  }));

  const lineConfig = {
    data: growthData,
    xField: 'date',
    yField: 'users',
    point: {
      size: 5,
      shape: 'diamond',
    },
    color: '#e50914',
    smooth: true,
    theme: 'dark',
  };

  const columnConfig = {
    data: subscriptionData,
    xField: 'type',
    yField: 'count',
    color: '#e50914',
    theme: 'dark',
  };

  return (
    <div>
      <div className="page-header">
        <Title level={2} className="page-title">
          Dashboard
        </Title>
        <Text style={{ color: '#b3b3b3' }}>
          Welcome to the Netflix Streaming Platform Admin Panel
        </Text>
      </div>

      {/* Statistics Cards */}
      <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
        <Col xs={24} sm={12} lg={6}>
          <Card className="stat-card">
            <Statistic
              title="Total Movies"
              value={overview.total_movies || 0}
              prefix={<VideoCameraOutlined style={{ color: '#e50914' }} />}
              valueStyle={{ color: '#ffffff' }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card className="stat-card">
            <Statistic
              title="Total TV Shows"
              value={overview.total_tv_shows || 0}
              prefix={<PlaySquareOutlined style={{ color: '#e50914' }} />}
              valueStyle={{ color: '#ffffff' }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card className="stat-card">
            <Statistic
              title="Total Users"
              value={overview.total_users || 0}
              prefix={<UserOutlined style={{ color: '#e50914' }} />}
              valueStyle={{ color: '#ffffff' }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card className="stat-card">
            <Statistic
              title="Total Videos"
              value={overview.total_videos || 0}
              prefix={<CloudUploadOutlined style={{ color: '#e50914' }} />}
              valueStyle={{ color: '#ffffff' }}
            />
          </Card>
        </Col>
      </Row>

      {/* Charts and Additional Stats */}
      <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
        <Col xs={24} lg={12}>
          <Card title="User Growth (Last 30 Days)" className="chart-card">
            {growthData.length > 0 ? (
              <Line {...lineConfig} />
            ) : (
              <div style={{ textAlign: 'center', padding: 40, color: '#b3b3b3' }}>
                No data available
              </div>
            )}
          </Card>
        </Col>
        <Col xs={24} lg={12}>
          <Card title="Subscription Distribution" className="chart-card">
            {subscriptionData.length > 0 ? (
              <Column {...columnConfig} />
            ) : (
              <div style={{ textAlign: 'center', padding: 40, color: '#b3b3b3' }}>
                No data available
              </div>
            )}
          </Card>
        </Col>
      </Row>

      <Row gutter={[16, 16]}>
        {/* System Status */}
        <Col xs={24} lg={8}>
          <Card title="System Status">
            <Space direction="vertical" style={{ width: '100%' }}>
              <div>
                <Text>Storage Usage</Text>
                <Progress
                  percent={65}
                  strokeColor="#e50914"
                  trailColor="#2a2a2a"
                  showInfo={false}
                />
                <Text style={{ color: '#b3b3b3', fontSize: 12 }}>
                  {overview.total_storage_gb || 0} GB used
                </Text>
              </div>
              
              <div>
                <Text>Active Import Jobs</Text>
                <div style={{ fontSize: 24, color: '#e50914', fontWeight: 600 }}>
                  {overview.active_import_jobs || 0}
                </div>
              </div>
              
              <div>
                <Text>Server Status</Text>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <div
                    style={{
                      width: 8,
                      height: 8,
                      borderRadius: '50%',
                      backgroundColor: '#4caf50',
                    }}
                  />
                  <Text style={{ color: '#4caf50' }}>Online</Text>
                </div>
              </div>
            </Space>
          </Card>
        </Col>

        {/* Recent Import Jobs */}
        <Col xs={24} lg={16}>
          <Card
            title="Recent Import Jobs"
            extra={
              <Button
                type="primary"
                size="small"
                onClick={() => window.location.href = '/import'}
              >
                View All
              </Button>
            }
          >
            {jobsLoading ? (
              <Spin />
            ) : (
              <Space direction="vertical" style={{ width: '100%' }}>
                {importJobs?.data?.jobs?.length > 0 ? (
                  importJobs.data.jobs.map((job) => (
                    <div
                      key={job.id}
                      style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        padding: 12,
                        background: '#2a2a2a',
                        borderRadius: 8,
                        border: '1px solid #404040',
                      }}
                    >
                      <div>
                        <Text style={{ fontWeight: 600 }}>
                          {job.job_type} Import
                        </Text>
                        <br />
                        <Text style={{ color: '#b3b3b3', fontSize: 12 }}>
                          {job.processed_items || 0} / {job.total_items || 0} items
                        </Text>
                      </div>
                      <div style={{ textAlign: 'right' }}>
                        <div className={`job-status ${job.status}`}>
                          {job.status}
                        </div>
                        {job.progress && (
                          <Progress
                            percent={job.progress}
                            size="small"
                            strokeColor="#e50914"
                            trailColor="#2a2a2a"
                            style={{ width: 100, marginTop: 4 }}
                          />
                        )}
                      </div>
                    </div>
                  ))
                ) : (
                  <div style={{ textAlign: 'center', padding: 40, color: '#b3b3b3' }}>
                    No recent import jobs
                  </div>
                )}
              </Space>
            )}
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default Dashboard;