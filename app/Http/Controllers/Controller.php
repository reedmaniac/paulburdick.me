<?php namespace App\Http\Controllers;

use Mail;
use Validator;
use Illuminate\Http\Request;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class Controller extends BaseController
{
    /**
     *  Load the Homepage
     *
     *  @return View
     */

    function homepage()
    {
        return view('homepage', ['token' => csrf_token()]);
    }

    /**
     *  Load the Now Page
     *
     *  @return View
     */

    function now()
    {
        return view('now');
    }

    /**
     *  Load the Blog Design
     *
     *  @return View
     */

    function blog()
    {
        return view('blog');
    }

    /**
     *  Process Contact Form
     *
     *  @return bool
     */

    function postContact(Request $request)
    {
        // Tempting to put some manner of throttling in here,
        // but the odds are low so unnecessary for now.

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'max:30',
            'message' => 'required|max:1000'
        ]);

        if ($validator->fails())
        {
            return \Response::json($validator->errors()->all(), 400);
        }

        $params = [
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'content' => $request->input('message'),
        ];

        $this->sendContactEmail($params);

        return \Response::json([], 200);        
    }

    /**
     *  Send contact email
     *
     *  @param  Mailer  $mailer
     *  @param  array   $params
     *  @return void
     */
    private function sendContactEmail($params)
    {
        Mail::send('emails.contact', $params, function ($m) use ($params) {
            $m->to('paul@reedmaniac.com', 'Paul Burdick');
            $m->from($params['email'], $params['name']);
            $m->subject("Website Contact Form: ".$params['name']);
        });
    }
}
