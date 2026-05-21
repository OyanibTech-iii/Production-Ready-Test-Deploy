# GEMINI.md

## Project Overview

**GROWFICO** is a comprehensive agricultural and sustainability platform designed to connect users with tools, products, and services for greener living. The platform integrates e-commerce for premium plant materials and agricultural inputs, professional landscaping services, and an educational component with courses, quizzes, and certifications.

### Core Technologies
- **Backend:** PHP 8.2+ (Symfony 7.3 Framework)
- **Frontend:** JavaScript (Webpack Encore), Tailwind CSS 4, Alpine.js, Stimulus, Turbo
- **Database:** MySQL 8.0 with Doctrine ORM
- **API:** API Platform 4.2 with Lexik JWT Authentication
- **Payments:** Stripe Integration
- **Infrastructure:** Docker-based development environment (MySQL, PHPMyAdmin)
- **Other Integrations:** Google OAuth2, Leaflet (Maps), SendGrid/Brevo (Email), Ionicons

### Architecture
The project follows a standard Symfony directory structure:
- `src/Entity/`: Core domain models (User, Product, Stock, Order, Course, etc.)
- `src/Controller/`: Application logic, including `Admin/` sub-namespace for administrative tasks.
- `src/Security/`: Authentication logic (JWT, Google OAuth2, Form Login).
- `templates/`: Twig-based frontend views.
- `assets/`: Frontend source assets (CSS, JS).
- `migrations/`: Database schema versioning.

---

## Building and Running

### Prerequisites
- PHP 8.2 or newer
- Composer 2.8+
- Node.js 18+ and npm
- Docker (for database services)

### Setup Instructions
1. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Environment Configuration:**
   Copy `.env` to `.env.local` and update the `DATABASE_URL` and other credentials.
   ```bash
   cp .env .env.local
   ```

3. **Infrastructure:**
   Start the database and PHPMyAdmin:
   ```bash
   docker-compose up -d
   ```

4. **Database Setup:**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load --append
   ```

5. **JWT Keypair Generation:**
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```

### Running the Application
- **Backend:** `symfony serve` (if Symfony CLI is installed) or `php -S localhost:8000 -t public/`
- **Frontend Assets:**
  - `npm run watch` (Development with hot reload)
  - `npm run build` (Production build)

### Testing
Run the PHPUnit test suite:
```bash
php bin/phpunit
```

---

## Development Conventions

### Coding Standards
- Follow **PSR-12** coding standards for PHP.
- Use **Type Hinting** for all method parameters and return types.
- Controllers should be kept thin; delegate complex logic to Service classes in `src/Service/`.

### Frontend Guidelines
- **Tailwind CSS:** Use utility classes for styling. Avoid writing custom CSS unless necessary.
- **Alpine.js & Stimulus:** Use for interactive components.
- **Twig:** Utilize template inheritance and components (found in `templates/`).

### Security
- **Authentication:** Managed via `App\Security\LoginFormAuthenticator` for web and JWT for API.
- **Access Control:** Defined in `config/packages/security.yaml`. Admin routes are prefixed with `/admin`.
- **Secrets:** Never commit sensitive credentials. Use `.env.local` or Symfony's secrets vault.

### Workflow
1. **Migrations:** Always create a migration for database changes: `php bin/console make:migration`.
2. **Fixtures:** Use `php bin/console doctrine:fixtures:load` to seed the database. `CourseFixtures` provides the base courses from the legacy database.
3. **Tests:** Add unit or functional tests in `tests/` for new features.
4. **Commit Messages:** Use clear, concise summaries of changes.
