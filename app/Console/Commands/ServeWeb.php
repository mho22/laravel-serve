<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;


class ServeWeb extends Command
{
    protected $signature = 'web:serve';

    private $vite;


    public function handle() : void
    {
        $this->getOutput()->isVerbose() ? $this->runComand( 'ports:clear' ) : $this->callSilently( 'ports:clear' );

        intro( 'Running Web Environment' );

        note( "Starting PHP Server" );

        Process::run( "npm run serve", function( string $type, string $output )
        {
            if( ! isset( $this->vite ) && Str::contains( $output, "PHP" ) )
            {
                note( "Starting Vite Development Server" );

                $this->vite = Process::forever()->run( "npm run dev:vite:web", function( string $type, string $output )
                {
                    if( Str::contains( $output, 'APP_URL' ) ) return info( $output );

                    if( $this->getOutput()->isVerbose() ) note( $output );
                } );
            }

            if( $this->getOutput()->isVerbose() ) note( $output );
        } );
    }
}