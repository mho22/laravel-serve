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
        $this->getOutput()->isVerbose() ? $this->call( 'ports:clear' ) : $this->callSilently( 'ports:clear' );

        intro( 'Running Web Environment' );

        note( "Starting PHP Server" );

        Process::run( "php artisan serve --port=2222", function( string $type, string $output )
        {
            if( ! isset( $this->vite ) && Str::contains( $output, "2222" ) )
            {
                note( "Starting Vite Development Server" );

                $this->vite = Process::forever()->run( "npm run dev:vite:web", function( string $type, string $output )
                {
                    if( Str::contains( $output, 'APP_URL' ) ) return info( $output );

                    if( $type == 'err' || $this->getOutput()->isVerbose() ) note( $output );
                } );
            }

            if( $type == 'err' || $this->getOutput()->isVerbose() ) note( $output );
        } );
    }
}
