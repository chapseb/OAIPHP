<?php
namespace Admin;

use \Model\Filepath;
use \Model\ConcordUserFilePath;
use \Model\Resumptiontoken;
use \Model\Setinfos;
use \Model\Settypes;
use \Model\Deletedfiles;
use \Sentry;
use \DB;
use \App;
use \Input;
use \Response;

Class FilesController extends BaseController
{
    /**
     * List set for a user
     *
     * @return void
     */
    public function listSetByUser()
    {
        $user = Sentry::getUser();
        $this->data['listsets']
            = DB::table('set_infos')
            ->select('set_name')
            ->where('id_user', $user['id'])
            ->where('state', 'Published')
            ->get();
        $listFormatBySet = array();
        foreach ($this->data['listsets'] as $set) {
                $formatsSet = DB::table('filepaths')
                ->leftjoin(
                    'set_infos AS sinfos',
                    'filepaths.data_set',
                    '=',
                    'sinfos.set_name'
                )
                ->where('filepaths.data_set', $set)
                ->where('sinfos.id_user', '=', Sentry::getUser()['id'])
                ->distinct()
                ->select('metadata_format')
                ->get();
                array_push($listFormatBySet, $formatsSet);
        }
        $this->data['existingtype'] = array_map(
            "unserialize",
            array_unique(array_map("serialize", $listFormatBySet))
        );
        $this->data['listformats']
            = DB::table('set_types')
            ->select('name')
            ->get();
        foreach ($this->data['listformats'] as $key => $format) {
            $organization = Sentry::getUser()['organization'];
            $path = App::config('pathfile') . $organization . "/" . $format['name'];
            if (!file_exists($path)) {
                unset($this->data['listformats'][$key]);
            }
        }
        $this->data['template'] = 'admin/listsets.twig';
        App::render('admin/index.twig', $this->data);
    }

    /**
     * Get the list of format file accepted in a set
     *
     * @param string $nameSet name of a set
     *
     * @return void
     */
    public function listMetadataformat($nameSet)
    {
        $this->data['listformats']
            = DB::table('filepaths')
            ->distinct()
            ->select('metadata_format')
            ->where('data_set', $nameSet)
            ->get();
        $this->data['template'] = 'admin/listformats.twig';
        $this->data['nameset']  = $nameSet;
        App::render('admin/index.twig', $this->data);
    }

    /**
     * Get file list by format and set name
     *
     * @param string $nameSet        Name of the set
     * @param string $metadataformat Name of the format
     *
     * @return void
     */
    public function listFiles($nameSet, $metadataformat)
    {
        $this->data['listFiles'] = DB::table('filepaths')
            ->select('id', 'xml_path', 'state', 'metadata_format')
            ->where('data_set', $nameSet)
            ->where('xml_path', '!=', 'NULL')
            ->where('metadata_format', $metadataformat)
            ->get();
        $this->data['template'] = 'admin/listfiles.twig';
        $this->data['set'] = $nameSet;
        App::render('admin/index.twig', $this->data);
    }

    /**
     * Render the create set template
     *
     * @return void
     */
    public function createForm()
    {
        $this->data['template'] = 'admin/createset.twig';
        App::render('admin/index.twig', $this->data);
    }

    /**
     * Add a set for a the connected user
     *
     * @return void
     */
    public function addSet()
    {
        $testExist = \Setinfos::where(
            'set_name',
            Input::post('data_set')
        )->where(
            'id_user',
            Sentry::getUser()['id']
        );
        if (($testExist->count() == 0)) {
            $setname           = Input::post('data_set');
            $dataSet           = new \Setinfos;
            $dataSet->set_name = $setname;
            $dataSet->state    = 'Published';
            $dataSet->id_user  = Sentry::getUser()['id'];
            $dataSet->save();
            Response::redirect(
                $this->siteUrl('admin/listSet/')
            );
        } else {
            App::flash('error', 'Vous possédez déjà un set à ce nom.');
            Response::redirect(
                $this->siteUrl('admin/createSet')
            );
        }
    }

    /**
     * Render the add files template
     *
     * @param string $setname Name of the set
     * @param string $format  Type of format
     *
     * @return void
     */
    public function displayAddFiles($setname, $format)
    {
        $organization = Sentry::getUser()['organization'];
        $path = App::config('pathfile') . $organization . "/" . $format;
        $oaiDirectory = opendir($path) or die('Erreur');
        $listFiles = array();
        while ($entry = @readdir($oaiDirectory)) {
            if ($entry != '.' && $entry != '..') {
                array_push($listFiles, $entry);
            }
        }
        closedir($oaiDirectory);
        $this->data['setname']  = $setname;
        $this->data['files']    = $listFiles;
        $this->data['format']   = $format;
        $this->data['template'] = 'admin/addfiles.twig';
        App::render('admin/index.twig', $this->data);
    }


    /**
     * Render the template of possible deleting files in a set
     *
     * @param string $setname Name of the setname
     * @param string $format  Name of the format of files
     *
     * @return void
     */
    public function displayDeleteFiles($setname, $format)
    {
        $this->data['files'] = DB::table('filepaths')
            ->select('xml_path')
            ->where('data_set', $setname)
            ->where('metadata_format', $format)
            ->where('state', 'Published')
            ->get();
        $this->data['setname']  = $setname;
        $this->data['format']   = $format;
        $this->data['template'] = 'admin/deletefiles.twig';
        App::render('admin/index.twig', $this->data);
    }

    /**
     * Add files in a set
     *
     * @return void
     */
    public function addFiles()
    {
        $listExistingFiles = DB::table('filepaths')->where('xml_path', '!=', 'NULL')
                            ->lists('xml_path');
        // get form value
        $format         = Input::post('format');
        $organization   = Sentry::getUser()['organization'];
        $filesToAdd     = Input::post('list_files');
        $setname        = Input::post('setname');
        $date           = date('Y-m-d H:i:s');
        if (!empty($filesToAdd)) {
            foreach ( $filesToAdd as &$file) {
                $file = "/" . $organization . "/" . $format . "/" . $file;
            }
            $listDeletingFiles = DB::table('deletedfiles')
                ->lists('xml_path');

            $filesToReinstate = array_intersect(
                $listDeletingFiles,
                $filesToAdd
            );
            $listToModifyFiles = array_intersect(
                $filesToAdd,
                $listExistingFiles
            );
            $filesToAdd = array_diff(
                $filesToAdd,
                $listExistingFiles,
                $listDeletingFiles
            );
            if (!empty($filesToReinstate)) {
                foreach ($filesToReinstate as &$file) {
                    try {
                        $currentFile = DB::table('deletedfiles')
                        ->select()
                        ->where('xml_path', $file)
                        ->get();
                        if ($setname == $currentFile[0]['data_set']) {
                            \Filepath::where(
                                'oai_identifier',
                                $currentFile[0]['oai_identifier']
                            )->update(
                                [
                                    'xml_path' => $file,
                                    'state' => 'Published']
                            );
                            $rowToDelete = \Deletedfiles::where(
                                'oai_identifier',
                                '=',
                                $currentFile[0]['oai_identifier']
                            );
                            $rowToDelete->delete();
                        } else {
                            array_push($filesToAdd, $file);
                        }
                    } catch ( \Exception $e) {
                        App::flash('error', $e->getMessage());
                        Response::redirect(
                            $this->siteUrl(
                                'admin/displayAddFiles/' .
                                $setname . '/' . Input::post('format')
                            )
                        );
                    }
                }
            }
            if (!empty($listToModifyFiles) ) {
                foreach ( $listToModifyFiles as $modifyFile) {
                    try {
                        $xmlFile = simplexml_load_file(App::config('pathfile') . $file);
                        if ($format == 'ead' || $format == 'ape_ead') {
                            $create = $xmlFile->xpath('.//creation/date');
                            if ((string) $create[0]['normal'] > (string) $create[1]['normal']) {
                                $create = (string) $create[0]['normal'];
                            } else {
                                $create = (string) $create[1]['normal'];
                            }
                        }
                        $editFileDB = \Filepath::where(
                            'xml_path',
                            $modifyFile
                        )->first();
                        if ($editFileDB['modification_date'] < $create
                            &&  $editFileDB['data_set'] == $setname
                        ) {
                            $editFileDB->modification_date = $create;
                            $editFileDB->save();
                        } else if ($editFileDB['data_set'] != $setname) {
                            array_push($filesToAdd, $modifyFile);
                        }
                    } catch ( \Exception $e) {
                        App::flash('error', $e->getMessage());
                        Response::redirect(
                            $this->siteUrl(
                                'admin/displayAddFiles/' .
                                $setname . '/' . Input::post('format')
                            )
                        );
                    }

                }
            }

            // creation of Filepath object to save
            if (!empty($filesToAdd)) {
                foreach ( $filesToAdd as &$file ) {
                    try {
                        $xmlFile = simplexml_load_file(App::config('pathfile') . $file);
                        if ($format == 'ead' || $format == 'ape_ead') {
                            $create = $xmlFile->xpath('.//creation/date');
                            if ((string) $create[0]['normal'] > (string) $create[1]['normal']) {
                                $create = (string) $create[0]['normal'];
                            } else {
                                $create = (string) $create[1]['normal'];
                            }
                        }
                        $addFile = new \Filepath;
                        $addFile->data_set          = $setname;
                        $addFile->metadata_format   = $format;
                        $addFile->xml_path          = $file;
                        $addFile->oai_identifier    = uniqid();
                        $addFile->creation_date     = $create;
                        $addFile->modification_date = $create;
                        $addFile->state             = 'Published';
                        $addFile->save();
                    } catch ( \Exception $e) {
                        App::flash('error', $e->getMessage());
                        Response::redirect(
                            $this->siteUrl(
                                'admin/displayAddFiles/' .
                                $setname . '/' . Input::post('format')
                            )
                        );
                    }

                }
            }
            App::flash('message', 'Les fichiers ont bien été ajoutés.');
        } else {
            App::flash('message', 'vous n\'avez pas selectionné de fichiers.');
        }
        Response::redirect(
            $this->siteUrl(
                'admin/displayAddFiles/' .
                $setname . '/' . $format
            )
        );

    }

    /**
     * Delete an entiere set
     *
     * @param string $set Set to delete
     *
     * @return void
     */
    public function deleteSet($set)
    {
        $filesToDelete = DB::table('filepaths')
            ->select('xml_path')
            ->where('data_set', $set)
            ->where('state', 'Published')
            ->get();
        if (!empty($filesToDelete)) {
            foreach ( $filesToDelete as $fileToDelete ) {
                try {
                    // get the oai_identifier
                    $databaseSelect = DB::table('filepaths')
                    ->select('oai_identifier')
                    ->where('xml_path', $fileToDelete['xml_path'])
                    ->get();

                    $databaseDeleted = \Filepath::where(
                        'xml_path',
                        $fileToDelete['xml_path']
                    )->update(
                        ['xml_path' => 'NULL', 'state' => 'Removed']
                    );

                    /* creation and save of Deletedfiles object */
                    $deleteFile = new \Deletedfiles;
                    $deleteFile->xml_path = $fileToDelete['xml_path'];
                    $deleteFile->oai_identifier
                        = $databaseSelect[0]['oai_identifier'];
                    $deleteFile->save();
                } catch ( \Exception $e ) {
                    App::flash('error', $e->getMessage());
                    Response::redirect(
                        $this->siteUrl(
                            'admin/displayDeleteFiles/' .
                            $set . '/' . Input::post('format')
                        )
                    );
                }
            }
        }
        /* set is in removed state to do not display in set list */
        $deleteSet = \Setinfos::where('set_name', $set)
            ->update(['state' => 'Removed']);

        App::flash(
            'message',
            'Le set ' . $set . ' ainsi que ses fichiers ont bien été supprimés'
        );
        Response::redirect(
            $this->siteUrl('admin/listSet/')
        );
    }

    /**
     * Delete files in a set
     *
     * @param string $set Set which we delete file
     *
     * @return void
     */
    public function deleteFiles($set)
    {
        $filesToDelete = Input::post('list_files');
        if (!empty($filesToDelete)) {
            foreach ( $filesToDelete as $fileToDelete ) {
                // get the oai_identifier
                try {
                    $databaseSelect = DB::table('filepaths')
                    ->select('oai_identifier')
                    ->where('xml_path', $fileToDelete)
                    ->get();

                    // update filepaths table
                    $databaseDeleted = \Filepath::where('xml_path', $fileToDelete)
                        ->update(['xml_path' => 'NULL', 'state' => 'Removed']);

                    /* creation and save of Deletedfiles object */
                    $deleteFile                 = new \Deletedfiles;
                    $deleteFile->xml_path       = $fileToDelete;
                    $deleteFile->oai_identifier
                        = $databaseSelect[0]['oai_identifier'];
                    $deleteFile->data_set       = $set;
                    $deleteFile->save();
                } catch ( \Exception $e ) {
                    App::flash('error', $e->getMessage());
                    Response::redirect(
                        $this->siteUrl(
                            'admin/displayDeleteFiles/' .
                            $set . '/' . Input::post('format')
                        )
                    );
                }
            }
            App::flash('message', 'Les fichiers ont bien été supprimé');
            Response::redirect(
                $this->siteUrl(
                    'admin/displayDeleteFiles/' .
                    $set . '/' . Input::post('format')
                )
            );

        }
    }

    /**
     * Delete file in a set by Id
     *
     * @param string $set Set which we delete file
     *
     * @return void
     */
    public function deleteFileById($set)
    {
        $id = Input::post('id');
        if (!empty($id)) {
            try {
                $databaseSelect = DB::table('filepaths')
                ->select('*')
                ->where('id', $id)
                ->get();
                $xml_path = $databaseSelect[0]['xml_path'];
                // update filepaths table
                $databaseDeleted = \Filepath::where('id', $id)
                    ->update(['xml_path' => 'NULL', 'state' => 'Removed']);
                /* creation and save of Deletedfiles object */
                $deleteFile                 = new \Deletedfiles();
                $deleteFile->xml_path       = $xml_path;
                $deleteFile->oai_identifier
                    = $databaseSelect[0]['oai_identifier'];
                $deleteFile->data_set       = $set;
                $deleteFile->save();
            } catch ( \Exception $e ) {
                echo json_encode('error supression sql');
            }
            echo json_encode(true);
        } else {
            echo json_encode('0');
        }
    }

}
