<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;


class ServeDesktop extends Command
{
    protected $signature = 'desktop:serve';


    public function handle()
    {
        $this->getOutput()->isVerbose() ? $this->call( 'ports:clear', [ '50002 50003 50004' ] ) : $this->callSilently( 'ports:clear', [ '50002 50003 50004' ] );

        intro( 'Running Desktop Environment' );

        $this->initTauriServer();
        $this->initViteServer();
        $this->initPHPServer();
    }


    private function initTauriServer() : void
    {
        note( 'Starting Desktop App' );

        if( ! File::exists( base_path( 'tauri/target' ) ) )
        {
            Process::path( 'tauri' )->forever()->tty()->run( "cargo build" );
        }

        if( ! File::exists( base_path( 'tauri/icons' ) ) )
        {
            Process::forever()->tty()->run( "npm run tauri icon tauri/icon.png" );
        }

        Process::tty()->start( "npm run dev:tauri:desktop -- --port=50003" );
    }

    private function initViteServer() : void
    {
        note( "Starting Vite Development Server" );

        Process::start( "npm run dev:vite:desktop" );
    }

    private function initPHPServer() : void
    {
        note( "Starting PHP Server" );

        Process::forever()->tty()->run( "php artisan serve --port=50002" );
    }
}
