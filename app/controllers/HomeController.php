<?php


Class HomeController extends BaseController
{

    public function welcome()
    {
        if ( ! Sentry::check() ){
            $this->data['title'] = "Bienvenue dans l'application OAI d'Anaphore";
            App::render('welcome.twig', $this->data);
        } else {
            Response::redirect($this->siteUrl('admin'));
        }

    }
}
