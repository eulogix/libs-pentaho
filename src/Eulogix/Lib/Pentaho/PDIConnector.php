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

    const DEFAULT_JOB_PATH = '/';

    /**
     * @var string
     */
    private $repositoryName, $user, $password;

    /**
     * @var array
     */
    private $configParameters;

    /**
     * @param string $repoName
     * @param string $user
     * @param string $password
     * @param array $configParameters
     */
    function __construct($repoName, $user, $password, array $configParameters = []) {
        $this->repositoryName = $repoName;
        $this->user = $user;
        $this->password = $password;
        $this->setConfigParameters($configParameters);
    }

    /**
     * @return array
     */
    public function getConfigParameters()
    {
        return $this->configParameters;
    }

    /**
     * @param array $configParameters
     * @return $this
     */
    public function setConfigParameters($configParameters)
    {
        $this->configParameters = $configParameters;
        return $this;
    }

    /**
     * @param string $jobName
     * @param string $jobPath
     * @param array $parameters
     * @return string
     */
    public function runJob($jobName, $jobPath=self::DEFAULT_JOB_PATH, $parameters=[]) {
        $cmd = $this->getBaseCmdLine($jobName, $jobPath);
        $parameters = array_merge($this->getConfigParameters(), $parameters);
        foreach($parameters as $k=>$v)
            $cmd.="/param:$k=\"$v\" ";
        return shell_exec($cmd);
    }

    /**
     * @param string $jobName
     * @param string $jobPath
     * @return array
     * @throws \Exception
     */
    public function getExpectedParameters($jobName, $jobPath=self::DEFAULT_JOB_PATH) {
        $ret = [];

        $cmd = $this->getBaseCmdLine($jobName, $jobPath);
        $cmd.=" /listparam"." 2>&1";
        $output = shell_exec($cmd);

        if(preg_match('/command not found$/sim', $output))
            throw new \Exception("kitchen.sh not in PATH");

        preg_match_all('/^Parameter: (.+?)=.*?(, default=(.*?) |): *(.*?)$/im', $output, $m, PREG_SET_ORDER);

        if($m)
            foreach($m as $mm)
                $ret[ $mm[1] ] = [
                    'default'       => $mm[3],
                    'description'   => $mm[4]
                ];

        return $ret;
    }

    /**
     * @param string $jobName
     * @param string $jobPath
     * @return string
     */
    private function getBaseCmdLine($jobName=null, $jobPath=self::DEFAULT_JOB_PATH) {
        $cmd = "kitchen.sh ";
        $cmd.="/rep:".$this->repositoryName." /user:".$this->user." /pass:".$this->password." ";
        if($jobName)
            $cmd.=" /job:\"$jobName\" /dir:\"$jobPath\" ";
        return $cmd;
    }
   
}