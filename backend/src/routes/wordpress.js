const express = require('express');
const { executeQuery } = require('../services/database');

const router = express.Router();

// WordPress integration endpoint - Get movies for WordPress
router.get('/movies', async (req, res) => {
  try {
    const { page = 1, limit = 20, status = 'published' } = req.query;
    const offset = (page - 1) * limit;
    
    const movies = await executeQuery(
      `SELECT m.*, GROUP_CONCAT(vf.id) as video_file_ids
       FROM movies m
       LEFT JOIN video_files vf ON m.id = vf.movie_id AND vf.transcoding_status = 'completed'
       WHERE m.status = ?
       GROUP BY m.id
       ORDER BY m.created_at DESC
       LIMIT ? OFFSET ?`,
      [status, parseInt(limit), offset]
    );

    const countResult = await executeQuery(
      'SELECT COUNT(*) as total FROM movies WHERE status = ?',
      [status]
    );

    const total = countResult[0].total;

    res.json({
      movies: movies.map(movie => ({
        id: movie.id,
        title: movie.title,
        description: movie.description,
        poster_url: movie.poster_url,
        backdrop_url: movie.backdrop_url,
        rating: movie.rating,
        genre: movie.genre,
        release_date: movie.release_date,
        duration: movie.duration,
        director: movie.director,
        cast: movie.cast,
        has_videos: !!movie.video_file_ids,
        streaming_url: movie.video_file_ids ? `/api/videos/${movie.video_file_ids.split(',')[0]}/stream` : null
      })),
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        total_pages: Math.ceil(total / limit)
      }
    });

  } catch (error) {
    console.error('WordPress movies API error:', error);
    res.status(500).json({
      error: 'Failed to get movies',
      message: 'Unable to retrieve movies for WordPress'
    });
  }
});

// WordPress integration endpoint - Get TV shows for WordPress
router.get('/tv-shows', async (req, res) => {
  try {
    const { page = 1, limit = 20, status = 'published' } = req.query;
    const offset = (page - 1) * limit;
    
    const shows = await executeQuery(
      `SELECT ts.*, COUNT(e.id) as episode_count
       FROM tv_shows ts
       LEFT JOIN episodes e ON ts.id = e.tv_show_id
       WHERE ts.status = ?
       GROUP BY ts.id
       ORDER BY ts.created_at DESC
       LIMIT ? OFFSET ?`,
      [status, parseInt(limit), offset]
    );

    const countResult = await executeQuery(
      'SELECT COUNT(*) as total FROM tv_shows WHERE status = ?',
      [status]
    );

    const total = countResult[0].total;

    res.json({
      tv_shows: shows.map(show => ({
        id: show.id,
        title: show.title,
        description: show.description,
        poster_url: show.poster_url,
        backdrop_url: show.backdrop_url,
        rating: show.rating,
        genre: show.genre,
        first_air_date: show.first_air_date,
        last_air_date: show.last_air_date,
        creator: show.creator,
        cast: show.cast,
        episode_count: show.episode_count
      })),
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        total_pages: Math.ceil(total / limit)
      }
    });

  } catch (error) {
    console.error('WordPress TV shows API error:', error);
    res.status(500).json({
      error: 'Failed to get TV shows',
      message: 'Unable to retrieve TV shows for WordPress'
    });
  }
});

// WordPress integration endpoint - Get episodes for a TV show
router.get('/tv-shows/:id/episodes', async (req, res) => {
  try {
    const { id } = req.params;
    const { season } = req.query;
    
    let query = `
      SELECT e.*, vf.id as video_file_id
      FROM episodes e
      LEFT JOIN video_files vf ON e.id = vf.episode_id AND vf.transcoding_status = 'completed'
      WHERE e.tv_show_id = ?
    `;
    
    const params = [id];
    
    if (season) {
      query += ' AND e.season_number = ?';
      params.push(season);
    }
    
    query += ' ORDER BY e.season_number, e.episode_number';
    
    const episodes = await executeQuery(query, params);

    res.json({
      episodes: episodes.map(episode => ({
        id: episode.id,
        title: episode.title,
        description: episode.description,
        season_number: episode.season_number,
        episode_number: episode.episode_number,
        air_date: episode.air_date,
        duration: episode.duration,
        rating: episode.rating,
        has_video: !!episode.video_file_id,
        streaming_url: episode.video_file_id ? `/api/videos/${episode.video_file_id}/stream` : null
      }))
    });

  } catch (error) {
    console.error('WordPress episodes API error:', error);
    res.status(500).json({
      error: 'Failed to get episodes',
      message: 'Unable to retrieve episodes for WordPress'
    });
  }
});

// WordPress integration endpoint - Get single movie/show details
router.get('/content/:type/:id', async (req, res) => {
  try {
    const { type, id } = req.params;
    
    if (type === 'movie') {
      const movies = await executeQuery(
        `SELECT m.*, GROUP_CONCAT(vf.id) as video_file_ids
         FROM movies m
         LEFT JOIN video_files vf ON m.id = vf.movie_id AND vf.transcoding_status = 'completed'
         WHERE m.id = ? AND m.status = 'published'
         GROUP BY m.id`,
        [id]
      );

      if (movies.length === 0) {
        return res.status(404).json({ error: 'Movie not found' });
      }

      const movie = movies[0];
      res.json({
        type: 'movie',
        content: {
          id: movie.id,
          title: movie.title,
          description: movie.description,
          poster_url: movie.poster_url,
          backdrop_url: movie.backdrop_url,
          trailer_url: movie.trailer_url,
          rating: movie.rating,
          genre: movie.genre,
          release_date: movie.release_date,
          duration: movie.duration,
          director: movie.director,
          cast: movie.cast,
          has_videos: !!movie.video_file_ids,
          streaming_url: movie.video_file_ids ? `/api/videos/${movie.video_file_ids.split(',')[0]}/stream` : null
        }
      });

    } else if (type === 'tv') {
      const shows = await executeQuery(
        `SELECT ts.*, COUNT(e.id) as episode_count
         FROM tv_shows ts
         LEFT JOIN episodes e ON ts.id = e.tv_show_id
         WHERE ts.id = ? AND ts.status = 'published'
         GROUP BY ts.id`,
        [id]
      );

      if (shows.length === 0) {
        return res.status(404).json({ error: 'TV show not found' });
      }

      const show = shows[0];
      res.json({
        type: 'tv',
        content: {
          id: show.id,
          title: show.title,
          description: show.description,
          poster_url: show.poster_url,
          backdrop_url: show.backdrop_url,
          rating: show.rating,
          genre: show.genre,
          first_air_date: show.first_air_date,
          last_air_date: show.last_air_date,
          creator: show.creator,
          cast: show.cast,
          episode_count: show.episode_count
        }
      });

    } else {
      return res.status(400).json({ error: 'Invalid content type' });
    }

  } catch (error) {
    console.error('WordPress content API error:', error);
    res.status(500).json({
      error: 'Failed to get content',
      message: 'Unable to retrieve content details for WordPress'
    });
  }
});

// Health check for WordPress
router.get('/health', (req, res) => {
  res.json({
    status: 'OK',
    service: 'Netflix Backend API for WordPress',
    timestamp: new Date().toISOString()
  });
});

module.exports = router;