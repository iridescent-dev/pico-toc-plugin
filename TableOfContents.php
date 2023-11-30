<?php

/**
 * Pico Table Of Contents Plugin
 *
 * @author  Iridescent
 * @link    https://github.com/iridescent-dev/pico-toc-plugin
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 2.0
 */
class TableOfContents extends AbstractPicoPlugin
{
    protected $config = array(
        // Minimum number of headers required.
        'min_headers' => 2,
        // Minimum level displayed in the table of contents.
        'min_level' => 1,
        // Maximum level displayed in the table of contents.
        'max_level' => 5,
        // The tag used for the list: ol (ordered) or ul (unordered).
        'tag' => 'ol',
        // The css style applied to the list: numbers, bullets, none or default.
        'style' => 'none',
        // Heading text, if a heading for the table of contents is desired.
        'heading' => null,
        // Show/hide the table of contents by default.
        'toggle' => true,
        // Hide toc initially
        'initially_hide' => true,
        // Hide text
        'hide_text' => '▲',
        // Show text
        'show_text' => '▼',
    );

    protected $min_headers, $min_level, $max_level, $tag, $style, $toggle, $initially_hide, $hide_text, $show_text, $heading, $toc_element_xml;

    protected $available_tags = ['ol', 'ul'];
    protected $available_styles = ['numbers', 'bullets', 'none', 'default'];

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
        if (isset($config['TOC'])) {
            foreach ($this->config as $key => $val) {
                if (isset($config['TOC'][$key])) {
                    $this->config[$key] = &$config['TOC'][$key];
                }
            }

            // Check if the tag is valid
            if (!in_array($this->config['tag'], $this->available_tags)) {
                throw new RuntimeException('Invalid TOC tag "' . $this->config['tag'] . '", the possible values are [ ' . implode(', ', $this->available_tags) . ' ].');
            }
            // Check if the style is valid
            if (!in_array($this->config['style'], $this->available_styles)) {
                throw new RuntimeException('Invalid TOC style "' . $this->config['style'] . '", the possible values are [ ' . implode(', ', $this->available_styles) . ' ].');
            }
        }
    }

    /**
     * Triggered when Pico reads its known meta header fields
     *
     * @see Pico::getMetaHeaders()
     *
     * @param string[] &$headers list of known meta header fields; the array
     *     key specifies the YAML key to search for, the array value is later
     *     used to access the found value
     */
    public function onMetaHeaders(array &$headers)
    {
        $headers['toc'] = 'TOC';
    }

    /**
     * Triggered after Pico has parsed the meta header
     *
     * @see DummyPlugin::onMetaParsing()
     * @see Pico::getFileMeta()
     *
     * @param string[] &$meta parsed meta data
     */
    public function onMetaParsed(array &$meta)
    {
        // Checks if the language of the page is set and that it is available for the site
        $this->min_headers = $this->getVal('min_headers', $meta);
        $this->min_level = $this->getVal('min_level', $meta);
        $this->max_level = $this->getVal('max_level', $meta);
        $this->tag = $this->getVal('tag', $meta);
        $this->style = $this->getVal('style', $meta);
        $this->heading = $this->getVal('heading', $meta);
        $this->initially_hide = $this->getVal('initially_hide', $meta);
        $this->toggle = $this->getVal('toggle', $meta);
        $this->hide_text = $this->getVal('hide_text', $meta);
        $this->show_text = $this->getVal('show_text', $meta);

        // Check if the tag is valid
        if (!in_array($this->tag, $this->available_tags)) {
            throw new RuntimeException('Invalid tag "' . $this->tag . '", the possible values are [ ' . implode(', ', $this->available_tags) . ' ].');
        }
        // Check if the style is valid
        if (!in_array($this->style, $this->available_styles)) {
            throw new RuntimeException('Invalid style "' . $this->style . '", the possible values are [ ' . implode(', ', $this->available_styles) . ' ].');
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
        if ($this->min_level > $this->max_level) {
            return; // No level to display
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

        // Get the list of headers
        $xPathExpression = [];
        for ($i = $this->min_level; $i <= $this->max_level; $i++) {
            $xPathExpression[] = "//h$i";
        }
        $xPathExpression = join("|", $xPathExpression);

        $domXPath = new DOMXPath($document);
        $headers = $domXPath->query($xPathExpression);
        if (!$headers || $headers->length < $this->min_headers) {
            return; // Not enough header to display
        }

        // Initialize TOC element
        $div_element = $document->createElement('div');
        $div_element->setAttribute('id', 'toc');

        // Add heading element, if enabled
        if (isset($this->heading) || $this->toggle) {
            $heading_element = $document->createElement('div', isset($this->heading) ? $this->heading : '&nbsp;');
            $heading_element->setAttribute('class', 'toc-heading');
            
            // Create the toogle button element if enabled
            if($this->toggle)
            {
                $button_element = $document->createElement('button', $this->initially_hide ? $this->show_text : $this->hide_text);
                $button_element->setAttribute('id', 'toc-toggle');
                $button_element->setAttribute('data-show-text', $this->show_text);
                $button_element->setAttribute('data-hide-text', $this->hide_text);

                $heading_element->appendChild($button_element);
            }

            $div_element->appendChild($heading_element);
        }

        // Add the list element
        $list_element = $this->getList($document, $headers);
        $div_element->appendChild($list_element);

        // Replace [toc] in document
        $nodes = $domXPath->query('//p');
        foreach ($nodes as $node) {
            if (trim($node->nodeValue) === "[toc]") {
                $node->parentNode->replaceChild($div_element, $node);
                break;
            }
        }

        $content = preg_replace(array("/<(!DOCTYPE|\?xml).+?>/", "/<\/?(html|body)>/"), array("", ""), $document->saveHTML());

        // Save the TOC element as string
        $this->toc_element_xml = $div_element->ownerDocument->saveXML($div_element);
    }

    /**
     * Triggered before Pico renders the page
     *
     * @see DummyPlugin::onPageRendered()
     *
     * @param string &$templateName  file name of the template
     * @param array  &$twigVariables template variables
     */
    public function onPageRendering(&$templateName, array &$twigVariables)
    {
        $twigVariables['toc'] = new Twig_Markup($this->toc_element_xml, 'UTF-8');
    }

    /* ********************************************************************************* */

    /**
     * Return the value from the key in metadatas if it exists, default config value otherwise.
     *
     * @param string $key
     * @param string[] $meta parsed meta data
     * @return string
     */
    private function getVal($key, $meta)
    {
        return (isset($meta['toc']) && isset($meta['toc'][$key])) ? $meta['toc'][$key] : $this->config[$key];
    }

    /**
     * Creates a list element from the headers.
     * Function called recursively to create nested lists.
     *
     * @param DOMDocument $document
     * @param DOMNodeList $headers
     * @param integer $index
     * @return DOMElement
     */
    private function getList($document, $headers, &$index = 0, $isTopLevel = true)
    {
        // Initialize ordered list element
        $list_element = $document->createElement($this->tag);
        if ($this->style !== "default") {
            $list_element->setAttribute('class', "toc-$this->style");
        }

        if ($this->toggle && $isTopLevel)
            {
                if ($this->initially_hide === true) {
                    $list_element->setAttribute('class', 'toc-hide');
                } else {
                    $list_element->setAttribute('class', 'toc-show');
                }
            }

        for ($index; $index < $headers->length; $index++) {
            $curr_header = $headers[$index];
            if (isset($curr_header->tagName) && $curr_header->tagName !== '') {
                $header_classes = explode(' ', $curr_header->getAttribute('class'));
				$is_unlisted = in_array('unlisted', $header_classes);

                // Add missing id's to the h tags
                $id = $curr_header->getAttribute('id');
                if ($id === "") {
                    $id = $this->slugify($curr_header->nodeValue) . '-' . $index;
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
                    $nested_list_element = $this->getList($document, $headers, $index, false);
                    if ($nested_list_element->hasChildNodes()) { 
						$li_element->appendChild($nested_list_element);
					};
                }

                if (!$is_unlisted) {
					$list_element->appendChild($li_element);
				}

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
}
