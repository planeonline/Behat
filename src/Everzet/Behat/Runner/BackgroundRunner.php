<?php

namespace Everzet\Behat\Runner;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\Event;

use Everzet\Gherkin\Element\Scenario\BackgroundElement;

use Everzet\Behat\Loader\StepsLoader;

class BackgroundRunner extends BaseStepsRunner implements RunnerInterface
{
    protected $background;
    protected $definitions;
    protected $dispatcher;

    public function __construct(BackgroundElement $background, StepsLoader $definitions, 
                                Container $container)
    {
        $this->background   = $background;
        $this->definitions  = $definitions;
        $this->dispatcher   = $container->getEventDispatcherService();

        $this->initStepRunners(
            $this->background->getSteps()
          , $this->definitions
          , $container
        );
    }

    public function getBackground()
    {
        return $this->background;
    }

    public function run(RunnerInterface $caller = null)
    {
        $this->setCaller($caller);
        $this->dispatcher->notify(new Event($this, 'background.pre_test'));

        foreach ($this as $runner) {
            $runner->run($this);
        }

        $this->dispatcher->notify(new Event($this, 'background.post_test'));
    }
}