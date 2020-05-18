
# Pico Table Of Contents Plugin

Generate a table of contents for the pages of your [Pico](http://picocms.org) site.

- [Pico Table Of Contents Plugin](#pico-table-of-contents-plugin)
  - [Getting Started](#getting-started)
    - [Usage](#usage)
    - [Update your theme](#update-your-theme)
    - [Configuration settings](#configuration-settings)
    - [Example](#example)
      - [The `index.md` file](#the-indexmd-file)
      - [Screenshot](#screenshot)
  - [License](#license)

## Getting Started
* Copy the `TableOfContents` folder into the `plugins` folder of your project.
* Update your theme to include CSS style.
* Define the configuration variables if default values are not suitable.
* Add `<toc />` in your content files where you want the table of contents to appear.

### Usage
In your content markdown files, simply add the `<toc />` element where you want the table of contents to be inserted.

### Update your theme
In your template files, add the plugin's CSS style in the `head` section:

``` html
<!-- index.twig -->
<head>
    ...
    <!-- Table Of Contents -->
    <link rel="stylesheet" href="{{ base_url }}/plugins/TableOfContents/style.css">
    ...
</head>
```

### Configuration settings
You can change the default configuration by adding values to your `config.php` file. Here are the options available and what they do.
* `toc_max_level` - Maximum header level displayed in the table of contents. - *Default value: 5*.
* `toc_min_headers` - Minimum number of headers required. - *Default value: 2*

For reference, these values are set in `config.php` using the following format:

``` yml
toc_max_level: 5
toc_min_headers: 2
```

This configuration will be applied to the entire site, but it's also possible to override the `toc_max_level` for a specific element using the attribute `max-level`.

``` html
<toc max-level="4" />
```

### Example
#### The `index.md` file

In this example, the `max-level` is set to 3.

``` html
---
Title: Table Of Contents Example
Description: 
---

Here is the Table Of Contents generated for the current page:

<toc max-level="3" />

# This is a `<h1>`
Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. 

## And this is a `<h2>`
Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. 

### Then, you can see a `<h3>`
Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. 

#### But `<h4>` are not visible in the Table Of Contents
Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. 

### An other `<h3>`
Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. 

# An other `<h1>`
Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. 

## An other `<h2>`
Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. 

## An other `<h2>`
Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. 

```

#### Screenshot
<p align="center">
  <img src="Screenshot.png" title="Screenshot">
</p>


## License
InAppPurchaseLib is open-sourced library licensed under the MIT License. See [LICENSE](LICENSE) for details.
