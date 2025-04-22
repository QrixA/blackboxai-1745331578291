# SakuraCloudID Billing System

A comprehensive billing and service management system for SakuraCloudID, built with native PHP and MySQL/SQLite.

## Features

### User Features

1. **Authentication System**
   - User registration and login
   - Role-based access control (User/Admin)
   - Profile management with photo upload
   - Password reset functionality

2. **Balance System**
   - Balance top-up via Duitku Payment Gateway
   - Balance history tracking
   - Multiple earning methods (AFK, Ads, Affiliate)

3. **Service Management**
   - View active services
   - Order new services
   - Multiple billing cycles (hourly/monthly/yearly)
   - Service specifications display

4. **Shopping Cart**
   - Add/remove services
   - Quantity adjustment
   - Billing cycle selection
   - Promo code support
   - Affiliate code integration
   - Tax calculation
   - Order notes

5. **Reward System**
   - AFK Rewards
     * Configurable duration
     * Anti-cheat measures
     * Real-time progress tracking
   - Ad Watch Rewards
     * Configurable duration
     * Daily limits
     * Progress tracking

6. **Affiliate System**
   - Apply via WhatsApp
   - Unique affiliate code
   - Commission tracking
   - Real-time statistics
   - Referral history

### Admin Features

1. **User Management**
   - View all users
   - Edit user details
   - Change user roles
   - Reset passwords
   - Deactivate accounts
   - Delete users

2. **Service Management**
   - Add/edit categories
   - Manage products
   - Set pricing tiers
   - Configure specifications

3. **Order Management**
   - View pending orders
   - Process payments
   - Order confirmation
   - Order history

4. **System Settings**
   - Tax rate configuration
   - API key management
   - Reward amounts
   - Commission rates
   - System maintenance mode

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/scid-billing.git
   cd scid-billing
   ```

2. Configure your database:
   - Edit `config/db_config.php` with your database credentials
   - For MySQL:
     ```php
     return [
         'host'     => 'localhost',
         'dbname'   => 'scid_billing',
         'username' => 'your_username',
         'password' => 'your_password'
     ];
     ```
   - For SQLite:
     ```php
     return [
         'type' => 'sqlite',
         'path' => __DIR__ . '/../database/scid_billing.sqlite'
     ];
     ```

3. Initialize the database:
   - For MySQL: `mysql -u username -p < database/schema.mysql.sql`
   - For SQLite: `sqlite3 database/scid_billing.sqlite < database/schema.sqlite.sql`

4. Configure payment gateway:
   - Get your Duitku API credentials
   - Update settings in admin panel
   - Set callback URL in Duitku dashboard

5. Set up the web server:
   - Point your web server to the project directory
   - Ensure `uploads` directory is writable
   - Configure PHP settings (file uploads, max post size)

## Default Credentials

```
Admin Account:
Username: admin
Password: admin123
```

## Directory Structure

```
scid-billing/
├── config/
│   ├── config.php
│   └── db_config.php
├── controllers/
│   ├── AdminController.php
│   ├── AffiliateController.php
│   ├── AuthController.php
│   ├── CartController.php
│   ├── DashboardController.php
│   ├── PaymentController.php
│   ├── RewardController.php
│   ├── ServiceController.php
│   └── UserController.php
├── database/
│   ├── schema.mysql.sql
│   └── schema.sqlite.sql
├── views/
│   ├── admin/
│   ├── affiliate/
│   ├── auth/
│   ├── cart/
│   ├── dashboard/
│   ├── layouts/
│   ├── payment/
│   ├── rewards/
│   ├── services/
│   └── user/
└── public/
    └── uploads/
```

## Security Features

- CSRF protection
- Password hashing
- Input sanitization
- SQL injection prevention
- XSS protection
- Session security
- Role-based access control

## Payment Integration

The system integrates with Duitku Payment Gateway for:
- Balance top-up
- Service purchases
- Real-time payment notifications
- Payment status tracking

## Reward System Details

1. AFK Rewards:
   - Configurable duration (default: 5 minutes)
   - Anti-cheat with page visibility detection
   - Cooldown period between rewards
   - Real-time progress tracking

2. Ad Rewards:
   - Configurable duration (default: 30 seconds)
   - Daily limit system
   - Anti-cheat measures
   - Instant reward crediting

## Affiliate System Details

1. Commission Structure:
   - Configurable commission rates
   - Real-time tracking
   - Automatic commission calculation
   - Instant crediting to balance

2. Referral Tracking:
   - Unique affiliate codes
   - Order tracking
   - Commission history
   - Performance statistics

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, contact:
- WhatsApp: +6289628127242
- Email: support@sakuracloud.id
