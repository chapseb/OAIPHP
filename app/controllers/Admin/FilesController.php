<?php

namespace Admin;

use \Model\Filepath;
use \Model\ConcordUserFilePath;
use \Model\Resumptiontoken;
use \Model\Setinfos;
use \Model\Settypes;
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
    public function getFiles()
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
    }

    /**
     * 
     *
     */
    public function listSetByUser()
    {
        $user = Sentry::getUser();
        $this->data['listsets']
            = DB::table('set_infos')
            ->select('set_name')
            ->where('id_user', $user['id'])
            ->get();
         $this->data['listformats']
             = DB::table('set_types')
             ->select('name')
             ->get();
        $this->data['template'] = 'admin/listsets.twig';
        App::render('admin/index.twig', $this->data);
    }

    /**
     *
     *
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
     *
     *
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
     *
     *
     */
    public function createForm()
    {
        $this->data['template'] = 'admin/createform.twig';
        App::render('admin/index.twig', $this->data);
    }

    /**
     *
     *
     *
     */
    public function addSet()
    {
        $setname  = Input::post('data_set');
        $dataSet  = new \Setinfos;
        $dataSet->set_name = $setname;
        $dataSet->id_user  = Sentry::getUser()['id'];
        $dataSet->save();
    }

    public function displayAddFiles($setname, $format)
    {
        $path = "/var/www/ead_files" . "". "/oai";
        $oaiDirectory = opendir($path) or die('Erreur');
        $listFiles = array();
        while($entry = @readdir($oaiDirectory)) {
            if( $entry != '.' && $entry != '..' ) {
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

    public function addFiles()
    {
        $filesToAdd = Input::post('list_files');
        print_r($filesToAdd);
    }

}
