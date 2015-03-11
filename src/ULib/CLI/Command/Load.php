<?php

namespace ULib\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use ULib\UCore;

class Load extends Command
{
    /**
     * Configure the command
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('load')
            ->setDescription('Load one or more test files')
            ->addArgument(
                'files',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'List of filenames'
            )
            ->addOption(
               'json',
               null,
               InputOption::VALUE_REQUIRED,
               'If set, the complete report will be returned in a JSON string'
            )
            ->addOption(
               'summary',
               null,
               InputOption::VALUE_NONE,
               'If set, the summary report will be returned'
            )
            ->addOption(
               'watch',
               null,
               InputOption::VALUE_NONE,
               'If set, the summary report will be watched on live'
            );
    }

    /**
     * Generate stdout and create a json file if the --json option is set
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return string strout
     */
    private function _execute(InputInterface $input, OutputInterface $output)
    {
        $stdOut = '';

        if (UCore::getBool()) {
            $stdOut .= '<info>OK!</info>';
        } else {
            $stdOut .= '<error>Doh!</error>';
        }

        if ($input->getOption('summary')) {
            $report = json_decode(UCore::getJSON());
            $ok     = $report->summary->asserts->ok;
            $nok    = $report->summary->asserts->nok;
            $total  = $ok + $nok;

            $stdOut .= ' '.$total.' asserts. '.$ok.' passed and '.$nok.' failed.';
        }

        if ($jsonFile = $input->getOption('json')) {
            file_put_contents($jsonFile, UCore::getJSON());
        }

        return $stdOut;
    }

    /**
     * Execute funcions of command and check if is a watch loop or a once executation
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('watch')) {
            while (true) {
                UCore::reset();

                foreach ($input->getArgument('files') as $file) {
                    UCore::load($file);
                }

                $output->write($this->_execute($input, $output)."          \r");
                sleep(1);
            }
        } else {
            $output->writeln($this->_execute($input, $output));
        }
    }
}
