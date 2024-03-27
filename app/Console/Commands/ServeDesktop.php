<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;


class ServeDesktop extends Command
{
    protected $signature = 'desktop:serve';

    private $vite;
    private $tauri;


    public function handle()
    {
        $this->getOutput()->isVerbose() ? $this->runComand( 'ports:clear' ) : $this->callSilently( 'ports:clear' );

        intro( 'Running Desktop Environment' );

        note( "Starting PHP Server" );

        Process::run( "npm run serve", function( string $type, string $output )
        {
            if( ! isset( $this->vite ) && Str::contains( $output, "PHP" ) )
            {
                note( "Starting Vite Development Server" );

                $this->vite = Process::forever()->run( "npm run dev:vite:desktop", function( string $type, string $output )
                {
                    if( ! isset( $this->tauri ) && Str::contains( $output, 'APP_URL' ) )
                    {
                        note( 'Starting Desktop App' );

                        $this->tauri = Process::forever()->run( "npm run dev:tauri:desktop", function( string $type, string $output )
                        {
                            if( ! Str::startsWith( json_encode( $output ), '"\n> ' ) ) note( $output );
                        } );
                    }

                    if( $this->getOutput()->isVerbose() ) note( $output );
                } );
            }

            if( $this->getOutput()->isVerbose() ) note( $output );
        } );
    }
}
