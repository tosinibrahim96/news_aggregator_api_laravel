# News Aggregator API

A Laravel-based API that aggregates news from multiple sources (The Guardian, NewsAPI, and New York Times) with user preference-based personalization.

## Features
- Multi-source news aggregation
- User authentication with JWT
- Personalized news feed based on user preferences
- Article search and filtering
- Rate limiting and caching
- Automated article fetching
- API documentation with Swagger/OpenAPI
- Docker-ready setup

## Requirements
- Docker & Docker Compose
- Git
- API keys for news sources (The Guardian, NewsAPI, NYT)

## Installation

### 1. Clone the repository
```bash
git clone 
cd news-aggregator
```

### 2. Environment Setup
```bash
# Copy environment file
cp .env.example .env
```

Edit `.env` file and update the following:
```env
# App Settings
APP_NAME="News Aggregator"
APP_URL=http://localhost:8000

# Database Settings
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=news_aggregator
DB_USERNAME=news_user
DB_PASSWORD=news_password

# JWT Configuration
JWT_SECRET=     # Will be generated in next steps
JWT_TTL=60      # Token lifetime in minutes
JWT_REFRESH_TTL=20160  # Refresh token lifetime in minutes

# News API Keys
GUARDIAN_API_KEY=your-guardian-api-key
NEWS_API_KEY=your-newsapi-key
NYT_API_KEY=your-nyt-api-key

# Swagger Documentation
L5_SWAGGER_GENERATE_ALWAYS=true
SWAGGER_VERSION=3.0
```

### 3. Get API Keys

#### The Guardian API
1. Visit https://open-platform.theguardian.com/access/
2. Click "Get API Key"
3. Fill out the registration form
4. Copy the API key to `GUARDIAN_API_KEY` in your `.env`

#### NewsAPI
1. Visit https://newsapi.org/register
2. Create an account
3. Copy the API key to `NEWS_API_KEY` in your `.env`

#### New York Times API
1. Visit https://developer.nytimes.com/
2. Create an account and create a new app
3. Enable "Article Search API"
4. Copy the API key to `NYT_API_KEY` in your `.env`

### 4. Start Docker Environment
```bash
# Build and start containers
docker-compose up -d --build

# Install PHP dependencies
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Generate JWT secret
docker-compose exec app php artisan jwt:secret

# Run database migrations and seeders
docker-compose exec app php artisan migrate --seed
```

### 5. Generate API Documentation
```bash
docker-compose exec app php artisan l5-swagger:generate
```

## Data Synchronization

### Initial Data Setup
After installation, you'll need to fetch initial articles:
```bash
# Fetch articles from all sources (this adds jobs to the queue)
docker-compose exec app php artisan news:fetch

# Process the queue to actually fetch the articles
docker-compose exec app php artisan queue:work --queue=news-the-guardian,news-newsapi,news-new-york-times 
```

### Fetch Command Options
```bash
# Fetch from specific source
docker-compose exec app php artisan news:fetch --source=the-guardian

# Fetch specific category
docker-compose exec app php artisan news:fetch --category=technology

# Control retry attempts and timeout
docker-compose exec app php artisan news:fetch --max-retry=3 --timeout=300

Remember that these commands add jobs to the queue. You need to process the jobs on the queue to actually fetch the articles
```

### Monitoring Data Sync
1. View Logs:
```bash
# View sync logs
docker-compose exec app tail -f storage/logs/laravel.log

# Filter for specific source
docker-compose exec app tail -f storage/logs/laravel.log | grep "The Guardian"
```

2. Check Sync Status:
```bash
# Get article counts
docker-compose exec app php artisan tinker
>>> App\Models\Article::count();
>>> App\Models\Article::whereDate('created_at', today())->count();
```

## API Usage

### API Documentation
Access the Swagger documentation at:
```
http://localhost:8000/api/documentation
```

### Available Endpoints

#### Authentication
- POST `/api/auth/register` - Register new user
- POST `/api/auth/login` - Login user
- POST `/api/auth/logout` - Logout user
- POST `/api/auth/refresh` - Refresh JWT token

#### Articles
- GET `/api/articles/search` - Search and filter articles
  - Supports filtering by source, category, author
  - Supports date range filtering
  - Returns personalized results for authenticated users

#### User Preferences
- GET `/api/preferences` - Get user preferences
- PUT `/api/preferences` - Update user preferences

## Testing
```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test suite
docker-compose exec app php artisan test --testsuite=Feature
docker-compose exec app php artisan test --testsuite=Unit
```

## Troubleshooting

### Common Issues
1. **Container Connection Issues**
```bash
# Restart containers
docker-compose down
docker-compose up -d

# Check container logs
docker-compose logs -f
```

2. **Permission Issues**
```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

3. **JWT Issues**
```bash
# Clear config cache
docker-compose exec app php artisan config:clear

# Regenerate JWT secret
docker-compose exec app php artisan jwt:secret
```

## Directory Structure
Important directories and files:
```
.
├── app
│   ├── Actions/           # Business logic
│   ├── Contracts/         # Interfaces
│   ├── Http/
│   │   ├── Controllers/   # API Controllers
│   │   ├── Requests/      # Form requests
│   │   └── Resources/     # API Resources
│   ├── Models/            # Eloquent models
│   └── Repositories/      # Data access layer
├── database
│   ├── factories/         # Model factories
│   ├── migrations/        # Database migrations
│   └── seeders/          # Database seeders
├── docker/               # Docker configuration
├── tests/                # Test suites
└── docker-compose.yml    # Docker services
```

## Maintenance
```bash
# Clear all caches
docker-compose exec app php artisan optimize:clear

# Update dependencies
docker-compose exec app composer update
```
