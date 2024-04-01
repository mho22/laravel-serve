<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;


class ServeDesktop extends Command
{
    protected $signature = 'desktop:serve';

    private $vite;
    private $tauri;


    public function handle()
    {
        $this->getOutput()->isVerbose() ? $this->call( 'ports:clear' ) : $this->callSilently( 'ports:clear' );

        intro( 'Running Desktop Environment' );

        note( "Starting PHP Server" );

        Process::run( "php artisan serve --port=2222", function( string $type, string $output )
        {
            if( ! isset( $this->vite ) && Str::contains( $output, "2222" ) )
            {
                note( "Starting Vite Development Server" );

                $this->vite = Process::forever()->run( "npm run dev:vite:desktop", function( string $type, string $output )
                {
                    if( ! isset( $this->tauri ) && Str::contains( $output, 'APP_URL' ) )
                    {
                        note( 'Starting Desktop App' );

                        if( ! File::exists( base_path( 'tauri/target' ) ) )
                        {
                            Process::path( 'tauri' )->forever()->run( "cargo build", function( string $type, string $output ){ if( $type == 'err' || $this->getOutput()->isVerbose() ) note( $output ); } );
                        }

                        if( ! File::exists( base_path( 'tauri/icons' ) ) )
                        {
                            Process::run( "npm run tauri icon tauri/icon.png", function( string $type, string $output ){ if( $type == 'err' || $this->getOutput()->isVerbose() ) note( $output ); } );
                        }

                        $this->tauri = Process::forever()->run( "npm run dev:tauri:desktop", function( string $type, string $output )
                        {
                            if( ! Str::startsWith( json_encode( $output ), '"\n> ' ) ) note( $output );
                        } );
                    }

                    if( $type == 'err' || $this->getOutput()->isVerbose() ) note( $output );
                } );
            }

            if( $type == 'err' || $this->getOutput()->isVerbose() ) note( $output );
        } );
    }
}
