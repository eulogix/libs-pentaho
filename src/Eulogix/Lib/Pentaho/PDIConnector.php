<?php

/*
* This file is part of the Eulogix\Lib package.
*
* (c) Eulogix <http://www.eulogix.com/>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Eulogix\Lib\Pentaho;

/**
* @author Pietro Baricco <pietro@eulogix.com>
*/

class PDIConnector {

    /**
     * @var string
     */
   private $repoName, $user, $password;

    /**
     * @param string $repoName
     * @param string $user
     * @param string $password
     */
   function __construct($repoName, $user, $password) {
        $this->repoName = $repoName;    
        $this->user = $user;    
        $this->password = $password;    
   }
   
   private function _getBaseCmdLine($jobName=null,$jobPath="/") {
        $cmd = !WINSERVER ? "kitchen.sh " : "Kitchen.bat ";
        $cmd.="/rep:".$this->repoName." /user:".$this->user." /pass:".$this->password." ";    
        if($jobName)
            $cmd.=" /job:\"$jobName\" /dir:\"$jobPath\" ";
        return $cmd;
   }
   
   function getConfigParameters() {
       return array();
   }
   
   function runJob($jobName,$jobPath='/',$parameters=array()) {
        $cmd = $this->_getBaseCmdLine($jobName,$jobPath);
        $parameters = array_merge($this->getConfigParameters(),$parameters);                
        foreach($parameters as $k=>$v)
            $cmd.="/param:$k=\"$v\" ";
        echo "cmd: $cmd\n";
        return shell_exec($cmd);    
   }
   
   function getExpectedParameters($jobName,$jobPath='/') {
       $cmd = $this->_getBaseCmdLine($jobName,$jobPath);
       $cmd.=" /listparam"." 2>&1";
       $output = shell_exec($cmd);
       preg_match_all('/^Parameter: (.+?)=.*?(, default=(.*?) |): *(.*?)$/im',$output,$m,PREG_SET_ORDER);   
       if($m) foreach($m as $mm)
                    $params[]=array('name'=>$mm[1],'default'=>$mm[3],'description'=>$mm[4]);
       return $params;
   }
   
}