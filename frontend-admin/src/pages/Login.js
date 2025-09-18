import React, { useState } from 'react';
import { Form, Input, Button, Card, Typography, Space } from 'antd';
import { UserOutlined, LockOutlined } from '@ant-design/icons';
import { useAuth } from '../contexts/AuthContext';

const { Title, Text } = Typography;

const Login = () => {
  const { login, loading } = useAuth();
  const [form] = Form.useForm();

  const handleSubmit = async (values) => {
    await login(values.email, values.password);
  };

  return (
    <div
      style={{
        minHeight: '100vh',
        background: 'linear-gradient(135deg, #141414 0%, #000000 100%)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: 20,
      }}
    >
      <Card
        style={{
          width: '100%',
          maxWidth: 400,
          background: 'rgba(31, 31, 31, 0.95)',
          border: '1px solid #404040',
          borderRadius: 12,
          boxShadow: '0 25px 50px rgba(0, 0, 0, 0.5)',
        }}
      >
        <div style={{ textAlign: 'center', marginBottom: 32 }}>
          <div
            style={{
              width: 64,
              height: 64,
              background: 'linear-gradient(45deg, #e50914, #f40612)',
              borderRadius: 12,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              color: 'white',
              fontWeight: 'bold',
              fontSize: 32,
              margin: '0 auto 16px',
            }}
          >
            N
          </div>
          <Title level={2} style={{ color: '#ffffff', margin: 0 }}>
            Netflix Admin
          </Title>
          <Text style={{ color: '#b3b3b3' }}>
            Sign in to access the admin panel
          </Text>
        </div>

        <Form
          form={form}
          name="login"
          onFinish={handleSubmit}
          autoComplete="off"
          layout="vertical"
          size="large"
        >
          <Form.Item
            name="email"
            label={<span style={{ color: '#ffffff' }}>Email</span>}
            rules={[
              {
                required: true,
                message: 'Please input your email!',
              },
              {
                type: 'email',
                message: 'Please enter a valid email!',
              },
            ]}
          >
            <Input
              prefix={<UserOutlined />}
              placeholder="admin@netflix.com"
              style={{
                background: '#2a2a2a',
                border: '1px solid #404040',
                color: '#ffffff',
                borderRadius: 8,
              }}
            />
          </Form.Item>

          <Form.Item
            name="password"
            label={<span style={{ color: '#ffffff' }}>Password</span>}
            rules={[
              {
                required: true,
                message: 'Please input your password!',
              },
            ]}
          >
            <Input.Password
              prefix={<LockOutlined />}
              placeholder="Enter your password"
              style={{
                background: '#2a2a2a',
                border: '1px solid #404040',
                color: '#ffffff',
                borderRadius: 8,
              }}
            />
          </Form.Item>

          <Form.Item style={{ marginBottom: 16 }}>
            <Button
              type="primary"
              htmlType="submit"
              loading={loading}
              block
              style={{
                height: 48,
                background: 'linear-gradient(45deg, #e50914, #f40612)',
                border: 'none',
                borderRadius: 8,
                fontSize: 16,
                fontWeight: 600,
              }}
            >
              Sign In
            </Button>
          </Form.Item>
        </Form>

        <div style={{ textAlign: 'center', marginTop: 24 }}>
          <Text style={{ color: '#b3b3b3', fontSize: 12 }}>
            Netflix Streaming Platform Admin Panel v2.0
          </Text>
        </div>

        {/* Demo credentials */}
        <div
          style={{
            marginTop: 24,
            padding: 16,
            background: 'rgba(229, 9, 20, 0.1)',
            border: '1px solid #e50914',
            borderRadius: 8,
          }}
        >
          <Text style={{ color: '#e50914', fontSize: 12, fontWeight: 600 }}>
            Demo Credentials:
          </Text>
          <br />
          <Text style={{ color: '#b3b3b3', fontSize: 12 }}>
            Email: admin@netflix.com
          </Text>
          <br />
          <Text style={{ color: '#b3b3b3', fontSize: 12 }}>
            Password: admin123
          </Text>
        </div>
      </Card>
    </div>
  );
};

export default Login;