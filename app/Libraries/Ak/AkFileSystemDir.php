<?php
/**
 *	dir class for handle file in disk
 * 
 *	@author		akiler <532171911@qq.com>
 *	@copyright	2013-
 *	@version	1.0
 *	@package	LIB-Ak
 *
 *	@since 2013-10-24
 */

class AkFileSystemDir
{
    /**
     * returns the directories in the path
     * if append path is set then this path will appended to the results
     *
     * @param string $path
     * @param string $appendPath
     * @return array
     */
    public static function getDirectories($path, $appendPath = false)
    {
        if (is_dir($path)) {
            $contents = scandir($path); //open directory and get contents
            if (is_array($contents)) { //it found files
                $returnDirs = false;
                foreach ($contents as $dir) {
                    //validate that this is a directory
                    if (is_dir($path . '/' . $dir) && $dir != '.' && $dir != '..' && $dir != '.svn') {
                        $returnDirs[] = $appendPath . $dir;
                    }
                }

                if ($returnDirs) {
                    return $returnDirs;
                }
            }

        }
    }

    /**
     * this is getting a little extreme i know
     * but it will help out later when we want to keep updated indexes
     * for right now, not much
     *
     * @param string $path
     */
    public static function make($path)
    {
        if(is_dir($path)) return true;

        return mkdir($path, 0755);
    }

    /**
     * adds a complete directory path
     * eg: /my/own/path
     * will create
     * >my
     * >>own
     * >>>path
     *
     * @param string $base
     * @param string $path
     */
    public static function makeRecursive($base, $path)
    {
        $pathArray = explode('/', $path);
        if (is_array($pathArray)) {
            $strPath = null;
            foreach ($pathArray as $path) {
                if (!empty($path)) {
                    $strPath .= '/' . $path;
                    if (!is_dir($base . $strPath)) {
                        if (!self::make($base . $strPath)) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
    }

    /**
     * renames a directory
     *
     * @param string $source
     * @param string $newName
     */
    public static function rename($source, $newName)
    {
        if (is_dir($source)) {
            return rename($source, $newName);
        }
    }

    /**
     * copies a directory recursively
     * if you want to move the directory then follow this with deleteRecursive()...
     * @param string $source
     * @param string $target
     */
    public static function copyRecursive( $source, $target )
    {
        if (is_dir($source)) {
            @mkdir( $target );

            $d = dir( $source );

            while (false !== ($entry = $d->read())) {
                if ( $entry == '.' || $entry == '..' ) {
                    continue;
                }

                $Entry = $source . '/' . $entry;
                if (is_dir($Entry)) {
                    continue;
                }
                copy( $Entry, $target . '/' . $entry );
            }

            $d->close();
        } else {
            copy( $source, $target );
        }
    }

    /**
     * deletes a directory 
     *
     * @param string $target
     * @param bool $only_clear	// if only clear it's childs, and keep the dir own.
     * 
     * @return bool
     */
    public static function delete($target, $recursive = false, $only_clear = false)
    {
        $exceptions=array('.','..');
        if (!$sourcedir=@opendir($target)) {
            return false;
        }
        
        while (false!==($sibling=readdir($sourcedir))) {
            if (!in_array($sibling,$exceptions)) {
                $object=str_replace('//','/',$target.'/'.$sibling);
                if (is_file($object)) {
                    @unlink($object);
                }
                
                if (is_dir($object)) {
                	@rmdir($object);
                }
            }
        }
        
        closedir($sourcedir);

        if (!$only_clear) {
        	if ($result=@rmdir($target)) {
	            return true;
	        } else {
	            return false;
	        }
        } else {
        	return true;
        }        
    }
    
    /**
     * delete all the files of the dir
     *
     * @param string $dir
     * 
     * @return bool
     */
    public static function deleteFiles($dir) {
    	$exceptions = array('.', '..');
    	
        if (!$sourcedir = @opendir($dir)) {
            return false;
        }
        
        while (false !== ($sibling = readdir($sourcedir))) {
            if (!in_array($sibling, $exceptions)) {
                $object = str_replace('//', '/', $dir.'/'.$sibling);
                if (is_file($object)) {
                    @unlink($object);
                }
            }
        }
        
        return true;
    }

    /**
     * get the path info of file path
     * instead of pathinfo() of php, to solve the problem of chinese file name
     *
     * @param $filePath
     * @return array
     */
    public static function pathInfo($filePath)
    {
        $path_parts = array();
        $path_parts ['dirname']     = rtrim(substr($filePath, 0, strrpos($filePath, '/')),"/")."/";
        $path_parts ['basename']    = ltrim(substr($filePath, strrpos($filePath, '/')),"/");
        $path_parts ['extension']   = substr(strrchr($filePath, '.'), 1);
        $path_parts ['filename']    = ltrim(substr($path_parts ['basename'], 0, strrpos($path_parts ['basename'], '.')),"/");

        return $path_parts;
    }

}