<?php

/**
 * Sample group routing with user check in middleware
 */
Route::group(
    '/admin',
    function(){
        if(!Sentry::check()){

            if(Request::isAjax()){
                Response::headers()->set('Content-Type', 'application/json');
                Response::setBody(json_encode(
                    array(
                        'success'   => false,
                        'message'   => 'Session expired or unauthorized access.',
                        'code'      => 401
                    )
                ));
                App::stop();
            }else{
                $redirect = Request::getResourceUri();
                Response::redirect(App::urlFor('login').'?redirect='.base64_encode($redirect));
            }
        }
    },
    function() use ($app) {
        /** sample namespaced controller */
        Route::get('/', 'Admin\AdminController:index')->name('admin');

        foreach (Module::getModules() as $module) {
            $module->registerAdminRoute();
        }
    }
);

Route::get('/login', 'Admin\AdminController:login')->name('login');
Route::get('/logout', 'Admin\AdminController:logout')->name('logout');
Route::post('/login', 'Admin\AdminController:doLogin');

/** Route to documentation */
Route::get('/doc(/:page+)', 'DocController:index');

/** Route to file listing */
Route::get('/getFiles', 'FilesController:getFiles');

/** Route to list set by user*/
Route::get('/listSet/:iduser', 'FilesController:listSetByUser');

/** Route to list metadataformat by set and by user */
Route::get('/listMetadataformat/:iduser/:set', 'FilesController:listMetadataformat');

/** Route to list files by metadataformat by set and by user */
Route::get('/listFiles/:iduser/:set/:metadataformat', 'FilesController:listFiles');

foreach (Module::getModules() as $module) {
    $module->registerPublicRoute();
}

/** default routing */
Route::get('/', 'HomeController:welcome');
