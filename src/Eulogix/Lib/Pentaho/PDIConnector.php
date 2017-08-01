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
    private $repositoryName, $user, $password, $sudoUser;

    /**
     * @var array
     */
    private $configParameters;

    /**
     * @param string $repoName
     * @param string $user
     * @param string $password
     * @param string $sudoUser
     * @param array $configParameters
     */
    function __construct($repoName, $user, $password, $sudoUser=null, array $configParameters = []) {
        $this->setRepositoryName($repoName);
        $this->setUser($user);
        $this->setPassword($password);
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
     * @return string
     */
    public function getRepositoryName()
    {
        return $this->repositoryName;
    }

    /**
     * @param string $repositoryName
     * @return $this
     */
    public function setRepositoryName($repositoryName)
    {
        $this->repositoryName = $repositoryName;
        return $this;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getSudoUser()
    {
        return $this->sudoUser;
    }

    /**
     * @param string $sudoUser
     * @return $this
     */
    public function setSudoUser($sudoUser)
    {
        $this->sudoUser = $sudoUser;
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

        echo "\n\n\n\n$cmd\n\n\n\n\n";

        return shell_exec($cmd);
    }

    /**
     * @param string $jobName
     * @param string $jobPath
     * @return array
     * @throws \Exception
     */
    public function getExpectedJobParameters($jobName, $jobPath=self::DEFAULT_JOB_PATH) {
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
        $cmd = ($this->getSudoUser() ? "sudo -u {$this->getSudoUser()} " : '' ). "kitchen.sh";
        $cmd.=" /rep:{$this->getRepositoryName()} /user:{$this->getUser()} /pass:{$this->getPassword()}";
        if($jobName)
            $cmd.=" /job:\"{$jobName}\" /dir:\"{$jobPath}\" ";
        return $cmd;
    }
   
}