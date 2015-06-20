# Laravel-Artisan-Supervisor-Command

## Installation

1. Copy or create `SupervisorCommand.php` file in your `app/Console/Commands` folder
2. Edit `Kernel.php` in `app/Console/Commands` folder
3. Add the command in `$commands` array property

```php
	protected $commands = [
		\App\Console\Commands\SupervisorCommand::class,
	];
```
4. Run from shell `php artisan shell:supervisor`
