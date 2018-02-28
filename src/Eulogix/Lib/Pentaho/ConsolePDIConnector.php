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

class ConsolePDIConnector extends PDIConnector
{

    const DEFAULT_KITCHEN_COMMAND = 'kitchen.sh';

    /**
     * @var string
     */
    protected $kitchenCommand, $sudoUser;

    /**
     * returns a connector that tries to launch jobs using kitchen
     *
     * @param string $repositoryName
     * @param string $repositoryUser
     * @param string $repositoryPassword
     * @param string|null $sudoUser
     * @param string|null $kitchenCommand
     * @return ConsolePDIConnector
     */
    public function __construct($repositoryName, $repositoryUser = null, $repositoryPassword = null, $sudoUser = null, $kitchenCommand = self::DEFAULT_KITCHEN_COMMAND)
    {
        $this   ->setRepositoryName($repositoryName)
                ->setRepositoryUser($repositoryUser)
                ->setRepositoryPassword($repositoryPassword)
                ->setSudoUser($sudoUser)
                ->setKitchenCommand($kitchenCommand);
    }

    /**
     * @return string
     */
    public function getKitchenCommand()
    {
        return $this->kitchenCommand;
    }

    /**
     * @param string $kitchenCommand
     * @return $this
     */
    public function setKitchenCommand($kitchenCommand)
    {
        $this->kitchenCommand = $kitchenCommand;
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
     * @inheritdoc
     */
    public function runJob($jobName, $jobPath = self::DEFAULT_JOB_PATH, $parameters=[]) {

        $ret = new JobExecutionResult();

        $cmd = $this->getBaseCommandLine($jobName, $jobPath);
        $parameters = array_merge($this->getCommonParameters(), $parameters);
        foreach($parameters as $k=>$v)
            $cmd.="/param:$k=\"$v\" ";

        $output = [];
        exec($cmd, $output, $exitCode);

        $ret->setOutput(implode("\n",$output))
            ->setSuccess( $exitCode == 0)
            ->setError( $exitCode > 0 ? JobExecutionResult::ERROR_GENERIC : null);

        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function getExpectedJobParameters($jobName, $jobPath = self::DEFAULT_JOB_PATH) {

        $ret = [];

        $cmd = $this->getBaseCommandLine($jobName, $jobPath)." /listparam 2>&1";
        $output = shell_exec($cmd);

        $this->checkCommandOutput($output);

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
    private function getBaseCommandLine($jobName = null, $jobPath = self::DEFAULT_JOB_PATH) {
        $cmd = ($this->getSudoUser() ? "sudo -u {$this->getSudoUser()} " : '' ). $this->getKitchenCommand();
        $cmd.=" /rep:{$this->getRepositoryName()} /user:{$this->getRepositoryUser()} /pass:{$this->getRepositoryPassword()}";
        if($jobName)
            $cmd.=" /job:\"{$jobName}\" /dir:\"{$jobPath}\" ";
        return $cmd;
    }

    /**
     * @param $output
     * @throws \Exception
     */
    private function checkCommandOutput($output)
    {
        if(preg_match('/command not found$/sim', $output))
            throw new \Exception("Command {$this->getKitchenCommand()} not available. Check your PATH");
    }

    /**
     * @return bool
     */
    public function isAvailable() {
        try {
            $o = shell_exec(($this->getSudoUser() ? "sudo -u {$this->getSudoUser()} " : '' ). $this->getKitchenCommand().' 2>&1');
            $this->checkCommandOutput($o);
        } catch(\Exception $e) {
            return false;
        }
        return true;
    }
}