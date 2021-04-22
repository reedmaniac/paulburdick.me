<?php


namespace App\Http\Controllers;

use Mail;
use Validator;
use Illuminate\Http\Request;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    /**
     *  Load the Homepage
     *
     *  @return View
     */

    public function homepage()
    {
        return view('homepage');
    }

    /**
     *  Load the Now Page
     *
     *  @return View
     */

    public function now()
    {
        return view('now');
    }
}
