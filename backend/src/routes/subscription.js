const express = require('express');
const { executeQuery } = require('../services/database');
const { validatePagination } = require('../middleware/validation');

const router = express.Router();

// Get subscription plans
router.get('/plans', async (req, res) => {
  try {
    // In a real implementation, these would come from a database
    const plans = [
      {
        id: 'free',
        name: 'Free',
        price: 0,
        duration: 'forever',
        features: [
          'Limited content access',
          'Watch on 1 device',
          'Standard quality (480p)',
          'Ads included'
        ],
        limitations: {
          max_devices: 1,
          max_quality: '480p',
          ads: true
        }
      },
      {
        id: 'basic',
        name: 'Basic',
        price: 9.99,
        duration: 'monthly',
        features: [
          'Access to basic content library',
          'Watch on 2 devices',
          'HD quality (720p)',
          'Limited ads'
        ],
        limitations: {
          max_devices: 2,
          max_quality: '720p',
          ads: false
        }
      },
      {
        id: 'premium',
        name: 'Premium',
        price: 19.99,
        duration: 'monthly',
        features: [
          'Access to full content library',
          'Watch on unlimited devices',
          'Ultra HD quality (1080p+)',
          'No ads',
          'Early access to new releases',
          'Download for offline viewing'
        ],
        limitations: {
          max_devices: 'unlimited',
          max_quality: '1080p',
          ads: false
        }
      }
    ];

    res.json({
      plans
    });

  } catch (error) {
    console.error('Get subscription plans error:', error);
    res.status(500).json({
      error: 'Failed to get subscription plans',
      message: 'Unable to retrieve subscription plans'
    });
  }
});

// Get user's current subscription
router.get('/current', async (req, res) => {
  try {
    const userId = req.user.id;

    const users = await executeQuery(
      'SELECT subscription_type, subscription_expires_at FROM users WHERE id = ?',
      [userId]
    );

    if (users.length === 0) {
      return res.status(404).json({
        error: 'User not found'
      });
    }

    const user = users[0];

    // Check if subscription is expired
    const now = new Date();
    const expiresAt = user.subscription_expires_at ? new Date(user.subscription_expires_at) : null;
    const isExpired = expiresAt && now > expiresAt;

    res.json({
      subscription: {
        type: user.subscription_type,
        expires_at: user.subscription_expires_at,
        is_expired: isExpired,
        is_active: !isExpired && user.subscription_type !== 'free',
        days_remaining: expiresAt ? Math.max(0, Math.ceil((expiresAt - now) / (1000 * 60 * 60 * 24))) : null
      }
    });

  } catch (error) {
    console.error('Get current subscription error:', error);
    res.status(500).json({
      error: 'Failed to get subscription',
      message: 'Unable to retrieve current subscription'
    });
  }
});

// Subscribe to a plan (simplified payment integration)
router.post('/subscribe', async (req, res) => {
  try {
    const { plan_id, payment_method } = req.body;
    const userId = req.user.id;

    // Validate plan
    const validPlans = ['free', 'basic', 'premium'];
    if (!validPlans.includes(plan_id)) {
      return res.status(400).json({
        error: 'Invalid plan',
        message: 'Please select a valid subscription plan'
      });
    }

    // Calculate expiration date
    let expiresAt = null;
    if (plan_id !== 'free') {
      expiresAt = new Date();
      expiresAt.setMonth(expiresAt.getMonth() + 1); // Add 1 month
    }

    // In a real implementation, you would:
    // 1. Validate payment method
    // 2. Process payment through payment gateway (Stripe, PayPal, etc.)
    // 3. Only update subscription if payment is successful

    // For demo purposes, we'll just update the subscription
    await executeQuery(
      'UPDATE users SET subscription_type = ?, subscription_expires_at = ? WHERE id = ?',
      [plan_id, expiresAt, userId]
    );

    // Log subscription change
    await executeQuery(
      `INSERT INTO subscription_history (user_id, plan_type, amount, payment_method, status, created_at)
       VALUES (?, ?, ?, ?, ?, NOW())`,
      [userId, plan_id, plan_id === 'free' ? 0 : (plan_id === 'basic' ? 9.99 : 19.99), payment_method || 'demo', 'completed']
    );

    res.json({
      message: 'Subscription updated successfully',
      subscription: {
        type: plan_id,
        expires_at: expiresAt,
        is_active: plan_id !== 'free'
      }
    });

  } catch (error) {
    console.error('Subscribe error:', error);
    res.status(500).json({
      error: 'Subscription failed',
      message: 'Unable to process subscription'
    });
  }
});

// Cancel subscription
router.post('/cancel', async (req, res) => {
  try {
    const userId = req.user.id;

    // Set subscription to expire at the end of current period
    // In a real implementation, you would cancel the recurring payment
    
    const users = await executeQuery(
      'SELECT subscription_type, subscription_expires_at FROM users WHERE id = ?',
      [userId]
    );

    if (users.length === 0) {
      return res.status(404).json({
        error: 'User not found'
      });
    }

    const user = users[0];

    if (user.subscription_type === 'free') {
      return res.status(400).json({
        error: 'No active subscription',
        message: 'You do not have an active subscription to cancel'
      });
    }

    // Mark subscription for cancellation (will downgrade to free at expiration)
    await executeQuery(
      'UPDATE users SET subscription_auto_renew = FALSE WHERE id = ?',
      [userId]
    );

    res.json({
      message: 'Subscription cancelled successfully',
      note: `Your ${user.subscription_type} subscription will remain active until ${user.subscription_expires_at}`,
      expires_at: user.subscription_expires_at
    });

  } catch (error) {
    console.error('Cancel subscription error:', error);
    res.status(500).json({
      error: 'Cancellation failed',
      message: 'Unable to cancel subscription'
    });
  }
});

// Get subscription history
router.get('/history', validatePagination, async (req, res) => {
  try {
    const { page = 1, limit = 20 } = req.query;
    const offset = (page - 1) * limit;
    const userId = req.user.id;

    // First, ensure the subscription_history table exists
    await executeQuery(`
      CREATE TABLE IF NOT EXISTS subscription_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        plan_type VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50),
        status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
      )
    `);

    const history = await executeQuery(
      `SELECT id, plan_type, amount, payment_method, status, created_at
       FROM subscription_history
       WHERE user_id = ?
       ORDER BY created_at DESC
       LIMIT ? OFFSET ?`,
      [userId, parseInt(limit), offset]
    );

    const countResult = await executeQuery(
      'SELECT COUNT(*) as total FROM subscription_history WHERE user_id = ?',
      [userId]
    );

    const total = countResult[0].total;

    res.json({
      history,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        total_pages: Math.ceil(total / limit)
      }
    });

  } catch (error) {
    console.error('Get subscription history error:', error);
    res.status(500).json({
      error: 'Failed to get subscription history',
      message: 'Unable to retrieve subscription history'
    });
  }
});

// Webhook for payment provider (simplified)
router.post('/webhook', async (req, res) => {
  try {
    // In a real implementation, you would:
    // 1. Verify webhook signature
    // 2. Handle different webhook events (payment succeeded, failed, etc.)
    // 3. Update user subscriptions accordingly

    const { event_type, user_id, plan_id, amount, status } = req.body;

    if (event_type === 'payment.succeeded') {
      // Update user subscription
      const expiresAt = new Date();
      expiresAt.setMonth(expiresAt.getMonth() + 1);

      await executeQuery(
        'UPDATE users SET subscription_type = ?, subscription_expires_at = ? WHERE id = ?',
        [plan_id, expiresAt, user_id]
      );

      // Log payment
      await executeQuery(
        `INSERT INTO subscription_history (user_id, plan_type, amount, status, created_at)
         VALUES (?, ?, ?, ?, NOW())`,
        [user_id, plan_id, amount, 'completed']
      );
    }

    res.json({ received: true });

  } catch (error) {
    console.error('Webhook error:', error);
    res.status(500).json({
      error: 'Webhook processing failed'
    });
  }
});

module.exports = router;