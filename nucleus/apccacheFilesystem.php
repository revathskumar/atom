<?php
#data is identified by a key
# keys have to be unique system wide
# ood idea to namespace
# name the key by the class thats storing the data, combined with for example an id
# ttl is time to live in seconds
# $data is user data

class CacheFilesystem extends apcCacheAbstract {

    function store($key,$data,$ttl) {
            $fp = fopen($this->getFileName($key),'a+');  // Opening the file in read/write mode
            if (!$fp){
                throw new Exception('Could not write to cache');
            }
            flock($fp,LOCK_EX); // exclusive lock, will get released when the file is closed
            fseek($fp,0); // go to the start of the file
 
            ftruncate($fp,0);// truncate the file
    
            $data = serialize(array(time()+$ttl,$data));// Serializing along with the TTL, mns time to live

            if (fwrite($fp,$data)===false) {
                      throw new Exception('Could not write to cache');
            }
            fclose($fp);
    }
    //-------------------------------------------------------------------------
    // The function to fetch data returns false on failure
    function fetch($key) {
        $filename = $this->getFileName($key);
        if (!file_exists($filename)) return false;
        $fp = fopen($filename,'r');
        if (!$fp) return false;

        // Getting a shared lock
        flock($fp,LOCK_SH);
        
        $data = file_get_contents($filename);

        fclose($fp);
        
        $data = @unserialize($data);
        
        if (!$data) {
            // If unserializing somehow didn't work out, we'll delete the file
            unlink($filename);

            return false;
            }
        if (time() > $data[0]) {
            // Unlinking when the file was expired
            unlink($filename);
            return false;

              }
    return $data[1];
    }
    //------------------------------------------------------------------
    function delete( $key ) {
            $filename = $this->getFileName($key);
            if (file_exists($filename)) {
            return unlink($filename);
            
            } else {

                return false;
                }
    }
//-----------------------------------------------------------------------
    private function getFileName($key) {

        return ini_get('session.save_path') . '/ipixcache' . md5($key);
    }
}
//===========================================================================
class apcCache extends apcCacheAbstract {
    function fetch($key) {
           if( extension_loaded('apc') && apc_exists( "$key" )  ) {
                return apc_fetch($key);
                  }
            else { return false; }
    }

    function store($key,$data,$ttl) {
         if( extension_loaded('apc') && (!function_exists('zend_optimizer_version')) ){
                     return apc_store($key,$data,$ttl);
                     }
          else  { return false; }
    }
    function delete($key) {
        return apc_delete($key);
    }
   
}

?>