<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\Output\Node\Printer\Html;

use Behat\Behat\Output\Node\Printer\FeaturePrinter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\TaggedNodeInterface;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;

/**
 * Prints feature header and footer.
 *
 * @author Ali Bahman <abn@webit4.me>
 */
final class HtmlFeaturePrinter implements FeaturePrinter
{
    /**
     * @var string
     */
    private $indentText;
    /**
     * @var string
     */
    private $subIndentText;

    /**
     * Too keep track of printed features
     * @var bool
     */
    static $firstRun = true;

    /**
     * Initializes printer.
     *
     * @param integer $indentation
     * @param integer $subIndentation
     */
    public function __construct($indentation = 0, $subIndentation = 2)
    {
        $this->indentText = str_repeat(' ', intval($indentation));
        $this->subIndentText = $this->indentText . str_repeat(' ', intval($subIndentation));
    }

    /**
     * {@inheritdoc}
     */
    public function printHeader(Formatter $formatter, FeatureNode $feature)
    {
        // IWe only want the html header to be printed on the first feature
        // (in cases that if we have more than one)
        if(self::$firstRun){
            $htmlTemplate = file_get_contents(__DIR__ . '/template-header.html');
            $formatter->getOutputPrinter()->writeln($htmlTemplate);
            self::$firstRun = false;
        }

        if ($feature instanceof TaggedNodeInterface) {
            $this->printTags($formatter->getOutputPrinter(), $feature->getTags());
        }

        $this->printTitle($formatter->getOutputPrinter(), $feature);
        $this->printDescription($formatter->getOutputPrinter(), $feature);
    }

    /**
     * {@inheritdoc}
     */
    public function printFooter(Formatter $formatter, TestResult $result)
    {
        $formatter->getOutputPrinter()->writeln('</div> <!-- div class="opened-for-codenpen" -->');
        $formatter->getOutputPrinter()->writeln('</div> <!-- div class="opened-for-coden" -->');
    }

    /**
     * Prints feature tags.
     *
     * @param OutputPrinter $printer
     * @param string[]      $tags
     */
    private function printTags(OutputPrinter $printer, array $tags)
    {
        if (!count($tags)) {
            return;
        }

        $tags = array_map(array($this, 'prependTagWithTagSign'), $tags);
        $printer->writeln(sprintf('%s{+tag}%s{-tag}', $this->indentText, implode(' ', $tags)));
    }

    /**
     * Prints feature title using provided printer.
     *
     * @param OutputPrinter $printer
     * @param FeatureNode   $feature
     */
    private function printTitle(OutputPrinter $printer, FeatureNode $feature)
    {

        $printer->write(sprintf('<h1> %s', $feature->getKeyword()));

        if ($title = $feature->getTitle()) {
            $printer->write(sprintf(' %s', $title));
        }
    }

    /**
     * Prints feature description using provided printer.
     *
     * @param OutputPrinter $printer
     * @param FeatureNode   $feature
     */
    private function printDescription(OutputPrinter $printer, FeatureNode $feature)
    {
        if (!$feature->getDescription()) {
            $printer->writeln();

            return;
        }

        foreach (explode("\n", $feature->getDescription()) as $descriptionLine) {
            $printer->writeln(sprintf('%s', $descriptionLine));
        }

        $printer->writeln('</h1><div class="opened-for-coden">');

    }

    /**
     * Prepends tags string with tag-sign.
     *
     * @param string $tag
     *
     * @return string
     */
    private function prependTagWithTagSign($tag)
    {
        return '@' . $tag;
    }
}
