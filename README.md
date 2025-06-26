# Logistics System Backend API

A comprehensive logistics management system built with Laravel 12, designed to handle multi-role operations including senders, receivers, agents, drivers, and administrators.

## 🚀 Features

### Core Functionality
- **Multi-Role User Management** (Sender, Receiver, Agent, Driver, Admin)
- **Shipment Management** with QR code tracking
- **Package Management** with sub-tracking IDs
- **Payment Processing** (Shipment fees, Product payments, Return fees)
- **Business Courier Rules** with flexible payment policies
- **Real-time Tracking** with status updates
- **Notification System** for all stakeholders
- **Driver Assignment** and route management
- **Agent Operations** for walk-in customers
- **Admin Dashboard** with analytics and reports

### Key Features
- **QR Code Generation** for packages
- **Multi-package Shipments** with master tracking IDs
- **Flexible Entry Points** (Web, Mobile, Agent stations)
- **Payment Flow Management** (Prepaid, Post-billed, Business rules)
- **Photo Capture** at pickup and delivery
- **Status Tracking** with detailed audit trail
- **Role-based Access Control**
- **API Authentication** with Sanctum tokens

## 🏗️ System Architecture

### User Roles
1. **Sender** - Creates shipments, tracks packages
2. **Receiver** - Receives packages, pays fees
3. **Agent** - Handles walk-ins, manages local operations
4. **Driver** - Picks up and delivers packages
5. **Admin** - System management and analytics

### Database Structure
- **Users** - Multi-role user management
- **Hubs** - Regional and local distribution centers
- **Agent Stations** - Local pickup/drop-off points
- **Routes** - Delivery route optimization
- **Shipments** - Core shipment data
- **Packages** - Individual package tracking
- **Payments** - Payment transaction management
- **Business Courier Rules** - Business logic policies
- **Vehicles** - Driver vehicle management
- **Driver Assignments** - Task assignment system
- **Tracking Events** - Detailed audit trail
- **Notifications** - System notifications

## 📋 API Endpoints

### Authentication
```
POST /api/auth/register     - Register new user
POST /api/auth/login        - User login
POST /api/auth/logout       - User logout
GET  /api/auth/profile      - Get user profile
PUT  /api/auth/profile      - Update profile
POST /api/auth/change-password - Change password
POST /api/auth/refresh      - Refresh token
```

### Shipments
```
GET    /api/shipments                    - List shipments
POST   /api/shipments                    - Create shipment
GET    /api/shipments/{trackingNumber}   - Get shipment details
POST   /api/shipments/{id}/status        - Update status
DELETE /api/shipments/{id}               - Delete shipment
```

### Packages
```
GET    /api/shipments/{id}/packages      - List packages
POST   /api/shipments/{id}/packages      - Add package
PUT    /api/packages/{id}                - Update package
DELETE /api/packages/{id}                - Delete package
```

### Payments
```
GET    /api/payments                     - List payments
POST   /api/payments                     - Create payment
GET    /api/payments/{id}                - Get payment details
POST   /api/payments/{id}/refund         - Refund payment
```

### Tracking
```
GET    /api/tracking/{trackingNumber}    - Track shipment (authenticated)
GET    /api/track/{trackingNumber}       - Public tracking
GET    /api/shipments/{id}/tracking      - Get tracking history
```

### Notifications
```
GET    /api/notifications                - List notifications
PUT    /api/notifications/{id}/read      - Mark as read
PUT    /api/notifications/read-all       - Mark all as read
```

### Agent Operations
```
GET    /api/agent/shipments              - Get agent shipments
POST   /api/agent/shipments              - Create walk-in shipment
PUT    /api/agent/shipments/{id}/receive - Receive shipment
GET    /api/agent/manifest               - Get daily manifest
```

### Driver Operations
```
GET    /api/driver/assignments           - Get assignments
PUT    /api/driver/assignments/{id}/accept   - Accept assignment
PUT    /api/driver/assignments/{id}/complete - Complete assignment
POST   /api/driver/shipments/{id}/pickup     - Pickup shipment
POST   /api/driver/shipments/{id}/delivery   - Deliver shipment
```

### Admin Operations
```
GET    /api/admin/dashboard              - Admin dashboard
GET    /api/admin/analytics              - System analytics
GET    /api/admin/reports                - Generate reports
POST   /api/admin/bulk-operations        - Bulk operations
```

## 🛠️ Installation

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer
- Laravel 12

### Setup
1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd areno-express-backend
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database configuration**
   ```bash
   # Update .env with your database credentials
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=logistics_system
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Start the server**
   ```bash
   php artisan serve
   ```

## 🔐 Authentication

The API uses Laravel Sanctum for authentication. Include the Bearer token in the Authorization header:

```
Authorization: Bearer {your_token}
```

## 📊 Business Logic

### Shipment Flow
1. **Booking** - Sender creates shipment
2. **Pickup** - Driver picks up packages
3. **Agent Station** - Packages received at local station
4. **Hub Transit** - Packages moved to regional hub
5. **Destination** - Packages dispatched to destination
6. **Delivery** - Driver delivers to receiver
7. **Completion** - Payment processing and confirmation

### Payment Rules
- **Personal Courier**: Standard payment flow
- **Business Courier**: 
  - Buyer prepays shipment fee
  - Product payment upon delivery
  - Return fee policies based on seller registration

### QR Code System
- **Master Tracking ID**: Main shipment identifier
- **Sub Tracking IDs**: Individual package identifiers (e.g., TRK202412345-A, B, C)
- **QR Codes**: Generated for each package for easy scanning

## 🧪 Testing

```bash
# Run tests
php artisan test

# Run with coverage
php artisan test --coverage
```

## 📈 Monitoring

The system includes comprehensive logging and monitoring:
- **Tracking Events**: Detailed audit trail
- **Status Updates**: Real-time status changes
- **Payment Tracking**: Complete payment history
- **User Activity**: Role-based activity monitoring

## 🔧 Configuration

Key configuration files:
- `config/auth.php` - Authentication settings
- `config/sanctum.php` - API token configuration
- `config/logistics.php` - System-specific settings

## 📝 API Documentation

For detailed API documentation, refer to the individual controller files or use tools like:
- Postman Collection
- Swagger/OpenAPI documentation
- Laravel Telescope (for debugging)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation

---

**Built with ❤️ using Laravel 12**
