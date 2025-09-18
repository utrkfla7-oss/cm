const jwt = require('jsonwebtoken');
const { executeQuery } = require('../services/database');

// Authenticate JWT token
function authenticate(req, res, next) {
  const authHeader = req.headers.authorization;
  
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({
      error: 'Authentication required',
      message: 'Please provide a valid JWT token'
    });
  }

  const token = authHeader.substring(7);

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    req.user = decoded;
    next();
  } catch (error) {
    return res.status(401).json({
      error: 'Invalid token',
      message: 'JWT token is invalid or expired'
    });
  }
}

// Authorize specific roles
function authorize(roles = []) {
  return async (req, res, next) => {
    try {
      // Get user details from database
      const users = await executeQuery(
        'SELECT role, is_active FROM users WHERE id = ?',
        [req.user.id]
      );

      if (users.length === 0) {
        return res.status(404).json({
          error: 'User not found',
          message: 'User account does not exist'
        });
      }

      const user = users[0];

      if (!user.is_active) {
        return res.status(403).json({
          error: 'Account deactivated',
          message: 'Your account has been deactivated'
        });
      }

      if (roles.length > 0 && !roles.includes(user.role)) {
        return res.status(403).json({
          error: 'Insufficient permissions',
          message: `This action requires one of the following roles: ${roles.join(', ')}`
        });
      }

      req.user.role = user.role;
      next();
    } catch (error) {
      console.error('Authorization error:', error);
      res.status(500).json({
        error: 'Authorization failed',
        message: 'Internal server error during authorization'
      });
    }
  };
}

// Check subscription access
function checkSubscription(requiredLevel = 'free') {
  const subscriptionLevels = {
    'free': 0,
    'basic': 1,
    'premium': 2
  };

  return async (req, res, next) => {
    try {
      const users = await executeQuery(
        'SELECT subscription_type, subscription_expires_at FROM users WHERE id = ?',
        [req.user.id]
      );

      if (users.length === 0) {
        return res.status(404).json({
          error: 'User not found'
        });
      }

      const user = users[0];
      const userLevel = subscriptionLevels[user.subscription_type] || 0;
      const requiredLevelNum = subscriptionLevels[requiredLevel] || 0;

      // Check if subscription is expired
      if (user.subscription_expires_at && new Date() > new Date(user.subscription_expires_at)) {
        return res.status(403).json({
          error: 'Subscription expired',
          message: 'Please renew your subscription to access this content'
        });
      }

      if (userLevel < requiredLevelNum) {
        return res.status(403).json({
          error: 'Subscription required',
          message: `This content requires a ${requiredLevel} subscription or higher`
        });
      }

      req.user.subscription = {
        type: user.subscription_type,
        expires_at: user.subscription_expires_at
      };
      
      next();
    } catch (error) {
      console.error('Subscription check error:', error);
      res.status(500).json({
        error: 'Subscription check failed',
        message: 'Internal server error during subscription verification'
      });
    }
  };
}

module.exports = {
  authenticate,
  authorize,
  checkSubscription
};