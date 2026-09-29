# Laravel Application

This repository contains a Laravel 13 application running on PostgreSQL (Neon). Review the prerequisites before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Project rules

- Laravel Boost is intentionally NOT installed in this repository and must NOT be installed. Do not run `composer require laravel/boost --dev` or `php artisan boost:install` — adding packages is against project policy.
- Never run a second PHPUnit / `migrate:fresh` process concurrently against the shared Neon test branch; only one suite at a time.
- Do not edit `app/`, `.env`, or `config/` files while a PHPUnit suite is running — lazy class loading mixes old and new code and poisons the run.
- Report findings in Indonesian. Classify issues as CRITICAL/HIGH/MEDIUM/LOW/DEAD; mark unverifiable items as "NEEDS VERIFICATION".
- Edits must be free of CJK characters (BEBAS CJK).