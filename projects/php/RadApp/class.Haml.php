<?php

### Conventions ###

## Variable Naming ##
# $ln: "Line" shorthand.
# $len: "Length" shorthand.
# $nme: "Name" shorthand.

## Commenting ##
# EOM: "End of Method"

/* --Sample Haml File--

-# yolo.haml
.yolo Hi Thar!
%hr
#nolo Hi Thar Too!
%hr.sweetness
%hr#robots
.polo Hi Thar Thrice!
%hr

*/


class Haml
{

    # Designates an (X)HTML/XML element.
    const HAML_ELE = '%';

    # "Pretty Print" Boolean constant.
    const PP = TRUE;

    # Current indention level column.
    public $indt = 0;

    public $ln = FALSE;

    # Haml attributes array defined as (Key)[Symbol] => (Value)[Attribute Name]
    public $haml_ele_attribs_ar = ['.' => 'class', '#' => 'id'];

    public $haml_ele_autoclose_ar = ['br', 'hr', 'img', 'link', 'meta', 'script'];

    # This method is redundant, since the RadApp class has it as well. Look into this... 
    # Also, maybe this should just be changed to a class property set by the constructor "$this->eol = FALSE/PHP_EOL"??
    public static function EOL() {
        return self::PP? PHP_EOL : FALSE;
    } #EOM EOL

    public function go($file) {
        $html = FALSE;
        if ($lines = self::fileToAr($file)) {
            foreach ($lines as $ln) {
                $this->ln = $ln;
                #echo 'Current Indt: ' . $this->lnIndtParse() . PHP_EOL;
                $html .= $this->lnParse($ln);
            }
        }
        return $html;
    } # EOM go

    # The fileToAr method checks for a file's existence and then returns an array.
    public static function fileToAr($file) {
        if (substr($file = trim($file), strlen($file) - 5) != '.haml') {
            $file .= '.haml';
        }
        return file_exists($file)? explode(PHP_EOL, file_get_contents($file)) : FALSE;
    } # EOM fileToAr

    ##### ln* methods #####

    public function lnParse($ln) {
        $html = FALSE;
        if (isset($ln[$this->indt])) {
            $ln_attrib_sym = $ln[$this->indt];
            if ($html = $this->lnAttribChk($ln, $ln_attrib_sym, TRUE)) {
                # Placeholder...
            } elseif ($ln[$this->indt] === self::HAML_ELE) {
                $ln = trim(substr_replace($ln, NULL, $this->indt, 1));
                $line_ws_pos = strpos($ln, ' '); # IDK if this is even needed at the moment...
                # This is temporary & uber hacky...
                $ln_ele_nme = $ln;

                $cur_attrib_str = $cur_attrib_nme = FALSE;
                if ($attrib_ar = $this->lnAttribParse($ln)) {
                    $cur_attrib_sym = $attrib_ar['attrib_str'][0];
                    $cur_attrib_nme = $this->haml_ele_attribs_ar[$cur_attrib_sym];
                    $cur_attrib_val = ltrim(substr_replace($attrib_ar['attrib_str'], NULL, 0, 1));
                    $cur_attrib_str = $cur_attrib_nme . '="' . $cur_attrib_val . '"';
                    $ln_ele_nme = ltrim(substr_replace($ln_ele_nme, NULL, stripos($ln_ele_nme, $cur_attrib_sym)));
                }
                if (in_array($ln_ele_nme, $this->haml_ele_autoclose_ar)) {
                    $html .= '<' . $ln_ele_nme . ($cur_attrib_str? ' ' . $cur_attrib_str : NULL) . '>' . (self::EOL());
                } else {
                    # The string one space after the detected element is the content wrapped inside...
                    $html .= '<' . $ln_ele_nme . ($cur_attrib_str? ' ' . $cur_attrib_str : NULL) . '>' . '<' . $ln_ele_nme . '>' . (self::EOL());
                }
            }
        }
        return $html;
    } # EOM lnParse

    # The lnEleParse method takes a line as input & returns the parsed HTML element, or FALSE if none is found.
    public function lnEleParse($ln) { /* Maybe rename this to lnEleGet() ? */
        if (array_key_exists($ln[0], $this->haml_ele_attribs_ar)) {
            return 'div';
        } elseif ($ln[0] === self::HAML_ELE) {
            $ln = self::lnRmFirstChar($ln);
            for ($i = 0, $len = strlen($ln); $i < $len; ++$i) {
                $ln_char = $ln[$i];
                if (array_key_exists($ln_char, $this->haml_ele_attribs_ar)) {
                    return $ele = rtrim(substr_replace($ln, '', $i));  # When an id, class or whitespace is discovered...
                }
            }
            return $ln;
        }
        return FALSE;
    } # EOM lnEleParse

    public static function lnRmFirstChar($ln) {
        $ln[0] = '';
        return ltrim($ln);
    } # EOM lnRmFirstChar

    # The lnChomp method takes a string and removes everything from the first index speicified to the end of the string (or a second optional string index).
    public static function lnChomp($ln, $ln_index_start, $ln_index_end = FALSE) {
        if (!$ln_index_end) {
            $ln_new = substr_replace($ln, '', $ln_index_start); 
        } else {
            $ln_new = substr_replace($ln, '', $ln_index_start, $ln_index_end);
        }
        return $ln_new;
    } # EOM lnChomp

    # The lnSansIndt method returns the current line with the current indentation removed from the begining of the string.
    public function lnSansIndt($ln) {
        return ltrim(substr_replace($ln, '', 0, $this->indt));
    } # EOM lnSansIndt

    # The lnIndtParse method returns the current indention level.
    public function lnIndtParse() {
        for ($i = 0, $len = strlen($this->ln); $i < $len; ++$i) {
            if (!ctype_space($this->ln[$i])) {
                return $i;
            }
        }
        return FALSE;
    } # EOM lnIndtParse

    public function lnAttribParse($ln) {
        for ($char_pos = 0, $len = strlen($ln); $char_pos < $len; ++$char_pos) {
            $ln_char = $ln[$char_pos];
            $haml_ele_char = FALSE;
            if (array_key_exists($ln_char, $this->haml_ele_attribs_ar)) {
                $rtrn_ar = [];
                $haml_sym_char = $ln_char;
                $haml_sym_char_pos = $char_pos;
                $ln_pre_sym = trim(substr_replace($ln, NULL, $haml_sym_char_pos));
                $ln_tag = $ln_pre_sym; # Temporary super hackiness!
                $ln_ele_sym_w_ele_val = trim(substr_replace($ln, NULL, 0, $haml_sym_char_pos)); # Returns [attrib symb][attrib val]
                $rtrn_ar['attrib_nme'] = $this->haml_ele_attribs_ar[$ln_pre_sym];
                $rtrn_ar['attrib_val'] = ltrim(substr_replace($ln_ele_sym_w_ele_val, NULL, 0, 1));
                $rtrn_ar['attrib_str'] = $ln_ele_sym_w_ele_val;
                return $rtrn_ar;
            }
        }
        # If we don't find anything during the loop return false...
        return FALSE;
    } # EOM lnAttribParse

    public function lnAttribChk($ln, $ln_attrib_sym, $div_flag = FALSE) {
        $html = FALSE;
        if (array_key_exists($ln_attrib_sym, $this->haml_ele_attribs_ar)) {
            
            # Use $this->indt + 1 to remove the Haml attribute symbols.
            # e.g. "#" = ID, "." = Class, etc.
            $ln = rtrim(substr($ln, $this->indt + 1));
            
            # Line whitespace starting position.
            $ln_ws_pos = strpos($ln, ' ');
            
            # $ln_attrib_val - The line attribute value, 
            # e.g. in [class="sweetrobots"] this would be "sweetrobots"
            $ln_attrib_val = substr($ln, 0, $ln_ws_pos);

            # $ln_cont gets the contents nested in the HTML tags as a string.
            # At this point "%div.steggy Stegosauruses are awesome!"
            # has be already parsed into "steggy Stegosauruses are awesome!"
            # this would now return...
            # "Stegosauruses are awesome!".
            $ln_cont = substr($ln, $ln_ws_pos + 1);
            
            if ($div_flag) {
                $html .= '<div ' . $this->haml_ele_attribs_ar[$ln_attrib_sym] . '="' . $ln_attrib_val . '">' . $ln_cont . '</div>' . (self::EOL());
            } else {
                $html .= $this->haml_ele_attribs_ar[$ln_attrib_sym] . '="' . $ln_attrib_val . '"';
            }
        }
        return $html;
    } # EOM lnAttribChk

    ##### End of ln* methods #####

    # This method is a temporary placeholder for the old RadApp haml() method...
    public static function theKitchenSink($file) {
        $file = trim($file);
        if (substr($file, strlen($file) - 5) != '.haml') {
            $file .= '.haml';
        }
        $lines = explode(PHP_EOL, file_get_contents($file));
        $indt = 0;
        $html = FALSE;
        $haml_ele_autoclose_ar = ['br', 'hr', 'img', 'link', 'meta', 'script'];
        $haml_ele_attribs_ar = ['.' => 'class', '#' => 'id'];
        foreach ($lines as $line) {
            $line_attrib_sym = $line[$indt];
            if (array_key_exists($line_attrib_sym, $haml_ele_attribs_ar)) {
                $line = trim(substr_replace($line, NULL, $indt, 1));
                $line_ws = strpos($line, ' ');
                $line_attrib_str = trim(substr_replace($line, NULL, $line_ws));
                $line_cont = trim(substr_replace($line, NULL, 0, $line_ws));
                $html .= '<div ' . $haml_ele_attribs_ar[$line_attrib_sym] . '="' . $line_attrib_str . '">' . $line_cont . '</div>' . (self::EOL());
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
        return $html;
    } # EOM theKitchenSink
 
    # So... random TSL note!?!? 
    # Automate "if something/else false" function e.g.:
    # if (conditional) {
    #  // Do stuff...
    # } else {
    #   return FALSE:
    #}
    # ...as (maybe like?):
    # conditional?
    #   // Do stuff...
    # orFalse
    #
    # ...or maybe(?):
    # conditional?!
    #  // Do stuff...
    # I like ^^^ that one so far the "?" is the conditional operator but appending the "!" to the end indicates do this or die out.

}

?>