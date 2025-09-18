import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { Layout } from 'antd';
import Sidebar from './components/Layout/Sidebar';
import Header from './components/Layout/Header';
import Dashboard from './pages/Dashboard';
import Movies from './pages/Movies';
import TVShows from './pages/TVShows';
import Episodes from './pages/Episodes';
import Users from './pages/Users';
import ImportManager from './pages/ImportManager';
import VideoUpload from './pages/VideoUpload';
import Settings from './pages/Settings';
import Login from './pages/Login';
import { useAuth } from './contexts/AuthContext';

const { Content } = Layout;

function App() {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="netflix-loading">
        <div className="netflix-spinner"></div>
      </div>
    );
  }

  if (!user) {
    return <Login />;
  }

  return (
    <Layout className="main-layout">
      <Sidebar />
      <Layout>
        <Header />
        <Content className="content-wrapper">
          <Routes>
            <Route path="/" element={<Dashboard />} />
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/movies" element={<Movies />} />
            <Route path="/tv-shows" element={<TVShows />} />
            <Route path="/episodes" element={<Episodes />} />
            <Route path="/users" element={<Users />} />
            <Route path="/import" element={<ImportManager />} />
            <Route path="/upload" element={<VideoUpload />} />
            <Route path="/settings" element={<Settings />} />
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </Content>
      </Layout>
    </Layout>
  );
}

export default App;