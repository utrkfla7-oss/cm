const { body, param, query, validationResult } = require('express-validator');

// Validation middleware
function validateApiKey(req, res, next) {
  const apiKey = req.headers['x-api-key'];
  
  if (!apiKey || apiKey !== process.env.API_KEY_WP_INTEGRATION) {
    return res.status(401).json({
      error: 'Invalid API key',
      message: 'WordPress integration requires a valid API key'
    });
  }
  
  next();
}

// Handle validation errors
function handleValidationErrors(req, res, next) {
  const errors = validationResult(req);
  
  if (!errors.isEmpty()) {
    return res.status(400).json({
      error: 'Validation failed',
      details: errors.array()
    });
  }
  
  next();
}

// User registration validation
const validateUserRegistration = [
  body('username')
    .isLength({ min: 3, max: 50 })
    .withMessage('Username must be between 3 and 50 characters')
    .matches(/^[a-zA-Z0-9_]+$/)
    .withMessage('Username can only contain letters, numbers, and underscores'),
  
  body('email')
    .isEmail()
    .withMessage('Please provide a valid email address')
    .normalizeEmail(),
  
  body('password')
    .isLength({ min: 8 })
    .withMessage('Password must be at least 8 characters long')
    .matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)
    .withMessage('Password must contain at least one lowercase letter, one uppercase letter, and one number'),
  
  handleValidationErrors
];

// User login validation
const validateUserLogin = [
  body('email')
    .isEmail()
    .withMessage('Please provide a valid email address')
    .normalizeEmail(),
  
  body('password')
    .notEmpty()
    .withMessage('Password is required'),
  
  handleValidationErrors
];

// Video upload validation
const validateVideoUpload = [
  body('title')
    .isLength({ min: 1, max: 255 })
    .withMessage('Title is required and must be less than 255 characters'),
  
  body('description')
    .optional()
    .isLength({ max: 5000 })
    .withMessage('Description must be less than 5000 characters'),
  
  body('genre')
    .optional()
    .isLength({ max: 255 })
    .withMessage('Genre must be less than 255 characters'),
  
  handleValidationErrors
];

// Movie/TV show import validation
const validateImport = [
  body('type')
    .isIn(['movie', 'tv'])
    .withMessage('Type must be either "movie" or "tv"'),
  
  body('imdb_id')
    .optional()
    .matches(/^tt\d+$/)
    .withMessage('IMDb ID must be in format "ttXXXXXXX"'),
  
  body('tmdb_id')
    .optional()
    .isInt({ min: 1 })
    .withMessage('TMDb ID must be a positive integer'),
  
  handleValidationErrors
];

// Pagination validation
const validatePagination = [
  query('page')
    .optional()
    .isInt({ min: 1 })
    .withMessage('Page must be a positive integer'),
  
  query('limit')
    .optional()
    .isInt({ min: 1, max: 100 })
    .withMessage('Limit must be between 1 and 100'),
  
  handleValidationErrors
];

// ID parameter validation
const validateId = [
  param('id')
    .isInt({ min: 1 })
    .withMessage('ID must be a positive integer'),
  
  handleValidationErrors
];

// Search validation
const validateSearch = [
  query('q')
    .isLength({ min: 1, max: 100 })
    .withMessage('Search query must be between 1 and 100 characters'),
  
  handleValidationErrors
];

module.exports = {
  validateApiKey,
  validateUserRegistration,
  validateUserLogin,
  validateVideoUpload,
  validateImport,
  validatePagination,
  validateId,
  validateSearch,
  handleValidationErrors
};