<?php

/**
 * Pico Table Of Contents Plugin
 *
 * @author  Iridescent
 * @link    https://github.com/iridescent-dev/pico-toc-plugin
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.5
 */
class TableOfContents extends AbstractPicoPlugin
{
    // Minimum level displayed in the table of contents.
    private $min_level = 1;
    // Maximum level displayed in the table of contents.
    private $max_level = 5;
    // Minimum number of headers required.
    private $min_headers = 2;
    // Heading text, if a heading for the table of contents is desired.
    private $heading;
    // The style of list: numbers, bullets or none.
    private $style = "none";
    private $available_styles = ["numbers", "bullets", "none"];

    /**
     * Triggered after Pico has read its configuration
     *
     * @see Pico::getConfig()
     * @see Pico::getBaseUrl()
     * @see Pico::isUrlRewritingEnabled()
     *
     * @param array &$config array of config variables
     */
    public function onConfigLoaded(&$config)
    {
        if (isset($config['toc_min_level'])) {
            $this->min_level = &$config['toc_min_level'];
        }
        if (isset($config['toc_max_level'])) {
            $this->max_level = &$config['toc_max_level'];
        }
        if (isset($config['toc_min_headers'])) {
            $this->min_headers = &$config['toc_min_headers'];
        }
        if (isset($config['toc_heading'])) {
            $this->heading = &$config['toc_heading'];
        }
        if (isset($config['toc_style'])) {
            $style = &$config['toc_style'];
            if (!in_array($style, $this->available_styles)) {
                throw new RuntimeException('Invalid toc_style "' . $style . '", the possible values are [ ' . implode(', ', $this->available_styles) . ' ].');
            }
            $this->style = $style;
        }
    }

    /**
     * Triggered after Pico has parsed the contents of the file to serve
     *
     * @see DummyPlugin::onContentParsing()
     * @see DummyPlugin::onContentPrepared()
     * @see Pico::getFileContent()
     *
     * @param string &$content parsed contents (HTML) of the requested page
     */
    public function onContentParsed(&$content)
    {
        if (trim($content) === "") {
            return;
        }

        $document = new DOMDocument('1.0', 'utf-8');
        libxml_use_internal_errors(true);
        if (!$document->loadHTML('<?xml encoding="utf-8" ?>' . $content)) {
            foreach (libxml_get_errors() as $error) {
                // handle errors here
            }
            libxml_clear_errors();
            return;
        }

        $elements = $document->getElementsByTagName("toc");
        if (isset($elements) && $elements->length === 1) {
            $toc_element = $elements[0];

            // Get tag attributes
            $min_level = $toc_element->getAttribute('min-level') ?: $this->min_level;
            $max_level = $toc_element->getAttribute('max-level') ?: $this->max_level;
            if ($min_level > $max_level) {
                return; // No level to display
            }
            $heading = $toc_element->getAttribute('heading') ?: $this->heading;
            $style = $toc_element->getAttribute('style') ?: $this->style;

            // Get the list of headers
            $xPathExpression = [];
            for ($i = $min_level; $i <= $max_level; $i++) {
                $xPathExpression[] = "//h$i";
            }
            $xPathExpression = join("|", $xPathExpression);

            $domXPath = new DOMXPath($document);
            $headers = $domXPath->query($xPathExpression);
            if (!$headers || $headers->length < $this->min_headers) {
                return; // Not enough header to display
            }

            // Initialize the Table Of Contents element
            $div_element = $document->createElement('div');
            $div_element->setAttribute('id', 'toc');

            // Add heading element, if enabled
            if (isset($heading)) {
                $heading_element = $document->createElement('div', $heading);
                $heading_element->setAttribute('class', 'toc-heading');
                $div_element->appendChild($heading_element);
            }

            // Add the list element
            $list_element = $this->get_list($document, $style, $headers);
            $div_element->appendChild($list_element);

            $toc_element->parentNode->replaceChild($div_element, $toc_element);

            $content = preg_replace(array("/<(!DOCTYPE|\?xml).+?>/", "/<\/?(html|body)>/"), array("", ""), $document->saveHTML());
        }
    }

    /**
     * Generate a slug from a string.
     *
     * @param string $text
     * @return string
     */
    private function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        // trim
        $text = trim($text, '-');
        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Creates a list element from the headers.
     * Function called recursively to create nested lists.
     *
     * @param DOMDocument $document
     * @param string $style
     * @param DOMNodeList $headers
     * @param integer $index
     * @return DOMElement
     */
    private function get_list($document, $style, $headers, &$index = 0)
    {
        // Initialize ordered list element
        $list_element = $document->createElement('ol');
        if (in_array($style, $this->available_styles)) {
            $list_element->setAttribute('class', "toc-$style");
        }

        for ($index; $index < $headers->length; $index++) {
            $curr_header = $headers[$index];
            if (isset($curr_header->tagName) && $curr_header->tagName !== '') {
                // Add missing id's to the h tags
                $id = $curr_header->getAttribute('id');
                if ($id === "") {
                    $id = $this->slugify($curr_header->nodeValue);
                    $curr_header->setAttribute('id', $id);
                }

                // Initialize the list item with a link to the header
                $li_element = $document->createElement('li');
                $a_element = $document->createElement('a');
                $a_element->setAttribute('href', "#$id");
                $a_element->nodeValue = $curr_header->nodeValue;
                $li_element->appendChild($a_element);

                $next_header = ($index + 1 < $headers->length) ? $headers[$index + 1] : null;
                if ($next_header && strtolower($curr_header->tagName) < strtolower($next_header->tagName)) {
                    // The next header is at a lower level -> add nested headers
                    $index++;
                    $nested_list_element = $this->get_list($document, $style, $headers, $index);
                    $li_element->appendChild($nested_list_element);
                }

                $list_element->appendChild($li_element);

                // Refresh next_header with the updated index
                $next_header = ($index + 1 < $headers->length) ? $headers[$index + 1] : null;
                if ($next_header && strtolower($curr_header->tagName) > strtolower($next_header->tagName)) {
                    // The next header is at a higher level -> stop current loop
                    break;
                }
            }
        }
        return $list_element;
    }
}
