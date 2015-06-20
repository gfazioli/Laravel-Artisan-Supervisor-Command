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

    
```
    Usage:
      shell:supervisor [options]
    
    Options:
          --list            List supervisor processes
          --start[=START]   Start supervisor process by process name
          --stop[=STOP]     Stop a supervisor process by PID or process name
          --start-all       Stop all supervisor processes
          --stop-all        Stop all supervisor processes
          --up              Start supervisor service
          --down            Stop supervisor service
```

## Broadcast and supervisor links

* http://laravel.com/docs/5.1/events#broadcasting-events
* http://blog.nedex.io/laravel-5-1-broadcasting-events-using-redis-driver-socket-io/
* http://posts.danharper.me/laravel-queue-supervisor/
