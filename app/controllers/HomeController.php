<?php


Class HomeController extends BaseController
{

    public function welcome()
    {
        $this->data['title'] ='OAI | Accueil';
        if ( ! Sentry::check() ){
            $this->data['title'] = "Bienvenue dans l'application OAI d'Anaphore";
            $this->app->render('welcome.twig', $this->data);
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
            $test=0;
            if ($this->_testemail(Input::post('email'))== 0) {
                if (strlen(Input::post('password')) >= 8 ) {
                    if (Input::post('password') == Input::post('confirm_password')) {
                        $newUser = Sentry::createUser(
                            array(
                                'email'       => Input::post('email'),
                                'password'    => Input::post('password'),
                                'first_name'  => Input::post('first_name'),
                                'last_name'   => Input::post('last_name'),
                                'organization' => Input::post('organization'),
                                'activated'   => true,
                                )
                        );
                        Response::redirect($this->siteUrl('login'));
                    } else {
                        App::flash('message', 'Password does not match.');
                        $test=1;
                    }
                } else {
                    App::flash(
                        'message',
                        'Votre mot de passe est trop court (plus de 8 charactères)'
                    );
                }
            } else {
                App::flash('message',  'Votre adresse email n\'est pas valide');
            }
            $newUser->save();
            if (!empty(Input::post('organization'))
                && !file_exists(App::config('pathfile') . Input::post('organization'))
            ) {
                mkdir(App::config('pathfile') . Input::post('organization'));
                $listFormat = DB::table('set_types')->select('name')->get();
                foreach ($listFormat as $format) {
                    mkdir(App::config('pathfile') . Input::post('organization') . '/' . $format['name'], 0755);
                }
            }
            $activationCode = $user->getActivationCode();
                Response::redirect($this->siteUrl('register'));
        } catch(\Exception $e)
        {
            App::flash('message', $e->getMessage());
        }
    }

    /**
     *  Test email
     *
     * @param string $email input email
     *
     * @return int
     */
    private function _testemail($email)
    {
        $atom   = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';   // caractères autorisés avant l'arobase
        $domain = '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)'; // caractères autorisés après l'arobase (nom de domaine)
        $regex = '/^' . $atom . '+' .   // Une ou plusieurs fois les caractères autorisés avant l'arobase
        '(\.' . $atom . '+)*' .         // Suivis par zéro point ou plus
                                        // séparés par des caractères autorisés avant l'arobase
        '@' .                           // Suivis d'un arobase
        '(' . $domain . '{1,63}\.)+' .  // Suivis par 1 à 63 caractères autorisés pour le nom de domaine
                                        // séparés par des points
        $domain . '{2,63}$/i';          // Suivi de 2 à 63 caractères autorisés pour le nom de domaine
        if (!preg_match($regex, $email)) {
            return 1;
        } else {
            return 0;
        }
    }

}
