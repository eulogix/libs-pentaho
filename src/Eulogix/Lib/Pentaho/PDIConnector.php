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

abstract class PDIConnector
{
    const DEFAULT_JOB_PATH = '/';

    /**
     * @var string
     */
    protected $repositoryName, $repositoryUser, $repositoryPassword, $kitchenCommand, $sudoUser;

    /**
     * these will be passed along with every job call
     * @var array
     */
    protected $commonParameters = [];

    /**
     * @param string $jobName
     * @param string|null $jobPath
     * @param array $parameters
     * @return JobExecutionResult
     */
    public abstract function runJob($jobName, $jobPath = self::DEFAULT_JOB_PATH, $parameters = []);

    /**
     * @param string $jobName
     * @param string|null $jobPath
     * @return array
     * @throws \Exception
     */
    public abstract function getExpectedJobParameters($jobName, $jobPath = self::DEFAULT_JOB_PATH);

    /**
     * @return array
     */
    public function getCommonParameters()
    {
        return $this->commonParameters;
    }

    /**
     * @param array $commonParameters
     * @return $this
     */
    public function setCommonParameters($commonParameters)
    {
        $this->commonParameters = $commonParameters ?? [];
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
    public function getRepositoryUser()
    {
        return $this->repositoryUser;
    }

    /**
     * @param string $repositoryUser
     * @return $this
     */
    public function setRepositoryUser($repositoryUser)
    {
        $this->repositoryUser = $repositoryUser;
        return $this;
    }

    /**
     * @return string
     */
    public function getRepositoryPassword()
    {
        return $this->repositoryPassword;
    }

    /**
     * @param string $repositoryPassword
     * @return $this
     */
    public function setRepositoryPassword($repositoryPassword)
    {
        $this->repositoryPassword = $repositoryPassword;
        return $this;
    }
}