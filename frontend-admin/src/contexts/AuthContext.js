import React, { createContext, useContext, useState, useEffect } from 'react';
import { notification } from 'antd';
import apiService from '../services/api';

const AuthContext = createContext();

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuthStatus();
  }, []);

  const checkAuthStatus = async () => {
    try {
      const token = localStorage.getItem('netflix_admin_token');
      if (token) {
        apiService.setAuthToken(token);
        const response = await apiService.get('/auth/profile');
        setUser(response.data.user);
      }
    } catch (error) {
      localStorage.removeItem('netflix_admin_token');
      apiService.setAuthToken(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    try {
      setLoading(true);
      const response = await apiService.post('/auth/login', { email, password });
      const { user: userData, token } = response.data;
      
      localStorage.setItem('netflix_admin_token', token);
      apiService.setAuthToken(token);
      setUser(userData);
      
      notification.success({
        message: 'Login Successful',
        description: `Welcome back, ${userData.username}!`,
      });
      
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Login failed';
      notification.error({
        message: 'Login Failed',
        description: message,
      });
      return { success: false, error: message };
    } finally {
      setLoading(false);
    }
  };

  const logout = () => {
    localStorage.removeItem('netflix_admin_token');
    apiService.setAuthToken(null);
    setUser(null);
    notification.info({
      message: 'Logged Out',
      description: 'You have been successfully logged out.',
    });
  };

  const register = async (userData) => {
    try {
      setLoading(true);
      const response = await apiService.post('/auth/register', userData);
      const { user: newUser, token } = response.data;
      
      localStorage.setItem('netflix_admin_token', token);
      apiService.setAuthToken(token);
      setUser(newUser);
      
      notification.success({
        message: 'Registration Successful',
        description: `Welcome, ${newUser.username}!`,
      });
      
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Registration failed';
      notification.error({
        message: 'Registration Failed',
        description: message,
      });
      return { success: false, error: message };
    } finally {
      setLoading(false);
    }
  };

  const updateProfile = async (profileData) => {
    try {
      const response = await apiService.put('/users/profile', profileData);
      setUser(prev => ({ ...prev, ...profileData }));
      
      notification.success({
        message: 'Profile Updated',
        description: 'Your profile has been updated successfully.',
      });
      
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || 'Profile update failed';
      notification.error({
        message: 'Update Failed',
        description: message,
      });
      return { success: false, error: message };
    }
  };

  const value = {
    user,
    loading,
    login,
    logout,
    register,
    updateProfile,
    checkAuthStatus
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};

export default AuthProvider;