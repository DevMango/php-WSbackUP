<?php
/**
 * WSbackUP base class
 *
 * @version 1.0.4
 * @package WSbackUP
 * @author Andreas Baimler (WSbackUP@semango.de)
 * @copyright (C) 2015 SEMango eSolutions (http://www.semango.de)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 * @see https://github.com/DevMango/php-WSbackUP
 */
class WSbackUP{

	/**
    * Message which will be sent
    * @var string
    */
  	private $msg;

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
    
    public function addIgnore(){
        $args = func_get_args();

		foreach($args as $arg){
			$this->ignore[] = $arg;
		}
    }
	
    public function removeIgnore($ignore){
        if(($key = array_search($ignore, $this->ignore)) !== false) {
			unset($this->ignore[$key]);
		}
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
    * amount of backup fiiles which should be keeped
    * @var int
    */
    private $amBackFiles;
    
    public function setAmBackFiles($amountFiles){
        $this->amBackFiles = $amountFiles;
    }
    
    public function getAmBackFiles(){
        return $this->amBackFiles;
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
        $this->ignore = array("*.gz", "*.tar", "usage", "logs", "WSbackUP");
        $this->fullPath = $storePath.$this->fName;
        $this->tar = true;
        $this->zip = true;
        $this->keepTAR = false;
        $this->amBackFiles = 4;
        $this->sendMail = false;
    }
    
   /**
    * Start the backup process
    */
    public function start(){
        if($this->tar) $this->createTAR();
        if($this->zip) $this->createZIP();
        if(!$this->keepTAR) $this->unlinkTAR();
        if($this->amBackFiles != 0) $this->clean();
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
        $this->msg .= "TAR-File created"."<br>";
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
                $this->msg .= "ZIP-File created"."<br>";
                
                return $zip->close();
            }
        }
    }
    
   /**
    * Deletes the TAR archive
    */
    private function unlinkTAR(){
        unlink($this->fullPath.".tar.gz");
        $this->msg .= "TAR-File unlinked"."<br>";
    }
    	
    /**
    * Deletes backup files if there are more then the size of $amBackFiles
    */
    private function clean(){
        $backFiles = scandir($this->destination);
		$backFiles = array_reverse($backFiles);
		array_pop($backFiles);
		array_pop($backFiles);
		
		while(sizeof($backFiles) > $this->amBackFiles){
			unlink($this->destination."/".$backFiles[sizeof($backFiles)-1]);
			$this->msg .= $this->destination."/".$backFiles[sizeof($backFiles)-1]." unlinked"."<br>";
			array_pop($backFiles);
		}
    }
    
    /**
    * Send notification Mail
    */
    private function sendMail(){
        if(isset($this->eMailAddress)){
        	$header  = "MIME-Version: 1.0\r\n";
		$header .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
		$header .= "From: ".$_SERVER["SERVER_ADMIN"]."\r\n";
		$header .= "Reply-To: ".$_SERVER["SERVER_ADMIN"]."\r\n";
		
		$mailSubject = "[SUCCESSFUL] WSbackUP von ".$_SERVER["SERVER_NAME"]." ".date('d-M-Y');
		$cHead = '<!DOCTYPE html><html><head><meta charset="UTF-8" /><title>'.$mailSubject.'</title><meta name="copyright" content="Copyright &copy; SEMango eSolutions" /><meta name="author" content="Andreas Baimler" /><meta name="generator" content="WSbackUP" /><meta http-equiv="cache-control" content="no-cache" /><meta http-equiv="pragma" content="no-cache" /><meta name="date" content="'.date("Y-m-d:h:s").'2015-09-14T11:10:03+00:00" /></head>';
		$cBody = '<body style="margin:0;padding:3px;font-family:monospace;font-size:12px;line-height:15px;background-color:#000;color:#fff;white-space:nowrap;">';
		$cContent = $this->msg.'<br>Backup Successful';
		$cFooter = '</body></html>';
		
		$mailText = $cHead.$cBody.$cContent.$cFooter;
		
		@mail($this->eMailAddress, $mailSubject, $mailText, "From: ".$header);            
        }else{
            die("no email adress assigned");
        }
    }
}
?>
