#!/bin/sh
set -e

cd /var/www/html

echo "Preparing Laravel directories..."

mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

echo "Waiting for database..."

until php -r "
try {
    \$host = getenv('DB_HOST');
    \$port = getenv('DB_PORT') ?: 3306;
    \$db = getenv('DB_DATABASE');
    \$user = getenv('DB_USERNAME');
    \$pass = getenv('DB_PASSWORD');

    \$connection = getenv('DB_CONNECTION') ?: 'mysql';

    if (\$connection === 'sqlsrv') {
        \$encrypt = getenv('DB_ENCRYPT') ?: 'yes';
        \$trust = getenv('DB_TRUST_SERVER_CERTIFICATE') ?: 'false';
        \$dsn = \"sqlsrv:Server={\$host},{\$port};Database={\$db};Encrypt={\$encrypt};TrustServerCertificate={\$trust}\";
    } else {
        \$dsn = \"mysql:host={\$host};port={\$port};dbname={\$db}\";
    }

    new PDO(\$dsn, \$user, \$pass);
    exit(0);
} catch (Throwable \$e) {
    exit(1);
}
"; do
    echo "Database is not ready yet..."
    sleep 3
done

echo "Database is ready."

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is empty. Generating key..."
    php artisan key:generate --force || true
fi

echo "Clearing Laravel cache..."

php artisan optimize:clear || true
php artisan storage:link || true

if [ "$AUTO_MIGRATE" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

if [ "$AUTO_SEED" = "true" ]; then
    if [ "$FORCE_SEED" = "true" ] || [ ! -f storage/app/seeded.lock ] || ! php -r '
        try {
            require "vendor/autoload.php";
            $app = require "bootstrap/app.php";
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            exit(App\Models\User::query()->exists() ? 0 : 1);
        } catch (Throwable $e) {
            exit(1);
        }
    '; then
        echo "Running seeders..."
        php artisan db:seed --force
        touch storage/app/seeded.lock
    else
        echo "Seed already completed and users exist. Skipping..."
    fi
fi

echo "Caching Laravel config..."

php artisan config:cache || true
php artisan route:cache || true
php artisan view:clear || true

echo "Starting services..."

exec "$@"
