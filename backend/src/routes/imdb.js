const express = require('express');
const imdbService = require('../services/imdb');
const { executeQuery } = require('../services/database');
const { validateImport, validatePagination, validateSearch, validateId } = require('../middleware/validation');

const router = express.Router();

// Search movies/TV shows
router.get('/search', validateSearch, async (req, res) => {
  try {
    const { q: query, type = 'multi', page = 1 } = req.query;
    
    const results = await imdbService.search(query, type);
    
    res.json({
      query,
      type,
      page: parseInt(page),
      results
    });
  } catch (error) {
    console.error('Search error:', error);
    res.status(500).json({
      error: 'Search failed',
      message: 'Failed to search for content'
    });
  }
});

// Get popular movies
router.get('/popular/movies', validatePagination, async (req, res) => {
  try {
    const { page = 1 } = req.query;
    
    const results = await imdbService.getPopularMovies(page);
    
    res.json({
      page: parseInt(page),
      total_pages: results.total_pages,
      total_results: results.total_results,
      results: results.results
    });
  } catch (error) {
    console.error('Popular movies error:', error);
    res.status(500).json({
      error: 'Failed to get popular movies',
      message: 'Unable to retrieve popular movies from TMDb'
    });
  }
});

// Get popular TV shows
router.get('/popular/tv', validatePagination, async (req, res) => {
  try {
    const { page = 1 } = req.query;
    
    const results = await imdbService.getPopularTVShows(page);
    
    res.json({
      page: parseInt(page),
      total_pages: results.total_pages,
      total_results: results.total_results,
      results: results.results
    });
  } catch (error) {
    console.error('Popular TV shows error:', error);
    res.status(500).json({
      error: 'Failed to get popular TV shows',
      message: 'Unable to retrieve popular TV shows from TMDb'
    });
  }
});

// Import single movie
router.post('/import/movie/:tmdbId', validateId, async (req, res) => {
  try {
    const { tmdbId } = req.params;
    const userId = req.user.id;
    
    const movie = await imdbService.importMovie(tmdbId, userId);
    
    res.json({
      message: 'Movie imported successfully',
      movie
    });
  } catch (error) {
    console.error('Movie import error:', error);
    
    if (error.message.includes('already exists')) {
      return res.status(409).json({
        error: 'Movie already exists',
        message: error.message
      });
    }
    
    res.status(500).json({
      error: 'Import failed',
      message: 'Failed to import movie'
    });
  }
});

// Import single TV show
router.post('/import/tv/:tmdbId', validateId, async (req, res) => {
  try {
    const { tmdbId } = req.params;
    const { include_episodes = false } = req.body;
    const userId = req.user.id;
    
    const tvShow = await imdbService.importTVShow(tmdbId, userId, include_episodes);
    
    res.json({
      message: 'TV show imported successfully',
      tvShow
    });
  } catch (error) {
    console.error('TV show import error:', error);
    
    if (error.message.includes('already exists')) {
      return res.status(409).json({
        error: 'TV show already exists',
        message: error.message
      });
    }
    
    res.status(500).json({
      error: 'Import failed',
      message: 'Failed to import TV show'
    });
  }
});

// Batch import movies
router.post('/batch-import/movies', async (req, res) => {
  try {
    const { tmdb_ids, auto_popular = false, pages = 1 } = req.body;
    const userId = req.user.id;
    
    let movieIds = tmdb_ids || [];
    
    // If auto_popular is true, get popular movies
    if (auto_popular) {
      movieIds = [];
      for (let page = 1; page <= pages; page++) {
        const popularMovies = await imdbService.getPopularMovies(page);
        movieIds.push(...popularMovies.results.map(movie => movie.id));
      }
    }
    
    if (movieIds.length === 0) {
      return res.status(400).json({
        error: 'No movies to import',
        message: 'Please provide tmdb_ids or set auto_popular to true'
      });
    }
    
    // Create import job
    const jobResult = await executeQuery(
      `INSERT INTO import_jobs (user_id, job_type, status, total_items, parameters) 
       VALUES (?, ?, ?, ?, ?)`,
      [
        userId, 
        'tmdb_movie', 
        'pending', 
        movieIds.length, 
        JSON.stringify({ tmdb_ids: movieIds, auto_popular, pages })
      ]
    );
    
    const jobId = jobResult.insertId;
    
    // Start batch import asynchronously
    setImmediate(async () => {
      try {
        await executeQuery(
          'UPDATE import_jobs SET status = "processing" WHERE id = ?',
          [jobId]
        );
        
        const results = await imdbService.batchImportMovies(movieIds, userId, jobId);
        
        await executeQuery(
          `UPDATE import_jobs SET 
            status = "completed", 
            processed_items = ?,
            failed_items = ?,
            error_log = ?
          WHERE id = ?`,
          [
            results.successful + results.failed,
            results.failed,
            results.errors.length > 0 ? JSON.stringify(results.errors) : null,
            jobId
          ]
        );
        
        console.log(`Batch import job ${jobId} completed:`, results);
      } catch (error) {
        console.error(`Batch import job ${jobId} failed:`, error);
        
        await executeQuery(
          'UPDATE import_jobs SET status = "failed", error_log = ? WHERE id = ?',
          [error.message, jobId]
        );
      }
    });
    
    res.json({
      message: 'Batch import job started',
      job_id: jobId,
      total_items: movieIds.length,
      status: 'processing'
    });
    
  } catch (error) {
    console.error('Batch import movies error:', error);
    res.status(500).json({
      error: 'Batch import failed',
      message: 'Failed to start batch import job'
    });
  }
});

// Batch import TV shows
router.post('/batch-import/tv', async (req, res) => {
  try {
    const { tmdb_ids, include_episodes = false, auto_popular = false, pages = 1 } = req.body;
    const userId = req.user.id;
    
    let showIds = tmdb_ids || [];
    
    // If auto_popular is true, get popular TV shows
    if (auto_popular) {
      showIds = [];
      for (let page = 1; page <= pages; page++) {
        const popularShows = await imdbService.getPopularTVShows(page);
        showIds.push(...popularShows.results.map(show => show.id));
      }
    }
    
    if (showIds.length === 0) {
      return res.status(400).json({
        error: 'No TV shows to import',
        message: 'Please provide tmdb_ids or set auto_popular to true'
      });
    }
    
    // Create import job
    const jobResult = await executeQuery(
      `INSERT INTO import_jobs (user_id, job_type, status, total_items, parameters) 
       VALUES (?, ?, ?, ?, ?)`,
      [
        userId, 
        'tmdb_show', 
        'pending', 
        showIds.length, 
        JSON.stringify({ tmdb_ids: showIds, include_episodes, auto_popular, pages })
      ]
    );
    
    const jobId = jobResult.insertId;
    
    // Start batch import asynchronously
    setImmediate(async () => {
      try {
        await executeQuery(
          'UPDATE import_jobs SET status = "processing" WHERE id = ?',
          [jobId]
        );
        
        const results = await imdbService.batchImportTVShows(showIds, userId, jobId, include_episodes);
        
        await executeQuery(
          `UPDATE import_jobs SET 
            status = "completed", 
            processed_items = ?,
            failed_items = ?,
            error_log = ?
          WHERE id = ?`,
          [
            results.successful + results.failed,
            results.failed,
            results.errors.length > 0 ? JSON.stringify(results.errors) : null,
            jobId
          ]
        );
        
        console.log(`Batch import job ${jobId} completed:`, results);
      } catch (error) {
        console.error(`Batch import job ${jobId} failed:`, error);
        
        await executeQuery(
          'UPDATE import_jobs SET status = "failed", error_log = ? WHERE id = ?',
          [error.message, jobId]
        );
      }
    });
    
    res.json({
      message: 'Batch import job started',
      job_id: jobId,
      total_items: showIds.length,
      status: 'processing',
      include_episodes
    });
    
  } catch (error) {
    console.error('Batch import TV shows error:', error);
    res.status(500).json({
      error: 'Batch import failed',
      message: 'Failed to start batch import job'
    });
  }
});

// Get import job status
router.get('/import-jobs/:id', validateId, async (req, res) => {
  try {
    const { id } = req.params;
    const userId = req.user.id;
    
    const jobs = await executeQuery(
      'SELECT * FROM import_jobs WHERE id = ? AND user_id = ?',
      [id, userId]
    );
    
    if (jobs.length === 0) {
      return res.status(404).json({
        error: 'Job not found',
        message: 'Import job not found or you do not have permission to view it'
      });
    }
    
    const job = jobs[0];
    
    res.json({
      id: job.id,
      job_type: job.job_type,
      status: job.status,
      total_items: job.total_items,
      processed_items: job.processed_items,
      failed_items: job.failed_items,
      progress: job.total_items > 0 ? Math.round((job.processed_items / job.total_items) * 100) : 0,
      parameters: job.parameters ? JSON.parse(job.parameters) : null,
      error_log: job.error_log ? JSON.parse(job.error_log) : null,
      created_at: job.created_at,
      updated_at: job.updated_at
    });
    
  } catch (error) {
    console.error('Get import job error:', error);
    res.status(500).json({
      error: 'Failed to get job status',
      message: 'Unable to retrieve import job information'
    });
  }
});

// List import jobs
router.get('/import-jobs', validatePagination, async (req, res) => {
  try {
    const { page = 1, limit = 20 } = req.query;
    const userId = req.user.id;
    const offset = (page - 1) * limit;
    
    const jobs = await executeQuery(
      `SELECT id, job_type, status, total_items, processed_items, failed_items, 
              created_at, updated_at 
       FROM import_jobs 
       WHERE user_id = ? 
       ORDER BY created_at DESC 
       LIMIT ? OFFSET ?`,
      [userId, parseInt(limit), offset]
    );
    
    const countResult = await executeQuery(
      'SELECT COUNT(*) as total FROM import_jobs WHERE user_id = ?',
      [userId]
    );
    
    const total = countResult[0].total;
    const totalPages = Math.ceil(total / limit);
    
    const jobsWithProgress = jobs.map(job => ({
      ...job,
      progress: job.total_items > 0 ? Math.round((job.processed_items / job.total_items) * 100) : 0
    }));
    
    res.json({
      jobs: jobsWithProgress,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        total_pages: totalPages
      }
    });
    
  } catch (error) {
    console.error('List import jobs error:', error);
    res.status(500).json({
      error: 'Failed to list import jobs',
      message: 'Unable to retrieve import jobs'
    });
  }
});

module.exports = router;