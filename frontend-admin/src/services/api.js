import axios from 'axios';

class ApiService {
  constructor() {
    this.baseURL = process.env.REACT_APP_API_URL || 'http://localhost:3001/api';
    this.api = axios.create({
      baseURL: this.baseURL,
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
      },
    });

    // Request interceptor
    this.api.interceptors.request.use(
      (config) => {
        const token = localStorage.getItem('netflix_admin_token');
        if (token) {
          config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
      },
      (error) => {
        return Promise.reject(error);
      }
    );

    // Response interceptor
    this.api.interceptors.response.use(
      (response) => {
        return response;
      },
      (error) => {
        if (error.response?.status === 401) {
          localStorage.removeItem('netflix_admin_token');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );
  }

  setAuthToken(token) {
    if (token) {
      this.api.defaults.headers.Authorization = `Bearer ${token}`;
    } else {
      delete this.api.defaults.headers.Authorization;
    }
  }

  // Generic HTTP methods
  async get(url, config = {}) {
    return this.api.get(url, config);
  }

  async post(url, data = {}, config = {}) {
    return this.api.post(url, data, config);
  }

  async put(url, data = {}, config = {}) {
    return this.api.put(url, data, config);
  }

  async delete(url, config = {}) {
    return this.api.delete(url, config);
  }

  // Authentication methods
  async login(credentials) {
    return this.post('/auth/login', credentials);
  }

  async register(userData) {
    return this.post('/auth/register', userData);
  }

  async logout() {
    return this.post('/auth/logout');
  }

  async refreshToken() {
    return this.post('/auth/refresh');
  }

  // User management
  async getUsers(params = {}) {
    return this.get('/admin/users', { params });
  }

  async updateUser(id, userData) {
    return this.put(`/admin/users/${id}`, userData);
  }

  async deleteUser(id) {
    return this.delete(`/admin/users/${id}`);
  }

  // Movie management
  async getMovies(params = {}) {
    return this.get('/admin/movies', { params });
  }

  async getMovie(id) {
    return this.get(`/admin/movies/${id}`);
  }

  async updateMovie(id, movieData) {
    return this.put(`/admin/movies/${id}`, movieData);
  }

  async deleteMovie(id) {
    return this.delete(`/admin/movies/${id}`);
  }

  // TV Show management
  async getTVShows(params = {}) {
    return this.get('/admin/tv-shows', { params });
  }

  async getTVShow(id) {
    return this.get(`/admin/tv-shows/${id}`);
  }

  async updateTVShow(id, showData) {
    return this.put(`/admin/tv-shows/${id}`, showData);
  }

  async deleteTVShow(id) {
    return this.delete(`/admin/tv-shows/${id}`);
  }

  // Video management
  async getVideos(params = {}) {
    return this.get('/videos', { params });
  }

  async getVideo(id) {
    return this.get(`/videos/${id}`);
  }

  async uploadVideo(formData, onProgress) {
    return this.post('/videos/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: onProgress,
    });
  }

  async deleteVideo(id) {
    return this.delete(`/videos/${id}`);
  }

  // Import management
  async searchContent(query, type = 'multi') {
    return this.get('/imdb/search', { params: { q: query, type } });
  }

  async getPopularMovies(page = 1) {
    return this.get('/imdb/popular/movies', { params: { page } });
  }

  async getPopularTVShows(page = 1) {
    return this.get('/imdb/popular/tv', { params: { page } });
  }

  async importMovie(tmdbId) {
    return this.post(`/imdb/import/movie/${tmdbId}`);
  }

  async importTVShow(tmdbId, includeEpisodes = false) {
    return this.post(`/imdb/import/tv/${tmdbId}`, { include_episodes: includeEpisodes });
  }

  async startBatchImport(type, options = {}) {
    const endpoint = type === 'movies' ? '/imdb/batch-import/movies' : '/imdb/batch-import/tv';
    return this.post(endpoint, options);
  }

  async getImportJobs(params = {}) {
    return this.get('/admin/import-jobs', { params });
  }

  async getImportJob(id) {
    return this.get(`/imdb/import-jobs/${id}`);
  }

  async cancelImportJob(id) {
    return this.post(`/admin/import-jobs/${id}/cancel`);
  }

  // Dashboard/Statistics
  async getDashboardStats() {
    return this.get('/admin/stats');
  }

  // Settings management
  async getSettings() {
    return this.get('/admin/settings');
  }

  async updateSettings(settings) {
    return this.put('/admin/settings', settings);
  }

  // Subscription management
  async getSubscriptionPlans() {
    return this.get('/subscriptions/plans');
  }

  async updateUserSubscription(userId, planId) {
    return this.post('/subscriptions/subscribe', { 
      user_id: userId, 
      plan_id: planId 
    });
  }

  // System maintenance
  async cleanupOldFiles(daysOld = 30) {
    return this.post('/admin/maintenance/cleanup-old-files', { days_old: daysOld });
  }

  async bulkImportPopular(type, pages = 1) {
    return this.post('/admin/bulk-import/popular', { type, pages });
  }

  // File upload helper
  createFormData(file, additionalData = {}) {
    const formData = new FormData();
    formData.append('video', file);
    
    Object.keys(additionalData).forEach(key => {
      formData.append(key, additionalData[key]);
    });
    
    return formData;
  }

  // URL helpers
  getStreamingUrl(videoId, quality = 'auto') {
    return `${this.baseURL}/videos/${videoId}/stream?quality=${quality}`;
  }

  getMediaUrl(path) {
    return `${this.baseURL.replace('/api', '')}/media/${path}`;
  }
}

const apiService = new ApiService();
export default apiService;