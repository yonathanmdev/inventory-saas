# Inventory SaaS — Setup Guide (Step 1: Project Foundation)

Stack: PHP + Slim Framework (PSR-7/PSR-15) + Composer, MySQL, multi-tenant architecture.
Run all commands in WSL (Ubuntu).

---

## 1. Install PHP + Composer (if not already installed)

```bash
sudo apt update
sudo apt install php-cli php-mbstring php-xml php-zip unzip -y
```

Install Composer (official method):
```bash
cd ~
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=$HOME --filename=composer
sudo mv $HOME/composer /usr/local/bin/composer
rm composer-setup.php
composer --version
```

---

## 2. Create the project folder

```bash
mkdir -p ~/projects/inventory-saas
cd ~/projects/inventory-saas
```

---

## 3. Initialize Composer

```bash
composer init
```

Answer the prompts:

| Prompt | Answer |
|---|---|
| Package name | Press **Enter** (accepts `yonathanmdev/inventory-saas`) |
| Description | `Multi-tenant inventory management SaaS` |
| Author | Press **Enter** if auto-detected correctly |
| Minimum Stability | Press **Enter** (default) |
| Package Type | `project` |
| License | `proprietary` (or press Enter to skip) |
| Add PSR-4 autoload mapping? | Type `app/` (with trailing slash) when asked for the path. It will default the namespace to `Yonathanmdev\InventorySaas` — that's fine, we fix this by hand in Step 4. |
| Define dependencies (require) interactively? | `no` |
| Define dev dependencies (require-dev) interactively? | `no` |
| Confirm generation | `yes` |

---

## 4. Fix the autoload namespace in `composer.json`

Open the file:
```bash
nano composer.json
```

Find the `"autoload"` block. It will look like:
```json
"autoload": {
    "psr-4": {
        "Yonathanmdev\\InventorySaas\\": "app/"
    }
}
```

Change it to:
```json
"autoload": {
    "psr-4": {
        "App\\": "app/"
    }
}
```

Save and exit (`Ctrl+O`, Enter, `Ctrl+X`).

Regenerate the autoloader:
```bash
composer dump-autoload
```

---

## 5. Install core packages

```bash
composer require slim/slim:"^4.0" slim/psr7
composer require vlucas/phpdotenv
```

---

## 6. Create the folder structure

```bash
mkdir -p app/Controllers app/Models app/Middleware app/Helpers app/Views
mkdir -p config public/assets routes
```

---

## 7. Create `.gitignore`

```bash
cat > .gitignore << 'EOF'
vendor/
.env
*.log
EOF
```

---

## 8. Create `.env`

```bash
cat > .env << 'EOF'
DB_HOST=localhost
DB_NAME=inventory_saas
DB_USER=root
DB_PASS=your_password_here
APP_ENV=development
EOF
```

(We create the actual MySQL database in a later step.)

---

## 9. Create the entry point — `public/index.php`

```bash
nano public/index.php
```

Paste:
```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create Slim app
$app = AppFactory::create();

// Load routes from a separate file
(require __DIR__ . '/../routes/web.php')($app);

$app->run();
```

---

## 10. Create the first route file — `routes/web.php`

```bash
nano routes/web.php
```

Paste:
```php
<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function ($app) {
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Inventory SaaS is running.');
        return $response;
    });
};
```

---

## 11. Run it

```bash
php -S localhost:8000 -t public
```

Open in your Windows browser: `http://localhost:8000`

Expected result: **"Inventory SaaS is running."**

---

## Next step (Step 2)

Once Step 1 is confirmed working, we move to:
- MySQL database setup (`inventory_saas` DB, dedicated user)
- Core schema: `Business` (tenant), `User`, `Role`, `Permission`
- Migration approach (plain SQL files vs a migration library)