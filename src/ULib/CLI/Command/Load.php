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
    private $_files;
    private $_autoloader;
    private $_out;
    private $_summary;
    private $_watch;

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
               'autoloader',
               null,
               InputOption::VALUE_REQUIRED,
               'Filename. If set, it will be included firt and only one time'
            )
            ->addOption(
               'out',
               null,
               InputOption::VALUE_REQUIRED,
               'Filename. If set, the complete report will be saved in a JSON string'
            )
            ->addOption(
               'summary',
               's',
               InputOption::VALUE_NONE,
               'If set, the summary report will be returned'
            )
            ->addOption(
               'watch',
               'w',
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

        try {
            UCore::reset();

            foreach ($this->_files as $file) {
                UCore::load($file);
            }

            if (UCore::getBool()) {
                $stdOut .= '<info>OK!</info>';
            } else {
                $stdOut .= '<error>Doh!</error>';
            }

            if ($this->_summary) {
                $report = json_decode(UCore::getJSON());
                $ok     = $report->summary->asserts->ok;
                $nok    = $report->summary->asserts->nok;
                $total  = $ok + $nok;

                $stdOut .= ' '.$total.' asserts. '.$ok.' passed and '.$nok.' failed.';
            }

            if ($this->_out) {
                file_put_contents($this->_out, UCore::getJSON());
            }
        } catch (Exception $e) {
            $stdOut = '<error>Oh crap! Wait a second...</error>';
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
        if ($this->_autoloader = $input->getOption('autoloader')) {
            require $this->_autoloader;
        }

        $this->_files       = $input->getArgument('files');
        $this->_out         = $input->getOption('out');
        $this->_summary     = $input->getOption('summary');
        $this->_watch       = $input->getOption('watch');

        if ($this->_watch) {
            $lastLineLength = 0;

            while (true) {
                $stdout = $this->_execute($input, $output).' <comment>(type Ctrl + C to stop)</comment>';

                $output->write("\x0D");
                $output->write(str_pad($stdout, $lastLineLength, "\x20", STR_PAD_RIGHT));

                $lastLineLength = strlen($stdout);

                sleep(1);
            }
        } else {
            $output->writeln($this->_execute($input, $output));
        }
    }
}
