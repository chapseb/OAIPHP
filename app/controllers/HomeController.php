<?php


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
     *
     **/
    public function register()
    {
        $this->data['title'] ='OAI | Inscription';
        $this->data['template'] = 'home/form.twig';
        App::render('welcome.twig', $this->data);
    }


    /**
     * seed the database with initial value
     */
    public function doRegister()
    {
        try{
            print_r(Input::post());
            if ($error) {
                App::flash('email', $e->getMessage());
                App::flash('firstName', $email);
                App::flash('name', $email);
                App::flash('organization', $redirect);
                App::flash('password', $remember);
                App::flash('confirmation', $remember);
                Response::redirect($this->siteUrl('login'));
            }
            $format = Input::post('format');
            $organization = ['organization'];
            $filesToAdd = Input::post('list_files');
            $setname = Input::post('setname');
            $date = date('Y-m-d H:i:s');
            Sentry::createUser(
                array(
                'email'       => 'admin@admin.com',
                'password'    => 'password',
                'first_name'  => 'Website',
                'last_name'   => 'Administrator',
                'activated'   => 1,
                'permissions' => array
                (
                    'admin'     => 1
                )
            ));
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
