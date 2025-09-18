const ffmpeg = require('fluent-ffmpeg');
const path = require('path');
const fs = require('fs').promises;
const { executeQuery } = require('./database');

class TranscodingService {
  constructor() {
    this.ffmpegPath = process.env.FFMPEG_PATH || 'ffmpeg';
    this.outputDir = process.env.MEDIA_DIR || './media';
    this.qualities = process.env.VIDEO_QUALITIES ? 
      process.env.VIDEO_QUALITIES.split(',') : 
      ['240p', '360p', '480p', '720p', '1080p'];
    
    // Quality settings
    this.qualitySettings = {
      '240p': { width: 426, height: 240, bitrate: '400k' },
      '360p': { width: 640, height: 360, bitrate: '800k' },
      '480p': { width: 854, height: 480, bitrate: '1200k' },
      '720p': { width: 1280, height: 720, bitrate: '2500k' },
      '1080p': { width: 1920, height: 1080, bitrate: '5000k' }
    };

    // Set FFmpeg path
    if (this.ffmpegPath !== 'ffmpeg') {
      ffmpeg.setFfmpegPath(this.ffmpegPath);
    }
  }

  // Start transcoding service
  async startTranscodingService() {
    try {
      // Ensure output directory exists
      await fs.mkdir(this.outputDir, { recursive: true });
      
      // Start processing queue
      this.processTranscodingQueue();
      
      console.log('Transcoding service started successfully');
    } catch (error) {
      console.error('Failed to start transcoding service:', error);
      throw error;
    }
  }

  // Process transcoding queue
  async processTranscodingQueue() {
    try {
      // Get pending transcoding jobs
      const pendingJobs = await executeQuery(
        'SELECT * FROM video_files WHERE transcoding_status = "pending" ORDER BY created_at ASC LIMIT 1'
      );

      for (const job of pendingJobs) {
        await this.transcodeVideo(job);
      }

      // Schedule next check
      setTimeout(() => this.processTranscodingQueue(), 10000); // Check every 10 seconds
    } catch (error) {
      console.error('Error processing transcoding queue:', error);
      // Retry after delay
      setTimeout(() => this.processTranscodingQueue(), 30000);
    }
  }

  // Transcode video to multiple qualities and generate HLS/DASH
  async transcodeVideo(videoFile) {
    try {
      console.log(`Starting transcoding for video: ${videoFile.original_filename}`);
      
      // Update status to processing
      await executeQuery(
        'UPDATE video_files SET transcoding_status = "processing" WHERE id = ?',
        [videoFile.id]
      );

      const inputPath = videoFile.file_path;
      const outputBaseDir = path.join(this.outputDir, videoFile.id.toString());
      
      // Create output directory
      await fs.mkdir(outputBaseDir, { recursive: true });

      // Generate video info
      const videoInfo = await this.getVideoInfo(inputPath);
      
      // Transcode to different qualities
      const transcodedFiles = await this.transcodeQualities(inputPath, outputBaseDir, videoInfo);
      
      // Generate HLS playlist
      const hlsPath = await this.generateHLS(outputBaseDir, transcodedFiles);
      
      // Generate DASH manifest (optional)
      const dashPath = await this.generateDASH(outputBaseDir, transcodedFiles);

      // Update database with transcoding results
      await executeQuery(
        `UPDATE video_files SET 
          transcoding_status = "completed",
          duration = ?,
          hls_path = ?,
          dash_path = ?
        WHERE id = ?`,
        [Math.round(videoInfo.duration), hlsPath, dashPath, videoFile.id]
      );

      console.log(`Transcoding completed for video: ${videoFile.original_filename}`);
      
    } catch (error) {
      console.error(`Transcoding failed for video ${videoFile.id}:`, error);
      
      // Update status to failed
      await executeQuery(
        'UPDATE video_files SET transcoding_status = "failed" WHERE id = ?',
        [videoFile.id]
      );
    }
  }

  // Get video information
  async getVideoInfo(inputPath) {
    return new Promise((resolve, reject) => {
      ffmpeg.ffprobe(inputPath, (err, metadata) => {
        if (err) {
          reject(err);
          return;
        }

        const videoStream = metadata.streams.find(stream => stream.codec_type === 'video');
        
        resolve({
          duration: metadata.format.duration,
          width: videoStream.width,
          height: videoStream.height,
          bitrate: metadata.format.bit_rate
        });
      });
    });
  }

  // Transcode to multiple qualities
  async transcodeQualities(inputPath, outputDir, videoInfo) {
    const transcodedFiles = [];

    for (const quality of this.qualities) {
      const settings = this.qualitySettings[quality];
      
      // Skip if source resolution is lower than target
      if (videoInfo.height < settings.height) {
        continue;
      }

      const outputPath = path.join(outputDir, `${quality}.mp4`);
      
      try {
        await this.transcodeToQuality(inputPath, outputPath, settings);
        transcodedFiles.push({
          quality,
          path: outputPath,
          resolution: `${settings.width}x${settings.height}`
        });
      } catch (error) {
        console.error(`Failed to transcode to ${quality}:`, error);
      }
    }

    return transcodedFiles;
  }

  // Transcode to specific quality
  async transcodeToQuality(inputPath, outputPath, settings) {
    return new Promise((resolve, reject) => {
      ffmpeg(inputPath)
        .videoCodec('libx264')
        .audioCodec('aac')
        .size(`${settings.width}x${settings.height}`)
        .videoBitrate(settings.bitrate)
        .audioBitrate('128k')
        .outputOptions([
          '-preset fast',
          '-crf 23',
          '-movflags +faststart'
        ])
        .output(outputPath)
        .on('end', () => {
          console.log(`Transcoding to ${settings.width}x${settings.height} completed`);
          resolve();
        })
        .on('error', (err) => {
          console.error(`Transcoding error:`, err);
          reject(err);
        })
        .run();
    });
  }

  // Generate HLS playlist
  async generateHLS(outputDir, transcodedFiles) {
    const hlsDir = path.join(outputDir, 'hls');
    await fs.mkdir(hlsDir, { recursive: true });

    const masterPlaylistPath = path.join(hlsDir, 'master.m3u8');
    let masterPlaylist = '#EXTM3U\n#EXT-X-VERSION:3\n\n';

    for (const file of transcodedFiles) {
      const segmentDir = path.join(hlsDir, file.quality);
      await fs.mkdir(segmentDir, { recursive: true });

      const playlistPath = path.join(segmentDir, 'playlist.m3u8');
      
      await this.generateHLSForQuality(file.path, segmentDir, playlistPath);
      
      // Add to master playlist
      const settings = this.qualitySettings[file.quality];
      const bandwidth = parseInt(settings.bitrate.replace('k', '')) * 1000;
      
      masterPlaylist += `#EXT-X-STREAM-INF:BANDWIDTH=${bandwidth},RESOLUTION=${file.resolution}\n`;
      masterPlaylist += `${file.quality}/playlist.m3u8\n\n`;
    }

    await fs.writeFile(masterPlaylistPath, masterPlaylist);
    
    return path.relative(this.outputDir, masterPlaylistPath);
  }

  // Generate HLS for specific quality
  async generateHLSForQuality(inputPath, outputDir, playlistPath) {
    return new Promise((resolve, reject) => {
      const segmentPattern = path.join(outputDir, 'segment%03d.ts');
      
      ffmpeg(inputPath)
        .outputOptions([
          '-codec copy',
          '-map 0',
          '-f hls',
          '-hls_time 10',
          '-hls_list_size 0',
          '-hls_segment_filename ' + segmentPattern
        ])
        .output(playlistPath)
        .on('end', () => {
          console.log(`HLS generation completed for ${path.basename(inputPath)}`);
          resolve();
        })
        .on('error', (err) => {
          console.error(`HLS generation error:`, err);
          reject(err);
        })
        .run();
    });
  }

  // Generate DASH manifest (simplified implementation)
  async generateDASH(outputDir, transcodedFiles) {
    // For now, return null - DASH implementation would be more complex
    // In a production environment, you might use MP4Box or similar tools
    return null;
  }

  // Get streaming URL for video
  getStreamingUrl(videoFileId, quality = 'auto') {
    const baseUrl = process.env.STREAMING_BASE_URL || 'http://localhost:3001/media';
    
    if (quality === 'auto') {
      return `${baseUrl}/${videoFileId}/hls/master.m3u8`;
    } else {
      return `${baseUrl}/${videoFileId}/hls/${quality}/playlist.m3u8`;
    }
  }

  // Clean up old transcoded files (for storage management)
  async cleanupOldFiles(daysOld = 30) {
    try {
      const cutoffDate = new Date();
      cutoffDate.setDate(cutoffDate.getDate() - daysOld);

      const oldFiles = await executeQuery(
        'SELECT id FROM video_files WHERE created_at < ? AND transcoding_status = "completed"',
        [cutoffDate.toISOString().slice(0, 19).replace('T', ' ')]
      );

      for (const file of oldFiles) {
        const fileDir = path.join(this.outputDir, file.id.toString());
        try {
          await fs.rm(fileDir, { recursive: true, force: true });
          console.log(`Cleaned up transcoded files for video ID: ${file.id}`);
        } catch (error) {
          console.error(`Error cleaning up files for video ID ${file.id}:`, error);
        }
      }

      return oldFiles.length;
    } catch (error) {
      console.error('Error during cleanup:', error);
      throw error;
    }
  }
}

module.exports = {
  TranscodingService,
  startTranscodingService: () => {
    const service = new TranscodingService();
    return service.startTranscodingService();
  }
};