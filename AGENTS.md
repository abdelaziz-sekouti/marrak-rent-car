# AGENTS.md

This file contains guidelines and commands for agentic coding agents working in the marrak-rent-car repository.

## Project Stack
- **Backend**: PHP 8.x with MySQL (Vanilla PHP, no Composer)
- **Frontend**: JavaScript (ES6+), HTML5, CSS3
- **Styling**: TailwindCSS
- **Build Tools**: npm (JS only), Manual PHP testing

## Build/Test/Lint Commands

### PHP Commands
```bash
# Check PHP syntax for specific file
php -l path/to/file.php

# Run PHP built-in web server for testing
php -S localhost:8000

# Run PHP syntax check on all PHP files in directory
find . -name "*.php" -exec php -l {} \;

# Run migration script
php migrate.php

# Run specific test files manually
php test-car.php
php test.php
```

### JavaScript Commands
```bash
# Install Node.js dependencies
npm install

# Run development server
npm run dev

# Build for production
npm run build

# Run JavaScript linter
npm run lint
# or
npx eslint path/to/file.js

# Fix linting issues automatically
npm run lint:fix

# Run JavaScript tests
npm test

# Run specific test file
npm test -- --grep "test name"
# or
npx jest path/to/test.js

# Run TailwindCSS build
npm run build:css

# Watch CSS changes
npm run watch:css
```

## Code Style Guidelines

### PHP Conventions
- **PSR Standards**: Follow PSR-1, PSR-2, and PSR-12
- **File Naming**: PascalCase for classes (e.g., `CarRental.php`), snake_case for functions
- **Class Naming**: PascalCase (e.g., `CarRentalService`, `UserModel`)
- **Method Naming**: camelCase (e.g., `getAvailableCars()`, `validateUserData()`)
- **Variable Naming**: camelCase (e.g., `$availableCars`, `$userData`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `DB_HOST`, `MAX_RENTAL_DAYS`)

#### PHP Import Structure
```php
<?php
// 1. Application initialization
require_once __DIR__ . '/../includes/init.php';

// 2. Model imports
require_once __DIR__ . '/../models/Car.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Rental.php';
```

#### PHP Error Handling
```php
// Use exceptions for error handling
try {
    $result = $carService->rentCar($userId, $carId);
} catch (CarNotAvailableException $e) {
    // Log error and handle gracefully
    error_log($e->getMessage());
    return ['error' => 'Car not available'];
} catch (Exception $e) {
    // Handle unexpected errors
    error_log($e->getMessage());
    return ['error' => 'An unexpected error occurred'];
}
```

### JavaScript Conventions
- **ES6+ Standards**: Use modern JavaScript features
- **File Naming**: PascalCase for components, camelCase for utilities
- **Function Naming**: camelCase (e.g., `getAvailableCars()`, `validateForm()`)
- **Variable Naming**: camelCase (e.g., `availableCars`, `formData`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `API_BASE_URL`, `MAX_RENTAL_DAYS`)

#### JavaScript Import Structure
```javascript
// 1. External libraries (loaded via script tags or npm)
import axios from 'axios';

// 2. DOM Ready and initialization
document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
    initFormValidations();
    initCarSearch();
});

// 3. Function definitions (no imports, vanilla JS)
function initMobileMenu() { /* ... */ }
function validateForm(form) { /* ... */ }
```

#### JavaScript Error Handling
```javascript
// Use async/await with try-catch
async function rentCar(userId, carId) {
    try {
        const response = await axios.post('/api/rent', { userId, carId });
        return response.data;
    } catch (error) {
        console.error('Failed to rent car:', error);
        throw new Error('Rental failed');
    }
}
```

### CSS/TailwindCSS Conventions
- **Utility-First**: Prefer Tailwind utilities over custom CSS
- **Component Classes**: Use `@apply` for reusable component classes
- **Responsive Design**: Mobile-first approach with Tailwind responsive prefixes
- **Naming**: kebab-case for custom CSS classes

#### CSS Structure
```css
/* 1. Tailwind base imports */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* 2. Custom component classes */
.car-card {
    @apply bg-white rounded-lg shadow-md p-6;
}

/* 3. Utility classes */
.text-primary {
    @apply text-blue-600;
}
```

## Database Conventions
- **Table Naming**: snake_case, plural (e.g., `cars`, `users`, `rentals`)
- **Column Naming**: snake_case (e.g., `first_name`, `created_at`)
- **Primary Keys**: `id` (auto-increment)
- **Foreign Keys**: `{table}_id` (e.g., `user_id`, `car_id`)
- **Timestamps**: `created_at`, `updated_at`

#### Database Class Pattern
```php
// Use custom Database wrapper class
public function __construct() {
    $this->db = new Database();
}

// Query pattern with prepared statements
public function getCarById($id) {
    $this->db->query("SELECT * FROM cars WHERE id = :id");
    $this->db->bind(':id', $id);
    return $this->db->single();
}

// Multiple parameter binding
foreach ($params as $key => $value) {
    $this->db->bind($key, $value);
}
return $this->db->resultSet();
```

## API Conventions
- **RESTful URLs**: Use resource-based URLs (e.g., `/api/cars`, `/api/users`)
- **HTTP Methods**: GET (read), POST (create), PUT/PATCH (update), DELETE (delete)
- **Response Format**: JSON with consistent structure
- **Error Responses**: Include error message and status code

#### API Response Structure
```json
{
    "success": true,
    "data": {
        "id": 1,
        "make": "Toyota",
        "model": "Camry"
    },
    "message": "Car retrieved successfully"
}
```

## File Organization
```
marrak-rent-car/
├── admin/            # Admin panel pages
│   ├── index.php     # Admin dashboard
│   ├── cars.php      # Car management
│   └── rentals.php   # Rental management
├── database/         # Database migration scripts
├── includes/         # PHP includes (config, database, helpers)
├── public/           # Web root and assets
│   ├── css/          # Compiled CSS files
│   └── js/           # JavaScript files
├── src/              # PHP source code
│   └── models/       # Database models
├── views/            # Frontend view templates
├── index.php         # Main entry point
├── migrate.php       # Database migration runner
├── package.json      # Node.js dependencies
├── tailwind.config.js # TailwindCSS configuration
└── postcss.config.js # PostCSS configuration
```

## Testing Guidelines
- **Unit Tests**: Test individual functions/methods
- **Integration Tests**: Test database interactions
- **API Tests**: Test endpoint responses
- **Frontend Tests**: Test UI components and interactions
- **Test Naming**: `test_{functionality}` or `{functionality}_test`

## Security Guidelines
- **Input Validation**: Always validate user input
- **SQL Injection**: Use prepared statements
- **XSS Prevention**: Escape output, use CSP headers
- **Authentication**: Use secure password hashing
- **Authorization**: Check user permissions
- **CSRF Protection**: Use CSRF tokens

## Performance Guidelines
- **Database**: Use indexes, optimize queries
- **Caching**: Implement appropriate caching strategies
- **Assets**: Minify CSS/JS, optimize images
- **Lazy Loading**: Load resources as needed
- **CDN**: Use CDN for static assets

## Git Workflow
- **Branch Naming**: `feature/{feature-name}`, `bugfix/{bug-description}`
- **Commit Messages**: Conventional Commits format
- **Code Review**: Require review before merging
- **Testing**: Ensure tests pass before committing