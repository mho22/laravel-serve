<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;


class ServeMobile extends Command
{
    protected $signature = 'mobile:serve {--android} {--ios}';

    private $vite;
    private $tauri;


    public function handle()
    {
        $this->getOutput()->isVerbose() ? $this->runComand( 'ports:clear' ) : $this->callSilently( 'ports:clear' );

        intro( 'Running Mobile Environment' );

        note( "Starting PHP Server" );

        Process::run( "npm run serve" , function( string $type, string $output )
        {
            if( ! isset( $this->vite ) && Str::contains( $output, "PHP" ) )
            {
                note( "Starting Vite Development Server" );

                $this->vite = Process::run( "npm run dev:vite:mobile", function( string $type, string $output )
                {
                    if( ! isset( $this->tauri ) && Str::contains( $output, 'APP_URL' ) )
                    {
                        $device = $this->hasOption( '--ios' ) ? 'ios' : 'android';

                        note( Str::headline( "Starting Mobile {$device} App" ) );

                        $this->tauri = Process::forever()->tty()->run( "npm run dev:tauri:mobile:{$device}", function( string $type, string $output )
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
