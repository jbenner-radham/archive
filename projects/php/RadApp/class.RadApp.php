<?php

class RadApp
{

    # Maybe do a document's DOM as an object?
    # ---------------------------------------
    /*
        $doc = new RadApp('DOM');
        $doc->head->title = 'My Cool Page';
        $doc->body = ['div' => ['stuff in here']]; //IDK??
    */


    /* Draw out tables via arrays?:
    RadHam::tbl(array([';h' => ['col_head_1', 'col_head_2', 'col_head_3'],
                       ';r0' => ['col_row_1.1', 'col_row_1.1', 'col_row_1.1'],
                       ';r1' => ['col_row_1.1', 'col_row_1.1', 'col_row_1.1']
                ));
    
    And/Or add...
    HAML support: http://haml.info/
    MediaWiki markup support: https://www.mediawiki.org/wiki/Help:Tables
    */

    # "Pretty Print" Boolean constant.
    const PP = TRUE;

    # Document indention constant.
    const INDT = '    ';

    # Development Boolean constant.
    const DEV = TRUE;

    # JavaScript path constant.
    const PTH_JS = 'js';

    # CSS path constant.
    const PTH_CSS = 'css';

    # Page character encoding constant.
    const PG_ENC = 'utf-8';

    # Page language constant.
    const PG_LANG = 'en';

    # Page structure...
    public $pg_html = array(['header'  => FALSE],
                            ['js'      => FALSE],
                            ['css'     => FALSE],
                            ['body'    => FALSE],
                            ['footer'  => FALSE]);

    # Haml constants...
    # -----------------

    protected $haml_init = FALSE;

    # Designates an (X)HTML/XML element.
    const HAML_ELE = '%';


    # toString "echo buffer".
    public $pg_buffer = FALSE;

    # Compile the page on string call.
    public function __toString() {
        # Clear the "buffer" for the HTML to output after transerfing it to a return var.
        $return_pg = $this->pg_buffer;
        $this->pg_buffer = NULL;
        return $return_pg;
    }

    public function js($js) {
        $html = FALSE;
        if (is_array($js)) {
            $html = self::chkScript($js, 'js');
        }
        if ($html) {
            $this->pg_html['js'] = $html;
        }
        return $this;
    }

    public function css($css) {
        $html = FALSE;
        if (is_array($css)) {
            $html = self::chkScript($css, 'css');
        }
        if ($html) {
            $this->pg_html['css'] = $html;
        }
        return $this;
    }

    public static function EOL() {
        return self::PP? PHP_EOL : FALSE;
    }

    public static function codePrnt($cde) {
        echo '<code style="white-space:pre">'
             . print_r($cde, TRUE)
             . '</code>';
    }

    public function eleJs($file_js) {
        #echo 'hi';
        return sprintf('<script type="text/javascript" src="%s"></script>', $file_js);
    }

    protected static function chkScript($script_ar, $script_type) {
        $html = FALSE;
        $limit = count($script_ar) - 1;
        $i = 0;
        $script_type_u = strtoupper($script_type);
        foreach ($script_ar as $script) {
            if ($script[0] !== '/' && $script[1] !== '/') {
                # These aren't the droids you're looking for... 
                # (or AKA it's not a CDN pull.)
                if ($script[0] === ';') {
                    $script = substr_replace($script, NULL, 0, 1);
                } elseif (constant('self::PTH_' . $script_type_u)) {
                    $script = constant('self::PTH_' . $script_type_u) . DIRECTORY_SEPARATOR . $script;
                }
                if (!self::DEV && file_exists($script . '.min.' . $script_type)) {
                    $script = $script . '.min.' . $script_type;
                } elseif (file_exists($script . '.' . $script_type)) {
                    $script = $script . '.' . $script_type;
                } else {
                    $html .= '<!-- Can\'t find: ' . $script . '.(min.).' . $script_type . '//-->';
                    continue;
                }
            }
            if ($script_type == 'js') {
                # Reference: http://www.w3.org/TR/html-markup/script.html
                $html .= '<script type="text/javascript" src="' . $script . '"></script>';
            } elseif ($script_type == 'css') {
                $html .= '<link rel="stylesheet" href="' . $script . '">';
            }
            if (self::PP && $i++ < $limit) {
                $html .= PHP_EOL;
            }
        }
        return $html;
    }

    public function header(array $attribs = NULL) {
        # HTML lang reference: https://www.iana.org/assignments/language-subtag-registry
        # HTML lang subtag reference: https://www.iana.org/assignments/language-subtag-registry
        $html_ar = ['<!DOCTYPE html>', '<html lang="' . self::PG_LANG . '">', '<head>', '<meta charset="' . self::PG_ENC . '">'];
        if (isset($attribs['title'])) {
            $html_ar[] = '<title>' . $attribs['title'] . '</title>';
        }
        if ($this->pg_html['js']) {
            $html_ar[] = $this->pg_html['js'];
        }
        if ($this->pg_html['css']) {
            $html_ar[] = $this->pg_html['css'];
        }
        $html_ar[] = '</head>';
        $this->pg_buffer .= implode(PHP_EOL, $html_ar) . (self::EOL());
        return $this;
    }

    public function body() {
        $html_ar = ['<body>'];
        $html_ar[] = '</body>';
        $this->pg_buffer .= implode(PHP_EOL, $html_ar) . (self::EOL());
        return $this;
    }

    public function footer() {
        $html_ar = ['</html>'];
        $this->pg_buffer .= implode(PHP_EOL, $html_ar);
        return $this;
    }

    public function haml($file) {
        # Load Haml module on initial method call?
        # e.g. if (!$this->haml_init) {
        #          require 'class.Haml.php';
        #          $this->haml_init = TRUE;
        #      }
        if (!$this->haml_init) {
            require 'class.Haml.php';
            $this->haml_init = TRUE;
        }
        #$html = Haml::theKitchenSink($file);
        /*
        $file = trim($file);
        if (substr($file, strlen($file) - 5) != '.haml') {
            $file .= '.haml';
        }
        $lines = explode(PHP_EOL, file_get_contents($file));
        $indt = 0;
        $html = FALSE;
        $haml_ele_autoclose_ar = ['br', 'hr', 'img', 'link', 'meta', 'script'];
        $haml_attribs_ar = ['.' => 'class', '#' => 'id'];
        foreach ($lines as $line) {
            $line_attrib_sym = $line[$indt];
            if (array_key_exists($line_attrib_sym, $haml_attribs_ar)) {
                $line = trim(substr_replace($line, NULL, $indt, 1));
                $line_ws = strpos($line, ' ');
                $line_attrib_str = trim(substr_replace($line, NULL, $line_ws));
                #var_dump($line_attrib_str); echo '<br>';
                $line_cont = trim(substr_replace($line, NULL, 0, $line_ws));
                $html .= '<div ' . $haml_attribs_ar[$line_attrib_sym] . '="' . $line_attrib_str . '">' . $line_cont . '</div>' . (self::EOL());
            } elseif ($line[$indt] === self::HAML_ELE) {
                $line = trim(substr_replace($line, NULL, $indt, 1));
                $line_ws = strpos($line, ' ');
                # This is temporary & uber hacky...
                $line_ele_nme = $line;
                if (in_array($line_ele_nme, $haml_ele_autoclose_ar)) {
                    $html .= '<' . $line_ele_nme . '>' . (self::EOL());
                } else {
                    # The string one space after the detected element is the content wrapped inside...
                    $html .= '<' . $line_ele_nme . '>' . '<' . $line_ele_nme . '>' . (self::EOL());
                }
            }
        }
        */
        $haml = new Haml();
        $html = $haml->go($file);
        unset($haml);
        return $html;
    }

    public static function delFirstChar($str) {
        if (is_string($str)) {
            #
        }
    }

}

?>