const winston = require('winston');

const logger = winston.createLogger({
  level: 'error',
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.json()
  ),
  transports: [
    new winston.transports.Console(),
    new winston.transports.File({ filename: 'logs/error.log' })
  ]
});

function errorHandler(err, req, res, next) {
  // Log the error
  logger.error({
    error: err.message,
    stack: err.stack,
    url: req.url,
    method: req.method,
    ip: req.ip,
    userAgent: req.get('User-Agent'),
    userId: req.user?.id
  });

  // Default error
  let status = 500;
  let message = 'Internal Server Error';
  let details = null;

  // Handle specific error types
  if (err.name === 'ValidationError') {
    status = 400;
    message = 'Validation Error';
    details = err.details;
  } else if (err.name === 'UnauthorizedError') {
    status = 401;
    message = 'Unauthorized';
  } else if (err.code === 'LIMIT_FILE_SIZE') {
    status = 413;
    message = 'File too large';
    details = 'The uploaded file exceeds the maximum allowed size';
  } else if (err.code === 'ENOENT') {
    status = 404;
    message = 'File not found';
  } else if (err.code === 'ER_DUP_ENTRY') {
    status = 409;
    message = 'Duplicate entry';
    details = 'A record with this information already exists';
  }

  // Don't leak error details in production
  const response = {
    error: message,
    timestamp: new Date().toISOString(),
    path: req.url
  };

  if (process.env.NODE_ENV === 'development') {
    response.details = details || err.message;
    response.stack = err.stack;
  } else if (details) {
    response.details = details;
  }

  res.status(status).json(response);
}

module.exports = errorHandler;