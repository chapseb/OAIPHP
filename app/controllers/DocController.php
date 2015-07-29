<?php

class DocController extends BaseController
{

    public function index($page=array())
    {
        if (!$page) {
            $this->data['title'] = "Le protocole OAI-PMH";
            App::render('docs/index.twig', $this->data);
        } else {
            $page = implode('/', $page);
            if ($page == 'informations') {
                $this->data['title'] = 'Informations techniques';
            } else if ($page == 'documentation') {
                $this->data['title'] = "Foire aux questions";
            }
            else {
                $this->data['title'] = ucfirst($page);
            }
            $page = 'docs/'. $page.'.twig';
            App::render($page, $this->data);
        }
    }
}
