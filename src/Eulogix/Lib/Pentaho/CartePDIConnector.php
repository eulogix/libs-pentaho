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

use Eulogix\Lib\Curl\Curler;

/**
* @author Pietro Baricco <pietro@eulogix.com>
*/

class CartePDIConnector extends PDIConnector
{

    /**
     * @var string
     */
    protected $carteServer, $carteUser, $cartePassword;

    /**
     * @var Curler
     */
    protected $curler;

    /**
     * returns a connector that tries to launch jobs using kitchen
     *
     * @param string $carteServer
     * @param string $carteUser
     * @param string $cartePassword
     * @param string $repositoryName
     * @param string|null $repositoryUser
     * @param string|null $repositoryPassword
     */
    public function __construct($carteServer, $carteUser, $cartePassword, $repositoryName, $repositoryUser = null, $repositoryPassword = null)
    {
        $this   ->setRepositoryName($repositoryName)
                ->setRepositoryUser($repositoryUser)
                ->setRepositoryPassword($repositoryPassword)
                ->setCarteServer($carteServer)
                ->setCarteUser($carteUser)
                ->setCartePassword($cartePassword);

        $this->curler = new Curler();
        $this->curler->setHttpAuth($carteUser, $cartePassword);
    }

    /**
     * @return string
     */
    public function getCarteServer()
    {
        return $this->carteServer;
    }

    /**
     * @param string $carteServer
     * @return $this
     */
    public function setCarteServer($carteServer)
    {
        $this->carteServer = $carteServer;
        return $this;
    }

    /**
     * @return string
     */
    public function getCartePassword()
    {
        return $this->cartePassword;
    }

    /**
     * @param string $cartePassword
     * @return $this
     */
    public function setCartePassword($cartePassword)
    {
        $this->cartePassword = $cartePassword;
        return $this;
    }

    /**
     * @return string
     */
    public function getCarteUser()
    {
        return $this->carteUser;
    }

    /**
     * @param string $carteUser
     * @return $this
     */
    public function setCarteUser($carteUser)
    {
        $this->carteUser = $carteUser;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function runJob($jobName, $jobPath = self::DEFAULT_JOB_PATH, $parameters = [], &$exitCode = null)
    {
        $ret = new JobExecutionResult();

        try {
            $this->checkConnection();

            $completeJobPath = str_replace('//','/',$jobPath.'/'.$jobName);
            preg_match('%^.*?/*([^/\n\r]*)$%sim', $completeJobPath, $m);
            $jobName = $m[1];

            $serviceCallParameters = array_merge([
                'xml' => 'Y',
                'rep' => $this->getRepositoryName(),
                'job' => $completeJobPath,
            ], $parameters ?? []);

            if($this->getRepositoryUser() && $this->getRepositoryPassword())
            {
                $serviceCallParameters['user'] = $this->getRepositoryUser();
                $serviceCallParameters['pass'] = $this->getRepositoryPassword();
            }

            $response = $this->fetchAsArray('/kettle/executeJob/', $serviceCallParameters);

            if(isset($response['result'])) {
                if($response['result'] == "OK") {
                    $ret->setId($response['id']);

                    $status = $this->getJobStatusWaitingUntilFinished($jobName, $ret->getId());
                    if($status['error_desc'])
                        $ret->setError($status['error_desc']);
                    else $ret->setSuccess(true);

                    $ret->setOutput($status['logging_string']);

                } else {
                    $ret->setError($response['message']);
                }
            } else $ret->setError(JobExecutionResult::ERROR_MALFORMED_RESPONSE);

        } catch(\Exception $e) {
            $ret->setError($e->getMessage());
        }

        return $ret;
    }

    /**
     * @param string $jobName
     * @param string $executionId
     * @return array
     */
    public function getJobStatus($jobName, $executionId) {
        $status = $this->fetchAsArray('/kettle/jobStatus/', [
            'name' => $jobName,
            'id' => $executionId,
            'xml' => 'Y'
        ]);

        if(preg_match('/\[CDATA\[(.+?)\]\]/sim', $status['logging_string'], $m)) {
            $d1 = base64_decode($m[1]);
            $status['logging_string'] = gzdecode($d1);
        }

        return $status;
    }

    /**
     * carte takes a while to recover log lines, so waiting for result to be there ensures that we get the job
     * log, otherwise it would be empty or truncated
     * @param $jobName
     * @param $executionId
     * @return array
     */
    public function getJobStatusWaitingUntilFinished($jobName, $executionId) {
        do {
            $status = $this->getJobStatus($jobName, $executionId);
            sleep(2);
        } while($status['status_desc'] == 'Running' || !isset($status['result']));
        return $status;
    }

    /**
     * @return array
     */
    public function getServerStatus()
    {
        return $this->fetchAsArray('/kettle/status/?xml=Y');
    }

    /**
     * @inheritdoc
     */
    public function getExpectedJobParameters($jobName, $jobPath = self::DEFAULT_JOB_PATH)
    {
        throw new \Exception("carte can not provide informations about jobs structure.");
    }

    /**
     * @throws \Exception
     */
    private function checkConnection()
    {
        if(!$this->isAvailable())
            throw new \Exception("Carte server not online");
    }

    /**
     * @param $relativeUrl
     * @param array|null $postData
     * @return array
     */
    private function fetchAsArray($relativeUrl, $postData = null) {
        $url = $this->getCarteServer().$relativeUrl;
        $ret = $this->curler->fetchPage($url, $postData);
        if($ret !== false) {
            $xml = new \SimpleXMLElement($ret->getBody());
            return SimpleXMLElement2array($xml);
        }
        return [];
    }

    /**
     * @return bool
     */
    public function isAvailable() {
        try {
            $status = $this->getServerStatus();
            return $status['statusdesc'] == 'Online';
        } catch(\Exception $e) {
            return false;
        }
    }

}