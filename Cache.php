<?php

namespace SWD;


class Cache
{
    private $cacheDirectory;
    private $cacheLifeInSeconds;

    public $success;

    function __construct($cachLifeInSeconds = 300, $subfolder=null, $cacheDirectoryOverride = null)
    {
        $this->cacheDirectory = ( $cacheDirectoryOverride !== null  ) ?
            $cacheDirectoryOverride :
            str_replace('Cache.php', '', __FILE__).'cache/';

        $this->cacheLifeInSeconds = (is_int($cachLifeInSeconds)?$cachLifeInSeconds:null);
        if ($this->cacheLifeInSeconds === null){throw new \Exception('Cache life must be set to an integer in \SWD\Cache');}

        $this->construct_folder_path_as_necessary($this->cacheDirectory);


        if ($subfolder !== null){
            $this->concat_subfolder_to_cachedirectory($subfolder);
        }

        $this->construct_folder_path_as_necessary($this->cacheDirectory);


    }

    private function concat_subfolder_to_cachedirectory($subfolder){

        if(is_string($subfolder)==false){
            throw new \Exception('subfolder definition passed to construct call in \SWD\Cache must be a string');
        }

        // allows for the user to pass 'mySubfolder' or 'mySubfolder/' <-- eliminates potential bug!!!
        if(strpos($subfolder, '/') === false){
            $subfolder = $subfolder.'/';
        }

        $this->cacheDirectory = $this->cacheDirectory.$subfolder;
    }


    public function store($keyedArrayToStore = array()){

        if(
            is_array($keyedArrayToStore)
        ){
            foreach ($keyedArrayToStore as $filename => $item) {
                $datastring = serialize($item);
                $this->add_cachedTime_to_File($datastring);
                $this->store_set($filename, $datastring);
            }
        }

        $this->success = ($this->success === null || $this->success === true )? true : false ;

    }

    private function store_set($filename, $datastring){

        if(
            is_string($filename) == false
        ){
            $this->success = false;throw new \Exception('Filename must be a string in call to \SWD\Cache');
        }
        $filename = preg_replace("/([^a-zA-Z0-9])/", "", $filename);


        if (
            (file_exists($this->cacheDirectory.$filename) && false == is_writable($this->cacheDirectory.$filename))
        ){
            throw new \Exception('Cached file already exists, and is not overwriteable my call to \SWD\Cache');
        }

        $success = file_put_contents($this->cacheDirectory . $filename, $datastring);

        $this->success = $success;


    }

    private function add_cachedTime_to_File(&$file){
        $now = new \DateTime();
        $file = $now->format('Y-m-d H:i:s').'_'.$file;
    }

    private static function get_cachedTime_from_file_string($string){


        $arrayOfFilenameParts = str_split($string, 19);
        $dateString = $arrayOfFilenameParts[0];
        $dateTimeObject = new \DateTime($dateString);

        return $dateTimeObject;

    }

    private function remove_cachedTime_from_file_string(&$filestring){
        $dateTimeObject = self::get_cachedTime_from_file_string($filestring);
        $removeThis = $dateTimeObject->format('Y-m-d H:i:s_');
        $filestring = str_replace($removeThis, '', $filestring);
    }

    private function cache_has_expired($filestring){

        $cachedDateTimeObject = self::get_cachedTime_from_file_string($filestring);
        $cachedDateTimeObject->modify('+'.$this->cacheLifeInSeconds.' seconds');
        $now = new \DateTime();

        if ($cachedDateTimeObject > $now){ //cachetime + cachelife is still in the future
            return false;
        }

        return true;

    }

    public function __get($filename){
        if (!is_string($filename)){throw new \Exception('\SWD\Cache->property must be called by a string ');}
        $filename = preg_replace("/([^a-zA-Z0-9])/", "", $filename);

        $filepathAndName = $this->cacheDirectory.$filename;

        if( is_dir($this->cacheDirectory) != true ){
            throw new \Exception(' The cache directory is not available. Please check call to \SWD\Cache');
        }

        if (file_exists($filepathAndName) != true){ return false;}
        if (
            file_exists($filepathAndName)
            && is_readable($filepathAndName) != true
        ){
             throw new \Exception(' The cached file exists, but is unreadable. Please check call to \SWD\Cache');
        }

        $filestring = file_get_contents($filepathAndName);

        if( $filestring === false ){ throw new \Exception('The cached file is unreadable. Please check call to \SWD\Cache ');}

        if ($this->cache_has_expired($filestring)){
            unlink($filestring);
        }
        $this->remove_cachedTime_from_file_string($filestring);
        return unserialize($filestring);

    }
    
    public function get($filename){
        return $this->$filename;
    }
    
    private function construct_folder_path_as_necessary($path){

        if (is_dir($path)== false){
            $success = mkdir($path);
            if (! $success){throw new \Exception('Unable to create specified cache directory in call to \SWD\Render');}
        }

    }
    
}
