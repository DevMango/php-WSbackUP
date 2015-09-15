<?php
/**
 * WSbackUP base class
 *
 * @version 1.0.0
 * @package WSbackUP
 * @author Andreas Baimler (WSbackUP@semango.de)
 * @copyright (C) 2015 SEMango eSolutions (http://www.semango.de)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 * @see https://github.com/DevMango/php-WSbackUP
 */
class WSbackUP{

   /**
    * Node where backup should start
    * @var string
    */
    private $source;
    
    public function getSource(){
        return $this->source;
    }

   /**
    * Path where the backup file will be stored
    * @var string
    */
    private $destination;
    
    public function getDestination(){
        return $this->destination;
    }

   /**
    * Backup file name
    * @var string
    */
    private $fName;
    
    public function getFName(){
        return $this->fName;
    }

   /**
    * Path to PEAR TAR class
    * @var string
    */
    private $tarPath;
    
    public function getTARPath(){
        return $this->tarPath;
    }
    
   /**
    * Full path where backups are stored
    * @var string
    */
    private $fullPath;
    
    public function getFullPath(){
        return $this->fullPath;
    }
    
   /**
    * Root path
    * @var string
    */
    private $root;
    
    public function getRoot(){
        return $this->root;
    }

   /**
    * Array with file names or extensions which will be ignored 
    * @var string array
    */
    private $ignore;
    
    public function setIgnore($ignore){
        $this->ignore = $ignore;
    }
    
    public function getIgnore(){
        return $this->ignore;
    }
    
   /**
    * If true tar file will be created
    * @var bool
    */
    private $tar;
    
    public function setTAR(){
        ($this->tar) ? $this->tar = false : $this->tar = true;
    }
    
    public function getTAR(){
        return $this->tar;
    }
    
   /**
    * If true zip file will be created
    * @var string
    */
    private $zip;
    
    public function setZIP(){
        ($this->zip) ? $this->zip = false : $this->zip = true;
    }
    
    public function getZIP(){
        return $this->zip;
    }
    
   /**
    * TAR archive file
    * @var Archive_Tar
    */
    private $archiveTAR;
    
    public function getTARFile(){
        return $this->tar;
    }
    
   /**
    * ZIP archive file
    * @var ZipArchive
    */
    private $archiveZIP;
    
    public function getZIPFile(){
        return $this->zip;
    }
    
   /**
    * If false TAR file will be deleted
    * @var bool
    */
    private $keepTAR;
    
    public function setKeepTAR(){
        ($this->keepTAR) ? $this->keepTAR = false : $this->keepTAR = true;
    }
    
    public function getKeepTAR(){
        return $this->keepTAR;
    }

   /**
    * If true a notification mail will be send
    * @var bool
    */
    private $sendMail;
    
    public function setSendMail(){
        ($this->sendMail) ? $this->sendMail = false : $this->sendMail = true;
    }
    
    public function getSendMail(){
        return $this->sendMail;
    }
    
   /**
    * E-Mail address who will recieve the notification mail
    * @var string
    */
    private $eMailAddress;
    
    public function setMailAddress($eMail){
        $this->eMailAddress = $eMail;
    }
    
    public function getMailAddress(){
        return $this->eMailAddress;
    }
    
   /**
    * Initialize settings
    *
    * @param type string $startNode
    * @param type string $storeNode
    * @param type string $fileName
    */
    function __construct($startNode, $storePath, $fileName){
        $this->root = $_SERVER['DOCUMENT_ROOT'];
        $this->source = realpath($startNode);
        $this->destination = realpath($storePath);
        $this->fName = date('Y-m-d_His-').$fileName;
        $this->tarPath = "/libs/tar.php";
        $this->ignore = array("*.gz", "*.tar", "usage", "logs");
        $this->fullPath = $storePath.$this->fName;
        $this->tar = true;
        $this->zip = true;
        $this->keepTAR = false;
        $this->sendMail = false;
    }
    
   /**
    * Start the backup process
    */
    public function start(){
        if($this->tar) $this->createTAR();
        if($this->zip) $this->createZIP();
        if(!$this->keepTAR) $this->unlinkTAR();
        if($this->sendMail) $this->sendMail();
    }

   /**
    * Creates the TAR archive
    */
    private function createTAR(){
        include $this->tarPath;

        $archiv = new Archive_Tar($this->fullPath.".tar.gz", true);
        $archiv->setIgnoreList($this->ignore);
        $this->archiveTAR = $archiv->createModify($this->source, "", preg_replace('/(\/www\/htdocs\/\w+\/).*/', '$1', $this->destination));
    }
    
   /**
    * Creates the TAR archive
    */
    private function createZIP(){
        if (extension_loaded('zip')) {
            if (file_exists($this->source)) {
                $zip = new ZipArchive();
                if ($zip->open($this->fullPath.".zip", ZIPARCHIVE::CREATE)) {
                    if (!$this->tar && is_dir($source)) {
                        $iterator = new RecursiveDirectoryIterator($this->source);
                        $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
                        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
                        foreach ($files as $file) {
                            $file = realpath($file);
                            if (is_dir($file)) {
                                $zip->addEmptyDir(str_replace($this->source . '/', '', $file . '/'));
                            } else if (is_file($file)) {
                                $zip->addFromString(str_replace($this->source . '/', '', $file), file_get_contents($file));
                            }
                        }
                    } else if (!$this->tar && is_file($this->source)) {
                        $zip->addFromString(basename($this->source), file_get_contents($this->source));
                    } else if ($this->tar) {
                        $zip->addFromString(basename($this->fullPath.".tar.gz"), file_get_contents($this->fullPath.".tar.gz"));
                    }
                }
                return $zip->close();
            }
        }
    }
    
   /**
    * Deletes the TAR archive
    */
    private function unlinkTAR(){
        unlink($this->fullPath.".tar.gz");
    }
    
    /**
    * Send notification Mail
    */
    private function sendMail(){
        if(isset($this->eMailAddress)){
			$mailSubject = "[SUCCESSFUL] WSbackUP von ".$_SERVER["SERVER_NAME"]." ".date('d-M-Y');
			$mailText = "Backup Successful";
			
			@mail($this->eMailAddress, $mailSubject, $mailText, "From: ".$_SERVER["SERVER_ADMIN"]);            
        }else{
            die("no email adress assigned");
        }
    }
}
?>
