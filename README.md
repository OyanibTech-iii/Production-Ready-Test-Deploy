# GROWFICO - Agricultural & Sustainability Platform

<div align="center">

**Cultivating Green Futures**

*Making sustainable agriculture accessible through a modern web platform*

[![Symfony](https://img.shields.io/badge/Symfony-7.3-000000?style=for-the-badge&logo=symfony)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![Twig](https://img.shields.io/badge/Twig-3.x-000000?style=for-the-badge&logo=twig)](https://twig.symfony.com/)
[![License](https://img.shields.io/badge/License-Proprietary-orange?style=for-the-badge)](#license)

</div>

---

<div align="center">

| | | | |
| :---: | :---: | :---: | :---: |
| <img src="https://github.com/user-attachments/assets/3ea2de40-119e-459b-ba50-73d767d9f668" width="200" alt="Ficobot" /> | <img src="https://github.com/user-attachments/assets/af119f8a-65e6-4ab4-b345-f5183d5445bd" width="200" alt="Dashboard" /> | <img src="https://github.com/user-attachments/assets/e53411b5-e55d-46f3-9c75-24702ef66305" width="200" alt="Realtime Chat" /> | <img src="https://github.com/user-attachments/assets/c0a8478f-5f7d-4caa-999d-8e92acc0b2b2" width="200" alt="Login" /> |
| <img src="https://github.com/user-attachments/assets/f3f2d295-9644-4e14-bbd4-8ba7887d6ae8" width="200" alt="Homepage" /> | <img src="https://github.com/user-attachments/assets/3c6c651a-4238-4b65-b3a0-76bc2fbf4d50" width="200" alt="Homepage Dark" /> | <img src="https://github.com/user-attachments/assets/ecdd8341-b74a-4f92-a104-1d543b71af65" width="200" alt="Techniques" /> | <img width="200"  alt="certificate" src="https://github.com/user-attachments/assets/ed98c408-a5e0-494d-8193-e4c819c9fcb8" />
 |

</div>

## Table of Contents

- [About](#about)
- [Mission](#mission)
- [Core Services](#core-services)
- [Key Features](#key-features)
- [Security and Authentication](#security-and-authentication)
- [Technology Stack](#technology-stack)
- [API Documentation](#api-documentation)
- [Installation & Setup](#installation--setup)
- [Project Structure](#project-structure)
- [Real-time Architecture](#real-time-architecture)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)

---

## About

GROWFICO is a comprehensive agricultural and sustainability platform designed to connect users with tools, products, and services for greener living. The platform integrates e-commerce for premium plant materials, professional landscaping services, and an educational component with courses and certifications.

## Mission

To democratize sustainable agriculture by providing an integrated ecosystem of quality planting solutions, professional support, organic inputs, and expert-led education.

---

## Core Services

### Premium Plant Materials
- Marcotted Fruiting Trees: Accelerated fruit production.
- Grafted Varieties: Improved disease resistance and yield characteristics.
- Curated Selection: Healthy, climate-adapted plants.

### Professional Landscaping
- Design & Install: Residential and commercial garden architecture.
- Maintenance: Ongoing sustainable care programs.
- Hardscaping: Irrigation and structural integration.

### Agricultural Inputs
- Organic Fertilizers: Bio-based nutrient solutions.
- Soil Health: Amendments and custom compost blends.

### Education & Training
- Workshops: Hands-on organic farming sessions.
- Certifications: Beginner to advanced gardening programs.
- Digital Learning: Quizzes, certificates, and structured courses.

---

## Key Features

- Secure Authentication: Multi-method access for API and Web interfaces.
- Real-time Communication: WebSocket-powered chat for administrator support.
- E-commerce Engine: Product management, stock tracking, and Stripe integration.
- Educational Suite: Course management, interactive quizzes, and automated certificate generation.
- A.I. Support: RAG-powered customer support bot (Ficobot).
- QR Integration: Per-user branded QR codes for quick profile access.
- Rate Limiting: Advanced protection against brute-force and API abuse.

---

## Security and Authentication

The platform implements a comprehensive security model to protect user data and system integrity:

- Authentication Methods:
    - JWT (JSON Web Token): Secure, stateless authentication for all API endpoints.
    - OAuth2: Integration with Google for streamlined social login.
    - Session-based: Traditional secure session management for the web-based administrative and user interfaces.
- Protected Routes: Access control is strictly enforced via Symfony Security. Routes are protected by firewall configurations, requiring specific roles (e.g., ROLE_USER, ROLE_ADMIN) or authenticated states.
- Password Handling: Industry-standard password hashing using modern algorithms (Argon2id or Bcrypt). Strict validation constraints ensure password complexity and prevent common vulnerabilities.
- Data Security: Sensitive information, including environment variables, API keys, and credentials, is secured using Symfony's secret vault or protected environment configurations. All customer-sensitive data is encrypted at rest where applicable and transmitted over TLS.
- Brute-force Protection: Rate limiting is applied to all authentication attempts to mitigate automated attacks.

---

## Technology Stack

### Backend
- Framework: Symfony 7.3
- Language: PHP 8.2+ (Targeting 8.4 compatibility)
- ORM: Doctrine with MySQL 8.0
- API Engine: API Platform 4.2

### Frontend
- Templating: Twig 3.x
- CSS Framework: Tailwind CSS 4
- Interactivity: Alpine.js & Stimulus
- Asset Bundling: Webpack Encore

### Real-time & Microservices
- Message Broker: Redis (Pub/Sub)
- Chat Service: Go-based WebSocket Microservice
- Client: Native Browser WebSockets

---

## API Documentation

The platform exposes a RESTful API powered by API Platform. Authentication is handled via JWT.

### Authentication

#### Login
`POST /api/login`

**Request:**
```json
{
  "email": "user@growfico.com",
  "password": "secure_password"
}
```

**Response (200 OK):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "user@growfico.com"
  }
}
```

#### Registration
`POST /api/register`

**Request:**
```json
{
  "email": "newuser@growfico.com",
  "password": "secure_password",
  "first_name": "Jane",
  "last_name": "Doe"
}
```

### Key Endpoints

| Resource | Method | Endpoint | Description |
| :--- | :--- | :--- | :--- |
| **Products** | GET | /api/products | List all available products |
| **Products** | GET | /api/products/{id} | Get detailed product information |
| **Courses** | GET | /api/courses | Browse educational courses |
| **User Profile** | GET | /api/user_profiles/{id} | Get public user profile |
| **Stock** | GET | /api/stocks | Check availability (Admin/Staff) |

### Sample Request: Get Products
```bash
curl -X GET "https://localhost:8000/api/products" -H "accept: application/json"
```

---

## Installation & Setup

### Prerequisites
- PHP: 8.3 or newer
- Composer: 2.8+
- Node.js: 18+ & npm
- Docker: For database and Redis services
- Symfony CLI: (Recommended)

### 1. Clone & Install
```bash
git clone https://github.com/OyanibTech-iii/Growfico-Official-webapp.git
cd Growfico-Official-webapp
composer install
npm install
```

### 2. Environment Configuration
```bash
cp .env .env.local
# Update DATABASE_URL and REDIS_URL in .env.local
```

### 3. Security Keys (JWT)
Ensure OpenSSL is configured, then generate the keypair for JWT:
```bash
php bin/console lexik:jwt:generate-keypair
```

### 4. Database Setup
```bash
docker-compose up -d
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load --append
```

### 5. Running the Application

**Terminal 1: Symfony Web Server**
```bash
symfony serve
# or
php -S localhost:8000 -t public/
```

**Terminal 2: Frontend Assets (Watch Mode)**
```bash
npm run watch
```

**Terminal 3: Real-time Chat Microservice**
```bash
cd microservices/chat
go run main.go
```

---

## Real-time Architecture

GROWFICO uses a hybrid architecture for high-concurrency chat:

1. Symfony Backend: Persists messages to MySQL and publishes to Redis channel chat.
2. Redis Broker: Facilitates communication between the PHP backend and the Go service.
3. Go Microservice: Subscribes to Redis and manages WebSocket connections for low-latency broadcasting.
4. Client-side: Connects directly to the Go service using standard WebSocket API.

---

## Project Structure

```text
├── assets/                 # Frontend assets: JS, Tailwind CSS, Stimulus controllers
├── bin/                    # Executable binaries (console, phpunit)
├── config/                 # Symfony configuration (packages, routes, services)
├── microservices/          # Independent services (Go-based Chat service)
├── migrations/             # Database schema versioning
├── public/                 # Web server root (index.php, assets, uploads)
├── src/                    # Application source code
│   ├── ApiResource/        # API Platform resources
│   ├── Command/            # CLI commands
│   ├── Constants/          # Application-wide constants
│   ├── Controller/         # Web and custom API controllers
│   ├── DataFixtures/       # Database seeding logic
│   ├── Entity/             # Doctrine ORM Models
│   ├── Enum/               # PHP Enums
│   ├── EventSubscriber/    # Symfony event subscribers
│   ├── Form/               # Form types
│   ├── Message/            # Messenger messages
│   ├── MessageHandler/     # Messenger handlers
│   ├── Repository/         # Doctrine repositories
│   ├── Security/           # Authentication & Authorization logic
│   ├── Serializer/         # Custom normalizers and encoders
│   ├── Service/            # Core business logic (Email, QR, Stripe)
│   ├── Twig/               # Custom Twig extensions
│   └── Validator/          # Custom constraints and validators
├── templates/              # Twig views (Frontend templates)
├── translations/           # Translation files
├── tests/                  # PHPUnit test suite
├── var/                    # Cache, logs, and temporary files
└── vendor/                 # Composer dependencies
```

---

## Testing

Run the full test suite to ensure stability:
```bash
php bin/phpunit
```

---

## License

This project is currently Proprietary. All rights reserved.

---

## Contact

- Email: [growficoofficial@gmail.com](mailto:growficoofficial@gmail.com)
- Website: [www.growfico.com](https://www.growfico.com)
- Facebook: [GROWFICO Official](https://facebook.com/GROWFICO_Official)
- Office: Kagawasan Avenue, Dumaguete City, 6200 Negros Oriental
