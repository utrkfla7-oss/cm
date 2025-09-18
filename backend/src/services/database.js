const mysql = require('mysql2/promise');
const winston = require('winston');

const logger = winston.createLogger({
  level: 'info',
  format: winston.format.simple(),
  transports: [new winston.transports.Console()]
});

let pool = null;

// Database configuration
const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  port: process.env.DB_PORT || 3306,
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'netflix_streaming',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  acquireTimeout: 60000,
  timeout: 60000
};

// Initialize database connection pool
async function initializeDatabase() {
  try {
    pool = mysql.createPool(dbConfig);
    
    // Test connection
    const connection = await pool.getConnection();
    await connection.ping();
    connection.release();
    
    logger.info('Database connection established successfully');
    
    // Create tables if they don't exist
    await createTables();
    
    return pool;
  } catch (error) {
    logger.error('Database connection failed:', error);
    throw error;
  }
}

// Create necessary tables
async function createTables() {
  const tables = [
    // Users table
    `CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(50) UNIQUE NOT NULL,
      email VARCHAR(100) UNIQUE NOT NULL,
      password_hash VARCHAR(255) NOT NULL,
      role ENUM('user', 'admin', 'moderator') DEFAULT 'user',
      subscription_type ENUM('free', 'basic', 'premium') DEFAULT 'free',
      subscription_expires_at DATETIME NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      is_active BOOLEAN DEFAULT TRUE,
      profile_image VARCHAR(255) NULL,
      preferences JSON NULL
    )`,
    
    // Movies table
    `CREATE TABLE IF NOT EXISTS movies (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      description TEXT,
      imdb_id VARCHAR(20) UNIQUE,
      tmdb_id INT,
      release_date DATE,
      duration INT, -- in minutes
      genre VARCHAR(255),
      director VARCHAR(255),
      cast TEXT,
      rating DECIMAL(3,1),
      poster_url VARCHAR(500),
      backdrop_url VARCHAR(500),
      trailer_url VARCHAR(500),
      status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      created_by INT,
      FOREIGN KEY (created_by) REFERENCES users(id)
    )`,
    
    // TV Shows table
    `CREATE TABLE IF NOT EXISTS tv_shows (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      description TEXT,
      imdb_id VARCHAR(20) UNIQUE,
      tmdb_id INT,
      first_air_date DATE,
      last_air_date DATE,
      genre VARCHAR(255),
      creator VARCHAR(255),
      cast TEXT,
      rating DECIMAL(3,1),
      poster_url VARCHAR(500),
      backdrop_url VARCHAR(500),
      status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      created_by INT,
      FOREIGN KEY (created_by) REFERENCES users(id)
    )`,
    
    // Episodes table
    `CREATE TABLE IF NOT EXISTS episodes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      tv_show_id INT NOT NULL,
      title VARCHAR(255) NOT NULL,
      description TEXT,
      season_number INT NOT NULL,
      episode_number INT NOT NULL,
      air_date DATE,
      duration INT, -- in minutes
      rating DECIMAL(3,1),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (tv_show_id) REFERENCES tv_shows(id) ON DELETE CASCADE,
      UNIQUE KEY unique_episode (tv_show_id, season_number, episode_number)
    )`,
    
    // Video files table
    `CREATE TABLE IF NOT EXISTS video_files (
      id INT AUTO_INCREMENT PRIMARY KEY,
      movie_id INT NULL,
      episode_id INT NULL,
      original_filename VARCHAR(255) NOT NULL,
      file_path VARCHAR(500) NOT NULL,
      file_size BIGINT NOT NULL,
      mime_type VARCHAR(100),
      duration INT, -- in seconds
      resolution VARCHAR(20),
      quality VARCHAR(10),
      format VARCHAR(20),
      hls_path VARCHAR(500) NULL,
      dash_path VARCHAR(500) NULL,
      transcoding_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
      FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
    )`,
    
    // Subtitles table
    `CREATE TABLE IF NOT EXISTS subtitles (
      id INT AUTO_INCREMENT PRIMARY KEY,
      movie_id INT NULL,
      episode_id INT NULL,
      language_code VARCHAR(5) NOT NULL,
      language_name VARCHAR(50) NOT NULL,
      file_path VARCHAR(500) NOT NULL,
      format ENUM('srt', 'vtt', 'ass') DEFAULT 'vtt',
      is_auto_generated BOOLEAN DEFAULT FALSE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
      FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
    )`,
    
    // User watch history
    `CREATE TABLE IF NOT EXISTS watch_history (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      movie_id INT NULL,
      episode_id INT NULL,
      watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      watch_time INT DEFAULT 0, -- in seconds
      total_duration INT DEFAULT 0, -- in seconds
      completed BOOLEAN DEFAULT FALSE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
      FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE
    )`,
    
    // User favorites
    `CREATE TABLE IF NOT EXISTS user_favorites (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      movie_id INT NULL,
      tv_show_id INT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
      FOREIGN KEY (tv_show_id) REFERENCES tv_shows(id) ON DELETE CASCADE,
      UNIQUE KEY unique_movie_favorite (user_id, movie_id),
      UNIQUE KEY unique_show_favorite (user_id, tv_show_id)
    )`,
    
    // Import jobs (for batch imports)
    `CREATE TABLE IF NOT EXISTS import_jobs (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      job_type ENUM('imdb_movie', 'imdb_show', 'tmdb_movie', 'tmdb_show') NOT NULL,
      status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
      total_items INT DEFAULT 0,
      processed_items INT DEFAULT 0,
      failed_items INT DEFAULT 0,
      parameters JSON NULL,
      error_log TEXT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id)
    )`
  ];

  for (const table of tables) {
    try {
      await pool.execute(table);
      logger.info(`Table created/verified successfully`);
    } catch (error) {
      logger.error(`Error creating table:`, error);
      throw error;
    }
  }
}

// Get database pool
function getPool() {
  if (!pool) {
    throw new Error('Database pool not initialized. Call initializeDatabase() first.');
  }
  return pool;
}

// Execute query with error handling
async function executeQuery(query, params = []) {
  try {
    const [rows] = await pool.execute(query, params);
    return rows;
  } catch (error) {
    logger.error('Database query error:', { query, params, error: error.message });
    throw error;
  }
}

// Transaction helper
async function executeTransaction(callback) {
  const connection = await pool.getConnection();
  await connection.beginTransaction();
  
  try {
    const result = await callback(connection);
    await connection.commit();
    connection.release();
    return result;
  } catch (error) {
    await connection.rollback();
    connection.release();
    throw error;
  }
}

module.exports = {
  initializeDatabase,
  getPool,
  executeQuery,
  executeTransaction
};