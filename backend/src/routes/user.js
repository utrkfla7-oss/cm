const express = require('express');
const { executeQuery } = require('../services/database');
const { validatePagination, validateId } = require('../middleware/validation');

const router = express.Router();

// Get user profile
router.get('/profile', async (req, res) => {
  try {
    const users = await executeQuery(
      'SELECT id, username, email, role, subscription_type, subscription_expires_at, profile_image, preferences, created_at FROM users WHERE id = ?',
      [req.user.id]
    );

    if (users.length === 0) {
      return res.status(404).json({
        error: 'User not found'
      });
    }

    const user = users[0];
    res.json({
      user: {
        ...user,
        preferences: user.preferences ? JSON.parse(user.preferences) : {}
      }
    });

  } catch (error) {
    console.error('Get profile error:', error);
    res.status(500).json({
      error: 'Failed to get profile',
      message: 'Unable to retrieve user profile'
    });
  }
});

// Update user profile
router.put('/profile', async (req, res) => {
  try {
    const { username, profile_image, preferences } = req.body;
    const userId = req.user.id;

    const updates = [];
    const values = [];

    if (username) {
      updates.push('username = ?');
      values.push(username);
    }

    if (profile_image) {
      updates.push('profile_image = ?');
      values.push(profile_image);
    }

    if (preferences) {
      updates.push('preferences = ?');
      values.push(JSON.stringify(preferences));
    }

    if (updates.length === 0) {
      return res.status(400).json({
        error: 'No updates provided',
        message: 'Please provide at least one field to update'
      });
    }

    values.push(userId);

    await executeQuery(
      `UPDATE users SET ${updates.join(', ')}, updated_at = CURRENT_TIMESTAMP WHERE id = ?`,
      values
    );

    res.json({
      message: 'Profile updated successfully'
    });

  } catch (error) {
    console.error('Update profile error:', error);
    
    if (error.code === 'ER_DUP_ENTRY') {
      return res.status(409).json({
        error: 'Username already taken',
        message: 'Please choose a different username'
      });
    }
    
    res.status(500).json({
      error: 'Profile update failed',
      message: 'Unable to update user profile'
    });
  }
});

// Get user favorites
router.get('/favorites', validatePagination, async (req, res) => {
  try {
    const { page = 1, limit = 20 } = req.query;
    const offset = (page - 1) * limit;
    const userId = req.user.id;

    const favorites = await executeQuery(
      `SELECT 
        uf.id,
        uf.created_at,
        m.id as movie_id,
        m.title as movie_title,
        m.poster_url as movie_poster,
        ts.id as tv_show_id,
        ts.title as tv_show_title,
        ts.poster_url as tv_show_poster
       FROM user_favorites uf
       LEFT JOIN movies m ON uf.movie_id = m.id
       LEFT JOIN tv_shows ts ON uf.tv_show_id = ts.id
       WHERE uf.user_id = ?
       ORDER BY uf.created_at DESC
       LIMIT ? OFFSET ?`,
      [userId, parseInt(limit), offset]
    );

    const countResult = await executeQuery(
      'SELECT COUNT(*) as total FROM user_favorites WHERE user_id = ?',
      [userId]
    );

    const total = countResult[0].total;

    res.json({
      favorites: favorites.map(fav => ({
        id: fav.id,
        type: fav.movie_id ? 'movie' : 'tv_show',
        content: {
          id: fav.movie_id || fav.tv_show_id,
          title: fav.movie_title || fav.tv_show_title,
          poster_url: fav.movie_poster || fav.tv_show_poster
        },
        created_at: fav.created_at
      })),
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        total_pages: Math.ceil(total / limit)
      }
    });

  } catch (error) {
    console.error('Get favorites error:', error);
    res.status(500).json({
      error: 'Failed to get favorites',
      message: 'Unable to retrieve user favorites'
    });
  }
});

// Add to favorites
router.post('/favorites', async (req, res) => {
  try {
    const { type, content_id } = req.body;
    const userId = req.user.id;

    if (!['movie', 'tv_show'].includes(type)) {
      return res.status(400).json({
        error: 'Invalid type',
        message: 'Type must be either "movie" or "tv_show"'
      });
    }

    const movieId = type === 'movie' ? content_id : null;
    const tvShowId = type === 'tv_show' ? content_id : null;

    await executeQuery(
      'INSERT INTO user_favorites (user_id, movie_id, tv_show_id) VALUES (?, ?, ?)',
      [userId, movieId, tvShowId]
    );

    res.json({
      message: 'Added to favorites successfully'
    });

  } catch (error) {
    console.error('Add favorite error:', error);
    
    if (error.code === 'ER_DUP_ENTRY') {
      return res.status(409).json({
        error: 'Already in favorites',
        message: 'This content is already in your favorites'
      });
    }
    
    res.status(500).json({
      error: 'Failed to add favorite',
      message: 'Unable to add content to favorites'
    });
  }
});

// Remove from favorites
router.delete('/favorites/:id', validateId, async (req, res) => {
  try {
    const { id } = req.params;
    const userId = req.user.id;

    const result = await executeQuery(
      'DELETE FROM user_favorites WHERE id = ? AND user_id = ?',
      [id, userId]
    );

    if (result.affectedRows === 0) {
      return res.status(404).json({
        error: 'Favorite not found',
        message: 'Favorite item not found or you do not have permission to remove it'
      });
    }

    res.json({
      message: 'Removed from favorites successfully'
    });

  } catch (error) {
    console.error('Remove favorite error:', error);
    res.status(500).json({
      error: 'Failed to remove favorite',
      message: 'Unable to remove content from favorites'
    });
  }
});

// Get watch history
router.get('/watch-history', validatePagination, async (req, res) => {
  try {
    const { page = 1, limit = 20 } = req.query;
    const offset = (page - 1) * limit;
    const userId = req.user.id;

    const history = await executeQuery(
      `SELECT 
        wh.id,
        wh.watched_at,
        wh.watch_time,
        wh.total_duration,
        wh.completed,
        m.id as movie_id,
        m.title as movie_title,
        m.poster_url as movie_poster,
        e.id as episode_id,
        e.title as episode_title,
        e.season_number,
        e.episode_number,
        ts.title as tv_show_title,
        ts.poster_url as tv_show_poster
       FROM watch_history wh
       LEFT JOIN movies m ON wh.movie_id = m.id
       LEFT JOIN episodes e ON wh.episode_id = e.id
       LEFT JOIN tv_shows ts ON e.tv_show_id = ts.id
       WHERE wh.user_id = ?
       ORDER BY wh.watched_at DESC
       LIMIT ? OFFSET ?`,
      [userId, parseInt(limit), offset]
    );

    const countResult = await executeQuery(
      'SELECT COUNT(*) as total FROM watch_history WHERE user_id = ?',
      [userId]
    );

    const total = countResult[0].total;

    res.json({
      watch_history: history.map(item => ({
        id: item.id,
        type: item.movie_id ? 'movie' : 'episode',
        content: item.movie_id ? {
          id: item.movie_id,
          title: item.movie_title,
          poster_url: item.movie_poster
        } : {
          id: item.episode_id,
          title: item.episode_title,
          season_number: item.season_number,
          episode_number: item.episode_number,
          tv_show_title: item.tv_show_title,
          poster_url: item.tv_show_poster
        },
        watched_at: item.watched_at,
        watch_time: item.watch_time,
        total_duration: item.total_duration,
        completed: item.completed,
        progress: item.total_duration > 0 ? Math.round((item.watch_time / item.total_duration) * 100) : 0
      })),
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        total_pages: Math.ceil(total / limit)
      }
    });

  } catch (error) {
    console.error('Get watch history error:', error);
    res.status(500).json({
      error: 'Failed to get watch history',
      message: 'Unable to retrieve watch history'
    });
  }
});

// Update watch progress
router.post('/watch-progress', async (req, res) => {
  try {
    const { type, content_id, watch_time, total_duration } = req.body;
    const userId = req.user.id;

    if (!['movie', 'episode'].includes(type)) {
      return res.status(400).json({
        error: 'Invalid type',
        message: 'Type must be either "movie" or "episode"'
      });
    }

    const movieId = type === 'movie' ? content_id : null;
    const episodeId = type === 'episode' ? content_id : null;
    const completed = total_duration > 0 && watch_time >= (total_duration * 0.9); // 90% completion

    // Insert or update watch progress
    await executeQuery(
      `INSERT INTO watch_history (user_id, movie_id, episode_id, watch_time, total_duration, completed, watched_at)
       VALUES (?, ?, ?, ?, ?, ?, NOW())
       ON DUPLICATE KEY UPDATE 
       watch_time = VALUES(watch_time),
       total_duration = VALUES(total_duration),
       completed = VALUES(completed),
       watched_at = NOW()`,
      [userId, movieId, episodeId, watch_time, total_duration, completed]
    );

    res.json({
      message: 'Watch progress updated successfully',
      completed
    });

  } catch (error) {
    console.error('Update watch progress error:', error);
    res.status(500).json({
      error: 'Failed to update watch progress',
      message: 'Unable to save watch progress'
    });
  }
});

module.exports = router;