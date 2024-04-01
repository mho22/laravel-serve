<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;


class ServeMobile extends Command
{
    protected $signature = 'mobile:serve {--android} {--ios}';

    private $vite;
    private $tauri;


    public function handle()
    {
        if( ! $this->option( 'ios' ) && ! $this->option( 'android' ) ) return warning( "A device option is needed : 'mobile:serve --android' or 'mobile:serve --ios'" );

        $this->getOutput()->isVerbose() ? $this->call( 'ports:clear' ) : $this->callSilently( 'ports:clear' );

        intro( 'Running Mobile Environment' );

        note( "Starting PHP Server" );

        Process::run( "php artisan serve --host='192.168.0.10' --port=2222" , function( string $type, string $output )
        {
            if( ! isset( $this->vite ) && Str::contains( $output, "2222" ) )
            {
                note( "Starting Vite Development Server" );

                $this->vite = Process::forever()->run( "npm run dev:vite:mobile", function( string $type, string $output )
                {
                    if( ! isset( $this->tauri ) && Str::contains( $output, 'APP_URL' ) )
                    {
                        $device = $this->option( 'ios' ) ? 'ios' : 'android';

                        note( Str::headline( "Starting Mobile {$device} App" ) );

                        if( ! File::exists( base_path( 'tauri/target' ) ) )
                        {
                            Process::path( 'tauri' )->forever()->run( "cargo build", function( string $type, string $output ){ if( $type == 'err' || $this->getOutput()->isVerbose() ) note( $output ); } );
                        }

                        if( ! File::exists( base_path( 'tauri/icons' ) ) )
                        {
                            Process::run( "npm run tauri icon tauri/icon.png", function( string $type, string $output ){ if( $type == 'err' || $this->getOutput()->isVerbose() ) note( $output ); } );
                        }

                        if( ! File::exists( base_path( "tauri/gen/{$device}" ) ) )
                        {
                            Process::run( "npm run tauri {$device} init", function( string $type, string $output ){ if( $type == 'err' || $this->getOutput()->isVerbose() ) note( $output ); } );
                        }

                        $this->tauri = Process::forever()->tty()->run( "npm run dev:tauri:mobile:{$device}", function( string $type, string $output )
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
