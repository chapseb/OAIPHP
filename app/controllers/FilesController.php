<?php

use \Filepath;


Class FilesController extends BaseController
{

    /**
     * Get files in a directory
     *
     * @param string $path path to the directory
     */
    public function getFiles()
    {
        $filePaths = Filepath::all();
        print_r($filePaths);
        $resumptionTokens = Resumptiontoken::all();
        print_r($resumptionTokens);
        $path = '/var/www/ead_files/lancelot/';
        $files['listFiles'] = array();
        if ($repo = opendir($path)) {
            while (false !== ($file = readdir($repo))) {
                array_push($files['listFiles'], $file);
            }
            asort($files);
        }
        App::render('listFiles.twig', $files);
    }

    /**
     * 
     *
     */
    public function listSetByUser($idUser)
    {
        $this->data['listsets']
            = DB::table('concord_user_filepath')
            ->distinct()
            ->select('data_set')
            ->where('id_user', $idUser)
            ->get();
        App::render('listsets.twig', $this->data);
    }

    /**
     *
     *
     */
    public function listMetadataformat($idUser, $nameSet)
    {
        $this->data['listformats']
            = DB::table('filepaths')
                ->distinct()
                ->select('metadata_format')
                ->where('data_set', $nameSet)
                ->get();
        App::render('listformats.twig', $this->data);
    }

    /**
     *
     *
     */
    public function listFiles($idUser, $nameSet, $metadataformat)
    {
        $this->data['listFiles'] = DB::table('filepaths')
            ->select('xml_path', 'state')
            ->where('data_set', $nameSet)
            ->where('metadata_format', $metadataformat)
            ->get();
        App::render('listfiles.twig', $this->data);
    }
}
