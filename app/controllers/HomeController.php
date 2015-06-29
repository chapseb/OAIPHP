<?php


use \Sentry;

Class HomeController extends BaseController
{

    public function welcome()
    {
        $this->data['title'] ='OAI | Accueil';
        if ( ! Sentry::check() ){
            $this->data['title'] = "Bienvenue dans l'application OAI d'Anaphore";
            App::render('welcome.twig', $this->data);
        } else {
            Response::redirect($this->siteUrl('admin'));
        }
    }



    /**
     * Redirect to the register view
     *
     * @return void
     **/
    public function register()
    {
        $this->data['title'] ='OAI | Inscription';
        $this->data['template'] = 'home/form.twig';
        App::render('welcome.twig', $this->data);
    }


    /**
     * Seed the database with initial value
     *
     * @return void
     */
    public function doRegister()
    {
        try{
            print_r(Input::post());
            /*if ($error) {
                App::flash('email', $e->getMessage());
                App::flash('firstName', $email);
                App::flash('name', $email);
                App::flash('organization', $redirect);
                App::flash('password', $remember);
                App::flash('confirmation', $remember);
                Response::redirect($this->siteUrl('login'));
            }*/
            $newUser = Sentry::createUser(
                array(
                    'email'       => Input::post('email'),
                    'password'    => Input::post('password'),
                    'first_name'  => Input::post('first_name'),
                    'last_name'   => Input::post('name'),
                    'organization' => Input::post('organization'),
                    'activated'   => true,
                )
            );
            $newUser->save();
        }catch(\Exception $e){
            App::flash('message', $e->getMessage());
        }
    }

    /**
     *
     */
    public function testInput($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

}
