<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\Output\Node\Printer\Html;

use Behat\Behat\Output\Node\Printer\Helper\ResultToStringConverter;
use Behat\Behat\Output\Node\Printer\Helper\StepTextPainter;
use Behat\Behat\Output\Node\Printer\StepPrinter;
use Behat\Behat\Tester\Result\DefinedStepResult;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Gherkin\Node\ArgumentInterface;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\ScenarioLikeInterface as Scenario;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Exception\ExceptionPresenter;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\ExceptionResult;

/**
 * Prints step.
 *
 * @author Ali Bahman <abn@webit4.me>
 */
final class HtmlStepPrinter implements StepPrinter
{
    /**
     * @var StepTextPainter
     */
    private $textPainter;
    /**
     * @var ResultToStringConverter
     */
    private $resultConverter;
    /**
     * @var HtmlPathPrinter
     */
    private $pathPrinter;
    /**
     * @var ExceptionPresenter
     */
    private $exceptionPresenter;
    /**
     * @var string
     */
    private $indentText;
    /**
     * @var string
     */
    private $subIndentText;

    /**
     * Initializes printer.
     *
     * @param StepTextPainter         $textPainter
     * @param ResultToStringConverter $resultConverter
     * @param HtmlPathPrinter       $pathPrinter
     * @param ExceptionPresenter      $exceptionPresenter
     * @param integer                 $indentation
     * @param integer                 $subIndentation
     */
    public function __construct(
        StepTextPainter $textPainter,
        ResultToStringConverter $resultConverter,
        HtmlPathPrinter $pathPrinter,
        ExceptionPresenter $exceptionPresenter,
        $indentation = 4,
        $subIndentation = 2
    ) {
        $this->textPainter = $textPainter;
        $this->resultConverter = $resultConverter;
        $this->pathPrinter = $pathPrinter;
        $this->exceptionPresenter = $exceptionPresenter;
        $this->indentText = str_repeat(' ', intval($indentation));
        $this->subIndentText = $this->indentText . str_repeat(' ', intval($subIndentation));
    }

    /**
     * {@inheritdoc}
     */
    public function printStep(Formatter $formatter, Scenario $scenario, StepNode $step, StepResult $result)
    {
        $this->printText($formatter->getOutputPrinter(), $step->getKeyword(), $step->getText(), $result);
        $this->pathPrinter->printStepPath($formatter, $scenario, $step, $result, mb_strlen($this->indentText, 'utf8'));
        $this->printArguments($formatter, $step->getArguments(), $result);
        $this->printStdOut($formatter->getOutputPrinter(), $result);
        $this->printException($formatter->getOutputPrinter(), $result);
    }

    /**
     * Prints step text.
     *
     * @param OutputPrinter $printer
     * @param string        $stepType
     * @param string        $stepText
     * @param StepResult    $result
     */
    private function printText(OutputPrinter $printer, $stepType, $stepText, StepResult $result)
    {
        if ($result && $result instanceof DefinedStepResult && $result->getStepDefinition()) {
            $definition = $result->getStepDefinition();
            $stepText = $this->textPainter->paintText($stepText, $definition, $result);
        }
        if($stepType == 'Then'){
            $printer->write(sprintf('<h3>%s %s</h3>', $stepType, $stepText));
        }else{
            $printer->write(sprintf('%s %s<br>', $stepType, $stepText));
        }
    }

    /**
     * Prints step multiline arguments.
     *
     * @param Formatter           $formatter
     * @param ArgumentInterface[] $arguments
     * @param StepResult          $result
     */
    private function printArguments(Formatter $formatter, array $arguments, StepResult $result)
    {
        $style = $this->resultConverter->convertResultToString($result);

        foreach ($arguments as $argument) {
            $text = $this->getArgumentString($argument, !$formatter->getParameter('multiline'));

            $indentedText = implode("\n", array_map(array($this, 'subIndent'), explode("\n", $text)));
            $formatter->getOutputPrinter()->writeln(sprintf('<div>%s</div>',$indentedText));
        }
    }

    /**
     * Prints step output (if has one).
     *
     * @param OutputPrinter $printer
     * @param StepResult    $result
     */
    private function printStdOut(OutputPrinter $printer, StepResult $result)
    {
        if (!$result instanceof ExecutedStepResult || null === $result->getCallResult()->getStdOut()) {
            return;
        }

        $callResult = $result->getCallResult();
        $indentedText = $this->subIndentText;

        $pad = function ($line) use ($indentedText) {
            return sprintf(
                '%s│ {+stdout}%s{-stdout}', $indentedText, $line
            );
        };

        $printer->writeln(implode("\n", array_map($pad, explode("\n", $callResult->getStdOut()))));
    }

    /**
     * Prints step exception (if has one).
     *
     * @param OutputPrinter $printer
     * @param StepResult    $result
     */
    private function printException(OutputPrinter $printer, StepResult $result)
    {
        $style = $this->resultConverter->convertResultToString($result);

        if (!$result instanceof ExceptionResult || !$result->hasException()) {
            return;
        }

        $text = $this->exceptionPresenter->presentException($result->getException());
        $indentedText = implode("\n", array_map(array($this, 'subIndent'), explode("\n", $text)));
        $printer->writeln(sprintf('{+%s}%s{-%s}', $style, $indentedText, $style));
    }

    /**
     * Returns argument string for provided argument.
     *
     * @param ArgumentInterface $argument
     * @param Boolean           $collapse
     *
     * @return string
     */
    private function getArgumentString(ArgumentInterface $argument, $collapse = false)
    {
        if ($collapse) {
            return '...';
        }

        if ($argument instanceof PyStringNode) {
            $text = '"""' . "\n" . $argument . "\n" . '"""';

            return $text;
        }

        return (string) $argument;
    }

    /**
     * Indents text to the subIndentation level.
     *
     * @param string $text
     *
     * @return string
     */
    private function subIndent($text)
    {
        return $this->subIndentText . $text;
    }
}
