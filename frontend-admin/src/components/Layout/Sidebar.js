import React, { useState } from 'react';
import { Layout, Menu } from 'antd';
import { Link, useLocation } from 'react-router-dom';
import {
  DashboardOutlined,
  VideoCameraOutlined,
  PlaySquareOutlined,
  UnorderedListOutlined,
  UserOutlined,
  CloudUploadOutlined,
  ImportOutlined,
  SettingOutlined,
  MenuFoldOutlined,
  MenuUnfoldOutlined,
} from '@ant-design/icons';

const { Sider } = Layout;

const Sidebar = () => {
  const [collapsed, setCollapsed] = useState(false);
  const location = useLocation();

  const menuItems = [
    {
      key: '/dashboard',
      icon: <DashboardOutlined />,
      label: <Link to="/dashboard">Dashboard</Link>,
    },
    {
      key: '/movies',
      icon: <VideoCameraOutlined />,
      label: <Link to="/movies">Movies</Link>,
    },
    {
      key: '/tv-shows',
      icon: <PlaySquareOutlined />,
      label: <Link to="/tv-shows">TV Shows</Link>,
    },
    {
      key: '/episodes',
      icon: <UnorderedListOutlined />,
      label: <Link to="/episodes">Episodes</Link>,
    },
    {
      key: '/users',
      icon: <UserOutlined />,
      label: <Link to="/users">Users</Link>,
    },
    {
      key: '/upload',
      icon: <CloudUploadOutlined />,
      label: <Link to="/upload">Upload Video</Link>,
    },
    {
      key: '/import',
      icon: <ImportOutlined />,
      label: <Link to="/import">Import Manager</Link>,
    },
    {
      key: '/settings',
      icon: <SettingOutlined />,
      label: <Link to="/settings">Settings</Link>,
    },
  ];

  return (
    <Sider
      collapsible
      collapsed={collapsed}
      onCollapse={setCollapsed}
      trigger={null}
      width={250}
      theme="dark"
      style={{
        background: '#000000',
        borderRight: '1px solid #404040',
      }}
    >
      <div
        style={{
          height: 64,
          display: 'flex',
          alignItems: 'center',
          justifyContent: collapsed ? 'center' : 'flex-start',
          padding: collapsed ? 0 : '0 24px',
          borderBottom: '1px solid #404040',
        }}
      >
        {!collapsed && (
          <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
            <div
              style={{
                width: 32,
                height: 32,
                background: 'linear-gradient(45deg, #e50914, #f40612)',
                borderRadius: 4,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: 'white',
                fontWeight: 'bold',
                fontSize: 16,
              }}
            >
              N
            </div>
            <span
              style={{
                color: '#ffffff',
                fontSize: 18,
                fontWeight: 600,
                letterSpacing: 1,
              }}
            >
              NETFLIX
            </span>
          </div>
        )}
        {collapsed && (
          <div
            style={{
              width: 32,
              height: 32,
              background: 'linear-gradient(45deg, #e50914, #f40612)',
              borderRadius: 4,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: 'white',
              fontWeight: 'bold',
              fontSize: 16,
            }}
          >
            N
          </div>
        )}
      </div>

      <div
        style={{
          padding: '16px 0',
          borderBottom: '1px solid #404040',
        }}
      >
        <div
          style={{
            display: 'flex',
            justifyContent: 'center',
          }}
        >
          <button
            onClick={() => setCollapsed(!collapsed)}
            style={{
              background: 'transparent',
              border: '1px solid #404040',
              color: '#ffffff',
              width: 32,
              height: 32,
              borderRadius: 4,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              cursor: 'pointer',
              transition: 'all 0.2s ease',
            }}
            onMouseEnter={(e) => {
              e.target.style.borderColor = '#e50914';
              e.target.style.color = '#e50914';
            }}
            onMouseLeave={(e) => {
              e.target.style.borderColor = '#404040';
              e.target.style.color = '#ffffff';
            }}
          >
            {collapsed ? <MenuUnfoldOutlined /> : <MenuFoldOutlined />}
          </button>
        </div>
      </div>

      <Menu
        theme="dark"
        mode="inline"
        selectedKeys={[location.pathname]}
        items={menuItems}
        style={{
          background: 'transparent',
          border: 'none',
        }}
      />

      {!collapsed && (
        <div
          style={{
            position: 'absolute',
            bottom: 20,
            left: 24,
            right: 24,
            padding: 16,
            background: 'rgba(229, 9, 20, 0.1)',
            border: '1px solid #e50914',
            borderRadius: 8,
            textAlign: 'center',
          }}
        >
          <div style={{ color: '#e50914', fontWeight: 600, marginBottom: 8 }}>
            Admin Panel
          </div>
          <div style={{ color: '#b3b3b3', fontSize: 12 }}>
            Netflix Streaming Platform
          </div>
        </div>
      )}
    </Sider>
  );
};

export default Sidebar;