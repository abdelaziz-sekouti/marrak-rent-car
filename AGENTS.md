# AGENTS.md

This file contains guidelines and commands for agentic coding agents working in the marrak-rent-car repository.

## Project Stack
- **Backend**: PHP 8.x with MySQL
- **Frontend**: JavaScript (ES6+), HTML5, CSS3
- **Styling**: TailwindCSS
- **Build Tools**: Composer (PHP), npm (JS)

## Build/Test/Lint Commands

### PHP Commands
```bash
# Install PHP dependencies
composer install

# Update dependencies
composer update

# Run PHP linter
composer run lint
# or
php -l path/to/file.php

# Run PHP tests
composer test
# or
php vendor/bin/phpunit

# Run specific test
php vendor/bin/phpunit tests/SpecificTest.php

# Check code style (PHP_CodeSniffer)
composer run phpcs

# Fix code style automatically
composer run phpcbf
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
// 1. External libraries
require_once 'vendor/autoload.php';

// 2. Application config
require_once 'config/database.php';
require_once 'config/app.php';

// 3. Application classes
require_once 'models/Car.php';
require_once 'models/User.php';
require_once 'services/CarRentalService.php';
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
// 1. External libraries
import axios from 'axios';
import { useState, useEffect } from 'react';

// 2. Internal utilities
import { formatDate } from './utils/dateHelpers';
import { validateEmail } from './utils/validation';

// 3. Components
import CarCard from './components/CarCard';
import RentalForm from './components/RentalForm';
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
├── config/           # Configuration files
├── src/              # PHP source code
│   ├── controllers/  # HTTP controllers
│   ├── models/       # Database models
│   ├── services/     # Business logic
│   └── middleware/   # Request middleware
├── public/           # Web root
│   ├── assets/       # CSS, JS, images
│   └── index.php     # Front controller
├── resources/        # Views, templates
├── tests/            # Test files
├── node_modules/     # Node.js dependencies
├── vendor/           # Composer dependencies
├── composer.json     # PHP dependencies
├── package.json      # Node.js dependencies
└── tailwind.config.js # TailwindCSS configuration
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