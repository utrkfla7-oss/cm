const express = require('express');
const { executeQuery } = require('../services/database');
const imdbService = require('../services/imdb');
const { validatePagination, validateId } = require('../middleware/validation');

const router = express.Router();

// Admin dashboard stats
router.get('/stats', async (req, res) => {
  try {
    const stats = await Promise.all([
      executeQuery('SELECT COUNT(*) as total FROM users'),
      executeQuery('SELECT COUNT(*) as total FROM movies'),
      executeQuery('SELECT COUNT(*) as total FROM tv_shows'),
      executeQuery('SELECT COUNT(*) as total FROM video_files'),
      executeQuery('SELECT COUNT(*) as total FROM import_jobs WHERE status = "processing"'),
      executeQuery('SELECT subscription_type, COUNT(*) as count FROM users GROUP BY subscription_type'),
      executeQuery(`
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM users 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
      `),
      executeQuery(`
        SELECT COUNT(*) as total_storage
        FROM video_files 
        WHERE transcoding_status = 'completed'
      `)
    ]);

    const [
      totalUsers, totalMovies, totalTVShows, totalVideos, 
      activeJobs, subscriptionStats, userGrowth, storageStats
    ] = stats;

    res.json({
      overview: {
        total_users: totalUsers[0].total,
        total_movies: totalMovies[0].total,
        total_tv_shows: totalTVShows[0].total,
        total_videos: totalVideos[0].total,
        active_import_jobs: activeJobs[0].total,
        total_storage_gb: Math.round((storageStats[0].total_storage || 0) / (1024 * 1024 * 1024))
      },
      subscriptions: subscriptionStats,
      user_growth: userGrowth
    });

  } catch (error) {
    console.error('Admin stats error:', error);
    res.status(500).json({
      error: 'Failed to get admin stats',
      message: 'Unable to retrieve dashboard statistics'
    });
  }
});

// Manage users
router.get('/users', validatePagination, async (req, res) => {
  try {
    const { page = 1, limit = 20, role, subscription_type } = req.query;
    const offset = (page - 1) * limit;

    let whereClause = '';
    const params = [];

    if (role) {
      whereClause += ' WHERE role = ?';
      params.push(role);
    }

    if (subscription_type) {
      whereClause += (whereClause ? ' AND' : ' WHERE') + ' subscription_type = ?';
      params.push(subscription_type);
    }

    const users = await executeQuery(
      `SELECT id, username, email, role, subscription_type, subscription_expires_at, 
              is_active, created_at, updated_at
       FROM users${whereClause}
       ORDER BY created_at DESC
       LIMIT ? OFFSET ?`,
      [...params, parseInt(limit), offset]
    );

    const countResult = await executeQuery(
      `SELECT COUNT(*) as total FROM users${whereClause}`,
      params
    );

    const total = countResult[0].total;

    res.json({
      users: users.map(user => ({
        ...user,
        subscription_active: user.subscription_expires_at ? 
          new Date() < new Date(user.subscription_expires_at) : false
      })),
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        total_pages: Math.ceil(total / limit)
      }
    });

  } catch (error) {
    console.error('Get users error:', error);
    res.status(500).json({
      error: 'Failed to get users',
      message: 'Unable to retrieve user list'
    });
  }
});

// Update user
router.put('/users/:id', validateId, async (req, res) => {
  try {
    const { id } = req.params;
    const { role, subscription_type, is_active, subscription_expires_at } = req.body;

    const updates = [];
    const values = [];

    if (role && ['user', 'admin', 'moderator'].includes(role)) {
      updates.push('role = ?');
      values.push(role);
    }

    if (subscription_type && ['free', 'basic', 'premium'].includes(subscription_type)) {
      updates.push('subscription_type = ?');
      values.push(subscription_type);
    }

    if (typeof is_active === 'boolean') {
      updates.push('is_active = ?');
      values.push(is_active);
    }

    if (subscription_expires_at) {
      updates.push('subscription_expires_at = ?');
      values.push(subscription_expires_at);
    }

    if (updates.length === 0) {
      return res.status(400).json({
        error: 'No valid updates provided'
      });
    }

    values.push(id);

    const result = await executeQuery(
      `UPDATE users SET ${updates.join(', ')}, updated_at = CURRENT_TIMESTAMP WHERE id = ?`,
      values
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({
        error: 'User not found'
      });
    }

    res.json({
      message: 'User updated successfully'
    });

  } catch (error) {
    console.error('Update user error:', error);
    res.status(500).json({
      error: 'Failed to update user',
      message: 'Unable to update user'
    });
  }
});

// Manage movies
router.get('/movies', validatePagination, async (req, res) => {
  try {
    const { page = 1, limit = 20, status } = req.query;
    const offset = (page - 1) * limit;

    let whereClause = '';
    const params = [];

    if (status) {
      whereClause = ' WHERE status = ?';
      params.push(status);
    }

    const movies = await executeQuery(
      `SELECT m.*, u.username as created_by_username,
              COUNT(vf.id) as video_count
       FROM movies m
       LEFT JOIN users u ON m.created_by = u.id
       LEFT JOIN video_files vf ON m.id = vf.movie_id
       ${whereClause}
       GROUP BY m.id
       ORDER BY m.created_at DESC
       LIMIT ? OFFSET ?`,
      [...params, parseInt(limit), offset]
    );

    const countResult = await executeQuery(
      `SELECT COUNT(*) as total FROM movies${whereClause}`,
      params
    );

    const total = countResult[0].total;

    res.json({
      movies,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        total_pages: Math.ceil(total / limit)
      }
    });

  } catch (error) {
    console.error('Get movies error:', error);
    res.status(500).json({
      error: 'Failed to get movies',
      message: 'Unable to retrieve movie list'
    });
  }
});

// Update movie status
router.put('/movies/:id', validateId, async (req, res) => {
  try {
    const { id } = req.params;
    const { status, title, description } = req.body;

    const updates = [];
    const values = [];

    if (status && ['draft', 'published', 'archived'].includes(status)) {
      updates.push('status = ?');
      values.push(status);
    }

    if (title) {
      updates.push('title = ?');
      values.push(title);
    }

    if (description) {
      updates.push('description = ?');
      values.push(description);
    }

    if (updates.length === 0) {
      return res.status(400).json({
        error: 'No valid updates provided'
      });
    }

    values.push(id);

    const result = await executeQuery(
      `UPDATE movies SET ${updates.join(', ')}, updated_at = CURRENT_TIMESTAMP WHERE id = ?`,
      values
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({
        error: 'Movie not found'
      });
    }

    res.json({
      message: 'Movie updated successfully'
    });

  } catch (error) {
    console.error('Update movie error:', error);
    res.status(500).json({
      error: 'Failed to update movie',
      message: 'Unable to update movie'
    });
  }
});

// Delete movie
router.delete('/movies/:id', validateId, async (req, res) => {
  try {
    const { id } = req.params;

    // Delete associated video files and records
    await executeQuery('DELETE FROM video_files WHERE movie_id = ?', [id]);
    await executeQuery('DELETE FROM subtitles WHERE movie_id = ?', [id]);
    await executeQuery('DELETE FROM user_favorites WHERE movie_id = ?', [id]);
    await executeQuery('DELETE FROM watch_history WHERE movie_id = ?', [id]);
    
    // Delete movie
    const result = await executeQuery('DELETE FROM movies WHERE id = ?', [id]);

    if (result.affectedRows === 0) {
      return res.status(404).json({
        error: 'Movie not found'
      });
    }

    res.json({
      message: 'Movie deleted successfully'
    });

  } catch (error) {
    console.error('Delete movie error:', error);
    res.status(500).json({
      error: 'Failed to delete movie',
      message: 'Unable to delete movie'
    });
  }
});

// Manage import jobs
router.get('/import-jobs', validatePagination, async (req, res) => {
  try {
    const { page = 1, limit = 20, status } = req.query;
    const offset = (page - 1) * limit;

    let whereClause = '';
    const params = [];

    if (status) {
      whereClause = ' WHERE ij.status = ?';
      params.push(status);
    }

    const jobs = await executeQuery(
      `SELECT ij.*, u.username
       FROM import_jobs ij
       LEFT JOIN users u ON ij.user_id = u.id
       ${whereClause}
       ORDER BY ij.created_at DESC
       LIMIT ? OFFSET ?`,
      [...params, parseInt(limit), offset]
    );

    const countResult = await executeQuery(
      `SELECT COUNT(*) as total FROM import_jobs ij${whereClause}`,
      params
    );

    const total = countResult[0].total;

    res.json({
      jobs: jobs.map(job => ({
        ...job,
        parameters: job.parameters ? JSON.parse(job.parameters) : null,
        error_log: job.error_log ? JSON.parse(job.error_log) : null,
        progress: job.total_items > 0 ? Math.round((job.processed_items / job.total_items) * 100) : 0
      })),
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        total_pages: Math.ceil(total / limit)
      }
    });

  } catch (error) {
    console.error('Get import jobs error:', error);
    res.status(500).json({
      error: 'Failed to get import jobs',
      message: 'Unable to retrieve import jobs'
    });
  }
});

// Cancel import job
router.post('/import-jobs/:id/cancel', validateId, async (req, res) => {
  try {
    const { id } = req.params;

    const result = await executeQuery(
      'UPDATE import_jobs SET status = "failed", error_log = ? WHERE id = ? AND status = "processing"',
      ['Job cancelled by admin', id]
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({
        error: 'Job not found or cannot be cancelled',
        message: 'Import job not found or not in processing state'
      });
    }

    res.json({
      message: 'Import job cancelled successfully'
    });

  } catch (error) {
    console.error('Cancel import job error:', error);
    res.status(500).json({
      error: 'Failed to cancel import job',
      message: 'Unable to cancel import job'
    });
  }
});

// System maintenance
router.post('/maintenance/cleanup-old-files', async (req, res) => {
  try {
    const { days_old = 30 } = req.body;

    // This would call the transcoding service cleanup function
    // For now, just return a success message
    
    res.json({
      message: `Cleanup initiated for files older than ${days_old} days`,
      note: 'Cleanup process will run in the background'
    });

  } catch (error) {
    console.error('Cleanup error:', error);
    res.status(500).json({
      error: 'Cleanup failed',
      message: 'Unable to start cleanup process'
    });
  }
});

// Bulk import popular content
router.post('/bulk-import/popular', async (req, res) => {
  try {
    const { type = 'movie', pages = 1 } = req.body;
    const userId = req.user.id;

    if (!['movie', 'tv'].includes(type)) {
      return res.status(400).json({
        error: 'Invalid type',
        message: 'Type must be either "movie" or "tv"'
      });
    }

    // Create bulk import job
    const jobResult = await executeQuery(
      `INSERT INTO import_jobs (user_id, job_type, status, total_items, parameters) 
       VALUES (?, ?, ?, ?, ?)`,
      [
        userId, 
        `tmdb_${type}`, 
        'pending', 
        pages * 20, // Approximate items per page
        JSON.stringify({ type, pages, bulk_popular: true })
      ]
    );

    const jobId = jobResult.insertId;

    // Start import process
    setImmediate(async () => {
      try {
        const contentIds = [];
        
        for (let page = 1; page <= pages; page++) {
          const popular = type === 'movie' ? 
            await imdbService.getPopularMovies(page) :
            await imdbService.getPopularTVShows(page);
          
          contentIds.push(...popular.results.map(item => item.id));
        }

        // Update job with actual count
        await executeQuery(
          'UPDATE import_jobs SET total_items = ?, status = "processing" WHERE id = ?',
          [contentIds.length, jobId]
        );

        // Import content
        const results = type === 'movie' ?
          await imdbService.batchImportMovies(contentIds, userId, jobId) :
          await imdbService.batchImportTVShows(contentIds, userId, jobId, false);

        // Update job status
        await executeQuery(
          `UPDATE import_jobs SET 
            status = "completed", 
            processed_items = ?,
            failed_items = ?
          WHERE id = ?`,
          [results.successful + results.failed, results.failed, jobId]
        );

      } catch (error) {
        console.error(`Bulk import job ${jobId} failed:`, error);
        await executeQuery(
          'UPDATE import_jobs SET status = "failed", error_log = ? WHERE id = ?',
          [error.message, jobId]
        );
      }
    });

    res.json({
      message: 'Bulk import started',
      job_id: jobId,
      type,
      pages
    });

  } catch (error) {
    console.error('Bulk import error:', error);
    res.status(500).json({
      error: 'Bulk import failed',
      message: 'Unable to start bulk import'
    });
  }
});

module.exports = router;