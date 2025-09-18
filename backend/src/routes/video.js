const express = require('express');
const multer = require('multer');
const path = require('path');
const { executeQuery } = require('../services/database');
const { checkSubscription } = require('../middleware/auth');
const { validateVideoUpload, validatePagination, validateId } = require('../middleware/validation');

const router = express.Router();

// Configure multer for video uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, process.env.UPLOAD_DIR || './uploads');
  },
  filename: (req, file, cb) => {
    const uniqueName = `${Date.now()}-${Math.round(Math.random() * 1E9)}${path.extname(file.originalname)}`;
    cb(null, uniqueName);
  }
});

const upload = multer({
  storage,
  limits: {
    fileSize: parseInt(process.env.MAX_FILE_SIZE) || 5 * 1024 * 1024 * 1024 // 5GB default
  },
  fileFilter: (req, file, cb) => {
    const allowedFormats = process.env.ALLOWED_VIDEO_FORMATS ? 
      process.env.ALLOWED_VIDEO_FORMATS.split(',') : 
      ['mp4', 'avi', 'mkv', 'mov', 'wmv'];
    
    const fileExt = path.extname(file.originalname).toLowerCase().substring(1);
    
    if (allowedFormats.includes(fileExt)) {
      cb(null, true);
    } else {
      cb(new Error(`File format not allowed. Allowed formats: ${allowedFormats.join(', ')}`));
    }
  }
});

// Upload video file
router.post('/upload', upload.single('video'), validateVideoUpload, async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({
        error: 'No file uploaded',
        message: 'Please select a video file to upload'
      });
    }

    const { title, description, movie_id, episode_id, quality = 'original' } = req.body;
    
    // Insert video file record
    const result = await executeQuery(
      `INSERT INTO video_files (
        movie_id, episode_id, original_filename, file_path, file_size, 
        mime_type, quality, format, transcoding_status
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        movie_id || null,
        episode_id || null,
        req.file.originalname,
        req.file.path,
        req.file.size,
        req.file.mimetype,
        quality,
        path.extname(req.file.originalname).substring(1),
        'pending'
      ]
    );

    res.json({
      message: 'Video uploaded successfully',
      video_file: {
        id: result.insertId,
        original_filename: req.file.originalname,
        file_size: req.file.size,
        quality,
        transcoding_status: 'pending'
      }
    });

  } catch (error) {
    console.error('Video upload error:', error);
    res.status(500).json({
      error: 'Upload failed',
      message: 'Failed to upload video file'
    });
  }
});

// Get video streaming URL
router.get('/:id/stream', validateId, checkSubscription('free'), async (req, res) => {
  try {
    const { id } = req.params;
    const { quality = 'auto' } = req.query;
    
    // Get video file details
    const videos = await executeQuery(
      `SELECT vf.*, m.title as movie_title, e.title as episode_title 
       FROM video_files vf
       LEFT JOIN movies m ON vf.movie_id = m.id
       LEFT JOIN episodes e ON vf.episode_id = e.id
       WHERE vf.id = ? AND vf.transcoding_status = 'completed'`,
      [id]
    );

    if (videos.length === 0) {
      return res.status(404).json({
        error: 'Video not found',
        message: 'Video file not found or not ready for streaming'
      });
    }

    const video = videos[0];
    
    // Check subscription requirements for premium content
    if (video.movie_id) {
      const movies = await executeQuery(
        'SELECT subscription_required FROM movies WHERE id = ?',
        [video.movie_id]
      );
      
      if (movies[0]?.subscription_required && req.user.subscription?.type === 'free') {
        return res.status(403).json({
          error: 'Premium content',
          message: 'This content requires a premium subscription'
        });
      }
    }

    // Generate streaming URL
    const streamingUrl = `/media/${id}/hls/master.m3u8`;
    
    // Log viewing activity
    await executeQuery(
      `INSERT INTO watch_history (user_id, movie_id, episode_id, watched_at) 
       VALUES (?, ?, ?, NOW())`,
      [req.user.id, video.movie_id, video.episode_id]
    );

    res.json({
      streaming_url: streamingUrl,
      quality_options: ['240p', '360p', '480p', '720p', '1080p'],
      video_info: {
        title: video.movie_title || video.episode_title,
        duration: video.duration,
        format: 'HLS'
      }
    });

  } catch (error) {
    console.error('Get streaming URL error:', error);
    res.status(500).json({
      error: 'Streaming failed',
      message: 'Failed to get streaming URL'
    });
  }
});

// Get video details
router.get('/:id', validateId, async (req, res) => {
  try {
    const { id } = req.params;
    
    const videos = await executeQuery(
      `SELECT vf.*, m.title as movie_title, e.title as episode_title,
              m.poster_url, m.description as movie_description,
              e.description as episode_description
       FROM video_files vf
       LEFT JOIN movies m ON vf.movie_id = m.id
       LEFT JOIN episodes e ON vf.episode_id = e.id
       WHERE vf.id = ?`,
      [id]
    );

    if (videos.length === 0) {
      return res.status(404).json({
        error: 'Video not found',
        message: 'Video file not found'
      });
    }

    const video = videos[0];
    
    res.json({
      id: video.id,
      title: video.movie_title || video.episode_title,
      description: video.movie_description || video.episode_description,
      poster_url: video.poster_url,
      duration: video.duration,
      quality: video.quality,
      format: video.format,
      transcoding_status: video.transcoding_status,
      created_at: video.created_at
    });

  } catch (error) {
    console.error('Get video details error:', error);
    res.status(500).json({
      error: 'Failed to get video details',
      message: 'Unable to retrieve video information'
    });
  }
});

// List videos
router.get('/', validatePagination, async (req, res) => {
  try {
    const { page = 1, limit = 20, status = 'completed' } = req.query;
    const offset = (page - 1) * limit;
    
    const videos = await executeQuery(
      `SELECT vf.id, vf.original_filename, vf.quality, vf.transcoding_status,
              vf.created_at, m.title as movie_title, e.title as episode_title,
              m.poster_url
       FROM video_files vf
       LEFT JOIN movies m ON vf.movie_id = m.id
       LEFT JOIN episodes e ON vf.episode_id = e.id
       WHERE vf.transcoding_status = ?
       ORDER BY vf.created_at DESC
       LIMIT ? OFFSET ?`,
      [status, parseInt(limit), offset]
    );

    const countResult = await executeQuery(
      'SELECT COUNT(*) as total FROM video_files WHERE transcoding_status = ?',
      [status]
    );

    const total = countResult[0].total;
    const totalPages = Math.ceil(total / limit);

    res.json({
      videos: videos.map(video => ({
        id: video.id,
        title: video.movie_title || video.episode_title || video.original_filename,
        poster_url: video.poster_url,
        quality: video.quality,
        transcoding_status: video.transcoding_status,
        created_at: video.created_at
      })),
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        total_pages: totalPages
      }
    });

  } catch (error) {
    console.error('List videos error:', error);
    res.status(500).json({
      error: 'Failed to list videos',
      message: 'Unable to retrieve video list'
    });
  }
});

// Delete video
router.delete('/:id', validateId, async (req, res) => {
  try {
    const { id } = req.params;
    
    // Only allow admin or the uploader to delete
    if (req.user.role !== 'admin') {
      return res.status(403).json({
        error: 'Permission denied',
        message: 'Only administrators can delete videos'
      });
    }
    
    await executeQuery('DELETE FROM video_files WHERE id = ?', [id]);
    
    // TODO: Also delete the physical files and transcoded versions
    
    res.json({
      message: 'Video deleted successfully'
    });

  } catch (error) {
    console.error('Delete video error:', error);
    res.status(500).json({
      error: 'Delete failed',
      message: 'Failed to delete video'
    });
  }
});

module.exports = router;