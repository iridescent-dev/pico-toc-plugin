<?php

/**
 * Pico Table Of Contents Plugin
 *
 * @author  Iridescent
 * @link    https://github.com/iridescent-dev/pico-toc-plugin
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.2
 */
class TableOfContents extends AbstractPicoPlugin
{
    // Maximum level displayed in the table of contents.
    private $max_level = 5;
    // Minimum number of headers required.
    private $min_headers = 2;
    // Heading text, if a heading for the table of contents is desired.
    private $heading;

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
        if (isset($config['toc_max_level'])) {
            $this->max_level = &$config['toc_max_level'];
        }
        if (isset($config['toc_min_headers'])) {
            $this->min_headers = &$config['toc_min_headers'];
        }
        if (isset($config['toc_heading'])) {
            $this->heading = &$config['toc_heading'];
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
        if (trim($content) == "") {
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
        if (isset($elements) && $elements->length == 1) {
            $toc_element = $elements[0];

            // Get list of headers
            $max_level = $toc_element->getAttribute('max-level');
            if ($max_level === '') {
                $max_level = $this->max_level;
            }
            $xPathExpression = [];
            for ($i = 1; $i <= $max_level; $i++) {
                $xPathExpression[] = "//h$i";
            }
            $xPathExpression = join("|", $xPathExpression);

            $domXPath = new DOMXPath($document);
            $headers = $domXPath->query($xPathExpression);
            if ($headers->length < $this->min_headers) {
                return;
            }

            // Initialize Table Of Contents element
            $div_element = $document->createElement('div');
            $div_element->setAttribute('id', 'toc');

            // Add heading element, if enabled
            $heading = $toc_element->getAttribute('heading');
            if ($heading === '') {
                $heading = $this->heading;
            }
            if (isset($heading)) {
                $heading_element = $document->createElement('div', $heading);
                $heading_element->setAttribute('class', 'toc-heading');
                $div_element->appendChild($heading_element);
            }

            $ul_element = $document->createElement('ul');

            // Add missing id's to the h tags
            foreach ($headers as $header) {
               if (isset($header->tagName) && $header->tagName !== '') {
                  if ($header->getAttribute('id') === "") {
                        $slug = $this->slugify($header->nodeValue);
                        $header->setAttribute('id', $slug);
                  }

                  $class = "toc-" . strtolower($header->tagName);
                  $id = $header->getAttribute('id');
                  
                  $li_element = $document->createElement('li');
                  $li_element->setAttribute('class', $class);
                  
                  $a_element = $document->createElement('a');
                  $a_element->setAttribute('href', "#$id");
                  $a_element->nodeValue = $header->nodeValue;

                  $li_element->appendChild($a_element);
                  $ul_element->appendChild($li_element);
               }
            }

            $div_element->appendChild($ul_element);
            $toc_element->parentNode->replaceChild($div_element, $toc_element);

            $content = preg_replace(array("/<(!DOCTYPE|\?xml).+?>/", "/<\/?(html|body)>/"), array("", ""), $document->saveHTML());
        }
    }

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
}
