<?php

namespace App\Exceptions;

use Exception;

class SlotAlreadyBookedException extends Exception
{
    /**
     * Report the exception.
     */
    public function report(): void
    {
        //
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        return back()->with('error', 'Slot jadwal yang Anda pilih sudah di-booking oleh klien lain. Silakan pilih jadwal alternatif.');
    }
}
