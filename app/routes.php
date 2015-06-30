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

        /** Route to list metadataformat by set and by user */
        Route::get('/listMetadataformat/:set', 'Admin\FilesController:listMetadataformat')->name('listmetadataformat');

        /** Route to list set by user*/
        Route::get('/listSet', 'Admin\FilesController:listSetByUser')->name('listset');

        /** Route to list files by metadataformat by set and by user */
        Route::get('/listFiles/:set/:metadataformat', 'Admin\FilesController:listFiles')->name('listfile');

        /** Route to display a form to create a set*/
        Route::get('/createSet', 'Admin\FilesController:createForm')->name('createform');

        Route::post('/addSet', 'Admin\FilesController:addSet')->name('addset');

        Route::get('/displayAddFiles/:set/:format', 'Admin\FilesController:displayAddFiles')->name('displayaddfiles');

        Route::get('/displayUploadsAddFiles/:set/:format', 'Admin\FilesController:displayUploadsAddFiles')->name('displayuploadsaddfiles');

        Route::get('/displayDeleteFiles/:set/:format', 'Admin\FilesController:displayDeleteFiles')->name('displaydeletefiles');

        Route::post('/addFiles', 'Admin\FilesController:addFiles')->name('addfiles');

        //Route::get('/uploadsFiles', 'Admin\FilesController:uploadsFiles')->name('uploadsfiles');
        Route::post('/uploadsFiles', 'Admin\FilesController:uploadsFiles')->name('uploadsfiles');

        Route::get('/deleteSet/:set', 'Admin\FilesController:deleteSet')->name('deleteset');

        Route::post('/deleteFile/:set', 'Admin\FilesController:deleteFiles')->name('deletesetfiles');

        Route::post('/deleteFileById/:set', 'Admin\FilesController:deleteFileById')->name('deletefilebyid');

        foreach (Module::getModules() as $module) {
            $module->registerAdminRoute();
        }
    }
);

Route::get('/login', 'Admin\AdminController:login')->name('login');
Route::get('/logout', 'Admin\AdminController:logout')->name('logout');
Route::post('/login', 'Admin\AdminController:doLogin');

/** Route to documentation */
Route::get('/doc(/:page+)', 'DocController:index')->name('documentation');


foreach (Module::getModules() as $module) {
    $module->registerPublicRoute();
}

/** default routing */
Route::get('/', 'HomeController:welcome');
Route::get('/register', 'HomeController:register')->name('formRegister');
Route::post('/inscription', 'HomeController:doRegister')->name('doRegister');
