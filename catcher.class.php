<?php
/**
 * @package PHPClassCollection
 * @subpackage Catcher
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 */
/**
 * @package PHPClassCollection
 * @subpackage Catcher
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 * @version 1.1
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 */
class catcher
{
	/**
	 * Decide if exceptions are handled.
	 *
	 * @var bool
	 */
	private $exceptions;
	/**
	 * Decide if errors are handled.
	 *
	 * @var bool
	 */
	private $errors;
	/**
	 * Decide if the class should stop the script on fatal errors.
	 *
	 * @var bool
	 */
	private $dieonfatal;
	/**
	 * Decide if the class should resume the script on recoverable errors.
	 *
	 * @var bool
	 */
	private $recovery;
	/**
	 * Decide if the class should stop the script on unknown errors.
	 *
	 * @var bool
	 */
	private $dieonunknown;
	/**
	 * Decide if message should be printed.
	 *
	 * @var bool
	 */
	private $output=true;
	/**
	 * Decide if output should be HTML.
	 *
	 * @var bool
	 */
	private $outputhtml=true;
	/**
	 * The recipient of error-mails.
	 *
	 * @var mixed
	 */
	private $mailto=false;
	/**
	 * The sender of error-mails.
	 *
	 * @var mixed
	 */
	private $mailfrom=false;
	/**
	 * The subject of error-mails.
	 *
	 * @var mixed
	 */
	private $mailsubject=false;
	/**
	 * The log-file for errors.
	 *
	 * @var mixed
	 */
	private $logfile=false;
	/**
	 * Decide if error-reports should include a backtrace.
	 *
	 * @var bool
	 */
	private $backtrace=false;
	/**
	 * Information on the last error.
	 *
	 * @var array
	 */
	private $lasterror;
	/**
	 * Information on the last exception.
	 *
	 * @var array
	 */
	private $lastexception;

	/**
	 * Constructor
	 *
	 * @param bool $exceptions
	 * @param bool $errors
	 * @param bool $dieonfatal
	 * @param bool $recovery
	 * @param bool $dieonunknown;
	 */
	public function __construct($exceptions=true,$errors=true,$dieonfatal=true,$recovery=false,$dieonunknown=true)
	{
		$this->exceptions=$exceptions;
		$this->errors=$errors;
		$this->dieonfatal=$dieonfatal;
		$this->recovery=$recovery;
		$this->dieonunknown=$dieonunknown;
		if ($this->exceptions===true)
		{
			set_exception_handler(array($this,'catchexception'));
		}
		if ($this->errors===true)
		{
			set_error_handler(array($this,'catcherror'));
		}
	}

	/**
	 * Destructor
	 *
	 */
	public function __destruct()
	{
		if ($this->exceptions===true)
		{
			restore_exception_handler();
		}
		if ($this->errors===true)
		{
			restore_error_handler();
		}
	}

	/**
	 * Set backtrace-parameters.
	 *
	 * @param bool $backtrace
	 */
	public function setbacktrace($backtrace)
	{
		$this->backtrace=$backtrace;
	}

	/**
	 * Set output-parameters.
	 *
	 * @param bool $output
	 * @param bool $outputhtml
	 */
	public function setoutput($output,$outputhtml=true)
	{
		$this->output=$output;
		$this->outputhtml=$outputhtml;
	}

	/**
	 * Set mail-parameters.
	 *
	 * @param mixed $mailto
	 * @param mixed $mailfrom
	 * @param mixed $mailsubject
	 */
	public function setmail($mailto,$mailfrom,$mailsubject)
	{
		$this->mailto=$mailto;
		$this->mailfrom=$mailfrom;
		$this->mailsubject=$mailsubject;
	}

	/**
	 * Set log-parameters.
	 *
	 * @param mixed $logfile
	 */
	public function setlogfile($logfile)
	{
		$this->logfile=$logfile;
	}

	/**
	 * Catch an exception.
	 *
	 * @param Exception $exception
	 */
	public function catchexception($exception)
	{
		$message='Catcher (Exception): '.$exception->getMessage().' (File: '.$exception->getFile().' - Line: '.$exception->getLine().')';
		$this->lastexception=array('message'=>$exception->getMessage(),'file'=>$exception->getFile(),'line'=>$exception->getLine());
		$this->outputmessage($message);
		$this->mailmessage($message);
		$this->logmessage($message);
	}

	/**
	 * Catch an error.
	 *
	 * @param int $errornr
	 * @param string $errortext
	 * @param string $errorfile
	 * @param int $errorline
	 * @param array $errorcontext
	 */
	public function catcherror($errornr,$errortext,$errorfile,$errorline,$errorcontext)
	{
		$this->lasterror=array('errornr'=>$errornr,'errortext'=>$errortext,'errorfile'=>$errorfile,'errorline'=>$errorline,'errorcontext'=>$errorcontext);
		switch($errornr)
		{
			case E_USER_ERROR:
				$errortype='Error';
				break;
			case E_RECOVERABLE_ERROR:
				$errortype='Recoverable Error';
				break;
			case E_WARNING:
			case E_USER_WARNING:
				$errortype='Warning';
				break;
			case E_NOTICE:
			case E_USER_NOTICE:
				$errortype='Notice';
				break;
			case E_STRICT:
				$errortype='Strict Notice';
				break;
			default:
				$errortype='Unknown Error';
				break;
		}
		$message='Catcher ('.$errortype.'): '.$errortext.' (File: '.$errorfile.' - Line: '.$errorline.')'."\n";
		if ($this->backtrace===true)
		{
			$backtrace=debug_backtrace();
			if (count($backtrace)>1)
			{
				$message.='Backtrace: '."\n";
				for ($step=count($backtrace)-1;$step>0;$step--)
				{
					if (!empty($backtrace[$step]['class']))
					{
						$message.=$backtrace[$step]['class'].'->';
					}
					$message.=$backtrace[$step]['function'].'()';
					if ((!empty($backtrace[$step]['file'])) && (!empty($backtrace[$step]['line'])))
					{
						$message.=' (File: '.$backtrace[$step]['file'].' - Line: '.$backtrace[$step]['line'].')';
					}
					$message.="\n";
				}
				$message.="\n";
			}
		}
		$this->outputmessage($message);
		$this->mailmessage($message);
		$this->logmessage($message);
		if ((($this->dieonfatal===true) && (($errortype=='Error') || (($errortype=='Recoverable Error') && ($this->recovery===false)))) || (($this->dieonunknown===true) && ($errortype=='Unknown Error')))
		{
			die();
		}
	}

	/**
	 * Print error-message.
	 *
	 * @param string $message
	 */
	private function outputmessage($message)
	{
		if ($this->output===true)
		{
			if ($this->outputhtml===true)
			{
				echo nl2br($message);
			}
			else
			{
				echo $message;
			}
		}
	}

	/**
	 * Mail error-message.
	 *
	 * @param string $message
	 */
	private function mailmessage($message)
	{
		if (($this->mailto!=false) && ($this->mailfrom!=false) && ($this->mailsubject!=false))
		{
			mail($this->mailto,$this->mailsubject,$message,'From: '.$this->mailfrom."\n");
		}
	}

	/**
	 * Log error-message.
	 *
	 * @param string $message
	 */
	private function logmessage($message)
	{
		if ($this->logfile!=false)
		{
			$file=fopen($this->logfile,'a');
			fwrite($file,'['.date('d/M/Y H:i:s',time()).'] '.$message);
			fclose($file);
		}
	}

	/**
	 * Get the last exception.
	 *
	 * @return array
	 */
	public function getlastexception()
	{
		return $this->lastexception;
	}

	/**
	 * Get the last error.
	 *
	 * @return array
	 */
	public function getlasterror()
	{
		return $this->lasterror;
	}
}
?>