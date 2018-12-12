<?php

namespace Pumukit\SchemaBundle\Command;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class NumericalIDCommand extends ContainerAwareCommand
{
    private $dm;
    private $step;
    private $force;
    private $output;
    private $limit;

    protected function configure()
    {
        $this
            ->setName('pumukit:update:numerical:id')
            ->setDescription('Generate and update numerical ID on series and Multimedia Object.')
            ->addOption('step', 'S', InputOption::VALUE_REQUIRED, 'Step of command. See help for more info', -99)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter force the execution of this action')
            ->setHelp(<<<EOT
            
EOT
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->step = $input->getOption('step');
        $this->force = (true === $input->getOption('force'));
        $this->limit = 100;

        $this->output = $output;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($this->step) {
            case 'generate':
                $this->generateNewNumericalID();
                break;
            default:
                $output->writeln(' ***** Please select an valid step');
        }
    }

    private function generateNewNumericalID()
    {
        $this->output->writeln(
            array('<info> ***** Executing pumukit:update:numerical:id *****</info>'),
            array('Checking status...')
        );

        $status = $this->checkStatus();
        if (!$status) {
            return false;
        }

        $lastNumericalIDSeries = $this->getLastNumericalID(true);
        $lastNumericalIDMultimediaObject = $this->getLastNumericalID(false);

        $criteria = array(
            'numerical_id' => array('$exists' => false),
        );

        $multimediaObjects = $this->getMultimediaObjects($criteria, false);

        $this->output->writeln(
            array(
                '',
                '<warning> ***** Updating multimedia objects *****</warning>',
            )
        );

        $this->generateNumericalID($multimediaObjects, $lastNumericalIDMultimediaObject);

        $series = $this->getSeries($criteria, false);

        $this->output->writeln(
            array(
                '',
                '<warning> ***** Updating series *****</warning>',
            )
        );
        $this->generateNumericalID($series, $lastNumericalIDSeries);

        die;
    }

    private function checkStatus()
    {
        $criteria = array(
            'numerical_id' => array('$exists' => false),
        );

        $multimediaObjects = $this->getMultimediaObjects($criteria, true, $this->limit);
        $series = $this->getSeries($criteria, true);

        if ($multimediaObjects || $series) {
            $this->output->writeln(
                array(
                    '<error>'.sprintf('There are %s multimedia objects and %s series with pumukit1id and not numerical ID, please execute query first', count($multimediaObjects), count($series)).'</error>',
                )
            );

            return false;
        }

        return true;
    }

    private function getMultimediaObjects($criteria, $withPumukit1Id = false)
    {
        $criteria = $this->createCriteria($criteria, $withPumukit1Id);

        $multimediaObjects = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy($criteria);

        return $multimediaObjects;
    }

    private function getSeries($criteria, $withPumukit1Id = false)
    {
        $criteria = $this->createCriteria($criteria, $withPumukit1Id);

        $series = $this->dm->getRepository('PumukitSchemaBundle:Series')->findBy($criteria);

        return $series;
    }

    private function createCriteria($criteria, $withPumukit1Id)
    {
        $newCriteria = $criteria;
        $newCriteria['status'] = array('$ne' => MultimediaObject::STATUS_PROTOTYPE);
        $newCriteria['properties.pumukit1id'] = array('$exists' => $withPumukit1Id);

        return $newCriteria;
    }

    private function getLastNumericalID($series = false)
    {
        if ($series) {
            $series = $this->dm->getRepository('PumukitSchemaBundle:Series')->createQueryBuilder()
                ->field('numerical_id')->exists(true)
                ->sort(array('numerical_id' => -1))
                ->getQuery()
                ->getSingleResult();

            $lastNumericalID = $series->getNumericalID();
        } else {
            $multimediaObject = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->createQueryBuilder()
                ->field('numerical_id')->exists(true)
                ->sort(array('numerical_id' => -1))
                ->getQuery()
                ->getSingleResult();

            $lastNumericalID = $multimediaObject->getNumericalID();
        }

        return $lastNumericalID;
    }

    private function generateNumericalID($elements, $lastNumericalID)
    {
        $progressBar = new ProgressBar($this->output, count($elements));
        $progressBar->setFormat('verbose');
        $progressBar->start();

        $i = 0;
        foreach ($elements as $element) {
            ++$i;
            $nextNumericalID = $lastNumericalID + 1;
            $element->setNumericalID($nextNumericalID);
            $lastNumericalID = $nextNumericalID;
            $progressBar->advance();

            if (0 == $i % 50) {
                $this->dm->flush();
            }
        }

        $this->dm->flush();
        $progressBar->finish();
    }
}
