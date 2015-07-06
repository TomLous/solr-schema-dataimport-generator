<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     16/06/15 12:02
 */

date_default_timezone_set('Europe/Amsterdam');

function jsonToXML(&$doc, &$el, $data, $dependancyData){

    print PHP_EOL.PHP_EOL."------------".PHP_EOL;
//    print_r($data);

    foreach($data as $key=>$val){
        $key = dependencyParser($key, $dependancyData);

//        print PHP_EOL.$key;



        if(is_scalar($val)){

            if($val === true){
                $val = 'true';
            }elseif($val === false){
                $val = 'false';
            }


            if($key != 'pattern'){
                $val = dependencyParser($val, $dependancyData);
            }

            if($val !== null){
                $el->setAttribute($key, $val);
            }
        }elseif(is_array($val)){
            foreach($val as $subkey=>$subval){
                $subel = $doc->createElement($key);
                if(!is_numeric($subkey)){
                    $subel->setAttribute('type', $subkey);
                }

                jsonToXML($doc, $subel, $subval, $dependancyData);
                $el->appendChild($subel);
            }
        }else{
            print "2".$key;
            $subel = $doc->createElement($key);
            jsonToXML($doc, $subel, $val, $dependancyData);
            $el->appendChild($subel);
        }

    }
}


function arrayCartesian($arrays) {
    $result = array();
    $keys = array_keys($arrays);
    $reverse_keys = array_reverse($keys);
    $size = intval(count($arrays) > 0);
    foreach ($arrays as $array) {
        $size *= count($array);
    }
    for ($i = 0; $i < $size; $i ++) {
        $result[$i] = array();
        foreach ($keys as $j) {
            $result[$i][$j] = current($arrays[$j]);
        }
        foreach ($reverse_keys as $j) {
            if (next($arrays[$j])) {
                break;
            }
            elseif (isset ($arrays[$j])) {
                reset($arrays[$j]);
            }
        }
    }
    return $result;
}

function createDependencyArray(&$fieldInfo, &$dependencyVariables){
    $dependancyArray = array(array());
    if(isset($fieldInfo['__dependency'])){
        $combinedArrays = array();
        foreach($fieldInfo['__dependency'] as $dependancyPlaceholder=>$dependancyVariable){
            if($dependencyVariables[$dependancyVariable]){
                $combinedArrays[$dependancyPlaceholder] = $dependencyVariables[$dependancyVariable];
            }
        }
        $dependancyArray = arrayCartesian($combinedArrays);
    }
    return $dependancyArray;
}

function dependencyParser($val, &$data){
    return preg_replace('/\[([^\]]+)\]/ies', "\$data['\\1'];", $val);
}

function jsonFormat($json_obj)
{
    $tab = "  ";
    $new_json = "";
    $indent_level = 0;
    $in_string = false;

//    $json_obj = json_decode($json);

    if($json_obj === false)
        return false;

    $json = json_encode($json_obj);
    $len = strlen($json);

    for($c = 0; $c < $len; $c++)
    {
        $char = $json[$c];
        switch($char)
        {
            case '{':
            case '[':
                if(!$in_string)
                {
                    $new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
                    $indent_level++;
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case '}':
            case ']':
                if(!$in_string)
                {
                    $indent_level--;
                    $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case ',':
                if(!$in_string)
                {
                    $new_json .= ",\n" . str_repeat($tab, $indent_level);
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case ':':
                if(!$in_string)
                {
                    $new_json .= ": ";
                }
                else
                {
                    $new_json .= $char;
                }
                break;
            case '"':
                if($c > 0 && $json[$c-1] != '\\')
                {
                    $in_string = !$in_string;
                }
            default:
                $new_json .= $char;
                break;
        }
    }

    return $new_json;
}

function doComparison($a, $operator, $b)
{
    switch ($operator) {
        case '<':  return ($a <  $b); break;
        case '<=': return ($a <= $b); break;
        case '=':  return ($a == $b); break; // SQL way
        case '==': return ($a == $b); break;
        case '!=': return ($a != $b); break;
        case '>=': return ($a >= $b); break;
        case '>':  return ($a >  $b); break;
    }

    throw new Exception("The {$operator} operator does not exists", 1);
}