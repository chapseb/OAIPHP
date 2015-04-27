<?php

use \Sentry;

Class HomeController extends BaseController
{

    public function welcome()
    {
        if ( ! Sentry::check() ){
            $this->data['title'] = 'Welcome to Slim Starter Application';
            App::render('welcome.twig', $this->data);
        } else {
            Response::redirect($this->siteUrl('admin'));
        }

    }
}
