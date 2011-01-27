<?php

/**
 * Error handling class for Atom
 * Error log keeps in the folder errorlog
 * Contributors Vineeth ,Revath S Kumar, Sreejith PM
 * Created Date : 25-11-2010
 * 
 * Modification Date :
 * Modified By :
 * Description :
 * @author habid
 * @package atom
 * @copyright
 * @license
 * @version 1.0
 * 
 */
namespace errors;
class errorhandler {

    /**
     * Constructor for the errorhandler
     *
     * @access public
     */
    function __construct($ip, $email=NULL) {
        $this->ip = $ip;

        if ($email != "" && $email != NULL) {
            $this->email = mysql_escape_string($email);
            $this->email_sent = true;
        } else {
            $this->email_sent = false;
        }

        $this->log_file = ROOT_FOLDER.DS."prj".DS."errorlog".DS. date("Y-m-d").".xml";
        $this->log_message = true;


        $this->error_codes = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR;
        $this->warning_codes = E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING;

        //associate error codes with errno...
        $this->error_names = array('E_ERROR', 'E_WARNING', 'E_PARSE', 'E_NOTICE', 'E_CORE_ERROR', 'E_CORE_WARNING',
            'E_COMPILE_ERROR', 'E_COMPILE_WARNING', 'E_USER_ERROR', 'E_USER_WARNING',
            'E_USER_NOTICE', 'E_STRICT', 'E_RECOVERABLE_ERROR');

        for ($i = 0, $j = 1, $num = count($this->error_names); $i < $num; $i++, $j = $j * 2)
            $this->error_numbers[$j] = $this->error_names[$i];

        set_error_handler(array($this, "handler"));
    }

    /**
     * Custom Error Handling
     *
     * @param integer $errno contains the level of the error raised, as an integer.
     * @param string $errstr ontains the error message, as a string.
     * @param string $errfile contains the filename that the error was raised in, as a string.
     * @param integer $errline contains the line number the error was raised at, as an integer
     * @param array $errcontext [optional]
     * @return array Returns a string containing the previously defined error handler
     * @access
     */
    function handler($errno, $errstr, $errfile, $errline, $errcontext) {
//		if(DEV === TRUE)return;
        $this->erroNo = $errno;
        $this->errorSting = $errstr;
        $this->errorFile = $errfile;
        $this->errorLine = $errline;

        if ($this->log_message) {
            $this->logErrorMessage();
        }
        if ($this->email_sent) {
            $this->sentMails();
        }
    }

    /**
     * function for creating error File
     *
     * @access private
     */
    private function logErrorMessage() {
        $errors = array();
        $errors [] = array(
            "time" => date("H:i:s"),
            "ip" => $this->ip,
            "error" => $this->errorSting,
            "file" => $this->errorFile,
            "lineno" => $this->errorLine
        );

        if (file_exists($this->log_file)) {
            $xmlDoc = new \DOMDocument();
            $xmlDoc->load($this->log_file);

            $root = $xmlDoc->firstChild;

            foreach ($errors as $error) {

                $b = $xmlDoc->createElement("error");

                $time = $xmlDoc->createElement("time");
                $time->appendChild(
                        $xmlDoc->createTextNode($error['time'])
                );
                $b->appendChild($time);

                $ip = $xmlDoc->createElement("ip");
                $ip->appendChild(
                        $xmlDoc->createTextNode($error['ip'])
                );
                $b->appendChild($ip);

                $errorString = $xmlDoc->createElement("error");
                $errorString->appendChild(
                        $xmlDoc->createTextNode($error['error'])
                );
                $b->appendChild($errorString);

                $errorFile = $xmlDoc->createElement("file");
                $errorFile->appendChild(
                        $xmlDoc->createTextNode($error['file'])
                );
                $b->appendChild($errorFile);

                $errorLineNumber = $xmlDoc->createElement("lino");
                $errorLineNumber->appendChild(
                        $xmlDoc->createTextNode($error['lineno'])
                );
                $b->appendChild($errorLineNumber);

                $root->appendChild($b);
            }
            $xmlDoc->save($this->log_file);
        } else {
            $doc = new \DOMDocument();
            $doc->formatOutput = true;

            $r = $doc->createElement("errorlogs");
            $doc->appendChild($r);

            foreach ($errors as $error) {

                $b = $doc->createElement("error");

                $time = $doc->createElement("time");
                $time->appendChild(
                        $doc->createTextNode($error['time'])
                );
                $b->appendChild($time);

                $ip = $doc->createElement("ip");
                $ip->appendChild(
                        $doc->createTextNode($error['ip'])
                );
                $b->appendChild($ip);

                $errorString = $doc->createElement("error");
                $errorString->appendChild(
                        $doc->createTextNode($error['error'])
                );
                $b->appendChild($errorString);

                $errorFile = $doc->createElement("file");
                $errorFile->appendChild(
                        $doc->createTextNode($error['file'])
                );
                $b->appendChild($errorFile);

                $errorLineNumber = $doc->createElement("lineno");
                $errorLineNumber->appendChild(
                        $doc->createTextNode($error['lineno'])
                );
                $b->appendChild($errorLineNumber);

                $r->appendChild($b);
            }

            $doc->save($this->log_file);
        }
    }

    /**
     * function for creating sent Email regarding the error to Specified Email
     *
     * @return boolean if mail is sucsess False if not
     * @access private
     */
    private function sentMails() {
        $to = $this->email;
        $subject = "Error Handler Atom";
        $message = "The is the error Details ,";
        $message .="Error No : " . $this->erroNo . "<br />";
        $message .="Error No : " . $this->errorSting . "<br />";
        $message .="Error No : " . $this->errorFile . "<br />";
        $message .="Error No : " . $this->errorLine . "<br />";

        $from = "admin@atom.com";
        $headers = "From: $from";

        $ok = mail($to, $subject, $message, $headers);
    }
}

?>