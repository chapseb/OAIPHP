<?php

namespace UserGroup\Controllers;

use \App;
use \View;
use \Menu;
use \User;
use \Input;
use \DB;
use \Sentry;
use \Request;
use \Response;
use \Exception;
use \Admin\BaseController;
use \Cartalyst\Sentry\Users\UserNotFoundException;
use \Model\Settypes;

class UserController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        Menu::get('admin_sidebar')->setActiveMenu('user');
    }

    /**
     * display list of resource
     */
    public function index($page = 1)
    {
        $user = Sentry::getUser();
        $this->data['title'] = 'Liste des utilisateurs';
        $this->data['users'][0] = $user;

        if ($user->hasAccess('admin')) {
            $this->data['permission'] = 'admin';
            $this->data['users'] = Sentry::findAllUsers();
        }

        /** load the user.js app */
        $this->loadJs('app/user.js');

        /** publish necessary js  variable */
        $this->publish('baseUrl', $this->data['baseUrl']);

        /** render the template */
        View::display('@usergroup/user/index.twig', $this->data);
    }

    /**
     * display resource with specific id
     */
    public function show($id)
    {
        if(Request::isAjax()){
            $user = null;
            $message = '';

            try{
                $user = Sentry::findUserById($id);
            }catch(Exception $e){
                $message = $e->getMessage();
            }


            Response::headers()->set('Content-Type', 'application/json');
            Response::setBody(json_encode(
                array(
                    'success'   => !is_null($user),
                    'data'      => !is_null($user) ? $user->toArray() : $user,
                    'message'   => $message,
                    'code'      => is_null($user) ? 404 : 200
                )
            ));
        }else{

        }
    }

    /**
     * show edit from resource with specific id
     */
    public function edit($id)
    {
        try{
            $user = Sentry::findUserById($id);
            //display edit form in non-ajax request
            //
            $this->data['title'] = 'Edit User';
            $this->data['user'] = $user->toArray();

            View::display('@usergroup/user/edit.twig', $this->data);
        }catch(UserNotFoundException $e){
            App::notFound();
        }catch(Exception $e){
            Response::setBody($e->getMessage());
            Response::finalize();
        }
    }

    /**
     * update resource with specific id
     */
    public function update($id)
    {
        $success = false;
        $message = '';
        $user    = null;
        $code    = 0;

        try{
            $input = Input::put();
            /** in case request come from post http form */
            $input = is_null($input) ? Input::post() : $input;

            if($input['password'] != $input['confirm_password']){
                throw new Exception("Le mot de passe et la confirmation ne correspondent pas", 1);
            }

            $user = Sentry::findUserById($id);

            $user->email        = $input['email'];
            $user->first_name   = $input['first_name'];
            $user->last_name    = $input['last_name'];
            $user->organization = $input['organization'];

            if($input['password']){
                $user->password = $input['password'];
            }

            $success = $user->save();
            $code    = 200;
            $message = 'User updated sucessully';
        }catch(UserNotFoundException $e){
            $message = $e->getMessage();
            $code    = 404;
        }catch (Exception $e){
            $message = $e->getMessage();
            $code    = 500;
        }

        if(Request::isAjax()){
            Response::headers()->set('Content-Type', 'application/json');
            Response::setBody(json_encode(
                array(
                    'success'   => $success,
                    'data'      => ($user) ? $user->toArray() : $user,
                    'message'   => $message,
                    'code'      => $code
                )
            ));
        }else{
            Response::redirect($this->siteUrl('admin/user/'.$id.'/edit'));
        }
    }

    /**
     * create new resource
     */
    public function store()
    {
        $user    = null;
        $message = '';
        $success = false;

        try{
            $input = Input::post();

            if ($input['password'] != $input['confirm_password']) {
                throw new Exception("Le mot de passe et la confirmation ne correspondent pas", 1);
            }

            $user = Sentry::createUser(
                array(
                    'email'       => $input['email'],
                    'password'    => $input['password'],
                    'first_name'  => $input['first_name'],
                    'last_name'   => $input['last_name'],
                    'organization'=> $input['organization'],
                    'activated'   => 1
                )
            );

            if (!empty($input['organization']) 
                && !file_exists(App::config('pathfile') . $input['organization'])
            ) {
                $pathToMakeDirectories = App::config('pathfile'). $input['organization'];
                mkdir($pathToMakeDirectories, 0775);
                $existingFormat = DB::table('set_types')
                    ->lists('name');
                foreach ($existingFormat as $format) {
                    mkdir($pathToMakeDirectories . "/". $format, 0775);
                }
            }

            $success = true;
            $message = 'Utilisateur crée avec succès';
        }catch (Exception $e){
            $message = $e->getMessage();
        }

        if (Request::isAjax()) {
            Response::headers()->set('Content-Type', 'application/json');
            Response::setBody(
                json_encode(
                    array(
                        'success'   => $success,
                        'data'      => ($user) ? $user->toArray() : $user,
                        'message'   => $message,
                        'code'      => $success ? 200 : 500
                    )
                )
            );
        }else{
            Response::redirect($this->siteUrl('admin/user'));
        }
    }

    /**
     * destroy resource with specific id
     */
    public function destroy($id)
    {
        $id      = (int) $id;
        $deleted = false;
        $message = '';
        $code    = 0;

        try{
            $user    = Sentry::findUserById($id);
            $deleted = $user->delete();
            $code    = 200;
        }catch(UserNotFoundException $e){
            $message = $e->getMessage();
            $code    = 404;
        }catch(Exception $e){
            $message = $e->getMessage();
            $code    = 500;
        }

        if(Request::isAjax()){
            Response::headers()->set('Content-Type', 'application/json');
            Response::setBody(json_encode(
                array(
                    'success'   => $deleted,
                    'data'      => array( 'id' => $id ),
                    'message'   => $message,
                    'code'      => $code
                )
            ));
        }else{
            Response::redirect($this->siteUrl('admin/user'));
        }
    }
}
