<?php 

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SupervisorCommand extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'shell:supervisor
                         {--list : List supervisor processes.}
                         {--start= : Start supervisor process by process name.}
                         {--stop= : Stop a supervisor process by PID or process name.}
                         {--start-all : Stop all supervisor processes.}
                         {--stop-all : Stop all supervisor processes.}
                         {--up : Start supervisor service.}
                         {--down : Stop supervisor service.}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Manage Supervisor Processes';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle()
  {

    // Handle options
    if ( $this->option( 'list' ) ) {
      $this->listProcess();
    }
    elseif ( $this->option( 'stop' ) ) {
      $processName = is_numeric( $this->option( 'stop' ) ) ? $this->getProcessName( $this->option( 'stop' ) ) : $this->option( 'stop' );

      if ( empty( $processName ) ) {
        $this->error( "\n \n\t[ERROR]\n\tNo such process.\n" );
      }
      else {
        $this->info( "\nStop process " . $processName );
        $this->line( shell_exec( "sudo supervisorctl stop " . $processName ) );
      }
      $this->listProcess();
    }
    elseif ( $this->option( 'start' ) ) {
      if ( $this->hasProcessName( $this->option( 'start' ) ) ) {
        $this->info( "\nStart process " . $this->option( 'start' ) );
        $this->line( shell_exec( "sudo supervisorctl start " . $this->option( 'start' ) ) );
      }
      else {
        $this->error( "\n \n\t[ERROR]\n\tNo such process.\n" );
      }
      $this->listProcess();
    }
    elseif ( $this->option( 'stop-all' ) ) {
      $this->stopProcess();
    }
    elseif ( $this->option( 'start-all' ) ) {
      $this->startProcess();
    }
    elseif ( $this->option( 'down' ) ) {
      $this->info( "\nStopping supervisor processes..." );
      $this->line( shell_exec( 'sudo supervisorctl stop all' ) );
      $this->info( "Stop supervisor" );
      $this->line( shell_exec( "sudo service supervisor stop" ) );

      // Flush app
      $this->call( 'clear-compiled' );
      $this->call( 'cache:clear' );
      $this->call( 'queue:flush' );
      $this->call( 'queue:restart' );
      $this->call( 'optimize' );

    }
    elseif ( $this->option( 'up' ) ) {
      $this->info( "\nStart supervisor service" );
      $this->line( shell_exec( "sudo service supervisor start" ) );
      $this->line( 'Reread... ' . shell_exec( "sudo supervisorctl reread" ) );
      $this->line( 'Update... ' . shell_exec( "sudo supervisorctl update" ) );
      $this->listProcess();
    }
    else {
      $this->listProcess();
    }
  }

  /**
   * Return the process name by PID.
   *
   * @param int $processPID PID
   *
   * @return bool|string
   */
  protected function getProcessName( $processPID )
  {
    $lines = array_filter( explode( "\n", shell_exec( "sudo supervisorctl status | awk '{print $4\" \"$1\" \"$2\" \"$6}'" ) ) );
    foreach ( $lines as $line ) {

      @list( $pid, $process ) = array_filter( explode( " ", $line ) );

      $pid = isset( $pid ) ? rtrim( $pid, "," ) : "";

      if ( $pid == $processPID ) {
        return $process;
      }
    }

    return false;

  }

  /**
   * Return true if exists a process.
   *
   * @param string $processName The process name.
   *
   * @return bool|string
   */
  protected function hasProcessName( $processName )
  {
    $lines = array_filter( explode( "\n", shell_exec( "sudo supervisorctl status | awk '{print $4\" \"$1\" \"$2\" \"$6}'" ) ) );
    foreach ( $lines as $line ) {

      @list( $pid, $process ) = array_filter( explode( " ", $line ) );

      if ( $process == $processName ) {
        return true;
      }
    }

    return false;

  }

  /**
   * List processes
   *
   */
  protected function listProcess()
  {
    $this->info( "\nList supervisor processes...\n" );
    $processes = [ ];
    $lines     = array_filter( explode( "\n", shell_exec( "sudo supervisorctl status | awk '{print $4\" \"$1\" \"$2\" \"$6}'" ) ) );
    foreach ( $lines as $line ) {

      @list( $pid, $process, $status, $uptime ) = array_filter( explode( " ", $line ) );

      $processes[] = [
        'pid'     => isset( $pid ) ? ( ( $status == 'RUNNING' ) ? rtrim( $pid, "," ) : '-' ) : '-',
        'process' => $process,
        'status'  => $status,
        'uptime'  => isset( $uptime ) ? $uptime : '',
      ];
    }

    $this->table( [ 'PID', 'Process', 'Status', 'Uptime' ], $processes );

  }

  /**
   * Start processes
   *
   */
  protected function startProcess()
  {
    $this->info( "Starting supervisor processes..." );
    $processes = shell_exec( "sudo supervisorctl status | awk '{print $1}'" );
    $this->listProcess();
    if ( $this->confirm( 'Are you sure to START these items?' ) ) {
      $arrayProcesses = array_filter( explode( "\n", $processes ) );
      $this->output->progressStart( count( $arrayProcesses ) );
      foreach ( $arrayProcesses as $process ) {
        shell_exec( "sudo supervisorctl start $process" );
        $this->output->progressAdvance();
      }
      $this->output->progressFinish();

      $this->info( "Complete" );
      $this->listProcess();
    }
  }

  /**
   * Stop processes
   *
   */
  protected function stopProcess()
  {
    $this->info( "Stopping supervisor processes..." );
    $processes = shell_exec( "sudo supervisorctl status | awk '{print $1}'" );
    $this->listProcess();
    if ( $this->confirm( 'Are you sure to STOP these items?' ) ) {
      $arrayProcesses = array_filter( explode( "\n", $processes ) );
      $this->output->progressStart( count( $arrayProcesses ) );
      foreach ( $arrayProcesses as $process ) {
        shell_exec( "sudo supervisorctl stop $process" );
        $this->output->progressAdvance();
      }
      $this->output->progressFinish();

      $this->info( "Complete" );
      $this->listProcess();
    }
  }
}
