import React from 'react';
import { Layout, Dropdown, Avatar, Badge, Button, Space } from 'antd';
import {
  UserOutlined,
  LogoutOutlined,
  SettingOutlined,
  BellOutlined,
  SearchOutlined,
} from '@ant-design/icons';
import { useAuth } from '../../contexts/AuthContext';

const { Header: AntHeader } = Layout;

const Header = () => {
  const { user, logout } = useAuth();

  const userMenuItems = [
    {
      key: 'profile',
      icon: <UserOutlined />,
      label: 'Profile',
    },
    {
      key: 'settings',
      icon: <SettingOutlined />,
      label: 'Settings',
    },
    {
      type: 'divider',
    },
    {
      key: 'logout',
      icon: <LogoutOutlined />,
      label: 'Logout',
      onClick: logout,
    },
  ];

  const notificationMenuItems = [
    {
      key: 'notification1',
      label: (
        <div style={{ width: 250 }}>
          <div style={{ fontWeight: 600, marginBottom: 4 }}>
            New import job completed
          </div>
          <div style={{ fontSize: 12, color: '#b3b3b3' }}>
            Successfully imported 25 movies from TMDb
          </div>
        </div>
      ),
    },
    {
      key: 'notification2',
      label: (
        <div style={{ width: 250 }}>
          <div style={{ fontWeight: 600, marginBottom: 4 }}>
            Video transcoding finished
          </div>
          <div style={{ fontSize: 12, color: '#b3b3b3' }}>
            "The Dark Knight" is now available for streaming
          </div>
        </div>
      ),
    },
    {
      type: 'divider',
    },
    {
      key: 'view-all',
      label: (
        <div style={{ textAlign: 'center', color: '#e50914' }}>
          View All Notifications
        </div>
      ),
    },
  ];

  return (
    <AntHeader
      style={{
        background: '#000000',
        padding: '0 24px',
        borderBottom: '1px solid #404040',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
        <h2 style={{ color: '#ffffff', margin: 0, fontSize: 20 }}>
          Admin Dashboard
        </h2>
      </div>

      <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
        {/* Search */}
        <Button
          type="ghost"
          icon={<SearchOutlined />}
          style={{
            border: '1px solid #404040',
            color: '#ffffff',
          }}
        >
          Search
        </Button>

        {/* Notifications */}
        <Dropdown
          menu={{ items: notificationMenuItems }}
          placement="bottomRight"
          trigger={['click']}
        >
          <Badge count={2} size="small">
            <Button
              type="ghost"
              icon={<BellOutlined />}
              style={{
                border: '1px solid #404040',
                color: '#ffffff',
              }}
            />
          </Badge>
        </Dropdown>

        {/* User Menu */}
        <Dropdown
          menu={{ items: userMenuItems }}
          placement="bottomRight"
          trigger={['click']}
        >
          <Space style={{ cursor: 'pointer' }}>
            <Avatar
              size={36}
              icon={<UserOutlined />}
              style={{
                backgroundColor: '#e50914',
              }}
            />
            <div style={{ color: '#ffffff' }}>
              <div style={{ fontSize: 14, fontWeight: 500 }}>
                {user?.username || 'Admin'}
              </div>
              <div style={{ fontSize: 12, color: '#b3b3b3' }}>
                {user?.role || 'Administrator'}
              </div>
            </div>
          </Space>
        </Dropdown>
      </div>
    </AntHeader>
  );
};

export default Header;