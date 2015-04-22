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

Class FilesController extends BaseController
{

    /**
     * Get files in a directory
     *
     * @param string $path path to the directory
     */
    /*public function getFiles()
    {
        $user = Sentry::getUser();
        $path = '/var/www/ead_files/lancelot/';
        $files['listFiles'] = array();
        if ($repo = opendir($path)) {
            while (false !== ($file = readdir($repo))) {
                array_push($files['listFiles'], $file);
            }
            asort($files);
        }
        App::render('admin/listFiles.twig', $files);
    }*/

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
         $this->data['listformats']
             = DB::table('set_types')
             ->select('name')
             ->get();
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
            ->select('xml_path', 'state')
            ->where('data_set', $nameSet)
            ->where('metadata_format', $metadataformat)
            ->get();
        $this->data['template'] = 'admin/listfiles.twig';
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
        $setname           = Input::post('data_set');
        $dataSet           = new \Setinfos;
        $dataSet->set_name = $setname;
        $dataSet->state    = 'Published';
        $dataSet->id_user  = Sentry::getUser()['id'];
        $dataSet->save();
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
        $listExistingFiles = DB::table('filepaths')
                            ->lists('xml_path');
        // get form value
        $format         = Input::post('format');
        $organization   = Sentry::getUser()['organization'];
        $filesToAdd     = Input::post('list_files');
        $setname        = Input::post('setname');
        $date           = date('Y-m-d H:i:s');
        foreach ( $filesToAdd as &$file) {
            $file = "/" . $organization . "/" . $format . "/" . $file;
        }
        $listDeletingFiles = DB::table('deletedfiles')
            ->lists('xml_path');

        $filesToReinstate = array_intersect(
            $listDeletingFiles,
            $filesToAdd
        );

        $filesToAdd = array_diff(
            $filesToAdd,
            $listExistingFiles,
            $listDeletingFiles
        );

        foreach ($filesToReinstate as &$file) {
            $currentFile = DB::table('deletedfiles')
            ->select()
            ->where('xml_path', $file)
            ->get();
            if ($setname == $currentFile[0]['data_set']) {
                \Filepath::where('oai_identifier', $currentFile[0]['oai_identifier'])
                    ->update([
                        'xml_path' => $file,
                        'state' => 'Published']
                    );
                $rowToDelete = \Deletedfiles::where('oai_identifier', '=', $currentFile[0]['oai_identifier']);
                $rowToDelete->delete();
            } else {
                 array_push($file, $filesToAdd);
            }
        }

        // creation of Filepath object to save
        foreach ( $filesToAdd as &$file ) {
            $xmlFile = simplexml_load_file(App::config('pathfile') . $file);
            $create = $xmlFile->xpath('.//creation//date[@normal]');
            print_r($create);
            break;
            $addFile = new \Filepath;
            $addFile->data_set          = $setname;
            $addFile->metadata_format   = $format;
            $addFile->xml_path          = $file;
            $addFile->oai_identifier    = uniqid();
            $addFile->creation_date     = $date;
            $addFile->modification_date = $date;
            $addFile->state             = 'Published';
            $addFile->save();
        }

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
        foreach ( $filesToDelete as $fileToDelete ) {
            // get the oai_identifier
            $databaseSelect = DB::table('filepaths')
            ->select('oai_identifier')
            ->where('xml_path', $fileToDelete['xml_path'])
            ->get();

            $databaseDeleted = \Filepath::where(
                'xml_path', $fileToDelete['xml_path']
            )->update([
                'xml_path' => 'NULL',
                'state' => 'Removed']
            );

            /* creation and save of Deletedfiles object */
            $deleteFile = new \Deletedfiles;
            $deleteFile->xml_path = $fileToDelete['xml_path'];
            $deleteFile->oai_identifier = $databaseSelect[0]['oai_identifier'];
            $deleteFile->save();
        }
        /* set is in removed state to do not display in set list */
        $deleteSet = \Setinfos::where('set_name', $set)
            ->update(['state' => 'Removed']);
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
        foreach ( $filesToDelete as $fileToDelete ) {
            // get the oai_identifier
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
            $deleteFile->oai_identifier = $databaseSelect[0]['oai_identifier'];
            $deleteFile->data_set       = $set;
            $deleteFile->save();
        }
    }
}
