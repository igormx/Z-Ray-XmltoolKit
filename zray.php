<?php
namespace XmltoolKit;

class XmltoolKit{
   
    public function getConstructor($context, &$storage){
        // Whether exception is thrown (probably due a connections problems) we catch it! //
        $this->traceErrors($context, $storage);
        if(isset($context['locals']['exceptionThrown']))
            $error = $context['locals']['exceptionThrown'];
        else 
            $error = 'No error detected';
        //-----end exeption management-----------------------------------------------------//
        // to recover the XMLToolkit version server side: it execute an sendXml() call traced by this ZRay Extension
        $xmltoolkitVersion = $context['this']->getDiagnostics(); 
        $SubmitjobParams   = 'No defined';
        if (isset($context['locals']['serviceParams']['sbmjobParams'][0]))
            $SubmitjobParams = $context['locals']['serviceParams']['sbmjobParams'][0];
        
        // Recovery variables related to service parameters defined into the ToolkitService::__Construct
        $storage["Instance"][] =  array('DbName'                  => $context['locals']['databaseNameOrResource'],
                                        'Version Server Side'     => $xmltoolkitVersion,
                                        'Connection Persistent'   => $context['locals']['isPersistent'],
                                        'User/i5Naming'           => $context['locals']['userOrI5NamingFlag'],
                                        'Xml Service Lib'         => $context['locals']['xmlServiceLib'],
                                        'Debug'                   => $context['locals']['debug'],
                                        'Debug Log File'          => $context['locals']['debugLogFile'],
                                        'Encoding'                => $context['locals']['encoding'],
                                        'Parse Only'              => $context['locals']['parseOnly'],
                                        'Parse Debug Level'       => $context['locals']['parseDebugLevel'],
                                        'Transport Type'          => $context['locals']['serviceParams']['transportType'],
                                        'Submit job Params'       => $SubmitjobParams,
                                        'Transport Type'          => $context['locals']['serviceParams']['transportType'],
                                        'Times Called'            => $context['timesCalled'],
                                        'Exception'               => $error,                                                      
                                         );
    }

    public function pgmCall($context, &$storage){
        // Whether exception is thrown (probably due a connections problems) we catch it! //
        $this->traceErrors($context, $storage);
        
        // Input parameters
        $pgmName     = $context['functionArgs'][0]; // required
        $lib         = $context['functionArgs'][1]; // required
        $inputParam  = $context['functionArgs'][0]; // Optional NULL
        $returnParam = $context['functionArgs'][0]; // Optional NULL
        $options     = $context['functionArgs'][0]; // Optional NULL this is an array  
	   
        $inputXml    = $context['locals']['inputXml'];
        $gotErrors   = 'No errors detected';
        // see if real data came back
        if( !is_array($context['locals']['outputParamArray']) and isset($context['locals']['gotErrors'])){
            // it means that there is an error - No real data. Look for errors. Retrieve details from joblog.
            $gotErrors = $context['locals']['gotErrors']; // this is an array 
        }
        /*
        // trace xmlOutput
        if (strlen($context['locals']['outputXml']) == 0)
            $context['locals']['outputXml'] = 'No xml output defined for PgmCall: '.$pgmName;
        $storage['xmlOutput'][] = array('OutputXml' => $context['locals']['outputXml']);
        // trace xmlInput
        $storage['xmlInput'][] = array('InputXml' => $context['locals']['inputXml']);
         */
        $storage['pgmCalls'][$pgmName] = array('Pgm Name'     => $pgmName,
                                               'Lib'          => $lib,
                                               'inputParam'   => $inputParam,
                                               'returnParam'  => $returnParam,
                                               'Options'      => $options,
                                               'Input Xml'    => $inputXml,
                                               'OutputXml'    => $context['locals']['outputXml'],
                                               'Errors'       => $gotErrors,
                                               'Times Called' => $context['timesCalled'], //number of times a function was called
                                               'Duration Inclusive' => $context['durationInclusive'], //duration for the function only in microsecs, available in after callback only
                                               'Duration Exclusive' => $context['durationExclusive'], //duration for the function and its children in microsecs, available in after callback only
                                               'Called From File'   => $context['calledFromFile'], //file where function is called
                                               'called From Line'   => $context['calledFromLine'], //line where function is called
                                                );
        
    } 
    
    public function clCommand($context, &$storage){
        // Whether exception is thrown (probably due a connections problems) we catch it! //
        $this->traceErrors($context, $storage);
        
        // $context['functionArgs'][1] contains the type string of the CLCommand called        
        if (!isset($context['functionArgs'][1])) 
            $context['functionArgs'][1] = ''; // this is the default value in case it not passed to the method
        switch($context['functionArgs'][1]){
            case '':
                // it means that is a simple CLCommand call
                $typeOfClCommand = 'ClCommand';
                break;
            case 'pase':
                // it means that is a CLInteractiveCommand call
                $typeOfClCommand = 'CLInteractiveCommand';
                break;
           case 'pasecmd':
                // it means that is a paseCommand call
                $typeOfClCommand = 'paseCommand';
                break;
           case 'rexx':
                // it means that is a ClCommandWithOutput call
                $typeOfClCommand = 'ClCommandWithOutput';
                break;
           case 'system':
                // it means that is a ClCommandWithCpf call
                $typeOfClCommand = 'ClCommandWithCpf';
                break;
           default:
                $typeOfClCommand = 'UnDefined';       
                break;
        }
        
        // get encoding
        $encoding = $context['this']->getOption('encoding');
        
        // check if the CLCommand call has some Data in Ouput provided
        if ($context['locals']['expectDataOutput'])   
            $outputParams = $context['locals']['outputParamArray']; // THIS IS AN ARRAY @TODO CHECK WHAT IS THE RESULT 
        else 
            $outputParams = "No output params provided"; 

        $storage['clCommands'][$context['functionArgs'][0]] = array('Command called'                  => $context['functionArgs'][0],
                                                                    'Type of command called'          => $typeOfClCommand,
                                                                    'Output Params'                   => $outputParams,
                                                                    'Encoding'                        => $encoding,
                                                                 //  get status: error or success, with a real CPF error message, and set the error code/msg.
                                                                    'timesCalled'                     => $context['timesCalled'],       // number of times a function was called
                                                                    'Duration Inclusive'              => $context['durationInclusive'], // duration for the function only in microsecs, available in after callback only
                                                                    'Duration Exclusive'              => $context['durationExclusive'], // duration for the function and its children in microsecs, available in after callback only
                                                                    'Called From File'                => $context['calledFromFile'],    // file where function is called
                                                                    'called From Line'                => $context['calledFromLine'],    // line where function is called
                                                                 // 'Cpf Error'                       => $context['this']->cpfErr,
                                                                 // 'Error Text'                      => $context['this']->errorText,
                                                                 //  true/false (success);
                                                                    'Execution result'                => $context['returnValue'],       // should be false in case of failure, or the returned values of the CL command
                                                                    'XML in Input to the command'     => $context['locals']['inputXml'],
                                                                    'XML in Output from the command'  => $context['locals']['outputXml'],
                                                    );
       }

    public function buildParamXml($context, &$storage){
        // Whether exception is thrown (probably due a connections problems) we catch it! //
        $this->traceErrors($context, $storage);
        
        $storage['XMLWrapper'][] = array('Xml parameters returned ' => $context['returnValue']);       
    }

    public function dbConnect($context, &$storage){
        // Whether exception is thrown (probably due a connections problems) we catch it! //
        $this->traceErrors($context, $storage);
        
        if(is_resource($context['returnValue']))
            $connection = "successful connection";
        else 
            $connection = "connection failed";
        
        if (isset($context['locals']['connectFunc']))
            $type = $context['locals']['connectFunc'];
        else 
            $type = $context['this']->getTransport();
        
       $storage['Connection'][] = array('Result of connection'  => $connection,
                                        'Type of connection'       => $type
                                        );
    }

    public function execXMLStoredProcedure($context, &$storage){
        // Whether exception is thrown (probably due a connections problems) we catch it! //
        $this->traceErrors($context, $storage);
        
        // check the return value: if is false there is an error, otherwise, we have the xmloutput
        if($context['returnValue'] === false){
            if(isset($context['locals']['errorReason']))
                $resultStoreProcedure = $context['locals']['errorReason'];
            else 
                $resultStoreProcedure = $context['this']->getErrorMsg();
        }
        else 
            $resultStoreProcedure = "Successful execution";
       
        // check if the transport is http because the variables available are differents
        if(strtolower($context['locals']['transportType']) == 'http'){
            $from = 'http call '.$context['locals']['inputXml'];
            $storage['HttpStoreProcedures'][]  = array('Input XML'                => $context['locals']['inputXml'], 
                                                       'Internal Key'             => $context['locals']['internalKey'],
                                                       'Control Key String'       => $context['locals']['controlKeyString'],
                                                       'Url'                      => $context['locals']['url'], 
                                                       'Result of Store Procedure'=> $resultStoreProcedure,
                                                       'Output XML'               => $context['locals']['outputXml'],
                                                       'Times Called'             => $context['timesCalled'], //number of times a function was called
                                                       'Duration Inclusive'       => $context['durationInclusive'], //duration for the function only in microsecs, available in after callback only
                                                       'Duration Exclusive'       => $context['durationExclusive'], //duration for the function and its children in microsecs, available in after callback only
                                                       'Called From File'         => $context['calledFromFile'], //file where function is called
                                                       'called From Line'         => $context['calledFromLine'], //line where function is called
                                                      );
            // trace xmlInput
            $storage['xmlInput'][] = array('InputXml' => $context['locals']['inputXml']);
             
        }
        else{
            
            // $out contains the sql completed with the parameters
            $out = $this->buildSql($context['locals']['bindArray'], $context['locals']['sql']);

            $from = $out;
            // ibm_db2 or odbc
                     
             $storage['DbQueries'][$out]        = array('SQL'                 => $out,
                                                        'Stmt'                => $context['locals']['sql'],
                                                        'Bind params'         => $context['locals']['bindArray'],
                                                        'OutputXml'           => $context['locals']['outputXml'],
                                                        'Error code'          => $context['this']->getErrorCode(),
                                                        'Error Message'       => $context['this']->getErrorMsg(),
                                                        'Times Called'        => $context['timesCalled'], //number of times a function was called
                                                        'Duration Inclusive'  => $context['durationInclusive'], //duration for the function only in microsecs, available in after callback only
                                                        'Duration Exclusive'  => $context['durationExclusive'], //duration for the function and its children in microsecs, available in after callback only
                                                        'Called From File'    => $context['calledFromFile'], //file where function is called
                                                        'called From Line'    => $context['calledFromLine'], //line where function is called
                
            );
        }
        
    }
    
    public function execQuery($context, &$storage){
        // Whether exception is thrown (probably due a connections problems) we catch it! //
        $this->traceErrors($context, $storage);
           
        $errorCode = 'No error code';
        $errorMsg  = 'No error message';
        if (!is_array($context['locals']['Txt'])){
            $errorCode = $context['this']->getErrorCode();
  	   		$errorMsg  = $context['this']->getErrorMsg();
        }
        
        $storage['DbQueries'][$context['locals']['stmt']]  = array('Sql'                 => $context['locals']['stmt'],
                                                                   'Bind params'         => '',
                                                                   'Error code'          => $errorCode,
                                                                   'Error Message'       => $errorMsg,
                                                                   'Duration Inclusive'  => $context['durationInclusive'], //duration for the function only in microsecs, available in after callback only
                                                                   'Duration Exclusive'  => $context['durationExclusive'], //duration for the function and its children in microsecs, available in after callback only
                                                                   'Called From File'    => $context['calledFromFile'],    //file where function is called
                                                                   'called From Line'    => $context['calledFromLine'],    //line where function is called
        
        );
    }
    
    
    public function sendXml($context, &$storage){
        $storage['SendXML'][] =   array('Xml in Input'             => $context['functionArgs'][0],
                                        'timesCalled'              => $context['timesCalled'], //number of times a function was called
                                        'Duration Inclusive'       => $context['durationInclusive'], //duration for the function only in microsecs, available in after callback only
                                        'Duration Exclusive'       => $context['durationExclusive'], //duration for the function and its children in microsecs, available in after callback only
                                        'Called From File'         => $context['calledFromFile'], //file where function is called
                                        'called From Line'         => $context['calledFromLine'], //line where function is called
                                        'XML in Output from the command'  => $context['returnValue'],
                                    );
    
        // trace xmlOutput
        if (strlen($context['returnValue']) != 0)
            $storage['xmlOutput'][] = array('OutputXml' => $context['returnValue']);
        // trace xmlInput
        $storage['xmlInput'][] = array('InputXml' => $context['functionArgs'][0]);
        
    }
    
        
    public function httpsuppSend($context, &$storage){
        // Whether exception is thrown (probably due a connections problems) we catch it! //
        $this->traceErrors($context, $storage);
        
        // If the transport choosen is http at the send moment we catch the xmlIn, the Out Size and the Url called
        $storage['XMLToolkit HttpTransport'][] = array('Xml In'                => $context['locals']['xmlIn']);
        $storage['XMLToolkit HttpTransport'][] = array('Out Size'              => $context['locals']['outSize']);
        $storage['XMLToolkit HttpTransport'][] = array('HTTP transport URL'    => $context['locals']['linkall']);
    }
    
    public function disconnect($context, &$storage){
        ksort($storage);
     }
         
    
    protected function setServiceParams($params){      
        if (!isset($params['schemaSep']))
            $params['schemaSep'] = 'No value set';
        // Recovery variables related to service parameters
        return array('InternalKey' => $params['InternalKey'],
                     'Debug'       => $params['debug'],
                     'Plug'        => $params['plug'],
                     'PlugSize'    => $params['plugSize'],
                     'PlugPrefix'  => $params['plugPrefix'],
                     'Schema Sep'  => $params['schemaSep'],
        );
    }
    
    /**
     * @desc This method do the bind of the parameters passed to the statement | example $stmt = "call ZENDSVR6.iPLUG512K(?,?,?,?)" | $out will be something like "call ZENDSVR6.iPLUG512K('/tmp/','*cdata *sbmjob(ZENDSVR6/ZSVR_JOBD/XTOOLKIT)',..."
     * @param array $bindArray
     * @param string $stmt
     * @return string
     */
    protected function buildSql($bindArray, $stmt){
        $out = preg_replace_callback('!\?!', function($m) use ($bindArray) { static $id = 0;
        $id++;
        $count = 0;
        foreach ($bindArray as $value){
            ++$count;
            if ($count == $id){
                if(is_string($value))
                    return "'$value'";
                    else
                        return $value;
            }
            }
            return "no value matched";
        }, $stmt);
        return $out;
        }
    
    /**
     * @desc this method trace every kind of errors from every call method
     * 
     * @param array $context
     * @param array $storage
     */
    protected function traceErrors($context, &$storage){
        if (isset($context['exceptionThrown']) and $context['exceptionThrown']) {
            $errorMessages = $context['exception']->getMessages();
            $stringErrorMessage = '';
            foreach($errorMessages as $message){
              $stringErrorMessage .= $message.' - ';
            }
            $storage['DetectedErrors'][] = array('Error Code'          => $context['exception']->getCode(),
                                                 'Error Message'       => $stringErrorMessage,
                                                 'File and Line error' => "File: {$context['exception']->getFile()} Line: {$context['exception']->getLine()}",
                                                );
            }
        }
    
}

$zre = new \ZRayExtension("xmltoolkit");
$XmlStorage = new XmltoolKit();

$zre->setMetadata(array(
    'logo' => __DIR__ . DIRECTORY_SEPARATOR . 'logo.png',
));

//$zre->setIsEnabled(true);
$zre->setEnabledAfter("ToolkitService::getInstance");
//----------------------------------------------------------------
$zre->traceFunction("ToolkitService::__construct",           function(){}, array($XmlStorage, 'getConstructor'));

$zre->traceFunction("ToolkitService::pgmCall",               function(){}, array($XmlStorage, 'pgmCall'));                  
$zre->traceFunction("ToolkitService::CLCommand",             function(){}, array($XmlStorage, 'clCommand'));                // for all kind of CL
$zre->traceFunction("ToolkitService::ExecuteProgram",        function(){}, array($XmlStorage, 'execXMLStoredProcedure'));   // alias of sendXML -- for all kind of transport Type (Db2/ODBC)
$zre->traceFunction("ToolkitService::sendXml",               function(){}, array($XmlStorage, 'sendXml'));                
$zre->traceFunction("ToolkitService::Executequery",          function(){}, array($XmlStorage, 'execQuery'));                
$zre->traceFunction("ToolkitService::Disconnect",            function(){}, array($XmlStorage, 'disconnect'));                
// Wrapper Class 
//$zre->traceFunction("XMLWrapper::buildParamXml",             function(){}, array($XmlStorage, 'buildParamXml'));            
// Case Db2 connection
$zre->traceFunction("Db2Supp::connect",                      function(){}, array($XmlStorage, 'dbConnect'));                 
// Case ODBC connection
$zre->traceFunction("odbcsupp::connect",                     function(){}, array($XmlStorage, 'dbConnect'));                 
// Case http connection 
$zre->traceFunction("httpsupp::connect",                     function(){}, array($XmlStorage, 'dbConnect'));                 
$zre->traceFunction("httpsupp::send",                        function(){}, array($XmlStorage, 'httpsuppSend'));              
